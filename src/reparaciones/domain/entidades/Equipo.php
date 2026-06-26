<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Domain\Entidades;

final class Equipo
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $contactoId,
        private readonly string $tipo,
        private readonly ?string $marca,
        private readonly ?string $modelo,
        private readonly ?string $serie
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

    public function tipo(): string
    {
        return $this->tipo;
    }

    public function marca(): ?string
    {
        return $this->marca;
    }

    public function modelo(): ?string
    {
        return $this->modelo;
    }

    public function serie(): ?string
    {
        return $this->serie;
    }
}
