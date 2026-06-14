<?php

declare(strict_types=1);

namespace Ventas\Dominio\Compartido\Contratos;

interface TransactionManager
{
    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function run(callable $operation): mixed;
}
