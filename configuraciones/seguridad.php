<?php

function iniciar_sesion():void{
    //verificamos si esta inicializada la sesion para no hacerla 2 veces
    if(session_status()===PHP_SESSION_NONE)
        session_start();
}

function esta_logueado():bool{
    iniciar_sesion();
    if (!isset($_SESSION["usuario_logueado"]) && auth_sin_login_habilitado()) {
        $_SESSION["usuario_logueado"] = [
            "id" => 0,
            "usuario" => "Sin login",
            "rol" => "ADMIN",
            "permisos" => []
        ];
    }
    $ok=false;
    if(isset($_SESSION["usuario_logueado"]))
        $ok=true;
    return $ok;
}

function auth_sin_login_habilitado(): bool {
    $archivo = __DIR__ . "/../almacenamiento/configuracion_sistema.json";
    if (!is_file($archivo))
        return false;
    $json = @file_get_contents($archivo);
    $datos = is_string($json) ? json_decode($json, true) : null;
    return is_array($datos) && (string)($datos["auth_modo"] ?? "login") === "sin_login";
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

function usuario_permisos_actuales(): array {
    iniciar_sesion();
    $permisos = $_SESSION["usuario_logueado"]["permisos"] ?? [];
    return is_array($permisos) ? array_values(array_filter(array_map("strval", $permisos))) : [];
}

function usuario_puede_modulo(string $modulo): bool {
    if (!esta_logueado())
        return false;
    if (rol_actual() === "ADMIN")
        return true;
    $permisos = usuario_permisos_actuales();
    if (count($permisos) === 0)
        return true;
    if (in_array("__none", $permisos, true))
        return false;
    return in_array($modulo, $permisos, true);
}
