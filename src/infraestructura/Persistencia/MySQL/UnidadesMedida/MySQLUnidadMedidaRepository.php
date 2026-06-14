<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Persistencia\MySQL\UnidadesMedida;

use PDO;
use Ventas\Dominio\UnidadesMedida\Entidades\UnidadMedida;
use Ventas\Dominio\UnidadesMedida\Repositorios\UnidadMedidaRepository;

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
}
