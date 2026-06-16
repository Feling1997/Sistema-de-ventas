<?php

declare(strict_types=1);

namespace Ventas\Clientes\Application;

use Ventas\Clientes\Domain\Repositorios\ClienteRepository;

final class ListarClientes
{
    public function __construct(private readonly ClienteRepository $clienteRepository)
    {
    }

    public function ejecutar(): array
    {
        return $this->clienteRepository->listar();
    }
}
