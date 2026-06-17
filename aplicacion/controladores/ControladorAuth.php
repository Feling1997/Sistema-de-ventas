<?php
require_once __DIR__ . "/../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/../../configuraciones/csrf.php";

class ControladorAuth{
    public function login():void{
        iniciar_sesion();
        global $container;
        \Ventas\Auth\Infrastructure\RegistroAuth::registrar($container);
        if (auth_admin_local_habilitado()) {
            $crearSesionSinLogin = $container->get(\Ventas\Auth\Application\CrearSesionSinLogin::class);
            $crearSesionSinLogin->ejecutar();
            header("Location:index.php?c=ventas&a=inicio");
            return;
        }
        $error="";
        if($_SERVER["REQUEST_METHOD"]==="POST"){
            $csrf=obtener_post("csrf","");
            //falsificaciones de petición desde otro sitio o token
            if(!csrf_valido($csrf))
                $error="Token invalido. Recarga la página.";
            else{
                $usuario=trim((string)obtener_post("usuario",""));
                $clave=trim((string)obtener_post("clave",""));
                if(texto_invalido($usuario) || texto_invalido($clave))
                    $error="Completa usuario o contraseña";
                else{
                    $usuarioRepository = $container->get(\Ventas\Usuarios\Domain\Repositorios\UsuarioRepository::class);
                    $u=$usuarioRepository->buscarPorUsuario($usuario);
                    if($u===null)
                        $error="Usuario o contraseña incorrectos";
                    else{
                        if(!$u->activo())
                            $error="Usuario inactivo";
                        else if(!password_verify($clave,(string)$u->claveHash()))
                            $error="Usuario o contraseña incorrectos";
                        else{
                            $iniciarSesionAuth = $container->get(\Ventas\Auth\Application\IniciarSesionAuth::class);
                            $iniciarSesionAuth->ejecutar([
                                "id"=>(int)$u->id(),
                                "usuario"=>$u->usuario(),
                                "rol"=>$u->rol(),
                                "permisos"=>$u->permisos()->comoArray()
                            ]);
                            header("Location:index.php?c=ventas&a=inicio");
                            return;
                        }
                    }
                }
            }
        }
    include __DIR__ . "/../vistas/parciales/encabezado.php";
    include __DIR__ . "/../vistas/auth/login.php";
    include __DIR__ . "/../vistas/parciales/pie.php";
    }

    public function salir():void{
        global $container;
        \Ventas\Auth\Infrastructure\RegistroAuth::registrar($container);
        $cerrarSesionAuth = $container->get(\Ventas\Auth\Application\CerrarSesionAuth::class);
        $cerrarSesionAuth->ejecutar();
        header("Location:index.php?c=auth&a=login");
    }
}
