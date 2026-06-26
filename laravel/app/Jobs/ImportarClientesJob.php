<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Support\Jobs\EstadoJobStore;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ImportarClientesJob
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
        $estado->actualizar($this->jobId, 'procesando', 50, 'Importacion de clientes preparada.');
        $estado->actualizar($this->jobId, 'completado', 100, 'No se ejecutaron escrituras reales.');
    }
}
