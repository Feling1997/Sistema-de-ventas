<?php

declare(strict_types=1);

namespace Ventas\Clientes\Application;

use Ventas\Clientes\Domain\Excepciones\ClienteConVentasException;
use Ventas\Clientes\Domain\Excepciones\ClienteNoEncontradoException;
use Ventas\Clientes\Domain\Excepciones\ClienteProtegidoException;
use Ventas\Clientes\Domain\Repositorios\ClienteRepository;

final class EliminarCliente
{
    public function __construct(private readonly ClienteRepository $clienteRepository)
    {
    }

    public function ejecutar(int $id): void
    {
        $cliente = $this->clienteRepository->buscarPorId($id);

        if ($cliente === null) {
            throw new ClienteNoEncontradoException('Cliente no encontrado.');
        }

        if (!$cliente->puedeEliminarse()) {
            throw new ClienteProtegidoException('Consumidor Final no se puede eliminar.');
        }

        if ($this->clienteRepository->tieneVentasAsociadas($id)) {
            throw new ClienteConVentasException('No se puede eliminar el cliente porque tiene ventas asociadas.');
        }

        $this->clienteRepository->eliminar($id);
    }
}
