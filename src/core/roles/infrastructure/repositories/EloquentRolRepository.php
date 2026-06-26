<?php

declare(strict_types=1);

namespace Ventas\Core\Roles\Infrastructure\Repositories;

use Ventas\Core\Roles\Domain\Entidades\Rol;
use Ventas\Core\Roles\Domain\Repositorios\RolRepository;
use Ventas\Core\Roles\Infrastructure\Models\RolModel;
use Ventas\Core\Usuarios\Infrastructure\Models\UsuarioCoreModel;

final class EloquentRolRepository implements RolRepository
{
    public function listar(): array
    {
        $roles = [];
        $modelos = RolModel::query()->orderBy('nombre')->get();

        foreach ($modelos as $modelo) {
            $roles[] = $this->mapear($modelo);
        }

        return $roles;
    }

    public function buscarPorId(int $id): ?Rol
    {
        $rol = null;
        $modelo = RolModel::query()->where('id', $id)->first();

        if ($modelo instanceof RolModel) {
            $rol = $this->mapear($modelo);
        }

        return $rol;
    }

    public function guardar(array $datos): Rol
    {
        $id = (int) ($datos['id'] ?? 0);
        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $modelo = $id > 0 ? RolModel::query()->where('id', $id)->first() : RolModel::query()->where('nombre', $nombre)->first();

        if (!$modelo instanceof RolModel) {
            $modelo = new RolModel();
        }

        $modelo->fill([
            'nombre' => $nombre,
            'descripcion' => trim((string) ($datos['descripcion'] ?? '')),
            'activo' => (bool) ($datos['activo'] ?? true),
        ]);
        $modelo->save();

        return $this->mapear($modelo);
    }

    public function desactivar(int $id): bool
    {
        $desactivado = false;
        $modelo = RolModel::query()->where('id', $id)->first();

        if ($modelo instanceof RolModel) {
            $critico = in_array($modelo->nombre, ['Dueno', 'Administrador'], true);
            $otrosActivos = $critico ? $this->cantidadUsuariosActivosConRoles(['Dueno', 'Administrador'], (int) $modelo->id) : 1;

            if (!$critico || $otrosActivos > 0) {
                $modelo->activo = false;
                $desactivado = (bool) $modelo->save();
            }
        }

        return $desactivado;
    }

    public function asegurarIniciales(): void
    {
        $roles = [
            ['nombre' => 'Dueno', 'descripcion' => 'Acceso total al sistema comercial.'],
            ['nombre' => 'Administrador', 'descripcion' => 'Gestion operativa y configuracion.'],
            ['nombre' => 'Gerente', 'descripcion' => 'Gestion comercial avanzada.'],
            ['nombre' => 'Vendedor', 'descripcion' => 'Ventas, clientes y consultas comerciales.'],
            ['nombre' => 'Tecnico', 'descripcion' => 'Reparaciones, equipos y contactos.'],
            ['nombre' => 'Caja', 'descripcion' => 'Cobros, pagos y cierre operativo.'],
        ];

        foreach ($roles as $rol) {
            RolModel::query()->updateOrCreate(
                ['nombre' => $rol['nombre']],
                ['descripcion' => $rol['descripcion'], 'activo' => true]
            );
        }
    }

    /**
     * @param array<int, string> $roles
     */
    private function cantidadUsuariosActivosConRoles(array $roles, int $exceptoRolId): int
    {
        $cantidad = UsuarioCoreModel::query()
            ->where('activo', true)
            ->whereHas('roles', static function ($query) use ($roles, $exceptoRolId): void {
                $query->whereIn('nombre', $roles)->where('roles.id', '<>', $exceptoRolId)->where('roles.activo', true);
            })
            ->count();

        return $cantidad;
    }

    private function mapear(RolModel $modelo): Rol
    {
        return new Rol(
            (int) $modelo->id,
            (string) $modelo->nombre,
            $modelo->descripcion !== null ? (string) $modelo->descripcion : null,
            (bool) $modelo->activo
        );
    }
}
