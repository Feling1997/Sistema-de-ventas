<?php

declare(strict_types=1);

namespace Ventas\Dominio\Ventas\Entidades;

final class DetalleVenta
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $idVenta,
        private readonly int $idProducto,
        private readonly float $cantidad,
        private readonly float $precioUnit,
        private readonly float $costoUnit,
        private readonly float $descuento,
        private readonly float $subtotal
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function idVenta(): int
    {
        return $this->idVenta;
    }

    public function idProducto(): int
    {
        return $this->idProducto;
    }

    public function cantidad(): float
    {
        return $this->cantidad;
    }

    public function precioUnit(): float
    {
        return $this->precioUnit;
    }

    public function costoUnit(): float
    {
        return $this->costoUnit;
    }

    public function descuento(): float
    {
        return $this->descuento;
    }

    public function subtotal(): float
    {
        return $this->subtotal;
    }
}
