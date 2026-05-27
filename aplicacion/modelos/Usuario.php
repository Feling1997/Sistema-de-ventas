<?php

require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";

class Usuario{
    private static function asegurar_columnas(): void {
        $pdo = obtener_pdo();
        if ($pdo === null)
            return;
        try {
            $st = $pdo->prepare("SHOW COLUMNS FROM usuarios LIKE ?");
            $st->execute(["permisos"]);
            if (!$st->fetch())
                $pdo->exec("ALTER TABLE usuarios ADD COLUMN permisos TEXT NULL");
        } catch (Throwable $e) {
            registrar_log("Usuario::asegurar_columnas", $e->getMessage());
        }
    }

    public static function listar_todos(string $orden_sql = "usuario ASC"): array{
        self::asegurar_columnas();
        $lista=[];
        $pdo=obtener_pdo();
        if($pdo!==null){
            try{
                $sql="SELECT id, usuario, rol, activo, creado_en, permisos FROM usuarios ORDER BY " . $orden_sql . ", id ASC";
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
        self::asegurar_columnas();
        $fila=null;
        $pdo=obtener_pdo();
        if($pdo!==null){
            try{
                $sql = "SELECT id, usuario, rol, activo, creado_en, permisos FROM usuarios WHERE id = ? LIMIT 1";
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
        self::asegurar_columnas();
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
        return self::crear_con_permisos($usuario, $clave, $rol, $activo, []);
    }

    public static function crear_con_permisos(string $usuario, string $clave, string $rol, int $activo, array $permisos):bool{
        self::asegurar_columnas();
        $ok=false;
        $pdo=obtener_pdo();
        if($pdo!==null){
            try{
                $sql = "INSERT INTO usuarios (usuario, clave, rol, activo, permisos) VALUES (?, ?, ?, ?, ?)";
                $st=$pdo->prepare($sql);
                $st->execute([$usuario, password_hash($clave, PASSWORD_DEFAULT), $rol, $activo, self::permisos_json($permisos)]);
                $ok=true;
            }catch(Throwable $e){
                registrar_log("Usuario::crear", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function actualizar_sin_clave(int $id, string $usuario, string $rol, int $activo):bool{
        return self::actualizar_sin_clave_permisos($id, $usuario, $rol, $activo, null);
    }

    public static function actualizar_sin_clave_permisos(int $id, string $usuario, string $rol, int $activo, ?array $permisos):bool{
        self::asegurar_columnas();
        $ok=false;
        $pdo=obtener_pdo();
        if($pdo!==null){
            try{
                $sql="UPDATE usuarios SET usuario = ?, rol = ?, activo = ?, permisos = ? WHERE id = ?";
                $st=$pdo->prepare($sql);
                $ok=$st->execute([$usuario, $rol, $activo, self::permisos_json($permisos ?? []), $id]);
            }catch(Throwable $e){
                $ok=false;
                registrar_log("Usuario::actualizar_sin_clave", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function actualizar_con_clave(int $id, string $usuario, string $hash_clave, string $rol, int $activo): bool{
        return self::actualizar_con_clave_permisos($id, $usuario, $hash_clave, $rol, $activo, null);
    }

    public static function actualizar_con_clave_permisos(int $id, string $usuario, string $hash_clave, string $rol, int $activo, ?array $permisos): bool{
        self::asegurar_columnas();
        $ok=false;
        $pdo=obtener_pdo();
        if($pdo!==null){
            try{
                $sql = "UPDATE usuarios SET usuario = ?, clave = ?, rol = ?, activo = ?, permisos = ? WHERE id = ?";
                $st=$pdo->prepare($sql);
                $ok = $st->execute([$usuario, $hash_clave, $rol, $activo, self::permisos_json($permisos ?? []), $id]);
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

    public static function permisos_array($valor): array {
        if (is_array($valor))
            return array_values(array_filter(array_map("strval", $valor)));
        $datos = json_decode((string)$valor, true);
        return is_array($datos) ? array_values(array_filter(array_map("strval", $datos))) : [];
    }

    private static function permisos_json(array $permisos): string {
        $limpios = array_values(array_unique(array_filter(array_map("strval", $permisos))));
        $json = json_encode($limpios, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($json) ? $json : "[]";
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
