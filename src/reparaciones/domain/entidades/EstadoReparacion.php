<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Domain\Entidades;

final class EstadoReparacion
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $nombre,
        private readonly int $orden,
        private readonly bool $finaliza
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function orden(): int
    {
        return $this->orden;
    }

    public function finaliza(): bool
    {
        return $this->finaliza;
    }
}
