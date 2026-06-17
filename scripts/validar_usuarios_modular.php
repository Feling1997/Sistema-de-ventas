<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Ventas\Infraestructura\Contenedor\Container;
use Ventas\Usuarios\Application\ActualizarUsuario;
use Ventas\Usuarios\Application\BuscarUsuarioPorId;
use Ventas\Usuarios\Application\CrearUsuario;
use Ventas\Usuarios\Application\EliminarUsuario;
use Ventas\Usuarios\Application\ListarUsuarios;
use Ventas\Usuarios\Application\VerificarPermisoModulo;
use Ventas\Usuarios\Domain\Repositorios\UsuarioRepository;
use Ventas\Usuarios\Infrastructure\RegistroUsuarios;

$ok = false;
$cantidad = 0;
$idBusqueda = 0;
$resultadoBusqueda = 'null';
$permisoVentas = 'NO';
$claseListar = '';
$claseBuscar = '';
$claseCrear = '';
$claseActualizar = '';
$claseEliminar = '';
$claseVerificar = '';
$claseRepositorio = '';
$claseRepositorioListar = '';
$claseRepositorioBuscar = '';
$claseEntidad = '';
$namespaceViejoUtilizado = false;
$namespaceModularUtilizado = false;
$mensaje = '';

try {
    $container = new Container();
    $pdo = new \PDO(
        'mysql:host=127.0.0.1;dbname=sistema_ventas;charset=utf8mb4',
        'root',
        '',
        [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]
    );

    $container->instance(\PDO::class, $pdo);
    RegistroUsuarios::registrar($container);

    $listarUsuarios = $container->get(ListarUsuarios::class);
    $buscarUsuarioPorId = $container->get(BuscarUsuarioPorId::class);
    $crearUsuario = $container->get(CrearUsuario::class);
    $actualizarUsuario = $container->get(ActualizarUsuario::class);
    $eliminarUsuario = $container->get(EliminarUsuario::class);
    $verificarPermisoModulo = $container->get(VerificarPermisoModulo::class);
    $repositorio = $container->get(UsuarioRepository::class);

    $usuarios = $listarUsuarios->ejecutar();

    foreach ($usuarios as $usuarioListado) {
        if ($idBusqueda === 0 && $usuarioListado->id() !== null && $usuarioListado->id() > 0) {
            $idBusqueda = $usuarioListado->id();
        }
    }

    $usuario = $idBusqueda > 0 ? $buscarUsuarioPorId->ejecutar($idBusqueda) : null;

    if ($usuario !== null) {
        $permisoVentas = $verificarPermisoModulo->ejecutar($usuario, 'ventas') ? 'SI' : 'NO';
    }

    $refListar = new \ReflectionClass($listarUsuarios);
    $propListar = $refListar->getProperty('usuarioRepository');
    $propListar->setAccessible(true);
    $repoListar = $propListar->getValue($listarUsuarios);

    $refBuscar = new \ReflectionClass($buscarUsuarioPorId);
    $propBuscar = $refBuscar->getProperty('usuarioRepository');
    $propBuscar->setAccessible(true);
    $repoBuscar = $propBuscar->getValue($buscarUsuarioPorId);

    $cantidad = count($usuarios);
    $resultadoBusqueda = $usuario === null ? 'null' : 'entidad';
    $claseListar = get_class($listarUsuarios);
    $claseBuscar = get_class($buscarUsuarioPorId);
    $claseCrear = get_class($crearUsuario);
    $claseActualizar = get_class($actualizarUsuario);
    $claseEliminar = get_class($eliminarUsuario);
    $claseVerificar = get_class($verificarPermisoModulo);
    $claseRepositorio = get_class($repositorio);
    $claseRepositorioListar = get_class($repoListar);
    $claseRepositorioBuscar = get_class($repoBuscar);
    $claseEntidad = $usuario === null ? 'null' : get_class($usuario);
    $namespaceViejoUtilizado = str_starts_with($claseRepositorio, 'Ventas\\Infraestructura\\Persistencia\\MySQL\\Usuarios')
        || str_starts_with($claseListar, 'Ventas\\Aplicacion\\Usuarios')
        || str_starts_with($claseEntidad, 'Ventas\\Dominio\\Usuarios');
    $namespaceModularUtilizado = str_starts_with($claseRepositorio, 'Ventas\\Usuarios\\Infrastructure')
        && str_starts_with($claseListar, 'Ventas\\Usuarios\\Application')
        && ($claseEntidad === 'null' || str_starts_with($claseEntidad, 'Ventas\\Usuarios\\Domain'));
    $ok = true;
} catch (\Throwable $e) {
    $mensaje = $e->getMessage();
}

echo 'Validacion modulo Usuarios modular' . PHP_EOL;
echo 'OK: ' . ($ok ? 'SI' : 'NO') . PHP_EOL;
echo 'Cantidad de usuarios encontrados: ' . $cantidad . PHP_EOL;
echo 'buscarPorId(' . $idBusqueda . '): ' . $resultadoBusqueda . PHP_EOL;
echo 'Permiso ventas del usuario encontrado: ' . $permisoVentas . PHP_EOL;
echo 'Clase ListarUsuarios: ' . $claseListar . PHP_EOL;
echo 'Clase BuscarUsuarioPorId: ' . $claseBuscar . PHP_EOL;
echo 'Clase CrearUsuario: ' . $claseCrear . PHP_EOL;
echo 'Clase ActualizarUsuario: ' . $claseActualizar . PHP_EOL;
echo 'Clase EliminarUsuario: ' . $claseEliminar . PHP_EOL;
echo 'Clase VerificarPermisoModulo: ' . $claseVerificar . PHP_EOL;
echo 'Repositorio resuelto: ' . $claseRepositorio . PHP_EOL;
echo 'Repositorio en Listar: ' . $claseRepositorioListar . PHP_EOL;
echo 'Repositorio en Buscar: ' . $claseRepositorioBuscar . PHP_EOL;
echo 'Entidad devuelta: ' . $claseEntidad . PHP_EOL;
echo 'Namespace viejo utilizado: ' . ($namespaceViejoUtilizado ? 'SI' : 'NO') . PHP_EOL;
echo 'Namespace modular utilizado: ' . ($namespaceModularUtilizado ? 'SI' : 'NO') . PHP_EOL;

if ($mensaje !== '') {
    echo 'Error: ' . $mensaje . PHP_EOL;
}
