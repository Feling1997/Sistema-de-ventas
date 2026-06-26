<?php

declare(strict_types=1);

namespace Ventas\Precios\Infrastructure;

use PDO;
use Ventas\Precios\Domain\Repositorios\PrecioRepository;

final class MySQLPrecioRepository implements PrecioRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function obtenerPrecioCostoStock(int $idStock): ?float
    {
        $resultado = null;
        $statement = $this->pdo->prepare("SELECT precio_costo FROM stock WHERE id = ? LIMIT 1");
        $statement->execute([$idStock]);
        $stock = $statement->fetch(PDO::FETCH_ASSOC);

        if ($stock) {
            $resultado = (float) $stock["precio_costo"];
        }

        return $resultado;
    }

    public function actualizarPreciosProductosPorStock(int $idStock, float $precioCosto): bool
    {
        $statement = $this->pdo->prepare("UPDATE productos SET precio_final = (? * factor_conversion) * (1 + (ganancia / 100)) WHERE id_stock = ?");
        $resultado = $statement->execute([$precioCosto, $idStock]);

        return $resultado;
    }

    public function recalcularListasPorStock(int $idStock, float $precioCosto): void
    {
        $this->pdo->prepare("UPDATE producto_precios pp
                           INNER JOIN productos p ON p.id = pp.id_producto
                           INNER JOIN listas_precios l ON l.id = pp.id_lista
                           SET pp.precio = CASE
                               WHEN LOWER(TRIM(l.nombre)) = 'costo' THEN ? * p.factor_conversion
                               ELSE (? * p.factor_conversion) * (1 + (pp.porcentaje / 100))
                           END
                           WHERE p.id_stock = ?")->execute([$precioCosto, $precioCosto, $idStock]);
    }

    public function obtenerStocksEnDolares(): array
    {
        $ids = $this->pdo->query("SELECT id FROM stock WHERE moneda_costo = 'USD'")->fetchAll(PDO::FETCH_COLUMN);
        $resultado = is_array($ids) ? $ids : [];

        return $resultado;
    }

    public function actualizarCostosPorCotizacion(float $cotizacion): void
    {
        $this->pdo->prepare("UPDATE stock SET precio_costo = costo_origen * ? WHERE moneda_costo = 'USD'")->execute([$cotizacion]);
    }
}
