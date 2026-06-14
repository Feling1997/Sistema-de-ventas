<?php

declare(strict_types=1);

namespace Ventas\Dominio\Usuarios\Entidades;

final class PermisosUsuario
{
    public const PERMISO_NINGUNO = '__none';

    /**
     * @param string[] $permisos
     */
    private function __construct(private readonly array $permisos)
    {
    }

    public static function todos(): self
    {
        return new self([]);
    }

    public static function ninguno(): self
    {
        return new self([self::PERMISO_NINGUNO]);
    }

    /**
     * @param string[] $modulos
     */
    public static function personalizados(array $modulos): self
    {
        $permisos = self::normalizar($modulos);

        return new self($permisos);
    }

    /**
     * @param string[] $permisos
     */
    public static function desdeLegacy(array $permisos): self
    {
        $permisosNormalizados = self::normalizar($permisos);

        if (in_array(self::PERMISO_NINGUNO, $permisosNormalizados, true)) {
            $permisosNormalizados = [self::PERMISO_NINGUNO];
        }

        return new self($permisosNormalizados);
    }

    /**
     * @return string[]
     */
    public function comoArray(): array
    {
        return $this->permisos;
    }

    public function tieneAccesoTotal(): bool
    {
        return count($this->permisos) === 0;
    }

    public function noTienePermisos(): bool
    {
        return in_array(self::PERMISO_NINGUNO, $this->permisos, true);
    }

    public function permite(string $modulo): bool
    {
        $permite = false;
        $moduloNormalizado = trim($modulo);

        if ($this->tieneAccesoTotal()) {
            $permite = true;
        } elseif (!$this->noTienePermisos() && $moduloNormalizado !== '') {
            $permite = in_array($moduloNormalizado, $this->permisos, true);
        }

        return $permite;
    }

    /**
     * @param string[] $permisos
     * @return string[]
     */
    private static function normalizar(array $permisos): array
    {
        $normalizados = array_map(
            static fn (mixed $permiso): string => trim((string) $permiso),
            $permisos
        );
        $normalizados = array_filter($normalizados, static fn (string $permiso): bool => $permiso !== '');
        $normalizados = array_values(array_unique($normalizados));

        return $normalizados;
    }
}
