<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backups\BackupsController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Contactos\ContactosController;
use App\Http\Controllers\CuentasCorrientes\CuentasCorrientesController;
use App\Http\Controllers\Configuracion\ConfiguracionController;
use App\Http\Controllers\Exportaciones\ExportacionesController;
use App\Http\Controllers\Instalacion\InstalacionController;
use App\Http\Controllers\Presupuestos\PresupuestosController;
use App\Http\Controllers\Reparaciones\ReparacionesController;
use App\Http\Controllers\Sistema\SistemaController;
use App\Http\Controllers\Productos\ProductosController;
use App\Http\Controllers\Stock\StockController;
use App\Http\Controllers\Usuarios\UsuariosController;
use App\Http\Controllers\Ventas\NuevaVentaController;
use App\Http\Middleware\VerificarAutenticacionSistema;
use App\Http\Middleware\VerificarPermisoSistema as VerificarPermisoSistemaMiddleware;
use Ventas\Backups\Infrastructure\RegistroBackups;
use Ventas\Clientes\Infrastructure\RegistroClientes;
use Ventas\Configuracion\Infrastructure\RegistroConfiguracion;
use Ventas\CuentasCorrientes\Infrastructure\RegistroCuentasCorrientes;
use Ventas\Core\Infrastructure\Container\Container;
use Ventas\Presupuestos\Infrastructure\RegistroPresupuestos;
use Ventas\Productos\Infrastructure\RegistroProductos;
use Ventas\Stock\Infrastructure\RegistroStock;
use Ventas\Usuarios\Infrastructure\RegistroUsuarios;
use Ventas\Ventas\Infrastructure\RegistroVentas;

Route::get('/', function () {
    return redirect('/reparaciones');
});

Route::get('/estado', function () {
    return response()->json([
        'laravel' => 'ok',
    ]);
});

Route::get('/estado-modulos', function (Container $container) {
    return response()->json([
        'laravel' => 'ok',
        'container' => $container instanceof Container ? 'ok' : 'error',
        'usuarios' => class_exists(RegistroUsuarios::class),
        'clientes' => class_exists(RegistroClientes::class),
        'productos' => class_exists(RegistroProductos::class),
        'stock' => class_exists(RegistroStock::class),
        'ventas' => class_exists(RegistroVentas::class),
        'configuracion' => class_exists(RegistroConfiguracion::class),
        'backups' => class_exists(RegistroBackups::class),
        'presupuestos' => class_exists(RegistroPresupuestos::class),
        'cuentascorrientes' => class_exists(RegistroCuentasCorrientes::class),
    ]);
});

Route::get('/instalacion', [InstalacionController::class, 'index'])->name('instalacion.index');
Route::post('/instalacion/preparar', [InstalacionController::class, 'preparar'])->name('instalacion.preparar');
Route::post('/instalacion/modo', [InstalacionController::class, 'modo'])->name('instalacion.modo');
Route::get('/sistema/diagnostico', [SistemaController::class, 'diagnostico'])->name('sistema.diagnostico');

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', LogoutController::class)->name('logout');
Route::get('/usuarios', [UsuariosController::class, 'index'])
    ->middleware([VerificarAutenticacionSistema::class, VerificarPermisoSistemaMiddleware::class . ':usuarios.ver'])
    ->name('usuarios.index');
Route::post('/usuarios', [UsuariosController::class, 'store'])
    ->middleware([VerificarAutenticacionSistema::class, VerificarPermisoSistemaMiddleware::class . ':usuarios.crear'])
    ->name('usuarios.store');
Route::put('/usuarios/{id}', [UsuariosController::class, 'update'])
    ->whereNumber('id')
    ->middleware([VerificarAutenticacionSistema::class, VerificarPermisoSistemaMiddleware::class . ':usuarios.editar'])
    ->name('usuarios.update');
Route::delete('/usuarios/{id}', [UsuariosController::class, 'destroy'])
    ->whereNumber('id')
    ->middleware([VerificarAutenticacionSistema::class, VerificarPermisoSistemaMiddleware::class . ':usuarios.eliminar'])
    ->name('usuarios.destroy');
Route::post('/usuarios/roles', [UsuariosController::class, 'storeRol'])
    ->middleware([VerificarAutenticacionSistema::class, VerificarPermisoSistemaMiddleware::class . ':usuarios.editar'])
    ->name('usuarios.roles.store');
Route::put('/usuarios/roles/{id}', [UsuariosController::class, 'updateRol'])
    ->whereNumber('id')
    ->middleware([VerificarAutenticacionSistema::class, VerificarPermisoSistemaMiddleware::class . ':usuarios.editar'])
    ->name('usuarios.roles.update');
Route::delete('/usuarios/roles/{id}', [UsuariosController::class, 'destroyRol'])
    ->whereNumber('id')
    ->middleware([VerificarAutenticacionSistema::class, VerificarPermisoSistemaMiddleware::class . ':usuarios.eliminar'])
    ->name('usuarios.roles.destroy');
Route::post('/usuarios/roles/{rol}/permisos/{permiso}/activar', [UsuariosController::class, 'activarPermiso'])
    ->whereNumber('rol')
    ->whereNumber('permiso')
    ->middleware([VerificarAutenticacionSistema::class, VerificarPermisoSistemaMiddleware::class . ':usuarios.editar'])
    ->name('usuarios.roles.permisos.activar');
Route::delete('/usuarios/roles/{rol}/permisos/{permiso}', [UsuariosController::class, 'quitarPermiso'])
    ->whereNumber('rol')
    ->whereNumber('permiso')
    ->middleware([VerificarAutenticacionSistema::class, VerificarPermisoSistemaMiddleware::class . ':usuarios.editar'])
    ->name('usuarios.roles.permisos.quitar');
Route::get('/contactos', [ContactosController::class, 'index'])->name('contactos.index');
Route::get('/clientes', [ContactosController::class, 'index'])->name('clientes.index');
Route::get('/contactos/buscar', [ContactosController::class, 'buscar'])->name('contactos.buscar');
Route::get('/contactos/autocompletar', [ContactosController::class, 'autocompletar'])->name('contactos.autocompletar');
Route::post('/contactos', [ContactosController::class, 'store'])->name('contactos.store');
Route::put('/contactos/{id}', [ContactosController::class, 'update'])->name('contactos.update');
Route::delete('/contactos/{id}', [ContactosController::class, 'destroy'])->name('contactos.destroy');
Route::get('/productos', [ProductosController::class, 'index'])->name('productos.index');
Route::get('/productos/buscar', [ProductosController::class, 'buscar'])->name('productos.buscar');
Route::get('/productos/autocompletar', [ProductosController::class, 'autocompletar'])->name('productos.autocompletar');
Route::post('/productos', [ProductosController::class, 'store'])->name('productos.store');
Route::get('/productos/{id}', [ProductosController::class, 'mostrar'])
    ->whereNumber('id')
    ->name('productos.mostrar');
Route::put('/productos/{id}', [ProductosController::class, 'update'])
    ->whereNumber('id')
    ->name('productos.update');
Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
Route::get('/stock/buscar', [StockController::class, 'buscar'])->name('stock.buscar');
Route::get('/stock/autocompletar', [StockController::class, 'autocompletar'])->name('stock.autocompletar');
Route::get('/stock/faltantes', [StockController::class, 'faltantes'])->name('stock.faltantes');
Route::get('/stock/alertas', [StockController::class, 'alertas'])->name('stock.alertas');
Route::post('/stock', [StockController::class, 'store'])->name('stock.store');
Route::get('/stock/{id}', [StockController::class, 'mostrar'])
    ->whereNumber('id')
    ->name('stock.mostrar');
Route::put('/stock/{id}', [StockController::class, 'update'])
    ->whereNumber('id')
    ->name('stock.update');
Route::patch('/stock/{id}/sumar', [StockController::class, 'sumar'])
    ->whereNumber('id')
    ->name('stock.sumar');
Route::delete('/stock/{id}', [StockController::class, 'destroy'])
    ->whereNumber('id')
    ->name('stock.destroy');
Route::get('/ventas/nueva', [NuevaVentaController::class, 'index'])->name('ventas.nueva');
Route::get('/ventas/productos', [NuevaVentaController::class, 'buscarProductos'])->name('ventas.productos');
Route::get('/ventas/clientes', [NuevaVentaController::class, 'buscarClientes'])->name('ventas.clientes');
Route::get('/ventas/carrito', [NuevaVentaController::class, 'carrito'])->name('ventas.carrito');
Route::post('/ventas/carrito', [NuevaVentaController::class, 'agregarItem'])->name('ventas.carrito.agregar');
Route::put('/ventas/carrito', [NuevaVentaController::class, 'actualizarItem'])->name('ventas.carrito.actualizar');
Route::delete('/ventas/carrito/{id}', [NuevaVentaController::class, 'quitarItem'])
    ->whereNumber('id')
    ->name('ventas.carrito.quitar');
Route::delete('/ventas/carrito', [NuevaVentaController::class, 'vaciar'])->name('ventas.carrito.vaciar');
Route::post('/ventas/confirmar', [NuevaVentaController::class, 'confirmar'])->name('ventas.confirmar');
Route::get('/cuentas-corrientes', [CuentasCorrientesController::class, 'index'])->name('cuentas-corrientes.index');
Route::get('/cuentas-corrientes/cliente', [CuentasCorrientesController::class, 'buscarCliente'])->name('cuentas-corrientes.cliente');
Route::get('/cuentas-corrientes/cuenta', [CuentasCorrientesController::class, 'buscarCuenta'])->name('cuentas-corrientes.cuenta');
Route::get('/cuentas-corrientes/saldo', [CuentasCorrientesController::class, 'saldo'])->name('cuentas-corrientes.saldo');
Route::get('/cuentas-corrientes/cuotas', [CuentasCorrientesController::class, 'cuotas'])->name('cuentas-corrientes.cuotas');
Route::get('/cuentas-corrientes/resumen', [CuentasCorrientesController::class, 'resumen'])->name('cuentas-corrientes.resumen');
Route::post('/cuentas-corrientes/pago', [CuentasCorrientesController::class, 'registrarPago'])->name('cuentas-corrientes.pago');
Route::get('/presupuestos', [PresupuestosController::class, 'index'])->name('presupuestos.index');
Route::get('/presupuestos/buscar', [PresupuestosController::class, 'buscar'])->name('presupuestos.buscar');
Route::get('/presupuestos/clientes', [PresupuestosController::class, 'clientes'])->name('presupuestos.clientes');
Route::get('/presupuestos/productos', [PresupuestosController::class, 'productos'])->name('presupuestos.productos');
Route::post('/presupuestos', [PresupuestosController::class, 'store'])->name('presupuestos.store');
Route::get('/presupuestos/{id}', [PresupuestosController::class, 'show'])
    ->whereNumber('id')
    ->name('presupuestos.show');
Route::put('/presupuestos/{id}', [PresupuestosController::class, 'update'])
    ->whereNumber('id')
    ->name('presupuestos.update');
Route::delete('/presupuestos/{id}', [PresupuestosController::class, 'destroy'])
    ->whereNumber('id')
    ->name('presupuestos.destroy');
Route::get('/presupuestos/{id}/pdf', [PresupuestosController::class, 'pdf'])
    ->whereNumber('id')
    ->name('presupuestos.pdf');
Route::get('/presupuestos/{id}/ticket', [PresupuestosController::class, 'ticket'])
    ->whereNumber('id')
    ->name('presupuestos.ticket');
Route::get('/exportaciones', [ExportacionesController::class, 'index'])->name('exportaciones.index');
Route::post('/exportaciones/productos', [ExportacionesController::class, 'exportarProductos'])->name('exportaciones.productos');
Route::post('/exportaciones/stock', [ExportacionesController::class, 'exportarStock'])->name('exportaciones.stock');
Route::post('/exportaciones/clientes', [ExportacionesController::class, 'exportarClientes'])->name('exportaciones.clientes');
Route::post('/exportaciones/ventas', [ExportacionesController::class, 'exportarVentas'])->name('exportaciones.ventas');
Route::post('/importaciones/productos', [ExportacionesController::class, 'importarProductos'])->name('importaciones.productos');
Route::post('/importaciones/stock', [ExportacionesController::class, 'importarStock'])->name('importaciones.stock');
Route::post('/importaciones/clientes', [ExportacionesController::class, 'importarClientes'])->name('importaciones.clientes');
Route::get('/backups', [BackupsController::class, 'index'])->name('backups.index');
Route::get('/backups/listar', [BackupsController::class, 'listarBackups'])->name('backups.listar');
Route::get('/backups/{id}', [BackupsController::class, 'descargar'])->name('backups.descargar');
Route::post('/backups', [BackupsController::class, 'crearBackup'])->name('backups.crear');
Route::post('/backups/backblaze', [BackupsController::class, 'subirBackblaze'])->name('backups.backblaze');
Route::delete('/backups/{id}', [BackupsController::class, 'eliminar'])->name('backups.eliminar');
Route::get('/jobs/{id}', [BackupsController::class, 'estadoJob'])->name('jobs.estado');
Route::get('/reparaciones', [ReparacionesController::class, 'index'])->name('reparaciones.index');
Route::get('/reparaciones/buscar', [ReparacionesController::class, 'buscar'])->name('reparaciones.buscar');
Route::get('/reparaciones/resumen', [ReparacionesController::class, 'resumen'])->name('reparaciones.resumen');
Route::get('/reparaciones/metricas', [ReparacionesController::class, 'metricas'])->name('reparaciones.metricas');
Route::get('/reparaciones/salud', [ReparacionesController::class, 'salud'])->name('reparaciones.salud');
Route::get('/reparaciones/configuracion', [ReparacionesController::class, 'configuracion'])->name('reparaciones.configuracion');
Route::post('/reparaciones/configuracion', [ReparacionesController::class, 'guardarConfiguracion'])->name('reparaciones.configuracion.guardar');
Route::get('/configuracion', [ConfiguracionController::class, 'index'])
    ->middleware(VerificarPermisoSistemaMiddleware::class . ':configuracion.ver')
    ->name('configuracion.index');
Route::post('/configuracion', [ConfiguracionController::class, 'guardar'])
    ->middleware(VerificarPermisoSistemaMiddleware::class . ':configuracion.editar')
    ->name('configuracion.guardar');
Route::get('/reparaciones/contactos', [ReparacionesController::class, 'contactos'])->name('reparaciones.contactos');
Route::get('/reparaciones/estados', [ReparacionesController::class, 'estados'])->name('reparaciones.estados');
Route::get('/equipos', static fn () => view('reparaciones.equipos'))->name('reparaciones.equipos.index');
Route::get('/reparaciones/equipos', [ReparacionesController::class, 'equipos'])->name('reparaciones.equipos');
Route::post('/reparaciones/equipos', [ReparacionesController::class, 'storeEquipo'])->name('reparaciones.equipos.store');
Route::put('/reparaciones/equipos/{id}', [ReparacionesController::class, 'updateEquipo'])->whereNumber('id')->name('reparaciones.equipos.update');
Route::get('/reparaciones/{id}/adjuntos', [ReparacionesController::class, 'adjuntos'])->whereNumber('id')->name('reparaciones.adjuntos');
Route::post('/reparaciones/{id}/adjuntos', [ReparacionesController::class, 'storeAdjunto'])->whereNumber('id')->name('reparaciones.adjuntos.store');
Route::delete('/reparaciones/adjuntos/{id}', [ReparacionesController::class, 'destroyAdjunto'])->whereNumber('id')->name('reparaciones.adjuntos.destroy');
Route::get('/reparaciones/{id}/ticket', [ReparacionesController::class, 'ticket'])->whereNumber('id')->name('reparaciones.ticket');
Route::post('/reparaciones', [ReparacionesController::class, 'store'])->name('reparaciones.store');
Route::get('/reparaciones/{id}', [ReparacionesController::class, 'mostrar'])
    ->whereNumber('id')
    ->name('reparaciones.mostrar');
Route::put('/reparaciones/{id}', [ReparacionesController::class, 'update'])
    ->whereNumber('id')
    ->name('reparaciones.update');
Route::delete('/reparaciones/{id}', [ReparacionesController::class, 'destroy'])
    ->whereNumber('id')
    ->name('reparaciones.destroy');
