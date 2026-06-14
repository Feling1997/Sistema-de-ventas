<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Ventas\NuevaVenta;

use PDO;
use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\ClienteVentaRepository;

final class MySQLClienteVentaRepository implements ClienteVentaRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listarParaVenta(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, nombre, dni, tipo_documento, condicion_iva, email, id_lista_precio
             FROM clientes
             ORDER BY (id=1) DESC, nombre ASC'
        );

        $statement->execute();
        $clientes = $statement->fetchAll(PDO::FETCH_ASSOC);

        return is_array($clientes) ? $clientes : [];
    }
}
