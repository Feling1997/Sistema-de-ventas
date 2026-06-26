<?php

declare(strict_types=1);

namespace Ventas\Core\Usuarios\Domain\Entidades;

final class UsuarioCore
{
    public function __construct(
        private readonly ?int $id,
        private readonly ?int $usuarioLegacyId,
        private readonly ?string $nombre,
        private readonly string $usuario,
        private readonly ?string $email,
        private readonly ?string $clave,
        private readonly bool $activo,
        private readonly ?string $ultimoAcceso,
        private readonly array $roles
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function usuarioLegacyId(): ?int
    {
        return $this->usuarioLegacyId;
    }

    public function usuario(): string
    {
        return $this->usuario;
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
            'usuario_legacy_id' => $this->usuarioLegacyId,
            'nombre' => $this->nombre,
            'usuario' => $this->usuario,
            'email' => $this->email,
            'activo' => $this->activo,
            'ultimo_acceso' => $this->ultimoAcceso,
            'roles' => $this->roles,
        ];

        return $datos;
    }
}
