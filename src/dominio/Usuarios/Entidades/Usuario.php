<?php

declare(strict_types=1);

namespace Ventas\Dominio\Usuarios\Entidades;

use InvalidArgumentException;
use Ventas\Dominio\Usuarios\Excepciones\RolUsuarioInvalidoException;

final class Usuario
{
    public const ROL_ADMIN = 'ADMIN';
    public const ROL_VENDEDOR = 'VENDEDOR';
    private const ROLES_VALIDOS = [self::ROL_ADMIN, self::ROL_VENDEDOR];

    public function __construct(
        private readonly ?int $id,
        private readonly string $usuario,
        private readonly ?string $claveHash,
        private readonly string $rol,
        private readonly bool $activo,
        private readonly PermisosUsuario $permisos,
        private readonly ?string $creadoEn = null
    ) {
        $this->validar();
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function usuario(): string
    {
        return $this->usuario;
    }

    public function claveHash(): ?string
    {
        return $this->claveHash;
    }

    public function rol(): string
    {
        return $this->rol;
    }

    public function activo(): bool
    {
        return $this->activo;
    }

    public function permisos(): PermisosUsuario
    {
        return $this->permisos;
    }

    public function creadoEn(): ?string
    {
        return $this->creadoEn;
    }

    public function esAdmin(): bool
    {
        return $this->rol === self::ROL_ADMIN;
    }

    public function esVendedor(): bool
    {
        return $this->rol === self::ROL_VENDEDOR;
    }

    public function estaActivo(): bool
    {
        return $this->activo;
    }

    public function puedeAutenticarse(): bool
    {
        return $this->estaActivo();
    }

    public function puedeAccederModulo(string $modulo): bool
    {
        $puedeAcceder = false;

        if ($this->esAdmin()) {
            $puedeAcceder = true;
        } else {
            $puedeAcceder = $this->permisos->permite($modulo);
        }

        return $puedeAcceder;
    }

    private function validar(): void
    {
        if (trim($this->usuario) === '') {
            throw new InvalidArgumentException('El nombre de usuario es obligatorio.');
        }

        if ($this->id !== null && $this->id < 1) {
            throw new InvalidArgumentException('El ID del usuario debe ser positivo.');
        }

        if (!in_array($this->rol, self::ROLES_VALIDOS, true)) {
            throw new RolUsuarioInvalidoException('El rol del usuario no es valido.');
        }
    }
}
