<?php

declare(strict_types=1);

namespace Ventas\Dominio\Productos\Entidades;

use InvalidArgumentException;

final class Producto
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $nombre,
        private readonly ?string $codBarras,
        private readonly ?int $idStock,
        private readonly float $factorConversion,
        private readonly float $ganancia,
        private readonly float $precioFinal,
        private readonly bool $activo,
        private readonly ?string $creadoEn = null
    ) {
        $this->validar();
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function codBarras(): ?string
    {
        return $this->codBarras;
    }

    public function idStock(): ?int
    {
        return $this->idStock;
    }

    public function factorConversion(): float
    {
        return $this->factorConversion;
    }

    public function ganancia(): float
    {
        return $this->ganancia;
    }

    public function precioFinal(): float
    {
        return $this->precioFinal;
    }

    public function activo(): bool
    {
        return $this->activo;
    }

    public function creadoEn(): ?string
    {
        return $this->creadoEn;
    }

    public function estaActivo(): bool
    {
        return $this->activo;
    }

    private function validar(): void
    {
        if (trim($this->nombre) === '') {
            throw new InvalidArgumentException('El nombre del producto es obligatorio.');
        }

        if ($this->id !== null && $this->id < 1) {
            throw new InvalidArgumentException('El ID del producto debe ser positivo.');
        }

        if ($this->factorConversion < 0) {
            throw new InvalidArgumentException('El factor de conversion no puede ser negativo.');
        }

        if ($this->ganancia < 0) {
            throw new InvalidArgumentException('La ganancia no puede ser negativa.');
        }

        if ($this->precioFinal < 0) {
            throw new InvalidArgumentException('El precio final no puede ser negativo.');
        }
    }
}
