<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Domain\Entidades;

final class Reparacion
{
    public function __construct(
        private readonly ?int $id,
        private readonly ?string $codigo,
        private readonly int $contactoId,
        private readonly ?int $equipoId,
        private readonly string $problema,
        private readonly ?string $diagnostico,
        private readonly ?string $garantia,
        private readonly float $precio,
        private readonly ?string $observaciones,
        private readonly int $estadoId,
        private readonly ?string $fechaIngreso,
        private readonly ?string $fechaEntrega,
        private readonly bool $activo
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function contactoId(): int
    {
        return $this->contactoId;
    }

    public function codigo(): ?string
    {
        return $this->codigo;
    }

    public function equipoId(): ?int
    {
        return $this->equipoId;
    }

    public function problema(): string
    {
        return $this->problema;
    }

    public function diagnostico(): ?string
    {
        return $this->diagnostico;
    }

    public function garantia(): ?string
    {
        return $this->garantia;
    }

    public function precio(): float
    {
        return $this->precio;
    }

    public function observaciones(): ?string
    {
        return $this->observaciones;
    }

    public function estadoId(): int
    {
        return $this->estadoId;
    }

    public function fechaIngreso(): ?string
    {
        return $this->fechaIngreso;
    }

    public function fechaEntrega(): ?string
    {
        return $this->fechaEntrega;
    }

    public function activo(): bool
    {
        return $this->activo;
    }
}
