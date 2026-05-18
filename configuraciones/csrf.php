<?php
//Falsificación de petición desde otro sitio
require_once __DIR__ .("/seguridad.php");

function csrf_token():string{
    iniciar_sesion();
    if(!isset($_SESSION["csrf"]))
        //el bin convierte en hexadecimal y el random_bytes genera aleatoriamente 16 caracteres
        $_SESSION["csrf"]=bin2hex(random_bytes(16));
    return $_SESSION["csrf"];
}

function csrf_valido($token):bool{
    iniciar_sesion();
    $ok=false;
    //confirmamos que exista el token y que sea un string
    if(isset($_SESSION["csrf"]) && is_string($token)){
        //hash_equals compara dos strings y devuelve true si son iguales ocultando el progreso de la comparacion
        //sin detenerse cuando encuentren diferencias, se detiene cuando terminen de comparar
        if(hash_equals($_SESSION["csrf"],$token))
            $ok=true;
    }
    return $ok;
}