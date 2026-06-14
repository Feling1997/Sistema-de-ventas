<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Persistencia\MySQL\Stock;

use PDO;
use Ventas\Dominio\Stock\Entidades\Stock;
use Ventas\Dominio\Stock\Repositorios\StockRepository;

final class MySQLStockRepository implements StockRepository
{
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
            $stocks[] = new Stock(
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
            $stock = new Stock(
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

        return $stock;
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
            $stocks[] = new Stock(
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

        return $stocks;
    }
}
