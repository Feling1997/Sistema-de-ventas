<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Persistencia\MySQL\Usuarios;

use PDO;
use RuntimeException;
use Ventas\Dominio\Usuarios\Entidades\PermisosUsuario;
use Ventas\Dominio\Usuarios\Entidades\Usuario;
use Ventas\Dominio\Usuarios\Repositorios\UsuarioRepository;

final class MySQLUsuarioRepository implements UsuarioRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listar(): array
    {
        $usuarios = [];

        $statement = $this->pdo->prepare(
            'SELECT id, usuario, rol, activo, creado_en, permisos
             FROM usuarios
             ORDER BY usuario ASC, id ASC'
        );
        $statement->execute();
        $filas = $statement->fetchAll();

        foreach ($filas as $fila) {
            $usuarios[] = $this->mapearUsuario($fila);
        }

        return $usuarios;
    }

    public function buscarPorId(int $id): ?Usuario
    {
        $usuario = null;

        $statement = $this->pdo->prepare(
            'SELECT id, usuario, clave, rol, activo, creado_en, permisos
             FROM usuarios
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $fila = $statement->fetch();

        if (is_array($fila)) {
            $usuario = $this->mapearUsuario($fila);
        }

        return $usuario;
    }

    public function buscarPorUsuario(string $usuario): ?Usuario
    {
        $usuarioEncontrado = null;

        $statement = $this->pdo->prepare(
            'SELECT id, usuario, clave, rol, activo, creado_en, permisos
             FROM usuarios
             WHERE usuario = :usuario
             LIMIT 1'
        );
        $statement->execute(['usuario' => $usuario]);
        $fila = $statement->fetch();

        if (is_array($fila)) {
            $usuarioEncontrado = $this->mapearUsuario($fila);
        }

        return $usuarioEncontrado;
    }

    public function existeUsuario(string $usuario, ?int $exceptoId = null): bool
    {
        $existe = false;
        $exceptoIdSeguro = $exceptoId ?? 0;

        $statement = $this->pdo->prepare(
            'SELECT id
             FROM usuarios
             WHERE usuario = :usuario AND id <> :excepto_id
             LIMIT 1'
        );
        $statement->execute([
            'usuario' => $usuario,
            'excepto_id' => $exceptoIdSeguro,
        ]);

        $existe = is_array($statement->fetch());

        return $existe;
    }

    public function guardar(Usuario $usuario): Usuario
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO usuarios (usuario, clave, rol, activo, permisos)
             VALUES (:usuario, :clave, :rol, :activo, :permisos)'
        );
        $statement->execute([
            'usuario' => $usuario->usuario(),
            'clave' => $usuario->claveHash() ?? '',
            'rol' => $usuario->rol(),
            'activo' => $usuario->activo() ? 1 : 0,
            'permisos' => $this->permisosJson($usuario->permisos()),
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $usuarioGuardado = $this->buscarPorId($id);

        if ($usuarioGuardado === null) {
            throw new RuntimeException('No se pudo recuperar el usuario guardado.');
        }

        return $usuarioGuardado;
    }

    public function actualizar(Usuario $usuario): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE usuarios
             SET usuario = :usuario,
                 rol = :rol,
                 activo = :activo,
                 permisos = :permisos
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $usuario->id(),
            'usuario' => $usuario->usuario(),
            'rol' => $usuario->rol(),
            'activo' => $usuario->activo() ? 1 : 0,
            'permisos' => $this->permisosJson($usuario->permisos()),
        ]);
    }

    public function actualizarClave(int $id, string $claveHash): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE usuarios
             SET clave = :clave
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'clave' => $claveHash,
        ]);
    }

    public function eliminar(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM usuarios WHERE id = :id');

        $statement->execute(['id' => $id]);
    }

    public function tieneVentasAsociadas(int $id): bool
    {
        $tieneVentas = false;
        $statement = $this->pdo->prepare(
            'SELECT id
             FROM ventas
             WHERE id_usuario = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);
        $tieneVentas = is_array($statement->fetch());

        return $tieneVentas;
    }

    /**
     * @param array<string, mixed> $fila
     */
    private function mapearUsuario(array $fila): Usuario
    {
        return new Usuario(
            (int) $fila['id'],
            (string) $fila['usuario'],
            isset($fila['clave']) ? (string) $fila['clave'] : null,
            (string) $fila['rol'],
            (int) $fila['activo'] === 1,
            PermisosUsuario::desdeLegacy($this->permisosArray($fila['permisos'] ?? [])),
            isset($fila['creado_en']) ? (string) $fila['creado_en'] : null
        );
    }

    /**
     * @return string[]
     */
    private function permisosArray(mixed $valor): array
    {
        $permisos = [];

        if (is_array($valor)) {
            $permisos = array_values(array_filter(array_map('strval', $valor)));
        } else {
            $datos = json_decode((string) $valor, true);

            if (is_array($datos)) {
                $permisos = array_values(array_filter(array_map('strval', $datos)));
            }
        }

        return $permisos;
    }

    private function permisosJson(PermisosUsuario $permisos): string
    {
        $json = json_encode($permisos->comoArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $permisosJson = '[]';

        if (is_string($json)) {
            $permisosJson = $json;
        }

        return $permisosJson;
    }
}
