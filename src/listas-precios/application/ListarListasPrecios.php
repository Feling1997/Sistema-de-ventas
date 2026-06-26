<?php

declare(strict_types=1);

namespace Ventas\ListasPrecios\Application;

use Ventas\ListasPrecios\Domain\Repositorios\ListaPrecioRepository;

final class ListarListasPrecios
{
    public function __construct(private readonly ListaPrecioRepository $listaPrecioRepository)
    {
    }

    public function ejecutar(bool $soloActivas = true, string $ordenSql = 'nombre ASC'): array
    {
        $resultado = $this->listaPrecioRepository->listar($soloActivas, $ordenSql);

        return $resultado;
    }
}
