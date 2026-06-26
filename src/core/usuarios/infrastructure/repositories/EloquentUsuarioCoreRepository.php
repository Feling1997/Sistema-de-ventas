<?php

declare(strict_types=1);

namespace Ventas\Core\Usuarios\Infrastructure\Repositories;

use Illuminate\Support\Facades\Hash;
use Ventas\Core\Roles\Infrastructure\Models\RolModel;
use Ventas\Core\Usuarios\Domain\Entidades\UsuarioCore;
use Ventas\Core\Usuarios\Domain\Repositorios\UsuarioRepository;
use Ventas\Core\Usuarios\Infrastructure\Models\UsuarioCoreModel;

final class EloquentUsuarioCoreRepository implements UsuarioRepository
{
    public function listar(): array
    {
        $usuarios = [];
        $modelos = UsuarioCoreModel::query()->with('roles')->orderBy('usuario')->get();

        foreach ($modelos as $modelo) {
            $usuarios[] = $this->mapear($modelo);
        }

        return $usuarios;
    }

    public function buscarPorId(int $id): ?UsuarioCore
    {
        $usuario = null;
        $modelo = UsuarioCoreModel::query()->with('roles')->where('id', $id)->first();

        if ($modelo instanceof UsuarioCoreModel) {
            $usuario = $this->mapear($modelo);
        }

        return $usuario;
    }

    public function buscarPorLegacyId(int $legacyId): ?UsuarioCore
    {
        $usuario = null;
        $modelo = UsuarioCoreModel::query()->with('roles')->where('usuario_legacy_id', $legacyId)->first();

        if ($modelo instanceof UsuarioCoreModel) {
            $usuario = $this->mapear($modelo);
        }

        return $usuario;
    }

    public function guardar(array $datos): UsuarioCore
    {
        $id = (int) ($datos['id'] ?? 0);
        $modelo = $id > 0 ? UsuarioCoreModel::query()->where('id', $id)->first() : null;

        if (!$modelo instanceof UsuarioCoreModel) {
            $modelo = new UsuarioCoreModel();
        }

        $nombreCompleto = trim(trim((string) ($datos['nombre'] ?? '')) . ' ' . trim((string) ($datos['apellido'] ?? '')));
        $clave = trim((string) ($datos['clave'] ?? ''));
        $confirmacion = trim((string) ($datos['clave_confirmation'] ?? $clave));
        $valores = [
            'usuario_legacy_id' => $datos['usuario_legacy_id'] ?? null,
            'nombre' => $nombreCompleto,
            'usuario' => trim((string) ($datos['usuario'] ?? '')),
            'email' => trim((string) ($datos['email'] ?? '')),
            'activo' => (bool) ($datos['activo'] ?? true),
        ];

        if ($clave !== '' && $clave === $confirmacion) {
            $valores['clave'] = Hash::make($clave);
        }

        $modelo->fill($valores);
        $modelo->save();

        if ((int) ($datos['rol_id'] ?? 0) > 0) {
            $this->asignarRol((int) $modelo->id, (int) $datos['rol_id']);
        }

        return $this->mapear($modelo->fresh('roles'));
    }

    public function desactivar(int $id): bool
    {
        $desactivado = false;
        $modelo = UsuarioCoreModel::query()->where('id', $id)->first();

        if ($modelo instanceof UsuarioCoreModel) {
            $critico = $modelo->roles()->whereIn('nombre', ['Dueno', 'Administrador'])->exists();
            $otrosActivos = $critico ? $this->cantidadAdministradoresActivos((int) $modelo->id) : 1;

            if (!$critico || $otrosActivos > 0) {
                $modelo->activo = false;
                $desactivado = (bool) $modelo->save();
            }
        }

        return $desactivado;
    }

    private function cantidadAdministradoresActivos(int $exceptoUsuarioId): int
    {
        $cantidad = UsuarioCoreModel::query()
            ->where('id', '<>', $exceptoUsuarioId)
            ->where('activo', true)
            ->whereHas('roles', static function ($query): void {
                $query->whereIn('nombre', ['Dueno', 'Administrador'])->where('roles.activo', true);
            })
            ->count();

        return $cantidad;
    }

    public function asignarRol(int $usuarioId, int $rolId): bool
    {
        $asignado = false;
        $usuario = UsuarioCoreModel::query()->where('id', $usuarioId)->first();
        $rol = RolModel::query()->where('id', $rolId)->first();

        if ($usuario instanceof UsuarioCoreModel && $rol instanceof RolModel) {
            $usuario->roles()->sync([$rolId]);
            $asignado = true;
        }

        return $asignado;
    }

    public function sincronizarLegacy(array $usuarioSesion, int $rolId): UsuarioCore
    {
        $legacyId = (int) ($usuarioSesion['id'] ?? 0);
        $usuario = UsuarioCoreModel::query()->where('usuario_legacy_id', $legacyId)->first();

        if (!$usuario instanceof UsuarioCoreModel) {
            $usuario = new UsuarioCoreModel();
        }

        $usuario->fill([
            'usuario_legacy_id' => $legacyId > 0 ? $legacyId : null,
            'nombre' => (string) ($usuarioSesion['usuario'] ?? ''),
            'usuario' => (string) ($usuarioSesion['usuario'] ?? ''),
            'activo' => true,
            'ultimo_acceso' => now(),
        ]);
        $usuario->save();
        $this->asignarRol((int) $usuario->id, $rolId);

        return $this->mapear($usuario->fresh('roles'));
    }

    private function mapear(UsuarioCoreModel $modelo): UsuarioCore
    {
        $roles = [];

        foreach ($modelo->roles as $rol) {
            $roles[] = [
                'id' => (int) $rol->id,
                'nombre' => (string) $rol->nombre,
                'descripcion' => $rol->descripcion !== null ? (string) $rol->descripcion : null,
                'activo' => (bool) $rol->activo,
            ];
        }

        return new UsuarioCore(
            (int) $modelo->id,
            $modelo->usuario_legacy_id !== null ? (int) $modelo->usuario_legacy_id : null,
            $modelo->nombre !== null ? (string) $modelo->nombre : null,
            (string) $modelo->usuario,
            $modelo->email !== null ? (string) $modelo->email : null,
            $modelo->clave !== null ? (string) $modelo->clave : null,
            (bool) $modelo->activo,
            $modelo->ultimo_acceso !== null ? (string) $modelo->ultimo_acceso : null,
            $roles
        );
    }
}
