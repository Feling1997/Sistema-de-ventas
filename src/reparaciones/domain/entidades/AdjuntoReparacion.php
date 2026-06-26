<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Domain\Entidades;

final class AdjuntoReparacion
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $reparacionId,
        private readonly string $nombre,
        private readonly string $ruta,
        private readonly ?string $mime
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

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function ruta(): string
    {
        return $this->ruta;
    }

    public function mime(): ?string
    {
        return $this->mime;
    }
}
