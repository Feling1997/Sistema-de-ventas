<?php

declare(strict_types=1);

namespace Ventas\Stock\Infrastructure;

use PDO;
use Ventas\Stock\Domain\Entidades\Stock;
use Ventas\Stock\Domain\Repositorios\StockRepository;

final class MySQLStockRepository implements StockRepository
{
    private bool $esquemaStockInicializado = false;

    private bool $alertasStockInicializadas = false;

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listar(): array
    {
        $stocks = [];
        $statement = $this->pdo->prepare(
            'SELECT s.id,
                    s.nombre,
                    s.unidad,
                    s.tipo_stock,
                    s.cantidad,
                    s.stock_minimo,
                    s.stock_maximo,
                    s.precio_costo,
                    s.moneda_costo,
                    s.costo_origen,
                    s.activo,
                    s.creado_en,
                    COALESCE(um.decimales, 3) AS unidad_decimales
             FROM stock s
             LEFT JOIN unidades_medida um
             ON um.abreviatura COLLATE utf8mb4_unicode_ci =
             s.unidad COLLATE utf8mb4_unicode_ci
             ORDER BY s.nombre ASC, s.id ASC'
        );

        $statement->execute();
        $filas = $statement->fetchAll();

        foreach ($filas as $fila) {
            $stocks[] = $this->mapearStock($fila);
        }

        return $stocks;
    }

    public function buscarPorId(int $id): ?Stock
    {
        $stock = null;
        $statement = $this->pdo->prepare(
            'SELECT s.id,
                    s.nombre,
                    s.unidad,
                    s.tipo_stock,
                    s.cantidad,
                    s.stock_minimo,
                    s.stock_maximo,
                    s.precio_costo,
                    s.moneda_costo,
                    s.costo_origen,
                    s.activo,
                    s.creado_en,
                    COALESCE(um.decimales, 3) AS unidad_decimales
             FROM stock s
             LEFT JOIN unidades_medida um
             ON um.abreviatura COLLATE utf8mb4_unicode_ci =
             s.unidad COLLATE utf8mb4_unicode_ci
             WHERE s.id = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);
        $fila = $statement->fetch();

        if (is_array($fila)) {
            $stock = $this->mapearStock($fila);
        }

        return $stock;
    }

    public function crear(
        string $nombre,
        string $unidad,
        float $cantidad,
        float $precioCosto,
        int $activo,
        float $stockMinimo = 0,
        float $stockMaximo = 0,
        string $tipoStock = 'general',
        string $monedaCosto = 'ARS',
        float $costoOrigen = 0
    ): bool {
        $ok = false;

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO stock (nombre, unidad, tipo_stock, cantidad, stock_minimo, stock_maximo, precio_costo, moneda_costo, costo_origen, activo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            $ok = $statement->execute([
                $nombre,
                $unidad,
                $tipoStock,
                $cantidad,
                $stockMinimo,
                $stockMaximo,
                $precioCosto,
                $monedaCosto,
                $costoOrigen,
                $activo,
            ]);
        } catch (\Throwable $e) {
            registrar_log('Stock::crear', $e->getMessage());
            $ok = false;
        }

        return $ok;
    }

    public function crearRetornandoId(
        string $nombre,
        string $unidad,
        float $cantidad,
        float $precioCosto,
        int $activo,
        float $stockMinimo = 0,
        float $stockMaximo = 0,
        string $tipoStock = 'general',
        string $monedaCosto = 'ARS',
        float $costoOrigen = 0
    ): int {
        $id = 0;

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO stock (nombre, unidad, tipo_stock, cantidad, stock_minimo, stock_maximo, precio_costo, moneda_costo, costo_origen, activo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            if ($statement->execute([
                $nombre,
                $unidad,
                $tipoStock,
                $cantidad,
                $stockMinimo,
                $stockMaximo,
                $precioCosto,
                $monedaCosto,
                $costoOrigen,
                $activo,
            ])) {
                $id = (int) $this->pdo->lastInsertId();
            }
        } catch (\Throwable $e) {
            registrar_log('Stock::crearRetornandoId', $e->getMessage());
        }

        return $id;
    }

    public function actualizar(
        int $id,
        string $nombre,
        string $unidad,
        float $cantidad,
        float $precioCosto,
        int $activo,
        float $stockMinimo = 0,
        float $stockMaximo = 0,
        string $tipoStock = '',
        string $monedaCosto = '',
        float $costoOrigen = 0
    ): bool {
        $ok = false;

        try {
            if ($tipoStock === '') {
                $statement = $this->pdo->prepare(
                    'UPDATE stock SET nombre = ?, unidad = ?, cantidad = ?, stock_minimo = ?, stock_maximo = ?, precio_costo = ?, moneda_costo = COALESCE(NULLIF(?, \'\'), moneda_costo), costo_origen = ?, activo = ? WHERE id = ?'
                );

                $ok = $statement->execute([
                    $nombre,
                    $unidad,
                    $cantidad,
                    $stockMinimo,
                    $stockMaximo,
                    $precioCosto,
                    $monedaCosto,
                    $costoOrigen,
                    $activo,
                    $id,
                ]);
            } else {
                $statement = $this->pdo->prepare(
                    'UPDATE stock SET nombre = ?, unidad = ?, tipo_stock = ?, cantidad = ?, stock_minimo = ?, stock_maximo = ?, precio_costo = ?, moneda_costo = COALESCE(NULLIF(?, \'\'), moneda_costo), costo_origen = ?, activo = ? WHERE id = ?'
                );

                $ok = $statement->execute([
                    $nombre,
                    $unidad,
                    $tipoStock,
                    $cantidad,
                    $stockMinimo,
                    $stockMaximo,
                    $precioCosto,
                    $monedaCosto,
                    $costoOrigen,
                    $activo,
                    $id,
                ]);
            }
        } catch (\Throwable $e) {
            registrar_log('Stock::actualizar', $e->getMessage());
            $ok = false;
        }

        return $ok;
    }

    public function sumarCantidad(int $id, float $cantidad): bool
    {
        $ok = false;

        try {
            if ($cantidad < 0) {
                $cantidad = 0;
            }

            $statement = $this->pdo->prepare('UPDATE stock SET cantidad = cantidad + ? WHERE id = ?');
            $ok = $statement->execute([$cantidad, $id]);
        } catch (\Throwable $e) {
            registrar_log('Stock::sumarCantidad', $e->getMessage());
            $ok = false;
        }

        return $ok;
    }

    public function contarProductosAsociados(int $idStock): int
    {
        $cantidad = 0;

        try {
            $statement = $this->pdo->prepare('SELECT COUNT(*) AS total FROM productos WHERE id_stock = ?');
            $statement->execute([$idStock]);
            $row = $statement->fetch();

            if (is_array($row)) {
                $cantidad = (int) ($row['total'] ?? 0);
            }
        } catch (\Throwable $e) {
            registrar_log('Stock::contarProductosAsociados', $e->getMessage());
        }

        return $cantidad;
    }

    public function estaAsociadoAProductos(int $idStock): bool
    {
        $rel = false;

        try {
            $statement = $this->pdo->prepare('SELECT id FROM productos WHERE id_stock = ? LIMIT 1');
            $statement->execute([$idStock]);
            $row = $statement->fetch();

            if (is_array($row)) {
                $rel = true;
            }
        } catch (\Throwable $e) {
            registrar_log('Stock::estaAsociadoAProductos', $e->getMessage());
        }

        return $rel;
    }

    public function eliminar(int $id): bool
    {
        $ok = false;

        try {
            $statement = $this->pdo->prepare('DELETE FROM stock WHERE id = ?');
            $ok = $statement->execute([$id]);
        } catch (\Throwable $e) {
            registrar_log('Stock::eliminar', $e->getMessage());
            $ok = false;
        }

        return $ok;
    }

    public function recalcularPreciosProductosPorStock(int $idStock): bool
    {
        $resultado = false;

        try {
            $statement = $this->pdo->prepare('SELECT precio_costo FROM stock WHERE id = ? LIMIT 1');
            $statement->execute([$idStock]);
            $row = $statement->fetch();

            if (is_array($row) && isset($row['precio_costo'])) {
                $precioCosto = (float) $row['precio_costo'];
                $statement = $this->pdo->prepare(
                    'UPDATE productos SET precio_costo = ? WHERE id_stock = ?'
                );
                $resultado = $statement->execute([$precioCosto, $idStock]);
            }
        } catch (\Throwable $e) {
            registrar_log('Stock::recalcularPreciosProductosPorStock', $e->getMessage());
            $resultado = false;
        }

        return $resultado;
    }

    public function recalcularCostosPorCotizacion(): int
    {
        $actualizados = 0;

        try {
            $statement = $this->pdo->query('SELECT id, costo_origen FROM stock WHERE moneda_costo = \'USD\'');
            $rows = $statement->fetchAll();

            if (is_array($rows)) {
                $cotizacion = max(0.0001, parsear_numero_form(config('productos_cotizacion_dolar', '1'), 1));
                $update = $this->pdo->prepare('UPDATE stock SET precio_costo = costo_origen * ? WHERE id = ?');

                foreach ($rows as $row) {
                    $id = (int) ($row['id'] ?? 0);
                    if ($id <= 0) {
                        continue;
                    }

                    $update->execute([$cotizacion, $id]);
                    $actualizados += $update->rowCount();
                }
            }
        } catch (\Throwable $e) {
            registrar_log('Stock::recalcularCostosPorCotizacion', $e->getMessage());
            $actualizados = 0;
        }

        return $actualizados;
    }

    public function alertasStockBajo(int $idUsuario = 0, bool $mostrarLeidas = true, string $filtro = 'bajo'): array
    {
        $lista = [];

        try {
            $statement = $this->pdo->prepare(
                'SELECT p.id AS id_producto, p.nombre AS producto, p.activo AS producto_activo,
                       s.id AS id_stock, s.nombre AS stock_nombre, s.unidad, s.cantidad, s.stock_minimo,
                       s.tipo_stock, s.activo AS stock_activo, COALESCE(um.decimales, 3) AS unidad_decimales,
                       COALESCE(mov.ultimo_movimiento, s.creado_en) AS ultimo_movimiento,
                       l.fecha_lectura, l.usuario, l.cantidad_leida, l.stock_minimo_leido,
                       CASE
                         WHEN l.id IS NULL THEN 1
                         WHEN s.cantidad < l.cantidad_leida - 0.000001 THEN 1
                         WHEN s.stock_minimo > l.stock_minimo_leido + 0.000001 THEN 1
                         ELSE 0
                       END AS pendiente
                FROM productos p
                INNER JOIN stock s ON s.id = p.id_stock
                LEFT JOIN unidades_medida um ON um.abreviatura COLLATE utf8mb4_unicode_ci = s.unidad COLLATE utf8mb4_unicode_ci
                LEFT JOIN (
                    SELECT d.id_producto, MAX(v.fecha) AS ultimo_movimiento
                    FROM detalle_venta d
                    INNER JOIN ventas v ON v.id = d.id_venta
                    GROUP BY d.id_producto
                ) mov ON mov.id_producto = p.id
                LEFT JOIN stock_alertas_leidas l ON l.id_producto = p.id AND l.usuario = ?
                WHERE p.activo = 1
                  AND s.activo = 1
                  AND s.tipo_stock = \'propio\'
                  AND s.cantidad <= s.stock_minimo
                ORDER BY (s.cantidad <= 0) DESC, pendiente DESC, s.cantidad ASC, p.nombre ASC'
            );

            $statement->execute([$idUsuario]);
            $rows = $statement->fetchAll();

            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $cantidad = (float) ($row['cantidad'] ?? 0);
                    $pendiente = (int) ($row['pendiente'] ?? 0) === 1;

                    if (!$mostrarLeidas && !$pendiente) {
                        continue;
                    }

                    if ($filtro === 'criticos' && $cantidad > 0.000001) {
                        continue;
                    }

                    $lista[] = $row;
                }
            }
        } catch (\Throwable $e) {
            registrar_log('Stock::alertasStockBajo', $e->getMessage());
        }

        return $lista;
    }

    public function resumenAlertasStockBajo(int $idUsuario = 0): array
    {
        $resumen = ['total' => 0, 'pendientes' => 0, 'leidas' => 0];

        try {
            $statement = $this->pdo->prepare(
                'SELECT COUNT(*) AS total,
                       SUM(CASE
                         WHEN l.id IS NULL THEN 1
                         WHEN s.cantidad < l.cantidad_leida - 0.000001 THEN 1
                         WHEN s.stock_minimo > l.stock_minimo_leido + 0.000001 THEN 1
                         ELSE 0
                       END) AS pendientes
                FROM productos p
                INNER JOIN stock s ON s.id = p.id_stock
                LEFT JOIN stock_alertas_leidas l ON l.id_producto = p.id AND l.usuario = ?
                WHERE p.activo = 1
                  AND s.activo = 1
                  AND s.tipo_stock = \'propio\'
                  AND s.cantidad <= s.stock_minimo'
            );

            $statement->execute([$idUsuario]);
            $row = $statement->fetch();

            if (is_array($row)) {
                $total = (int) ($row['total'] ?? 0);
                $pendientes = (int) ($row['pendientes'] ?? 0);
                $resumen = ['total' => $total, 'pendientes' => $pendientes, 'leidas' => max(0, $total - $pendientes)];
            }
        } catch (\Throwable $e) {
            registrar_log('Stock::resumenAlertasStockBajo', $e->getMessage());
        }

        return $resumen;
    }

    public function marcarAlertaLeida(int $idProducto, int $idUsuario): bool
    {
        $ok = false;

        try {
            $statement = $this->pdo->prepare(
                'SELECT p.id, s.cantidad, s.stock_minimo
                 FROM productos p
                 INNER JOIN stock s ON s.id = p.id_stock
                 WHERE p.id = ? AND p.activo = 1 AND s.activo = 1 AND s.tipo_stock = \'propio\' AND s.cantidad <= s.stock_minimo
                 LIMIT 1'
            );

            $statement->execute([$idProducto]);
            $row = $statement->fetch();

            if (!is_array($row)) {
                return false;
            }

            $insert = $this->pdo->prepare(
                'INSERT INTO stock_alertas_leidas (id_producto, fecha_lectura, usuario, cantidad_leida, stock_minimo_leido)
                 VALUES (?, NOW(), ?, ?, ?)
                 ON DUPLICATE KEY UPDATE fecha_lectura = VALUES(fecha_lectura), cantidad_leida = VALUES(cantidad_leida), stock_minimo_leido = VALUES(stock_minimo_leido)'
            );

            $ok = $insert->execute([
                $idProducto,
                max(0, $idUsuario),
                (float) ($row['cantidad'] ?? 0),
                (float) ($row['stock_minimo'] ?? 0),
            ]);
        } catch (\Throwable $e) {
            registrar_log('Stock::marcarAlertaLeida', $e->getMessage());
            $ok = false;
        }

        return $ok;
    }

    public function listarFaltantes(bool $soloMinimo = true): array
    {
        $lista = [];

        try {
            $where = $soloMinimo ? 'WHERE s.activo = 1 AND s.stock_minimo > 0 AND s.cantidad <= s.stock_minimo' : 'WHERE s.activo = 1';
            $statement = $this->pdo->prepare(
                "SELECT s.id, s.nombre, s.unidad, s.tipo_stock, s.cantidad, s.stock_minimo, s.stock_maximo, s.precio_costo,
                               COALESCE(um.decimales, 3) AS unidad_decimales,
                               CASE
                                 WHEN s.stock_maximo > 0 AND s.stock_maximo > s.cantidad THEN s.stock_maximo - s.cantidad
                                 WHEN s.stock_minimo > 0 AND s.stock_minimo > s.cantidad THEN s.stock_minimo - s.cantidad
                                 ELSE 0
                               END AS cantidad_sugerida
                        FROM stock s
                        LEFT JOIN unidades_medida um ON um.abreviatura COLLATE utf8mb4_unicode_ci = s.unidad COLLATE utf8mb4_unicode_ci
                        $where
                        ORDER BY (s.cantidad <= s.stock_minimo AND s.stock_minimo > 0) DESC, s.nombre ASC"
            );

            $statement->execute();
            $rows = $statement->fetchAll();

            if (is_array($rows)) {
                $lista = $rows;
            }
        } catch (\Throwable $e) {
            registrar_log('Stock::listarFaltantes', $e->getMessage());
        }

        return $lista;
    }

    public function listarGeneralesActivos(): array
    {
        $stocks = [];
        $statement = $this->pdo->prepare(
            "SELECT s.id,
                    s.nombre,
                    s.unidad,
                    s.tipo_stock,
                    s.cantidad,
                    s.stock_minimo,
                    s.stock_maximo,
                    s.precio_costo,
                    s.moneda_costo,
                    s.costo_origen,
                    s.activo,
                    s.creado_en,
                    COALESCE(um.decimales, 3) AS unidad_decimales
             FROM stock s
             LEFT JOIN unidades_medida um ON um.abreviatura COLLATE utf8mb4_unicode_ci = s.unidad COLLATE utf8mb4_unicode_ci
             WHERE s.activo = 1 AND s.tipo_stock = 'general'
             ORDER BY s.nombre ASC, s.id ASC"
        );

        $statement->execute();
        $filas = $statement->fetchAll();

        foreach ($filas as $fila) {
            $stocks[] = $this->mapearStock($fila);
        }

        return $stocks;
    }

    public function obtenerCotizacionDolar(): float
    {
        return max(0.0001, parsear_numero_form(config('productos_cotizacion_dolar', '1'), 1));
    }

    public function obtenerCostoStock(int $id): float
    {
        $costo = 0.0;

        try {
            $statement = $this->pdo->prepare('SELECT precio_costo FROM stock WHERE id = ? LIMIT 1');
            $statement->execute([$id]);
            $row = $statement->fetch();

            if (is_array($row)) {
                $costo = (float) ($row['precio_costo'] ?? 0);
            }
        } catch (\Throwable $e) {
            registrar_log('Stock::obtenerCostoStock', $e->getMessage());
        }

        return $costo;
    }

    public function obtenerStockActivo(): array
    {
        $stocks = [];
        $statement = $this->pdo->prepare(
            "SELECT s.id,
                    s.nombre,
                    s.unidad,
                    s.tipo_stock,
                    s.cantidad,
                    s.stock_minimo,
                    s.stock_maximo,
                    s.precio_costo,
                    s.moneda_costo,
                    s.costo_origen,
                    s.activo,
                    s.creado_en,
                    COALESCE(um.decimales, 3) AS unidad_decimales
             FROM stock s
             LEFT JOIN unidades_medida um ON um.abreviatura COLLATE utf8mb4_unicode_ci = s.unidad COLLATE utf8mb4_unicode_ci
             WHERE s.activo = 1
             ORDER BY s.nombre ASC, s.id ASC"
        );

        $statement->execute();
        $filas = $statement->fetchAll();

        foreach ($filas as $fila) {
            $stocks[] = $this->mapearStock($fila);
        }

        return $stocks;
    }

    public function obtenerStockPorProducto(int $idProducto): ?Stock
    {
        $stock = null;

        try {
            $statement = $this->pdo->prepare(
                'SELECT s.id,
                        s.nombre,
                        s.unidad,
                        s.tipo_stock,
                        s.cantidad,
                        s.stock_minimo,
                        s.stock_maximo,
                        s.precio_costo,
                        s.moneda_costo,
                        s.costo_origen,
                        s.activo,
                        s.creado_en,
                        COALESCE(um.decimales, 3) AS unidad_decimales
                 FROM stock s
                 INNER JOIN productos p ON p.id_stock = s.id
                 LEFT JOIN unidades_medida um ON um.abreviatura COLLATE utf8mb4_unicode_ci = s.unidad COLLATE utf8mb4_unicode_ci
                 WHERE p.id = ?
                 LIMIT 1'
            );

            $statement->execute([$idProducto]);
            $fila = $statement->fetch();

            if (is_array($fila)) {
                $stock = $this->mapearStock($fila);
            }
        } catch (\Throwable $e) {
            registrar_log('Stock::obtenerStockPorProducto', $e->getMessage());
        }

        return $stock;
    }

    public function inicializarEsquemaStock(): void
    {
        if (!$this->esquemaStockInicializado) {
            try {
                $statement = $this->pdo->prepare('SHOW COLUMNS FROM stock LIKE ?');
                $statement->execute(['stock_minimo']);
                if (!$statement->fetch()) {
                    $this->pdo->exec('ALTER TABLE stock ADD COLUMN stock_minimo DECIMAL(14,3) NOT NULL DEFAULT 0 AFTER cantidad');
                }

                $statement = $this->pdo->prepare('SHOW COLUMNS FROM stock LIKE ?');
                $statement->execute(['stock_maximo']);
                if (!$statement->fetch()) {
                    $this->pdo->exec('ALTER TABLE stock ADD COLUMN stock_maximo DECIMAL(14,3) NOT NULL DEFAULT 0 AFTER stock_minimo');
                }

                $statement = $this->pdo->prepare('SHOW COLUMNS FROM stock LIKE ?');
                $statement->execute(['tipo_stock']);
                if (!$statement->fetch()) {
                    $this->pdo->exec("ALTER TABLE stock ADD COLUMN tipo_stock VARCHAR(20) NOT NULL DEFAULT 'general' AFTER unidad");
                    $this->pdo->exec("UPDATE stock s
                            INNER JOIN productos p ON p.id_stock = s.id
                            INNER JOIN (
                                SELECT id_stock, COUNT(*) AS total
                                FROM productos
                                WHERE id_stock IS NOT NULL
                                GROUP BY id_stock
                            ) c ON c.id_stock = s.id
                            SET s.tipo_stock = 'propio'
                            WHERE c.total = 1 AND LOWER(TRIM(s.nombre)) = LOWER(TRIM(p.nombre))");
                }

                $statement = $this->pdo->prepare('SHOW COLUMNS FROM stock LIKE ?');
                $statement->execute(['moneda_costo']);
                if (!$statement->fetch()) {
                    $this->pdo->exec("ALTER TABLE stock ADD COLUMN moneda_costo VARCHAR(3) NOT NULL DEFAULT 'ARS' AFTER precio_costo");
                }

                $statement = $this->pdo->prepare('SHOW COLUMNS FROM stock LIKE ?');
                $statement->execute(['costo_origen']);
                if (!$statement->fetch()) {
                    $this->pdo->exec('ALTER TABLE stock ADD COLUMN costo_origen DECIMAL(14,4) NOT NULL DEFAULT 0 AFTER moneda_costo');
                    $this->pdo->exec('UPDATE stock SET costo_origen = precio_costo');
                }

                $this->asegurarIndice('stock', 'idx_stock_alerta_menu', 'ALTER TABLE stock ADD INDEX idx_stock_alerta_menu (activo, tipo_stock, cantidad, stock_minimo)');
                $this->asegurarIndice('productos', 'idx_productos_stock_activo', 'ALTER TABLE productos ADD INDEX idx_productos_stock_activo (id_stock, activo)');
                $this->asegurarIndice('detalle_venta', 'idx_detalle_producto_venta', 'ALTER TABLE detalle_venta ADD INDEX idx_detalle_producto_venta (id_producto, id_venta)');
                $this->esquemaStockInicializado = true;
            } catch (\Throwable $e) {
                registrar_log('Stock::asegurar_columnas_minmax', $e->getMessage());
            }
        }
    }

    public function inicializarAlertasStock(): void
    {
        if (!$this->alertasStockInicializadas) {
            try {
                $this->pdo->exec('CREATE TABLE IF NOT EXISTS stock_alertas_leidas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_producto INT NOT NULL,
                fecha_lectura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                usuario INT NOT NULL DEFAULT 0,
                cantidad_leida DECIMAL(14,3) NOT NULL DEFAULT 0,
                stock_minimo_leido DECIMAL(14,3) NOT NULL DEFAULT 0,
                UNIQUE KEY uq_stock_alerta_producto_usuario (id_producto, usuario),
                KEY idx_stock_alertas_producto (id_producto),
                KEY idx_stock_alertas_usuario (usuario)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

                $statement = $this->pdo->prepare('SHOW COLUMNS FROM stock_alertas_leidas LIKE ?');
                $statement->execute(['cantidad_leida']);
                if (!$statement->fetch()) {
                    $this->pdo->exec('ALTER TABLE stock_alertas_leidas ADD COLUMN cantidad_leida DECIMAL(14,3) NOT NULL DEFAULT 0 AFTER usuario');
                }

                $statement = $this->pdo->prepare('SHOW COLUMNS FROM stock_alertas_leidas LIKE ?');
                $statement->execute(['stock_minimo_leido']);
                if (!$statement->fetch()) {
                    $this->pdo->exec('ALTER TABLE stock_alertas_leidas ADD COLUMN stock_minimo_leido DECIMAL(14,3) NOT NULL DEFAULT 0 AFTER cantidad_leida');
                }

                $this->alertasStockInicializadas = true;
            } catch (\Throwable $e) {
                registrar_log('Stock::asegurar_tabla_alertas_leidas', $e->getMessage());
            }
        }
    }

    private function asegurarIndice(string $tabla, string $indice, string $sql): void
    {
        try {
            $statement = $this->pdo->prepare("SHOW INDEX FROM `{$tabla}` WHERE Key_name = ?");
            $statement->execute([$indice]);
            if (!$statement->fetch()) {
                $this->pdo->exec($sql);
            }
        } catch (\Throwable $e) {
            registrar_log('Stock::asegurar_indice', $tabla . '.' . $indice . ' ' . $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $fila
     */
    private function mapearStock(array $fila): Stock
    {
        return new Stock(
            (int) $fila['id'],
            (string) $fila['nombre'],
            (string) $fila['unidad'],
            (string) $fila['tipo_stock'],
            (float) $fila['cantidad'],
            (float) $fila['stock_minimo'],
            (float) $fila['stock_maximo'],
            (float) $fila['precio_costo'],
            (string) $fila['moneda_costo'],
            (float) $fila['costo_origen'],
            (int) $fila['activo'] === 1,
            (int) $fila['unidad_decimales'],
            isset($fila['creado_en']) ? (string) $fila['creado_en'] : null
        );
    }
}
