<?php

declare(strict_types=1);

namespace Ventas\Backups\Domain\Repositorios;

interface DatabaseDumpRepository
{
    public function generarDump(): string;
}
