<?php

declare(strict_types=1);

namespace Ventas\ListasPrecios\Infrastructure;

use PDO;
use Throwable;
use Ventas\ListasPrecios\Domain\Entidades\ListaPrecio;
use Ventas\ListasPrecios\Domain\Repositorios\ListaPrecioRepository;

final class MySQLListaPrecioRepository implements ListaPrecioRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listar(bool $soloActivas = true, string $ordenSql = 'nombre ASC'): array
    {
        $listasPrecios = [];
        $ordenPermitido = $this->normalizarOrden($ordenSql);
        $statement = $this->pdo->prepare(
            'SELECT id, nombre, activo, creado_en
             FROM listas_precios'
             . ($soloActivas ? ' WHERE activo = 1' : '')
             . ' ORDER BY ' . $ordenPermitido . ', id ASC'
        );

        $statement->execute();
        $filas = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($filas as $fila) {
            $listasPrecios[] = new ListaPrecio(
                isset($fila['id']) ? (int) $fila['id'] : null,
                (string) ($fila['nombre'] ?? ''),
                ((int) ($fila['activo'] ?? 0)) === 1,
                isset($fila['creado_en']) ? (string) $fila['creado_en'] : null
            );
        }

        return $listasPrecios;
    }

    public function buscarPorId(int $id): ?ListaPrecio
    {
        $listaPrecio = null;

        if ($id > 0) {
            $statement = $this->pdo->prepare(
                'SELECT id, nombre, activo, creado_en
                 FROM listas_precios
                 WHERE id = :id
                 LIMIT 1'
            );

            $statement->execute(['id' => $id]);
            $fila = $statement->fetch(PDO::FETCH_ASSOC);

            if (is_array($fila)) {
                $listaPrecio = new ListaPrecio(
                    isset($fila['id']) ? (int) $fila['id'] : null,
                    (string) ($fila['nombre'] ?? ''),
                    ((int) ($fila['activo'] ?? 0)) === 1,
                    isset($fila['creado_en']) ? (string) $fila['creado_en'] : null
                );
            }
        }

        return $listaPrecio;
    }

    public function idPredeterminada(): int
    {
        $id = 1;
        $listas = $this->listar();
        $idPublico = 0;
        $idNoCosto = 0;
        $idPrimera = 0;

        foreach ($listas as $lista) {
            $nombre = strtolower(trim($lista->nombre()));

            if ($idPrimera === 0 && $lista->id() !== null) {
                $idPrimera = $lista->id();
            }

            if ($idPublico === 0 && ($nombre === 'publico' || $nombre === 'pÃºblico')) {
                $idPublico = (int) $lista->id();
            }

            if ($idNoCosto === 0 && $nombre !== 'costo') {
                $idNoCosto = (int) $lista->id();
            }
        }

        if ($idPublico > 0) {
            $id = $idPublico;
        } elseif ($idNoCosto > 0) {
            $id = $idNoCosto;
        } elseif ($idPrimera > 0) {
            $id = $idPrimera;
        }

        return $id;
    }

    public function esListaBase(int $id): bool
    {
        $resultado = false;
        $lista = $this->buscarPorId($id);

        if ($lista !== null) {
            $resultado = $this->esNombreCosto($lista->nombre());
        }

        return $resultado;
    }

    public function crear(string $nombre, int $activo): bool
    {
        $statement = $this->pdo->prepare('INSERT INTO listas_precios (nombre, activo) VALUES (?, ?)');
        $resultado = $statement->execute([$nombre, $activo]);

        return $resultado;
    }

    public function actualizar(int $id, string $nombre, int $activo): bool
    {
        $resultado = false;

        if ($id > 0) {
            $statement = $this->pdo->prepare('UPDATE listas_precios SET nombre = ?, activo = ? WHERE id = ?');
            $resultado = $statement->execute([$nombre, $activo, $id]);
        }

        return $resultado;
    }

    public function eliminar(int $id): bool
    {
        $resultado = false;

        if ($id > 0) {
            $statement = $this->pdo->prepare('DELETE FROM listas_precios WHERE id = ?');
            $resultado = $statement->execute([$id]);
        }

        return $resultado;
    }

    public function precioProducto(int $idProducto, int $idLista): ?float
    {
        $resultado = null;
        $precio = $this->precioProductoCargado($idProducto, $idLista);

        if ($precio !== null) {
            $resultado = (float) $precio['precio'];
        }

        return $resultado;
    }

    public function precioProductoCargado(int $idProducto, int $idLista): ?array
    {
        $resultado = null;

        if ($idProducto > 0 && $idLista > 0) {
            try {
                $statement = $this->pdo->prepare(
                    "SELECT l.nombre AS lista_nombre, COALESCE(pp.porcentaje, 0) AS porcentaje,
                            COALESCE(pp.precio, 0) AS precio,
                            COALESCE(s.precio_costo, 0) AS costo_stock,
                            COALESCE(pc.precio, 0) AS costo_lista
                     FROM productos p
                     INNER JOIN listas_precios l ON l.id = ?
                     LEFT JOIN stock s ON s.id = p.id_stock
                     LEFT JOIN producto_precios pp ON pp.id_producto = p.id AND pp.id_lista = l.id
                     LEFT JOIN listas_precios lc ON LOWER(lc.nombre) = 'costo' AND lc.activo = 1
                     LEFT JOIN producto_precios pc ON pc.id_producto = p.id AND pc.id_lista = lc.id
                     WHERE p.id = ?
                     LIMIT 1"
                );
                $statement->execute([$idLista, $idProducto]);
                $fila = $statement->fetch(PDO::FETCH_ASSOC);

                if (is_array($fila)) {
                    if ($this->esNombreCosto((string) ($fila['lista_nombre'] ?? ''))) {
                        $costo = (float) ($fila['precio'] ?? 0);

                        if ($costo <= 0) {
                            $costo = (float) ($fila['costo_stock'] ?? 0);
                        }

                        $resultado = ['porcentaje' => 0.0, 'precio' => $costo];
                    } elseif ((float) ($fila['precio'] ?? 0) > 0) {
                        $porcentaje = (float) ($fila['porcentaje'] ?? 0);
                        $costoBase = (float) ($fila['costo_lista'] ?? 0);

                        if ($costoBase <= 0) {
                            $costoBase = (float) ($fila['costo_stock'] ?? 0);
                        }

                        if ($porcentaje <= 0 && $costoBase > 0) {
                            $porcentaje = (((float) $fila['precio'] / $costoBase) - 1) * 100;
                        }

                        $resultado = ['porcentaje' => $porcentaje, 'precio' => (float) $fila['precio']];
                    }
                }
            } catch (Throwable $throwable) {
                registrar_log('ListaPrecio::precio_producto_cargado', $throwable->getMessage());
            }
        }

        return $resultado;
    }

    public function precioProductoCompleto(int $idProducto, int $idLista): ?array
    {
        $resultado = null;

        if ($idProducto > 0 && $idLista > 0) {
            try {
                $statement = $this->pdo->prepare(
                    'SELECT COALESCE(pp.porcentaje, 0) AS porcentaje,
                            CASE
                                WHEN COALESCE(pp.precio, 0) > 0 THEN pp.precio
                                WHEN COALESCE(pp.porcentaje, 0) > 0 THEN COALESCE(s.precio_costo, 0) * (1 + (pp.porcentaje / 100))
                                ELSE 0
                            END AS precio
                     FROM productos p
                     LEFT JOIN stock s ON s.id = p.id_stock
                     LEFT JOIN producto_precios pp ON pp.id_producto = p.id AND pp.id_lista = ?
                     WHERE p.id = ?
                     LIMIT 1'
                );
                $statement->execute([$idLista, $idProducto]);
                $fila = $statement->fetch(PDO::FETCH_ASSOC);

                if (is_array($fila)) {
                    $resultado = [
                        'porcentaje' => (float) ($fila['porcentaje'] ?? 0),
                        'precio' => (float) ($fila['precio'] ?? 0),
                    ];
                }
            } catch (Throwable $throwable) {
                registrar_log('ListaPrecio::precio_producto_completo', $throwable->getMessage());
            }
        }

        return $resultado;
    }

    public function guardarPrecioProducto(int $idProducto, int $idLista, float $porcentaje, float $precio): bool
    {
        $resultado = $this->guardarPrecioProductoOrigen($idProducto, $idLista, $porcentaje, $precio, 'manual');

        return $resultado;
    }

    public function guardarPrecioProductoOrigen(int $idProducto, int $idLista, float $porcentaje, float $precio, string $origen = 'manual'): bool
    {
        $resultado = false;

        if ($idProducto > 0 && $idLista > 0) {
            try {
                if ($porcentaje < 0) {
                    $porcentaje = 0;
                }

                if ($precio < 0) {
                    $precio = 0;
                }

                $statementActual = $this->pdo->prepare('SELECT precio FROM producto_precios WHERE id_producto = ? AND id_lista = ? LIMIT 1');
                $statementActual->execute([$idProducto, $idLista]);
                $precioAnterior = $statementActual->fetchColumn();
                $precioAnterior = $precioAnterior === false ? 0.0 : (float) $precioAnterior;

                $statement = $this->pdo->prepare(
                    'INSERT INTO producto_precios (id_producto, id_lista, porcentaje, precio)
                     VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE porcentaje = VALUES(porcentaje), precio = VALUES(precio)'
                );
                $resultado = $statement->execute([$idProducto, $idLista, $porcentaje, $precio]);

                if ($resultado && abs($precioAnterior - $precio) >= 0.01) {
                    $origenHistorial = trim($origen) !== '' ? trim($origen) : 'manual';
                    $origenHistorial = substr($origenHistorial, 0, 40);
                    $statementHistorial = $this->pdo->prepare(
                        'INSERT INTO historial_precios (id_producto, id_lista, precio_anterior, precio_nuevo, origen)
                         VALUES (?, ?, ?, ?, ?)'
                    );
                    $statementHistorial->execute([$idProducto, $idLista, $precioAnterior, $precio, $origenHistorial]);
                }
            } catch (Throwable $throwable) {
                registrar_log('ListaPrecio::guardar_precio_producto', $throwable->getMessage());
                $resultado = false;
            }
        }

        return $resultado;
    }

    public function productosParaExportar(int $idLista = 0): array
    {
        $resultado = [];

        try {
            $join = $idLista > 0 ? 'INNER JOIN listas_precios l ON l.id = ? LEFT JOIN producto_precios pp ON pp.id_producto = p.id AND pp.id_lista = l.id' : '';
            $precioListaSql = $idLista > 0
                ? "CASE
                    WHEN LOWER(COALESCE(l.nombre, '')) = 'costo' THEN COALESCE(s.precio_costo, 0)
                    WHEN COALESCE(pp.precio, 0) > 0 THEN pp.precio
                    ELSE 0
                  END"
                : 'p.precio_final';
            $statement = $this->pdo->prepare(
                "SELECT p.id, p.nombre, p.cod_barras, p.precio_final, COALESCE(s.unidad, '') AS unidad,
                        " . $precioListaSql . " AS precio_lista
                 FROM productos p
                 LEFT JOIN stock s ON s.id = p.id_stock
                 " . $join . "
                 WHERE p.activo = 1
                 ORDER BY p.nombre ASC"
            );

            if ($idLista > 0) {
                $statement->execute([$idLista]);
            } else {
                $statement->execute();
            }

            $filas = $statement->fetchAll(PDO::FETCH_ASSOC);

            if (is_array($filas)) {
                $resultado = $filas;
            }
        } catch (Throwable $throwable) {
            registrar_log('ListaPrecio::productos_para_exportar', $throwable->getMessage());
        }

        return $resultado;
    }

    public function historialPrecios(string $desde = '', string $hasta = '', int $idLista = 0): array
    {
        $resultado = [];

        try {
            $params = [];
            $where = [];

            if ($desde !== '') {
                $where[] = 'h.creado_en >= ?';
                $params[] = $desde . ' 00:00:00';
            }

            if ($hasta !== '') {
                $where[] = 'h.creado_en <= ?';
                $params[] = $hasta . ' 23:59:59';
            }

            if ($idLista > 0) {
                $where[] = 'h.id_lista = ?';
                $params[] = $idLista;
            }

            $sql = 'SELECT h.creado_en, l.nombre AS lista, p.cod_barras AS codigo, p.nombre AS producto,
                           h.precio_anterior, h.precio_nuevo, h.origen
                    FROM historial_precios h
                    INNER JOIN productos p ON p.id = h.id_producto
                    INNER JOIN listas_precios l ON l.id = h.id_lista';

            if (count($where) > 0) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }

            $sql .= ' ORDER BY h.creado_en DESC, l.nombre ASC, p.nombre ASC';
            $statement = $this->pdo->prepare($sql);
            $statement->execute($params);
            $filas = $statement->fetchAll(PDO::FETCH_ASSOC);

            if (is_array($filas)) {
                $resultado = $filas;
            }
        } catch (Throwable $throwable) {
            registrar_log('ListaPrecio::historial_precios', $throwable->getMessage());
        }

        return $resultado;
    }

    public function inicializarEsquema(): void
    {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS listas_precios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(80) NOT NULL,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            asegurar_columna_bd($this->pdo, 'listas_precios', 'nombre', "ALTER TABLE listas_precios ADD COLUMN nombre VARCHAR(80) NOT NULL DEFAULT '' AFTER id");
            asegurar_columna_bd($this->pdo, 'listas_precios', 'activo', 'ALTER TABLE listas_precios ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER nombre');
            asegurar_columna_bd($this->pdo, 'listas_precios', 'creado_en', 'ALTER TABLE listas_precios ADD COLUMN creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS producto_precios (
                id_producto INT NOT NULL,
                id_lista INT NOT NULL,
                porcentaje DECIMAL(12,4) NOT NULL DEFAULT 0,
                precio DECIMAL(14,2) NOT NULL DEFAULT 0,
                PRIMARY KEY (id_producto, id_lista)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            asegurar_columna_bd($this->pdo, 'producto_precios', 'porcentaje', 'ALTER TABLE producto_precios ADD COLUMN porcentaje DECIMAL(12,4) NOT NULL DEFAULT 0 AFTER id_lista');
            asegurar_columna_bd($this->pdo, 'producto_precios', 'precio', 'ALTER TABLE producto_precios ADD COLUMN precio DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER porcentaje');
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS historial_precios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_producto INT NOT NULL,
                id_lista INT NOT NULL,
                precio_anterior DECIMAL(14,2) NOT NULL DEFAULT 0,
                precio_nuevo DECIMAL(14,2) NOT NULL DEFAULT 0,
                origen VARCHAR(40) NOT NULL DEFAULT 'manual',
                creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_historial_precios_fecha (creado_en),
                KEY idx_historial_precios_lista (id_lista),
                KEY idx_historial_precios_producto (id_producto)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $this->asegurarListaBase('Costo');
            $this->asegurarColumnaCliente();
        } catch (Throwable $throwable) {
            registrar_log('ListaPrecio::asegurar_tablas', $throwable->getMessage());
        }
    }

    private function asegurarListaBase(string $nombre): void
    {
        $statement = $this->pdo->prepare('SELECT id FROM listas_precios WHERE LOWER(nombre) = LOWER(?) LIMIT 1');
        $statement->execute([$nombre]);
        $fila = $statement->fetch(PDO::FETCH_ASSOC);

        if (is_array($fila)) {
            $this->pdo->prepare('UPDATE listas_precios SET activo = 1 WHERE id = ?')->execute([(int) $fila['id']]);
        } else {
            $this->pdo->prepare('INSERT INTO listas_precios (nombre, activo) VALUES (?, 1)')->execute([$nombre]);
        }
    }

    private function asegurarColumnaCliente(): void
    {
        $statement = $this->pdo->prepare('SHOW COLUMNS FROM clientes LIKE ?');
        $statement->execute(['id_lista_precio']);

        if (!$statement->fetch()) {
            $this->pdo->exec('ALTER TABLE clientes ADD COLUMN id_lista_precio INT NULL AFTER email');
        }
    }

    private function normalizarOrden(string $ordenSql): string
    {
        $permitidos = [
            'nombre ASC',
            'nombre DESC',
            'activo ASC',
            'activo DESC',
            'creado_en ASC',
            'creado_en DESC',
        ];
        $resultado = in_array($ordenSql, $permitidos, true) ? $ordenSql : 'nombre ASC';

        return $resultado;
    }

    private function esNombreCosto(string $nombre): bool
    {
        $resultado = strtolower(trim($nombre)) === 'costo';

        return $resultado;
    }
}
