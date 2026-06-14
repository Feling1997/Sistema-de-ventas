<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Persistencia\MySQL\Presupuestos;

use PDO;
use Ventas\Dominio\Presupuestos\Entidades\DetallePresupuesto;
use Ventas\Dominio\Presupuestos\Entidades\Presupuesto;
use Ventas\Dominio\Presupuestos\Repositorios\PresupuestoRepository;

final class MySQLPresupuestoRepository implements PresupuestoRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function buscarPorId(int $id): ?Presupuesto
    {
        $presupuesto = null;
        $statement = $this->pdo->prepare(
            'SELECT p.id,
                    p.fecha,
                    p.total,
                    c.nombre AS cliente_nombre,
                    u.usuario AS usuario_nombre
             FROM presupuestos p
             INNER JOIN clientes c ON c.id = p.id_cliente
             INNER JOIN usuarios u ON u.id = p.id_usuario
             WHERE p.id = ?
             LIMIT 1'
        );

        $statement->execute([$id]);
        $fila = $statement->fetch();

        if (is_array($fila)) {
            $presupuesto = new Presupuesto(
                (int) $fila['id'],
                (string) $fila['fecha'],
                (float) $fila['total'],
                (string) $fila['cliente_nombre'],
                (string) $fila['usuario_nombre']
            );
        }

        return $presupuesto;
    }

    public function obtenerDetalle(int $idPresupuesto): array
    {
        $detalles = [];
        $statement = $this->pdo->prepare(
            'SELECT *
             FROM detalle_presupuesto
             WHERE id_presupuesto = ?
             ORDER BY id ASC'
        );

        $statement->execute([$idPresupuesto]);
        $filas = $statement->fetchAll();

        foreach ($filas as $fila) {
            $detalles[] = new DetallePresupuesto(
                (int) $fila['id'],
                (int) $fila['id_presupuesto'],
                (int) $fila['id_producto'],
                (string) $fila['producto_nombre'],
                (float) $fila['cantidad'],
                (float) $fila['precio_unit'],
                (float) $fila['descuento'],
                (float) $fila['subtotal']
            );
        }

        return $detalles;
    }
}
