<?php
require_once __DIR__ . "/../modelos/Usuario.php";
require_once __DIR__ . "/../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/../../configuraciones/csrf.php";

class ControladorAuth{
    public function login():void{
        iniciar_sesion();
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
                    $u=Usuario::buscar_por_usuario($usuario);
                    if($u===null)
                        $error="Usuario o contraseña incorrectos";
                    else{
                        if(!password_verify($clave,$u["clave"]))
                            $error="Usuario o contraseña incorrectos";
                        else{
                            $_SESSION["usuario_logueado"]=[
                                "id"=>(int)$u["id"],
                                "usuario"=>$u["usuario"],
                                "rol"=>$u["rol"]
                            ];
                            header("Location:index.php?c=ventas&a=inicio");
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
        iniciar_sesion();
        $_SESSION=[];
        session_destroy();
        header("Location:index.php?c=auth&a=login");
    }
}
