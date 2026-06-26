<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Domain\Entidades;

final class TicketReparacion
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $reparacionId,
        private readonly string $codigo,
        private readonly ?string $emitidoEn
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function reparacionId(): int
    {
        return $this->reparacionId;
    }

    public function codigo(): string
    {
        return $this->codigo;
    }

    public function emitidoEn(): ?string
    {
        return $this->emitidoEn;
    }
}
