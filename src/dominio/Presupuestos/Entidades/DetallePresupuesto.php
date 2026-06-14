<?php

declare(strict_types=1);

namespace Ventas\Dominio\Presupuestos\Entidades;

use InvalidArgumentException;

final class DetallePresupuesto
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $idPresupuesto,
        private readonly int $idProducto,
        private readonly string $productoNombre,
        private readonly float $cantidad,
        private readonly float $precioUnit,
        private readonly float $descuento,
        private readonly float $subtotal
    ) {
        $this->validar();
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function idPresupuesto(): int
    {
        return $this->idPresupuesto;
    }

    public function idProducto(): int
    {
        return $this->idProducto;
    }

    public function productoNombre(): string
    {
        return $this->productoNombre;
    }

    public function cantidad(): float
    {
        return $this->cantidad;
    }

    public function precioUnit(): float
    {
        return $this->precioUnit;
    }

    public function descuento(): float
    {
        return $this->descuento;
    }

    public function subtotal(): float
    {
        return $this->subtotal;
    }

    private function validar(): void
    {
        if ($this->id !== null && $this->id <= 0) {
            throw new InvalidArgumentException('El ID del detalle de presupuesto debe ser positivo.');
        }

        if ($this->idPresupuesto <= 0) {
            throw new InvalidArgumentException('El ID del presupuesto debe ser positivo.');
        }

        if ($this->idProducto <= 0) {
            throw new InvalidArgumentException('El ID del producto debe ser positivo.');
        }

        if (trim($this->productoNombre) === '') {
            throw new InvalidArgumentException('El nombre del producto es obligatorio.');
        }

        if ($this->cantidad < 0) {
            throw new InvalidArgumentException('La cantidad no puede ser negativa.');
        }

        if ($this->precioUnit < 0) {
            throw new InvalidArgumentException('El precio unitario no puede ser negativo.');
        }

        if ($this->descuento < 0) {
            throw new InvalidArgumentException('El descuento no puede ser negativo.');
        }

        if ($this->subtotal < 0) {
            throw new InvalidArgumentException('El subtotal no puede ser negativo.');
        }
    }
}
