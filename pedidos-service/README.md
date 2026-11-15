<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
# CancelaciÃ³n de pedidos y stock

Un pedido pendiente o pagado puede cancelarse. Pedidos Service repone por HTTP
la cantidad de cada detalle en Productos Service antes de guardar el estado
cancelado. La fila del pedido permanece bloqueada mientras se procesa la
operaciÃ³n; una cancelaciÃ³n ya completada no vuelve a reponer stock.

La reposiciÃ³n no es una transacciÃ³n distribuida. Si falla una llamada, vence
su tiempo de espera, falla la persistencia local o se interrumpe el proceso,
algunos productos pueden haber sido repuestos aunque el pedido siga sin
cancelar. Cuando el fallo es capturado, se registra con el pedido y las
reposiciones conocidas; una interrupciÃ³n abrupta puede impedir ese registro.
Una respuesta perdida puede dejar incierto el resultado del producto en curso.
Cada reposiciÃ³n usa una clave estable por pedido y producto y el servicio de
Productos debe aplicar su contrato idempotente. No se descuentan automÃ¡ticamente
los productos ya repuestos. Los registros antiguos cancelados no se corrigen
automÃ¡ticamente.

## Intentos de creaciÃ³n y conciliaciÃ³n

Cada creaciÃ³n registra un UUID, los datos normalizados y el progreso de stock
antes de modificar inventario. Los descuentos y compensaciones usan claves
distintas, estables por intento y producto. Ante un error de conexiÃ³n al
descontar se reintenta una vez con la misma clave; si el resultado sigue siendo
incierto, el intento queda en `needs_reconciliation`.

Pedido y estado `completed` se guardan en la misma transacciÃ³n local. El mÃ©todo
interno `PedidoService::resumeAttempt` devuelve el pedido ya completado o
reanuda un intento `processing` no reclamado con sus datos y claves originales.
No se ofrece un endpoint de reintento: enviar un nuevo POST inicia otro intento.
Un intento `claimed` tras una interrupciÃ³n requiere revisiÃ³n operativa para
comprobar que su proceso terminÃ³ antes de liberarlo; no hay vencimiento
automÃ¡tico, para evitar dos procesadores simultÃ¡neos. Los intentos con
compensaciÃ³n iniciada o resultado incierto requieren conciliaciÃ³n, no un
reintento de creaciÃ³n automÃ¡tico.

