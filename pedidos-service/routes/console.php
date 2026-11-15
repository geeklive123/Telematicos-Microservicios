<?php

use App\Models\OutboxEvent;
use App\Services\OutboxPublisher;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('outbox:publish {--limit=100}', function (OutboxPublisher $publisher) {
    $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10000]]);
    if ($limit === false) {
        $this->error('El límite debe estar entre 1 y 10000.');

        return 1;
    }
    $ids = OutboxEvent::whereIn('status', ['pending', 'failed'])->orderBy('id')->limit($limit)->pluck('id');
    $failed = 0;
    foreach ($ids as $id) {
        $failed += $publisher->publish($id) ? 0 : 1;
    }
    $this->info("Eventos intentados: {$ids->count()}; fallidos: {$failed}.");

    return $failed ? 1 : 0;
})->purpose('Publicar eventos pendientes de outbox');
