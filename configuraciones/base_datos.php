<?php

require_once __DIR__."/ayudas.php";

function obtener_pdo(): ?PDO{
    static $pdo=null;
    if($pdo===null){
        $host="localhost";
        $bd="sistema_ventas";
        $usuario="root";
        $clave="";
        $dsn="mysql:host=$host;dbname=$bd;charset=utf8mb4";
        try{
            $pdo=new PDO($dsn,$usuario,$clave,[
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
        }catch(Throwable $e){
            $pdo=null;
            registrar_log("BD",$e->getMessage());
        }
    }
    return $pdo;
}