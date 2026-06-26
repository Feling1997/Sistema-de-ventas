<?php

declare(strict_types=1);

namespace Ventas\Importacion\Domain\Repositorios;

interface ImportacionPreciosRepository
{
    public function obtenerPrecioActual(int $idProducto, int $idLista): float;

    public function guardarPrecio(int $idProducto, int $idLista, float $precio): void;
}
