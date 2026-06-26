<?php

declare(strict_types=1);

namespace Ventas\Importacion\Domain\Repositorios;

interface ImportacionProductosRepository
{
    public function iniciarTransaccion(): void;

    public function confirmarTransaccion(): void;

    public function revertirTransaccionSiActiva(): void;

    public function actualizarProducto(int $idProducto, string $nombre, string $codigo): void;

    public function crearProducto(string $nombre, string $codigo, float $precioFinal): int;
}
