<?php

function iniciar_sesion():void{
    //verificamos si esta inicializada la sesion para no hacerla 2 veces
    if(session_status()===PHP_SESSION_NONE)
        session_start();
}

function esta_logueado():bool{
    iniciar_sesion();
    if (!isset($_SESSION["usuario_logueado"]) && auth_admin_local_habilitado()) {
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
    $modo_db = auth_modo_desde_bd();
    if ($modo_db === "sin_login")
        return true;

    $archivo = __DIR__ . "/../almacenamiento/configuracion_sistema.json";
    if (!is_file($archivo))
        return false;
    $json = @file_get_contents($archivo);
    if (is_string($json))
        $json = preg_replace('/^\xEF\xBB\xBF/', '', $json);
    $datos = is_string($json) ? json_decode($json, true) : null;
    return is_array($datos) && (string)($datos["auth_modo"] ?? "login") === "sin_login";
}

function auth_modo_desde_bd(): ?string {
    require_once __DIR__ . "/base_datos.php";
    $pdo = obtener_pdo();
    if ($pdo === null)
        return null;
    try {
        $st = $pdo->prepare("SELECT valor FROM configuraciones WHERE clave = ? LIMIT 1");
        $st->execute(["auth_modo"]);
        $fila = $st->fetch();
        if (!$fila)
            return null;
        $modo = trim((string)($fila["valor"] ?? ""));
        return in_array($modo, ["login", "sin_login"], true) ? $modo : null;
    } catch (Throwable $e) {
        registrar_log("auth_modo_desde_bd", $e->getMessage());
        return null;
    }
}

function auth_admin_local_habilitado(): bool {
    return auth_sin_login_habilitado() || auth_no_hay_usuarios_creados();
}

function auth_no_hay_usuarios_creados(): bool {
    require_once __DIR__ . "/base_datos.php";
    $resultado = false;
    $pdo = obtener_pdo();
    if ($pdo !== null) {
        try {
            $st = $pdo->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE id > 0 OR usuario <> ?");
            $st->execute(["Sin login"]);
            $fila = $st->fetch();
            $resultado = (int)($fila["total"] ?? 0) === 0;
        } catch (Throwable $e) {
            registrar_log("auth_no_hay_usuarios_creados", $e->getMessage());
        }
    }
    return $resultado;
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
