<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Presupuestos\CasosUso;

use Ventas\Dominio\Presupuestos\Entidades\DetallePresupuesto;
use Ventas\Dominio\Presupuestos\Entidades\Presupuesto;
use Ventas\Dominio\Presupuestos\Repositorios\ComprobantePresupuestoRepository;
use Ventas\Dominio\Presupuestos\Repositorios\PresupuestoRepository;

final class RenderizarTicketPresupuesto
{
    public function __construct(
        private readonly PresupuestoRepository $presupuestoRepository,
        private readonly ComprobantePresupuestoRepository $comprobantePresupuestoRepository
    ) {
    }

    public function ejecutar(int $idPresupuesto, bool $autoPrint): array
    {
        $resultado = ['ok' => false, 'html' => '', 'error' => 'Presupuesto invalido.'];
        $presupuesto = $this->presupuestoRepository->buscarPorId($idPresupuesto);

        if ($presupuesto !== null) {
            $items = $this->presupuestoRepository->obtenerDetalle($idPresupuesto);
            $resultado = [
                'ok' => true,
                'html' => $this->comprobantePresupuestoRepository->renderizarTicket($this->presupuestoComoArray($presupuesto), $this->itemsComoArray($items), false, $autoPrint),
                'error' => '',
            ];
        }

        return $resultado;
    }

    private function presupuestoComoArray(Presupuesto $presupuesto): array
    {
        $datos = [
            'id' => $presupuesto->id(),
            'fecha' => $presupuesto->fecha(),
            'total' => $presupuesto->total(),
            'cliente_nombre' => $presupuesto->clienteNombre(),
            'usuario_nombre' => $presupuesto->usuarioNombre(),
        ];

        return $datos;
    }

    /**
     * @param array<int, DetallePresupuesto> $items
     */
    private function itemsComoArray(array $items): array
    {
        $datos = [];

        foreach ($items as $item) {
            $datos[] = [
                'id' => $item->id(),
                'id_presupuesto' => $item->idPresupuesto(),
                'id_producto' => $item->idProducto(),
                'producto_nombre' => $item->productoNombre(),
                'cantidad' => $item->cantidad(),
                'precio_unit' => $item->precioUnit(),
                'descuento' => $item->descuento(),
                'subtotal' => $item->subtotal(),
            ];
        }

        return $datos;
    }
}
