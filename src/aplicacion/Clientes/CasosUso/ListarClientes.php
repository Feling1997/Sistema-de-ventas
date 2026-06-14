<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Clientes\CasosUso;

use Ventas\Dominio\Clientes\Repositorios\ClienteRepository;

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
