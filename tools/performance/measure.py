"""Prueba académica local, sin dependencias externas ni almacenamiento de credenciales."""

import argparse
from collections import Counter
from concurrent.futures import ThreadPoolExecutor
from datetime import datetime
import getpass
import json
import os
from pathlib import Path
import statistics
import time
import urllib.error
import urllib.request


def now():
    return datetime.now().astimezone().isoformat(timespec='seconds')


def call(url, method='GET', body=None, token=None):
    headers = {'Accept': 'application/json'}
    if token:
        headers['Authorization'] = 'Bearer ' + token
    data = None if body is None else json.dumps(body).encode('utf-8')
    if data is not None:
        headers['Content-Type'] = 'application/json'
    request = urllib.request.Request(url, data=data, headers=headers, method=method)
    start = time.perf_counter()
    try:
        with urllib.request.urlopen(request, timeout=30) as response:
            content = response.read()
            code = response.status
    except urllib.error.HTTPError as response:
        response.read()
        code, content = response.code, b''
    except (urllib.error.URLError, TimeoutError, OSError):
        code, content = 0, b''
    return code, (time.perf_counter() - start) * 1000, content


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', required=True, help='Archivo JSON de resultados sin secretos')
    parser.add_argument('--concurrency', type=int, default=5)
    args = parser.parse_args()
    if args.concurrency < 1:
        parser.error('La concurrencia debe ser positiva')
    password = os.environ.get('PERF_DEMO_PASSWORD') or getpass.getpass('Contraseña de la cuenta demo: ')
    if not password:
        parser.error('Se necesita la contraseña de la cuenta demo')
    login = {'email': os.environ.get('PERF_DEMO_EMAIL', 'admin@tiendapc.local'), 'password': password}
    auth_url = 'http://127.0.0.1:8000/api/auth/login'
    code, _, content = call(auth_url, 'POST', login)
    if code != 200:
        raise SystemExit(f'No se pudo obtener el token: HTTP {code}')
    token = json.loads(content)['access_token']
    cases = [
        ('Auth login', auth_url, 'POST', login, None),
        ('Productos', 'http://127.0.0.1:8001/api/productos', 'GET', None, token),
        ('Clientes', 'http://127.0.0.1:8002/api/clientes', 'GET', None, token),
        ('Pedidos', 'http://127.0.0.1:8003/api/pedidos', 'GET', None, token),
        ('Health auth', 'http://127.0.0.1:8000/api/health', 'GET', None, None),
    ]
    results = {'started_at': now(), 'concurrency': args.concurrency, 'results': []}
    for name, url, method, body, bearer in cases:
        status, _, _ = call(url, method, body, bearer)
        if status != 200:
            raise SystemExit(f'Calentamiento de {name} falló: HTTP {status}')
        for level, count in [('Baja', 10), ('Media', 50), ('Alta', 100)]:
            started = now()
            begin = time.perf_counter()
            with ThreadPoolExecutor(max_workers=args.concurrency) as executor:
                observations = list(executor.map(lambda _: call(url, method, body, bearer)[:2], range(count)))
            elapsed = time.perf_counter() - begin
            timings = [item[1] for item in observations]
            codes = Counter(str(item[0]) for item in observations)
            successes = sum(1 for item in observations if 200 <= item[0] < 300)
            result = {
                'endpoint': name, 'method': method, 'url': url, 'level': level,
                'requests': count, 'successes': successes, 'failures': count - successes,
                'mean_ms': round(statistics.mean(timings), 2), 'min_ms': round(min(timings), 2),
                'max_ms': round(max(timings), 2), 'elapsed_s': round(elapsed, 3),
                'requests_per_second': round(count / elapsed, 2),
                'error_percent': round(100 * (count - successes) / count, 2),
                'status_counts': dict(codes), 'started_at': started, 'finished_at': now(),
            }
            results['results'].append(result)
            print(f"{name} {level}: {successes}/{count} OK; {result['mean_ms']} ms; {result['requests_per_second']} req/s", flush=True)
    results['finished_at'] = now()
    Path(args.output).write_text(json.dumps(results, indent=2, ensure_ascii=False) + '\n', encoding='utf-8')


if __name__ == '__main__':
    main()
