<?php

declare(strict_types=1);

namespace Ventas\Ventas\Application\NuevaVenta;

use Ventas\Ventas\Domain\NuevaVenta\Repositorios\ConfiguracionVentaRepository;
use Ventas\Ventas\Domain\NuevaVenta\Repositorios\UsuarioActualRepository;

final class ObtenerInicioVentas
{
    public function __construct(
        private readonly UsuarioActualRepository $usuarioActualRepository,
        private readonly ConfiguracionVentaRepository $configuracionVentaRepository
    ) {
    }

    public function ejecutar(): array
    {
        $usuario = $this->usuarioActualRepository->obtener();
        $rol = (string) ($usuario['rol'] ?? '');
        $configuracion = $this->configuracionVentaRepository->configuracionInicio();
        $modulos = $this->modulosBase('inicio');

        if ($rol === 'ADMIN') {
            $modulos[] = [
                'titulo' => 'Usuarios',
                'texto' => 'Administrar accesos, roles y estado.',
                'icono' => 'bi-person-gear',
                'clase' => 'modulo-usuarios',
                'url' => 'index.php?c=usuarios&a=index',
            ];
            $modulos[] = [
                'titulo' => 'Backup',
                'texto' => 'Copias completas a pendrive, carpeta, Drive o Backblaze.',
                'icono' => 'bi-database-fill-check',
                'clase' => 'modulo-backup',
                'url' => 'index.php?c=configuraciones&a=backup',
            ];
        }

        if ((string) ($configuracion['mostrar_reparaciones'] ?? '1') === '1') {
            $modulos[] = [
                'titulo' => 'Reparaciones',
                'texto' => 'Abrir Reparaciones Laravel desde Ventas.',
                'icono' => 'bi-tools',
                'clase' => 'modulo-reparaciones',
                'url' => $this->normalizarUrlReparaciones((string) ($configuracion['url_reparaciones'] ?? '')),
            ];
        }

        $datos = [
            'modulos' => $modulos,
            'body_class' => 'bg-light page-home',
        ];

        return $datos;
    }

    private function modulosBase(string $tipo): array
    {
        $exportacionesUrl = $tipo === 'panel' ? 'index.php?c=exportaciones&a=index' : 'index.php?c=exportaciones&a=inicio';
        $modulos = [
            ['titulo' => 'Ventas', 'texto' => 'Ver historial, filtrar y revisar comprobantes.', 'icono' => 'bi-receipt-cutoff', 'clase' => 'modulo-ventas', 'url' => 'index.php?c=ventas&a=lista'],
            ['titulo' => 'Nueva venta', 'texto' => 'Cargar una venta rapida con cliente y productos.', 'icono' => 'bi-cart-plus-fill', 'clase' => 'modulo-nueva', 'url' => 'index.php?c=ventas&a=nueva'],
            ['titulo' => 'Clientes', 'texto' => 'Buscar, crear y editar clientes.', 'icono' => 'bi-people-fill', 'clase' => 'modulo-clientes', 'url' => 'index.php?c=clientes&a=index'],
            ['titulo' => 'Stock', 'texto' => 'Controlar cantidades, costos y movimientos base.', 'icono' => 'bi-box-seam-fill', 'clase' => 'modulo-stock', 'url' => 'index.php?c=stock&a=index'],
            ['titulo' => 'Productos', 'texto' => 'Administrar productos y su relacion con stock.', 'icono' => 'bi-bag-fill', 'clase' => 'modulo-productos', 'url' => 'index.php?c=productos&a=index'],
            ['titulo' => 'Exportaciones', 'texto' => 'Descargar stock, listas, pedidos y estadisticas.', 'icono' => 'bi-graph-up-arrow', 'clase' => 'modulo-exportaciones', 'url' => $exportacionesUrl],
        ];

        return $modulos;
    }

    private function normalizarUrlReparaciones(string $url): string
    {
        $normalizada = trim($url);

        if ($normalizada === '') {
            $normalizada = '/Sistema-de-ventas/laravel/public/reparaciones';
        } elseif (str_contains($normalizada, 'index.php?c=reparaciones')) {
            $normalizada = '/Sistema-de-ventas/laravel/public/reparaciones';
        }

        return $normalizada;
    }
}
