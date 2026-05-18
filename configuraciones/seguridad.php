<?php

function iniciar_sesion():void{
    //verificamos si esta inicializada la sesion para no hacerla 2 veces
    if(session_status()===PHP_SESSION_NONE)
        session_start();
}

function esta_logueado():bool{
    iniciar_sesion();
    $ok=false;
    if(isset($_SESSION["usuario_logueado"]))
        $ok=true;
    return $ok;
}

function require_login():bool{
    $ok=esta_logueado();
    return $ok;
}

function rol_actual(): string{
    iniciar_sesion();
    $rol="INVITADO";
    if(isset($_SESSION["usuario_logueado"]["rol"]))
        $rol=$_SESSION["usuario_logueado"]["rol"];
    return $rol;
}

function require_rol(array $roles_permitidos):bool{
    $ok=false;
    if(esta_logueado()){
        $rol=rol_actual();
        if(in_array($rol,$roles_permitidos,true))
            $ok=true;
    }
    return $ok;
}