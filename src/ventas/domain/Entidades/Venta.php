<?php

declare(strict_types=1);

namespace Ventas\Ventas\Domain\Entidades;

final class Venta
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $fecha,
        private readonly int $idCliente,
        private readonly int $idUsuario,
        private readonly float $total
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function fecha(): string
    {
        return $this->fecha;
    }

    public function idCliente(): int
    {
        return $this->idCliente;
    }

    public function idUsuario(): int
    {
        return $this->idUsuario;
    }

    public function total(): float
    {
        return $this->total;
    }
}
