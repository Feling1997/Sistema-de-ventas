<?php

declare(strict_types=1);

namespace Ventas\Auth\Infrastructure;

use PDO;
use Throwable;
use Ventas\Auth\Domain\Repositorios\ConfiguracionAuthRepository;

final class MySQLConfiguracionAuthRepository implements ConfiguracionAuthRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $archivoConfiguracion
    ) {
    }

    public function modoAuth(): ?string
    {
        $modo = $this->modoDesdeBaseDatos();

        if ($modo === null) {
            $modo = $this->modoDesdeArchivo();
        }

        return $modo;
    }

    public function sinLoginHabilitado(): bool
    {
        $habilitado = $this->modoAuth() === 'sin_login';

        return $habilitado;
    }

    public function noHayUsuariosCreados(): bool
    {
        $resultado = false;

        try {
            $statement = $this->pdo->prepare('SELECT COUNT(*) AS total FROM usuarios WHERE id > 0 OR usuario <> ?');
            $statement->execute(['Sin login']);
            $fila = $statement->fetch();
            $resultado = (int) ($fila['total'] ?? 0) === 0;
        } catch (Throwable) {
            $resultado = false;
        }

        return $resultado;
    }

    public function existeUsuarioEspecialSinLogin(): bool
    {
        $existe = false;

        try {
            $statement = $this->pdo->prepare('SELECT id FROM usuarios WHERE id = ? AND usuario = ? LIMIT 1');
            $statement->execute([0, 'Sin login']);
            $existe = is_array($statement->fetch());
        } catch (Throwable) {
            $existe = false;
        }

        return $existe;
    }

    private function modoDesdeBaseDatos(): ?string
    {
        $modo = null;

        try {
            $statement = $this->pdo->prepare('SELECT valor FROM configuraciones WHERE clave = ? LIMIT 1');
            $statement->execute(['auth_modo']);
            $fila = $statement->fetch();

            if (is_array($fila)) {
                $modoEncontrado = trim((string) ($fila['valor'] ?? ''));

                if (in_array($modoEncontrado, ['login', 'sin_login'], true)) {
                    $modo = $modoEncontrado;
                }
            }
        } catch (Throwable) {
            $modo = null;
        }

        return $modo;
    }

    private function modoDesdeArchivo(): ?string
    {
        $modo = null;

        if (is_file($this->archivoConfiguracion)) {
            $json = @file_get_contents($this->archivoConfiguracion);

            if (is_string($json)) {
                $json = preg_replace('/^\xEF\xBB\xBF/', '', $json) ?? $json;
                $datos = json_decode($json, true);

                if (is_array($datos)) {
                    $modoEncontrado = (string) ($datos['auth_modo'] ?? 'login');

                    if (in_array($modoEncontrado, ['login', 'sin_login'], true)) {
                        $modo = $modoEncontrado;
                    }
                }
            }
        }

        return $modo;
    }
}
