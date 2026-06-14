<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Persistencia\MySQL\Clientes;

use PDO;
use RuntimeException;
use Ventas\Dominio\Clientes\Entidades\Cliente;
use Ventas\Dominio\Clientes\Repositorios\ClienteRepository;

final class MySQLClienteRepository implements ClienteRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listar(): array
    {
        $clientes = [];
        $statement = $this->pdo->prepare(
            'SELECT c.id, c.nombre, c.dni, c.tipo_documento, c.condicion_iva, c.email,
                    c.id_lista_precio, lp.nombre AS lista_precio_nombre, c.telefono,
                    c.direccion, c.creado_en
             FROM clientes c
             LEFT JOIN listas_precios lp ON lp.id = c.id_lista_precio
             ORDER BY c.nombre ASC, c.id ASC'
        );

        $statement->execute();
        $filas = $statement->fetchAll();

        foreach ($filas as $fila) {
            $clientes[] = $this->mapearCliente($fila);
        }

        return $clientes;
    }

    public function buscarPorId(int $id): ?Cliente
    {
        $cliente = null;
        $statement = $this->pdo->prepare(
            'SELECT c.id, c.nombre, c.dni, c.tipo_documento, c.condicion_iva, c.email,
                    c.id_lista_precio, lp.nombre AS lista_precio_nombre, c.telefono,
                    c.direccion, c.creado_en
             FROM clientes c
             LEFT JOIN listas_precios lp ON lp.id = c.id_lista_precio
             WHERE c.id = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);
        $fila = $statement->fetch();

        if (is_array($fila)) {
            $cliente = $this->mapearCliente($fila);
        }

        return $cliente;
    }

    public function existeDocumento(string $documento, ?int $exceptoId = null): bool
    {
        $existe = false;
        $documentoLimpio = trim($documento);
        $exceptoIdSeguro = $exceptoId ?? 0;

        if ($documentoLimpio !== '') {
            $statement = $this->pdo->prepare(
                'SELECT id FROM clientes WHERE dni = :documento AND id <> :excepto_id LIMIT 1'
            );
            $statement->execute([
                'documento' => $documentoLimpio,
                'excepto_id' => $exceptoIdSeguro,
            ]);

            $existe = is_array($statement->fetch());
        }

        return $existe;
    }

    public function guardar(Cliente $cliente): Cliente
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO clientes (nombre, dni, telefono, direccion, tipo_documento, condicion_iva, email, id_lista_precio)
             VALUES (:nombre, :documento, :telefono, :direccion, :tipo_documento, :condicion_iva, :email, :id_lista_precio)'
        );

        $statement->execute([
            'nombre' => $cliente->nombre(),
            'documento' => $cliente->documento(),
            'telefono' => $cliente->telefono(),
            'direccion' => $cliente->direccion(),
            'tipo_documento' => $cliente->tipoDocumento(),
            'condicion_iva' => $cliente->condicionIva(),
            'email' => $cliente->email(),
            'id_lista_precio' => $cliente->idListaPrecio(),
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $clienteGuardado = $this->buscarPorId($id);

        if ($clienteGuardado === null) {
            throw new RuntimeException('No se pudo recuperar el cliente guardado.');
        }

        return $clienteGuardado;
    }

    public function actualizar(Cliente $cliente): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE clientes
             SET nombre = :nombre,
                 dni = :documento,
                 telefono = :telefono,
                 direccion = :direccion,
                 tipo_documento = :tipo_documento,
                 condicion_iva = :condicion_iva,
                 email = :email,
                 id_lista_precio = :id_lista_precio
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $cliente->id(),
            'nombre' => $cliente->nombre(),
            'documento' => $cliente->documento(),
            'telefono' => $cliente->telefono(),
            'direccion' => $cliente->direccion(),
            'tipo_documento' => $cliente->tipoDocumento(),
            'condicion_iva' => $cliente->condicionIva(),
            'email' => $cliente->email(),
            'id_lista_precio' => $cliente->idListaPrecio(),
        ]);
    }

    public function eliminar(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM clientes WHERE id = :id');

        $statement->execute(['id' => $id]);
    }

    public function tieneVentasAsociadas(int $id): bool
    {
        $tieneVentas = false;
        $statement = $this->pdo->prepare('SELECT id FROM ventas WHERE id_cliente = :id LIMIT 1');

        $statement->execute(['id' => $id]);
        $tieneVentas = is_array($statement->fetch());

        return $tieneVentas;
    }

    /**
     * @param array<string, mixed> $fila
     */
    private function mapearCliente(array $fila): Cliente
    {
        return new Cliente(
            (int) $fila['id'],
            (string) $fila['nombre'],
            isset($fila['dni']) ? (string) $fila['dni'] : null,
            (string) ($fila['tipo_documento'] ?? 'DNI'),
            (string) ($fila['condicion_iva'] ?? 'Consumidor Final'),
            isset($fila['email']) ? (string) $fila['email'] : null,
            isset($fila['telefono']) ? (string) $fila['telefono'] : null,
            isset($fila['direccion']) ? (string) $fila['direccion'] : null,
            isset($fila['id_lista_precio']) ? (int) $fila['id_lista_precio'] : null,
            isset($fila['lista_precio_nombre']) ? (string) $fila['lista_precio_nombre'] : null,
            isset($fila['creado_en']) ? (string) $fila['creado_en'] : null
        );
    }
}
