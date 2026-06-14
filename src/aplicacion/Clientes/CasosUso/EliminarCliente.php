<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Clientes\CasosUso;

use Ventas\Dominio\Clientes\Excepciones\ClienteConVentasException;
use Ventas\Dominio\Clientes\Excepciones\ClienteNoEncontradoException;
use Ventas\Dominio\Clientes\Excepciones\ClienteProtegidoException;
use Ventas\Dominio\Clientes\Repositorios\ClienteRepository;

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
