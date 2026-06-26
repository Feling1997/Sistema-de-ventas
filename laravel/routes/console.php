<?php

use Ventas\Sistema\Application\CrearBackupSistema;
use Ventas\Sistema\Application\GenerarDiagnosticoSistema;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sistema:diagnostico', function () {
    $diagnostico = app(GenerarDiagnosticoSistema::class)->ejecutar();

    $this->line('Estado general: ' . $diagnostico['estado_general']);
    $this->line('Version: ' . $diagnostico['version']);
    $this->line('Modo: ' . $diagnostico['modo']);
    $this->line(json_encode($diagnostico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
})->purpose('Verifica PHP, MariaDB, storage, bases, migraciones y modo actual');

Artisan::command('sistema:backup', function () {
    $backup = app(CrearBackupSistema::class)->ejecutar();

    $this->line('Backup generado: ' . $backup['manifiesto']);
    $this->line(json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
})->purpose('Genera respaldos de las bases del sistema');
