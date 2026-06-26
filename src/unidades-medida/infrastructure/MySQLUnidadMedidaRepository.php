<?php

declare(strict_types=1);

namespace Ventas\UnidadesMedida\Infrastructure;

use PDO;
use Ventas\UnidadesMedida\Domain\Entidades\UnidadMedida;
use Ventas\UnidadesMedida\Domain\Repositorios\UnidadMedidaRepository;

final class MySQLUnidadMedidaRepository implements UnidadMedidaRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listar(): array
    {
        $unidadesMedida = [];
        $statement = $this->pdo->prepare(
            "SELECT id, nombre, abreviatura, tipo, decimales, activo
             FROM unidades_medida
             WHERE activo = 1
             ORDER BY CASE WHEN FIELD(abreviatura, 'kg', 'g', 'l', 'ml', 'm', 'cm', 'cj', 'doc', 'pack', 'u') = 0 THEN 1 ELSE 0 END,
                      FIELD(abreviatura, 'kg', 'g', 'l', 'ml', 'm', 'cm', 'cj', 'doc', 'pack', 'u'),
                      nombre ASC"
        );

        $statement->execute();
        $filas = $statement->fetchAll();

        foreach ($filas as $fila) {
            $unidadesMedida[] = new UnidadMedida(
                (int) $fila['id'],
                (string) $fila['nombre'],
                (string) $fila['abreviatura'],
                (string) $fila['tipo'],
                (int) $fila['decimales'],
                (int) $fila['activo'] === 1
            );
        }

        return $unidadesMedida;
    }

    public function buscarPorId(int $id): ?UnidadMedida
    {
        $unidadMedida = null;
        $statement = $this->pdo->prepare(
            'SELECT id, nombre, abreviatura, tipo, decimales, activo
             FROM unidades_medida
             WHERE id = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);
        $fila = $statement->fetch();

        if (is_array($fila)) {
            $unidadMedida = new UnidadMedida(
                (int) $fila['id'],
                (string) $fila['nombre'],
                (string) $fila['abreviatura'],
                (string) $fila['tipo'],
                (int) $fila['decimales'],
                (int) $fila['activo'] === 1
            );
        }

        return $unidadMedida;
    }

    public function buscarPorAbreviatura(string $abreviatura): ?UnidadMedida
    {
        $unidadMedida = null;
        $abreviatura = $this->normalizarAbreviatura($abreviatura);

        if ($abreviatura !== '') {
            $statement = $this->pdo->prepare(
                'SELECT id, nombre, abreviatura, tipo, decimales, activo
                 FROM unidades_medida
                 WHERE abreviatura = :abreviatura
                 LIMIT 1'
            );

            $statement->execute(['abreviatura' => $abreviatura]);
            $fila = $statement->fetch();

            if (is_array($fila)) {
                $unidadMedida = $this->crearEntidadDesdeFila($fila);
            }
        }

        return $unidadMedida;
    }

    public function crear(string $nombre, string $abreviatura, string $tipo, int $decimales, int $activo = 1): bool
    {
        $ok = false;
        $nombre = trim($nombre);
        $abreviatura = $this->normalizarAbreviatura($abreviatura);
        $tipo = $this->normalizarTipo($tipo);
        $decimales = max(0, min(4, $decimales));

        if (!$this->textoInvalido($nombre) && $abreviatura !== '') {
            $statement = $this->pdo->prepare(
                'INSERT INTO unidades_medida (nombre, abreviatura, tipo, decimales, activo)
                 VALUES (:nombre, :abreviatura, :tipo, :decimales, :activo)
                 ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), tipo = VALUES(tipo), decimales = VALUES(decimales), activo = VALUES(activo)'
            );
            $ok = $statement->execute([
                'nombre' => $nombre,
                'abreviatura' => $abreviatura,
                'tipo' => $tipo,
                'decimales' => $decimales,
                'activo' => $activo,
            ]);
        }

        return $ok;
    }

    public function crearSinDuplicar(string $nombre, string $abreviatura, string $tipo, int $decimales, int $activo = 1): ?UnidadMedida
    {
        $unidadMedida = null;
        $nombre = trim($nombre);
        $abreviatura = $this->normalizarAbreviatura($abreviatura);
        $tipo = $this->normalizarTipo($tipo);
        $decimales = max(0, min(3, $decimales));

        if (!$this->textoInvalido($nombre) && $abreviatura !== '' && $this->buscarPorAbreviatura($abreviatura) === null) {
            $statement = $this->pdo->prepare(
                'INSERT INTO unidades_medida (nombre, abreviatura, tipo, decimales, activo)
                 VALUES (:nombre, :abreviatura, :tipo, :decimales, :activo)'
            );
            $creado = $statement->execute([
                'nombre' => $nombre,
                'abreviatura' => $abreviatura,
                'tipo' => $tipo,
                'decimales' => $decimales,
                'activo' => $activo,
            ]);

            if ($creado) {
                $unidadMedida = $this->buscarPorAbreviatura($abreviatura);
            }
        }

        return $unidadMedida;
    }

    public function asegurarDesdeFormulario(string $unidad, array $datos): string
    {
        $abreviatura = $this->normalizarAbreviatura($unidad);

        if ($abreviatura === '') {
            $abreviatura = 'u';
        }

        if ($this->buscarPorAbreviatura($abreviatura) === null) {
            $nombre = trim((string)($datos['nombre'] ?? ''));
            $tipo = (string)($datos['tipo'] ?? 'cantidad');
            $decimales = (int)($datos['decimales'] ?? 0);

            if (!$this->textoInvalido($nombre)) {
                $this->crear($nombre, $abreviatura, $tipo, $decimales, 1);
            }
        }

        return $abreviatura;
    }

    private function crearEntidadDesdeFila(array $fila): UnidadMedida
    {
        return new UnidadMedida(
            (int) $fila['id'],
            (string) $fila['nombre'],
            (string) $fila['abreviatura'],
            (string) $fila['tipo'],
            (int) $fila['decimales'],
            (int) $fila['activo'] === 1
        );
    }

    private function normalizarAbreviatura(string $abreviatura): string
    {
        return strtolower(trim($abreviatura));
    }

    private function normalizarTipo(string $tipo): string
    {
        $tipoNormalizado = strtolower(trim($tipo));

        if (!in_array($tipoNormalizado, ['peso', 'volumen', 'longitud', 'cantidad'], true)) {
            $tipoNormalizado = 'cantidad';
        }

        return $tipoNormalizado;
    }

    private function textoInvalido(string $texto): bool
    {
        $invalido = trim($texto) === '';

        if (!$invalido && function_exists('texto_invalido')) {
            $invalido = \texto_invalido($texto);
        }

        return $invalido;
    }
}
