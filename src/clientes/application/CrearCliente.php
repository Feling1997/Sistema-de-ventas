<?php

declare(strict_types=1);

namespace Ventas\Clientes\Application;

use Ventas\Clientes\Domain\Entidades\Cliente;
use Ventas\Clientes\Domain\Repositorios\ClienteRepository;

final class CrearCliente
{
    public function __construct(private readonly ClienteRepository $clienteRepository)
    {
    }

    public function ejecutar(Cliente $cliente): Cliente
    {
        return $this->clienteRepository->guardar($cliente);
    }
}
