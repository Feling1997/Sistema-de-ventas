<?php

declare(strict_types=1);

namespace Ventas\Impresoras\Domain\Repositorios;

interface ImpresoraRepository
{
    /**
     * @return array<int, string>
     */
    public function listar(): array;
}
