<?php
require_once __DIR__ . "/../modelos/Usuario.php";
require_once __DIR__ . "/../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/../../configuraciones/csrf.php";

class ControladorUsuarios{
    private function permiso_admin():bool{
        $ok=false;
        if(!require_login()){
            flash_error("Tenes que iniciar sesión");
            redirigir("index.php?c=auth&a=login");
        }else{
            if(!require_rol(["ADMIN"])){
                flash_error("No tenés permiso para acceder a Usuarios");
                redirigir("index.php?c=ventas&a=lista");
            }else
                $ok=true;
        }
        return $ok;
    }

    public function index():void{
        if($this->permiso_admin()){
            $usuarios=Usuario::listar_todos();
            $texto_buscar = trim((string)obtener_get("buscar", ""));
            $campo_buscar = trim((string)obtener_get("campo", "todos"));
            $metodo_buscar = trim((string)obtener_get("metodo", "contiene"));
            $campos_busqueda = [
                "id" => "ID",
                "usuario" => "Usuario",
                "rol" => "Rol",
                "activo" => "Activo",
                "creado_en" => "Fecha"
            ];
            $usuarios = filtrar_registros_busqueda($usuarios, $texto_buscar, $campo_buscar, $campos_busqueda, $metodo_buscar);
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/usuarios/index.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function nuevo():void{
        if($this->permiso_admin()){
            $modo="crear";
            $u=["id"=>0,"usuario"=>"","rol"=>"VENDEDOR","activo"=>1];
            $permisos_usuario = [];
            $modulos_permisos = $this->modulos_para_permisos();
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/usuarios/formulario.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function crear():void{
        if($this->permiso_admin()){
            $error="";
            if($_SERVER["REQUEST_METHOD"]=="POST"){
                $csrf=obtener_post("csrf","");
                if(!csrf_valido($csrf))
                    $error="Token inválido. Recargá la página.";
                else{
                    $usuario = trim((string)obtener_post("usuario", ""));
                    $clave = (string)obtener_post("clave", "");
                    $clave2 = (string)obtener_post("clave2", "");
                    $rol = (string)obtener_post("rol", "VENDEDOR");
                    $activo = (int)obtener_post("activo", 1);
                    $permisos = $this->permisos_post();
                    if (texto_invalido($usuario) || texto_invalido($clave) || texto_invalido($clave2))
                        $error = "No se permite usuario/clave vacíos o placeholders.";
                    else {
                        if ($clave !== $clave2)
                            $error = "Las contraseñas no coinciden.";
                        else {
                            if(!in_array($rol, ["VENDEDOR", "ADMIN"], true))
                                $error = "Rol no válido.";
                            else{
                                if(Usuario::usuario_existe($usuario))
                                    $error = "El usuario ya existe.";
                                else{
                                    $ok=Usuario::crear_con_permisos($usuario, $clave, $rol, $activo, $rol === "ADMIN" ? [] : $permisos);
                                    if($ok){
                                        flash_ok("Usuario creado correctamente.");
                                        redirigir("index.php?c=usuarios&a=index");
                                    }else
                                        $error = "No se pudo crear el usuario (ver logs).";
                                }
                            }
                        }
                    }
                }
            }else
                $error="Acceso inválido.";
            if ($error !== "") {
                flash_error($error);
                $modo = "crear";
                $u = ["id" => 0, "usuario" => $usuario ?? "", "rol" => $rol ?? "VENDEDOR", "activo" => $activo ?? 1];
                $permisos_usuario = $permisos ?? [];
                $modulos_permisos = $this->modulos_para_permisos();
                include __DIR__ . "/../vistas/parciales/encabezado.php";
                include __DIR__ . "/../vistas/usuarios/formulario.php";
                include __DIR__ . "/../vistas/parciales/pie.php";
            }
        }
    }

    public function editar():void{
        if($this->permiso_admin()){
            $id=(int)obtener_get("id",0);
            $u=Usuario::buscar_por_id($id);
            if($u===null){
                flash_error("Usuario no encontrado.");
                redirigir("index.php?c=usuarios&a=index");
            }else{
                $modo="editar";
                $permisos_usuario = Usuario::permisos_array($u["permisos"] ?? "[]");
                $modulos_permisos = $this->modulos_para_permisos();
                include __DIR__ . "/../vistas/parciales/encabezado.php";
                include __DIR__ . "/../vistas/usuarios/formulario.php";
                include __DIR__ . "/../vistas/parciales/pie.php";
            }
        }
    }

    public function actualizar():void{
        if($this->permiso_admin()){
            $error="";
            if($_SERVER["REQUEST_METHOD"]=="POST"){
                $csrf=obtener_post("csrf","");
                if(!csrf_valido($csrf))
                    $error="Token inválido. Recargá la página.";
                else{
                    $id=(int)obtener_post("id",0);
                    $usuario = trim((string)obtener_post("usuario", ""));
                    $clave_nueva = (string)obtener_post("clave", "");
                    $rol = (string)obtener_post("rol", "VENDEDOR");
                    $activo = (int)obtener_post("activo", 1);
                    $permisos = $this->permisos_post();
                    $u_actual=Usuario::buscar_por_id($id);
                    if($u_actual===null)
                        $error="Usuario no encontrado.";
                    else{
                        if(texto_invalido($usuario))
                            $error = "Usuario inválido (vacíos o placeholders).";
                        else{
                            if(!in_array($rol, ["VENDEDOR", "ADMIN"], true))
                                $error = "Rol no válido.";
                            else{
                                if(Usuario::usuario_existe($usuario, $id))
                                    $error = "El usuario ya existe.";
                                else{
                                    $cambia_clave=false;
                                    if(is_string($clave_nueva) && trim($clave_nueva)!=="")
                                        $cambia_clave=true;
                                    $ok=false;
                                    if($cambia_clave){
                                        if(texto_invalido($clave_nueva))
                                            $error="Clave inválida";
                                        else{
                                            $hash=password_hash($clave_nueva, PASSWORD_DEFAULT);
                                            $ok=Usuario::actualizar_con_clave_permisos($id, $usuario, $hash, $rol, $activo, $rol === "ADMIN" ? [] : $permisos);
                                            if(!$ok)
                                                $error="No se pudo actualizar el usuario (ver logs).";
                                        }
                                    }else{
                                        $ok=Usuario::actualizar_sin_clave_permisos($id, $usuario, $rol, $activo, $rol === "ADMIN" ? [] : $permisos);
                                        if(!$ok)
                                            $error="No se pudo actualizar el usuario (ver logs).";
                                    }
                                    if($error===""){
                                        flash_ok("Usuario actualizado correctamente.");
                                        redirigir("index.php?c=usuarios&a=index");
                                    }
                                }
                            }
                        }
                    }
                }
            }else
                $error="Acceso inválido.";
            if ($error !== "") {
                flash_error($error);
                $modo = "editar";
                $u = ["id" => $id ?? 0, "usuario" => $usuario ?? "", "rol" => $rol ?? "VENDEDOR", "activo" => $activo ?? 1];
                $permisos_usuario = $permisos ?? [];
                $modulos_permisos = $this->modulos_para_permisos();
                include __DIR__ . "/../vistas/parciales/encabezado.php";
                include __DIR__ . "/../vistas/usuarios/formulario.php";
                include __DIR__ . "/../vistas/parciales/pie.php";
            }
        }
    }

    public function eliminar():void{
        if($this->permiso_admin()){
            $id=(int)obtener_get("id",0);
            $u=Usuario::buscar_por_id($id);
            if($u===null){
                flash_error("Usuario no encontrado.");
                redirigir("index.php?c=usuarios&a=index");
            }else{
                iniciar_sesion();
                $id_logueado=(int)($_SESSION["usuario_logueado"]["id"]??0);
                if($id_logueado===$id){
                    flash_error("No puedes eliminar a ti mismo.");
                    redirigir("index.php?c=usuarios&a=index");
                }else{
                    if(Usuario::esta_relacionado_con_ventas($id)){
                        flash_error("No puedes eliminar un usuario con ventas asociadas.");
                        redirigir("index.php?c=usuarios&a=index");
                    }else{
                        $ok=Usuario::eliminar($id);
                        if($ok)
                            flash_ok("Usuario eliminado correctamente.");
                        else
                            flash_error("No se pudo eliminar el usuario (ver logs).");
                        redirigir("index.php?c=usuarios&a=index");
                    }
                }
            }
        }
    }

    private function permisos_post(): array {
        $permisos = $_POST["permisos"] ?? [];
        if (!is_array($permisos))
            return ["__none"];
        $permitidos = array_keys($this->modulos_para_permisos());
        $limpios = array_values(array_intersect(array_map("strval", $permisos), $permitidos));
        return count($limpios) > 0 ? $limpios : ["__none"];
    }

    private function modulos_para_permisos(): array {
        $modulos = menu_modulos_base();
        unset($modulos["inicio"], $modulos["usuarios"], $modulos["configuraciones"]);
        return $modulos;
    }
}
