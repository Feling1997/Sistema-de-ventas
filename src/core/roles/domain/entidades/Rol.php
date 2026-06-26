<?php

declare(strict_types=1);

namespace Ventas\Core\Roles\Domain\Entidades;

final class Rol
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $nombre,
        private readonly ?string $descripcion,
        private readonly bool $activo
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

    public function descripcion(): ?string
    {
        return $this->descripcion;
    }

    public function activo(): bool
    {
        return $this->activo;
    }

    /**
     * @return array<string, mixed>
     */
    public function comoArray(): array
    {
        $datos = [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo,
        ];

        return $datos;
    }
}
