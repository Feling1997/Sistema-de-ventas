<?php

declare(strict_types=1);

namespace Ventas\Ventas\Application\NuevaVenta;

use Ventas\ListasPrecios\Domain\Entidades\ListaPrecio;
use Ventas\ListasPrecios\Domain\Repositorios\ListaPrecioRepository;
use Ventas\Ventas\Domain\NuevaVenta\Repositorios\FormularioVentaRepository;

final class ObtenerFormularioVenta
{
    public function __construct(
        private readonly FormularioVentaRepository $formularioVentaRepository,
        private readonly ListaPrecioRepository $listaPrecioRepository
    ) {
    }

    public function ejecutar(): array
    {
        $datos = [
            'id_cliente' => 1,
            'buscar_cliente' => '',
            'id_producto' => '',
            'cantidad' => 1,
            'descuento' => 0,
            'precio_unit' => '',
            'tipo_comprobante' => 98,
            'buscar_producto' => '',
            'id_lista_precio' => $this->idListaPredeterminada(),
        ];

        $flash = $this->formularioVentaRepository->obtener();

        if ($flash !== []) {
            $datos = array_merge($datos, $flash);
        }

        return $datos;
    }

    private function idListaPredeterminada(): int
    {
        $id = 1;
        $encontrado = false;
        $listas = $this->listaPrecioRepository->listar();

        foreach ($listas as $lista) {
            if (!$encontrado && $this->esListaPublico($lista)) {
                $id = (int) $lista->id();
                $encontrado = true;
            }
        }

        foreach ($listas as $lista) {
            if (!$encontrado && !$this->esListaCosto($lista)) {
                $id = (int) $lista->id();
                $encontrado = true;
            }
        }

        if (!$encontrado && isset($listas[0])) {
            $id = (int) $listas[0]->id();
        }

        return $id;
    }

    private function esListaCosto(ListaPrecio $listaPrecio): bool
    {
        return strtolower(trim($listaPrecio->nombre())) === 'costo';
    }

    private function esListaPublico(ListaPrecio $listaPrecio): bool
    {
        $nombre = strtolower(trim($listaPrecio->nombre()));
        $esPublico = $nombre === 'publico' || $nombre === 'public';

        return $esPublico;
    }
}
