<?php

declare(strict_types=1);

namespace Ventas\Importacion\Infrastructure;

use PDO;
use Ventas\Importacion\Domain\Repositorios\ImportacionProductosRepository;

final class MySQLImportacionProductosRepository implements ImportacionProductosRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function iniciarTransaccion(): void
    {
        $this->pdo->beginTransaction();
    }

    public function confirmarTransaccion(): void
    {
        $this->pdo->commit();
    }

    public function revertirTransaccionSiActiva(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function actualizarProducto(int $idProducto, string $nombre, string $codigo): void
    {
        $statement = $this->pdo->prepare("UPDATE productos SET nombre = ?, cod_barras = ? WHERE id = ?");
        $statement->execute([$nombre, $codigo, $idProducto]);
    }

    public function crearProducto(string $nombre, string $codigo, float $precioFinal): int
    {
        $statement = $this->pdo->prepare("INSERT INTO productos (nombre, cod_barras, id_stock, id_asociado, factor_conversion, ganancia, precio_final, activo) VALUES (?, ?, NULL, NULL, 1, 0, ?, 1)");
        $statement->execute([$nombre, $codigo, $precioFinal]);
        $resultado = (int) $this->pdo->lastInsertId();

        return $resultado;
    }
}
