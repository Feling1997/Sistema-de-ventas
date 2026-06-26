<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Support\Jobs\EstadoJobStore;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Ventas\Backups\Application\GenerarRespaldoSistema;
use Ventas\Core\Infrastructure\Container\Container;

final class CrearBackupJob
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly string $jobId)
    {
    }

    public function handle(Container $container, EstadoJobStore $estado): void
    {
        $estado->actualizar($this->jobId, 'procesando', 50, 'Dependencias de backup resueltas.');
        $container->get(GenerarRespaldoSistema::class);
        $estado->actualizar($this->jobId, 'completado', 100, 'Backup real no ejecutado por restriccion de la fase.');
    }
}
