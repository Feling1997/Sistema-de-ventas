<?php

declare(strict_types=1);

namespace Ventas\Core\Permisos\Domain\Entidades;

final class Permiso
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $modulo,
        private readonly string $accion,
        private readonly string $codigo,
        private readonly ?string $descripcion,
        private readonly bool $activo
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function codigo(): string
    {
        return $this->codigo;
    }

    /**
     * @return array<string, mixed>
     */
    public function comoArray(): array
    {
        $datos = [
            'id' => $this->id,
            'modulo' => $this->modulo,
            'accion' => $this->accion,
            'codigo' => $this->codigo,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo,
        ];

        return $datos;
    }
}
