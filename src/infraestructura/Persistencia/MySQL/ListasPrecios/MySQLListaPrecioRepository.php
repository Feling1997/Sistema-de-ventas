<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Persistencia\MySQL\ListasPrecios;

use PDO;
use Ventas\Dominio\ListasPrecios\Entidades\ListaPrecio;
use Ventas\Dominio\ListasPrecios\Repositorios\ListaPrecioRepository;

final class MySQLListaPrecioRepository implements ListaPrecioRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listar(): array
    {
        $listasPrecios = [];
        $statement = $this->pdo->prepare(
            'SELECT id, nombre, activo, creado_en
             FROM listas_precios
             WHERE activo = 1
             ORDER BY nombre ASC, id ASC'
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

            if ($idPublico === 0 && ($nombre === 'publico' || $nombre === 'público')) {
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
}
