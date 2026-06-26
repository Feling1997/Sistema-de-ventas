<?php

declare(strict_types=1);

namespace Ventas\Core\Permisos\Infrastructure\Repositories;

use Ventas\Core\Permisos\Domain\Entidades\Permiso;
use Ventas\Core\Permisos\Domain\Repositorios\PermisoRepository;
use Ventas\Core\Permisos\Infrastructure\Models\PermisoModel;
use Ventas\Core\Roles\Infrastructure\Models\RolModel;
use Ventas\Core\Usuarios\Infrastructure\Models\UsuarioCoreModel;

final class EloquentPermisoRepository implements PermisoRepository
{
    public function listar(): array
    {
        $permisos = [];
        $modelos = PermisoModel::query()->orderBy('modulo')->orderBy('accion')->get();

        foreach ($modelos as $modelo) {
            $permisos[] = $this->mapear($modelo);
        }

        return $permisos;
    }

    public function buscarPorId(int $id): ?Permiso
    {
        $permiso = null;
        $modelo = PermisoModel::query()->where('id', $id)->first();

        if ($modelo instanceof PermisoModel) {
            $permiso = $this->mapear($modelo);
        }

        return $permiso;
    }

    public function asegurarIniciales(): void
    {
        foreach ($this->permisosIniciales() as $codigo => $datos) {
            PermisoModel::query()->updateOrCreate(
                ['codigo' => $codigo],
                [
                    'modulo' => $datos['modulo'],
                    'accion' => $datos['accion'],
                    'descripcion' => $datos['descripcion'],
                    'activo' => true,
                ]
            );
        }

        $this->asignarPermisosAdministrador();
    }

    public function permisosDeRol(int $rolId): array
    {
        $codigos = [];
        $rol = RolModel::query()->where('id', $rolId)->first();

        if ($rol instanceof RolModel) {
            $codigos = $rol->permisos()->pluck('codigo')->map(static fn (mixed $codigo): string => (string) $codigo)->all();
        }

        return $codigos;
    }

    public function activarParaRol(int $rolId, int $permisoId): bool
    {
        $activado = false;
        $rol = RolModel::query()->where('id', $rolId)->first();
        $permiso = PermisoModel::query()->where('id', $permisoId)->first();

        if ($rol instanceof RolModel && $permiso instanceof PermisoModel) {
            $rol->permisos()->syncWithoutDetaching([$permisoId]);
            $activado = true;
        }

        return $activado;
    }

    public function quitarDeRol(int $rolId, int $permisoId): bool
    {
        $quitado = false;
        $rol = RolModel::query()->where('id', $rolId)->first();

        if ($rol instanceof RolModel) {
            $permiso = PermisoModel::query()->where('id', $permisoId)->first();
            $esRolCritico = in_array((string) $rol->nombre, ['Dueno', 'Administrador'], true);
            $esPermisoCritico = $permiso instanceof PermisoModel && in_array((string) $permiso->codigo, ['usuarios.ver', 'usuarios.editar'], true);

            if (!$esRolCritico || !$esPermisoCritico || $this->hayOtroRolAdministradorConPermiso((int) $rol->id, (string) $permiso->codigo)) {
                $rol->permisos()->detach($permisoId);
                $quitado = true;
            }
        }

        return $quitado;
    }

    public function usuarioTienePermiso(int $usuarioLegacyId, string $codigo): bool
    {
        $tiene = false;
        $usuario = UsuarioCoreModel::query()
            ->with('roles.permisos')
            ->where(function ($query) use ($usuarioLegacyId): void {
                $query->where('usuario_legacy_id', $usuarioLegacyId)->orWhere('id', $usuarioLegacyId);
            })
            ->where('activo', true)
            ->first();

        if ($usuario instanceof UsuarioCoreModel) {
            foreach ($usuario->roles as $rol) {
                if ((bool) $rol->activo && in_array($rol->nombre, ['Dueno', 'Administrador'], true)) {
                    $tiene = true;
                }

                foreach ($rol->permisos as $permiso) {
                    if ((bool) $permiso->activo && $permiso->codigo === $codigo) {
                        $tiene = true;
                    }
                }
            }
        }

        return $tiene;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function permisosIniciales(): array
    {
        return [
            'ventas.ver' => ['modulo' => 'ventas', 'accion' => 'ver', 'descripcion' => 'Ver ventas'],
            'ventas.crear' => ['modulo' => 'ventas', 'accion' => 'crear', 'descripcion' => 'Crear ventas'],
            'ventas.editar' => ['modulo' => 'ventas', 'accion' => 'editar', 'descripcion' => 'Editar ventas'],
            'ventas.eliminar' => ['modulo' => 'ventas', 'accion' => 'eliminar', 'descripcion' => 'Eliminar ventas'],
            'ventas.anular' => ['modulo' => 'ventas', 'accion' => 'anular', 'descripcion' => 'Anular ventas'],
            'ventas.historial' => ['modulo' => 'ventas', 'accion' => 'historial', 'descripcion' => 'Ver historial de ventas'],
            'clientes.ver' => ['modulo' => 'clientes', 'accion' => 'ver', 'descripcion' => 'Ver clientes'],
            'clientes.crear' => ['modulo' => 'clientes', 'accion' => 'crear', 'descripcion' => 'Crear clientes'],
            'clientes.editar' => ['modulo' => 'clientes', 'accion' => 'editar', 'descripcion' => 'Editar clientes'],
            'clientes.eliminar' => ['modulo' => 'clientes', 'accion' => 'eliminar', 'descripcion' => 'Eliminar clientes'],
            'productos.ver' => ['modulo' => 'productos', 'accion' => 'ver', 'descripcion' => 'Ver productos'],
            'productos.crear' => ['modulo' => 'productos', 'accion' => 'crear', 'descripcion' => 'Crear productos'],
            'productos.editar' => ['modulo' => 'productos', 'accion' => 'editar', 'descripcion' => 'Editar productos'],
            'productos.eliminar' => ['modulo' => 'productos', 'accion' => 'eliminar', 'descripcion' => 'Eliminar productos'],
            'stock.ver' => ['modulo' => 'stock', 'accion' => 'ver', 'descripcion' => 'Ver stock'],
            'stock.crear' => ['modulo' => 'stock', 'accion' => 'crear', 'descripcion' => 'Crear stock'],
            'stock.editar' => ['modulo' => 'stock', 'accion' => 'editar', 'descripcion' => 'Editar stock'],
            'stock.eliminar' => ['modulo' => 'stock', 'accion' => 'eliminar', 'descripcion' => 'Eliminar stock'],
            'stock.movimientos' => ['modulo' => 'stock', 'accion' => 'movimientos', 'descripcion' => 'Gestionar movimientos de stock'],
            'reparaciones.ver' => ['modulo' => 'reparaciones', 'accion' => 'ver', 'descripcion' => 'Ver reparaciones'],
            'reparaciones.crear' => ['modulo' => 'reparaciones', 'accion' => 'crear', 'descripcion' => 'Crear reparaciones'],
            'reparaciones.editar' => ['modulo' => 'reparaciones', 'accion' => 'editar', 'descripcion' => 'Editar reparaciones'],
            'reparaciones.eliminar' => ['modulo' => 'reparaciones', 'accion' => 'eliminar', 'descripcion' => 'Eliminar reparaciones'],
            'reparaciones.equipos' => ['modulo' => 'reparaciones', 'accion' => 'equipos', 'descripcion' => 'Gestionar equipos'],
            'reparaciones.contactos' => ['modulo' => 'reparaciones', 'accion' => 'contactos', 'descripcion' => 'Gestionar contactos de reparaciones'],
            'presupuestos.ver' => ['modulo' => 'presupuestos', 'accion' => 'ver', 'descripcion' => 'Ver presupuestos'],
            'presupuestos.crear' => ['modulo' => 'presupuestos', 'accion' => 'crear', 'descripcion' => 'Crear presupuestos'],
            'presupuestos.editar' => ['modulo' => 'presupuestos', 'accion' => 'editar', 'descripcion' => 'Editar presupuestos'],
            'cuentascorrientes.ver' => ['modulo' => 'cuentascorrientes', 'accion' => 'ver', 'descripcion' => 'Ver cuentas corrientes'],
            'cuentascorrientes.pagos' => ['modulo' => 'cuentascorrientes', 'accion' => 'pagos', 'descripcion' => 'Registrar pagos'],
            'configuracion.ver' => ['modulo' => 'configuracion', 'accion' => 'ver', 'descripcion' => 'Ver configuracion'],
            'configuracion.editar' => ['modulo' => 'configuracion', 'accion' => 'editar', 'descripcion' => 'Editar configuracion'],
            'usuarios.ver' => ['modulo' => 'usuarios', 'accion' => 'ver', 'descripcion' => 'Ver usuarios'],
            'usuarios.crear' => ['modulo' => 'usuarios', 'accion' => 'crear', 'descripcion' => 'Crear usuarios'],
            'usuarios.editar' => ['modulo' => 'usuarios', 'accion' => 'editar', 'descripcion' => 'Editar usuarios'],
            'usuarios.eliminar' => ['modulo' => 'usuarios', 'accion' => 'eliminar', 'descripcion' => 'Eliminar usuarios'],
            'usuarios.roles' => ['modulo' => 'usuarios', 'accion' => 'roles', 'descripcion' => 'Crear y editar roles'],
            'usuarios.permisos' => ['modulo' => 'usuarios', 'accion' => 'permisos', 'descripcion' => 'Modificar permisos'],
            'backups.ver' => ['modulo' => 'backups', 'accion' => 'ver', 'descripcion' => 'Ver backups'],
            'backups.crear' => ['modulo' => 'backups', 'accion' => 'crear', 'descripcion' => 'Crear backups'],
        ];
    }

    private function hayOtroRolAdministradorConPermiso(int $rolId, string $codigo): bool
    {
        $existe = RolModel::query()
            ->where('id', '<>', $rolId)
            ->whereIn('nombre', ['Dueno', 'Administrador'])
            ->where('activo', true)
            ->whereHas('permisos', static function ($query) use ($codigo): void {
                $query->where('codigo', $codigo)->where('permisos.activo', true);
            })
            ->exists();

        return $existe;
    }

    private function asignarPermisosAdministrador(): void
    {
        $permisos = PermisoModel::query()->pluck('id')->all();
        $roles = RolModel::query()->whereIn('nombre', ['Dueno', 'Administrador'])->get();

        foreach ($roles as $rol) {
            $rol->permisos()->syncWithoutDetaching($permisos);
        }
    }

    private function mapear(PermisoModel $modelo): Permiso
    {
        return new Permiso(
            (int) $modelo->id,
            (string) $modelo->modulo,
            (string) $modelo->accion,
            (string) $modelo->codigo,
            $modelo->descripcion !== null ? (string) $modelo->descripcion : null,
            (bool) $modelo->activo
        );
    }
}
