<?php

declare(strict_types=1);

namespace Ventas\Importacion\Infrastructure;

use PDO;
use Ventas\Importacion\Domain\Repositorios\ImportacionPreciosRepository;

final class MySQLImportacionPreciosRepository implements ImportacionPreciosRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function obtenerPrecioActual(int $idProducto, int $idLista): float
    {
        $statement = $this->pdo->prepare("SELECT precio FROM producto_precios WHERE id_producto = ? AND id_lista = ? LIMIT 1");
        $statement->execute([$idProducto, $idLista]);
        $actual = $statement->fetch(PDO::FETCH_ASSOC);
        $resultado = $actual ? (float) $actual["precio"] : 0.0;

        return $resultado;
    }

    public function guardarPrecio(int $idProducto, int $idLista, float $precio): void
    {
        $statement = $this->pdo->prepare("INSERT INTO producto_precios (id_producto, id_lista, porcentaje, precio) VALUES (?, ?, 0, ?) ON DUPLICATE KEY UPDATE precio = VALUES(precio)");
        $statement->execute([$idProducto, $idLista, $precio]);
    }
}
