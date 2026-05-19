<?php
//Falsificación de petición desde otro sitio
require_once __DIR__ . "/seguridad.php";

function csrf_token(): string {
    iniciar_sesion();
    
    if (!isset($_SESSION["csrf"])) {
        // el bin convierte en hexadecimal y el random_bytes genera aleatoriamente 16 caracteres
        $_SESSION["csrf"] = bin2hex(random_bytes(16));
    }
    
    return $_SESSION["csrf"];
}

function csrf_valido($token): bool {
    iniciar_sesion();
    $es_valido = false;

    if (isset($_SESSION["csrf"]) && is_string($token)) {
        if (hash_equals($_SESSION["csrf"], $token)) {
            $es_valido = true;
        }
    }
    
    return $es_valido;
}