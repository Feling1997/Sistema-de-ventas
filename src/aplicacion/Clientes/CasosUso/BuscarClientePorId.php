<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Clientes\CasosUso;

use Ventas\Dominio\Clientes\Entidades\Cliente;
use Ventas\Dominio\Clientes\Repositorios\ClienteRepository;

final class BuscarClientePorId
{
    public function __construct(private readonly ClienteRepository $clienteRepository)
    {
    }

    public function ejecutar(int $id): ?Cliente
    {
        return $this->clienteRepository->buscarPorId($id);
    }
}
