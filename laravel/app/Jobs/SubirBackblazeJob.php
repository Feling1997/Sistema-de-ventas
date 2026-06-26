<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Support\Jobs\EstadoJobStore;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Ventas\Backups\Application\SubirRespaldoBackblaze;
use Ventas\Core\Infrastructure\Container\Container;

final class SubirBackblazeJob
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
        $estado->actualizar($this->jobId, 'procesando', 50, 'Dependencias de Backblaze resueltas.');
        $container->get(SubirRespaldoBackblaze::class);
        $estado->actualizar($this->jobId, 'completado', 100, 'Subida real a Backblaze no ejecutada.');
    }
}
