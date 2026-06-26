<?php

declare(strict_types=1);

namespace Ventas\Clientes\Domain\Repositorios;

use Ventas\Clientes\Domain\Entidades\Cliente;

interface ClienteRepository
{
    /**
     * @return Cliente[]
     */
    public function listar(): array;

    public function buscarPorId(int $id): ?Cliente;

    public function existeDocumento(string $documento, ?int $exceptoId = null): bool;

    public function guardar(Cliente $cliente): Cliente;

    public function actualizar(Cliente $cliente): void;

    public function eliminar(int $id): void;

    public function tieneVentasAsociadas(int $id): bool;

    public function inicializarEsquemaFiscal(): void;
}
