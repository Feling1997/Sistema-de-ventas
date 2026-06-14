<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Ventas\NuevaVenta;

use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\UsuarioActualRepository;

final class ObtenerPanelVentas
{
    public function __construct(private readonly UsuarioActualRepository $usuarioActualRepository)
    {
    }

    public function ejecutar(): array
    {
        $usuario = $this->usuarioActualRepository->obtener();
        $rol = (string) ($usuario['rol'] ?? '');
        $modulos = [
            ['titulo' => 'Ventas', 'texto' => 'Ver historial, filtrar y revisar comprobantes.', 'icono' => 'bi-receipt-cutoff', 'clase' => 'modulo-ventas', 'url' => 'index.php?c=ventas&a=lista'],
            ['titulo' => 'Nueva venta', 'texto' => 'Cargar una venta rapida con cliente y productos.', 'icono' => 'bi-cart-plus-fill', 'clase' => 'modulo-nueva', 'url' => 'index.php?c=ventas&a=nueva'],
            ['titulo' => 'Clientes', 'texto' => 'Buscar, crear y editar clientes.', 'icono' => 'bi-people-fill', 'clase' => 'modulo-clientes', 'url' => 'index.php?c=clientes&a=index'],
            ['titulo' => 'Stock', 'texto' => 'Controlar cantidades, costos y movimientos base.', 'icono' => 'bi-box-seam-fill', 'clase' => 'modulo-stock', 'url' => 'index.php?c=stock&a=index'],
            ['titulo' => 'Productos', 'texto' => 'Administrar productos y su relacion con stock.', 'icono' => 'bi-bag-fill', 'clase' => 'modulo-productos', 'url' => 'index.php?c=productos&a=index'],
            ['titulo' => 'Exportaciones', 'texto' => 'Descargar stock, listas, pedidos y estadisticas.', 'icono' => 'bi-graph-up-arrow', 'clase' => 'modulo-exportaciones', 'url' => 'index.php?c=exportaciones&a=index'],
        ];

        if ($rol === 'ADMIN') {
            $modulos[] = [
                'titulo' => 'Usuarios',
                'texto' => 'Administrar accesos, roles y estado.',
                'icono' => 'bi-person-gear',
                'clase' => 'modulo-usuarios',
                'url' => 'index.php?c=usuarios&a=index',
            ];
        }

        $datos = [
            'modulos' => $modulos,
            'body_class' => 'bg-light page-ventas-panel',
        ];

        return $datos;
    }
}
