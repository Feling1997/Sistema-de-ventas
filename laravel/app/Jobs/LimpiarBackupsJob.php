<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Support\Jobs\EstadoJobStore;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class LimpiarBackupsJob
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly string $jobId)
    {
    }

    public function handle(EstadoJobStore $estado): void
    {
        $estado->actualizar($this->jobId, 'procesando', 50, 'Limpieza de backups preparada.');
        $estado->actualizar($this->jobId, 'completado', 100, 'No se eliminaron archivos reales.');
    }
}
