<?php

declare(strict_types=1);

namespace Ventas\Dominio\UnidadesMedida\Entidades;

use InvalidArgumentException;

final class UnidadMedida
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $nombre,
        private readonly string $abreviatura,
        private readonly string $tipo,
        private readonly int $decimales,
        private readonly bool $activo
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

    public function abreviatura(): string
    {
        return $this->abreviatura;
    }

    public function tipo(): string
    {
        return $this->tipo;
    }

    public function decimales(): int
    {
        return $this->decimales;
    }

    public function activo(): bool
    {
        return $this->activo;
    }

    private function validar(): void
    {
        if ($this->id !== null && $this->id <= 0) {
            throw new InvalidArgumentException('El ID de la unidad de medida debe ser positivo.');
        }

        if (trim($this->nombre) === '') {
            throw new InvalidArgumentException('El nombre de la unidad de medida es obligatorio.');
        }

        if (trim($this->abreviatura) === '') {
            throw new InvalidArgumentException('La abreviatura de la unidad de medida es obligatoria.');
        }

        if (trim($this->tipo) === '') {
            throw new InvalidArgumentException('El tipo de la unidad de medida es obligatorio.');
        }

        if ($this->decimales < 0) {
            throw new InvalidArgumentException('Los decimales de la unidad de medida no pueden ser negativos.');
        }
    }
}
