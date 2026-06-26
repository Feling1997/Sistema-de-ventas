<?php

declare(strict_types=1);

namespace Ventas\Importacion\Infrastructure;

use PDO;
use Ventas\Importacion\Domain\Repositorios\ImportacionHistorialRepository;

final class MySQLImportacionHistorialRepository implements ImportacionHistorialRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function guardarCambioPrecio(int $idProducto, int $idLista, float $precioAnterior, float $precioNuevo): void
    {
        $statement = $this->pdo->prepare("INSERT INTO historial_precios (id_producto, id_lista, precio_anterior, precio_nuevo, origen) VALUES (?, ?, ?, ?, 'importacion_excel')");
        $statement->execute([$idProducto, $idLista, $precioAnterior, $precioNuevo]);
    }
}
