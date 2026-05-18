<?php

require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";

class Usuario{
    public static function listar_todos(): array{
        $lista=[];
        $pdo=obtener_pdo();
        if($pdo!==null){
            try{
                $sql="SELECT id, usuario, rol, activo, creado_en FROM usuarios ORDER BY id DESC";
                $st=$pdo->prepare($sql);
                $st->execute();
                $rows=$st->fetchAll();
                if(is_array($rows))
                    $lista=$rows;
            }catch(Throwable $e){
                registrar_log("Usuario::listar_todos", $e->getMessage());
            }
        }
        return $lista;
    }

    public static function buscar_por_id(int $id): ?array{
        $fila=null;
        $pdo=obtener_pdo();
        if($pdo!==null){
            try{
                $sql = "SELECT id, usuario, rol, activo, creado_en FROM usuarios WHERE id = ? LIMIT 1";
                $st=$pdo->prepare($sql);
                $st->execute([$id]);
                $r=$st->fetch();
                if($r)
                    $fila=$r;
            }catch(Throwable $e){
                registrar_log("Usuario::buscar_por_id", $e->getMessage());
            }
        }
        return $fila;
    }

    public static function buscar_por_usuario(string $usuario):?array{
        $fila=null;
        $pdo=obtener_pdo();

        if($pdo!==null){
            try{
                $sql="SELECT * FROM usuarios WHERE usuario=? LIMIT 1";
                $st=$pdo->prepare($sql);
                $st->execute([$usuario]);
                $r=$st->fetch();
                if($r)
                    $fila=$r;
            }catch(Throwable $e){
                registrar_log("Usuario::buscar_por_usuario ",$e->getMessage());
            }
        }
    return $fila;
    }

    public static function usuario_existe(string $usuario, int $excepto_id=0):bool{
        $existe=false;
        $pdo=obtener_pdo();
        if($pdo!==null){
            try{
                $sql = "SELECT id FROM usuarios WHERE usuario = ? AND id <> ? LIMIT 1";
                $st=$pdo->prepare($sql);
                $st->execute([$usuario, $excepto_id]);
                $r=$st->fetch();
                if($r)
                    $existe=true;
            }catch(Throwable $e){
                registrar_log("Usuario::usuario_existe", $e->getMessage());
            }
        }
        return $existe;
    }

    public static function crear(string $usuario, string $clave, string $rol, int $activo):bool{
        $ok=false;
        $pdo=obtener_pdo();
        if($pdo!==null){
            try{
                $sql = "INSERT INTO usuarios (usuario, clave, rol, activo) VALUES (?, ?, ?, ?)";
                $st=$pdo->prepare($sql);
                $st->execute([$usuario, password_hash($clave, PASSWORD_DEFAULT), $rol, $activo]);
                $ok=true;
            }catch(Throwable $e){
                registrar_log("Usuario::crear", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function actualizar_sin_clave(int $id, string $usuario, string $rol, int $activo):bool{
        $ok=false;
        $pdo=obtener_pdo();
        if($pdo!==null){
            try{
                $sql="UPDATE usuarios SET usuario = ?, rol = ?, activo = ? WHERE id = ?";
                $st=$pdo->prepare($sql);
                $ok=$st->execute([$usuario, $rol, $activo, $id]);
            }catch(Throwable $e){
                $ok=false;
                registrar_log("Usuario::actualizar_sin_clave", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function actualizar_con_clave(int $id, string $usuario, string $hash_clave, string $rol, int $activo): bool{
        $ok=false;
        $pdo=obtener_pdo();
        if($pdo!==null){
            try{
                $sql = "UPDATE usuarios SET usuario = ?, clave = ?, rol = ?, activo = ? WHERE id = ?";
                $st=$pdo->prepare($sql);
                $ok = $st->execute([$usuario, $hash_clave, $rol, $activo, $id]);
            }catch(Throwable $e){
                $ok=false;
                registrar_log("Usuario::actualizar_con_clave", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function esta_relacionado_con_ventas(int $id_usuario):bool{
        $rel=false;
        $pdo=obtener_pdo();
        if($pdo!==null){
            try{
                $sql = "SELECT id FROM ventas WHERE id_usuario = ? LIMIT 1";
                $st=$pdo->prepare($sql);
                $st->execute([$id_usuario]);
                $r=$st->fetch();
                if($r)
                    $rel=true;
            }catch(Throwable $e){
                registrar_log("Usuario::esta_relacionado_con_ventas", $e->getMessage());
            }
        }
        return $rel;
    }

    public static function eliminar(int $id):bool{
        $ok=false;
        $pdo=obtener_pdo();
        if($pdo!==null){
            try{
                $sql = "DELETE FROM usuarios WHERE id = ?";
                $st=$pdo->prepare($sql);
                $ok=$st->execute([$id]);
            }catch(Throwable $e){
                $ok=false;
                registrar_log("Usuario::eliminar", $e->getMessage());
            }
        }
        return $ok;
    }
}