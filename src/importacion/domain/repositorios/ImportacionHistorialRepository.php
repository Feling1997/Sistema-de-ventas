<?php

declare(strict_types=1);

namespace Ventas\Importacion\Domain\Repositorios;

interface ImportacionHistorialRepository
{
    public function guardarCambioPrecio(int $idProducto, int $idLista, float $precioAnterior, float $precioNuevo): void;
}
