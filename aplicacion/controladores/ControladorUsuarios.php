<?php
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
            $orden_usuarios = orden_parametros([
                "usuario" => "usuario",
                "nombre" => "usuario",
                "estado" => "activo",
                "fecha" => "creado_en"
            ], "usuario", "ASC");
            global $container;
            $listarUsuarios = $container->get(\Ventas\Usuarios\Application\ListarUsuarios::class);
            $usuarios_dominio = $listarUsuarios->ejecutar();
            $usuarios = [];
            foreach ($usuarios_dominio as $usuario_dominio) {
                $usuarios[] = [
                    "id" => $usuario_dominio->id(),
                    "usuario" => $usuario_dominio->usuario(),
                    "rol" => $usuario_dominio->rol(),
                    "activo" => $usuario_dominio->activo() ? 1 : 0,
                    "creado_en" => $usuario_dominio->creadoEn(),
                ];
            }
            usort($usuarios, function (array $a, array $b) use ($orden_usuarios): int {
                $campo = (string)($orden_usuarios["campo"] ?? "usuario");
                $direccion = strtoupper((string)($orden_usuarios["direccion"] ?? "ASC"));
                $columnas = [
                    "usuario" => "usuario",
                    "nombre" => "usuario",
                    "estado" => "activo",
                    "fecha" => "creado_en",
                ];
                $columna = $columnas[$campo] ?? "usuario";
                $valor_a = $a[$columna] ?? "";
                $valor_b = $b[$columna] ?? "";
                $comparacion = is_numeric($valor_a) && is_numeric($valor_b)
                    ? ((float)$valor_a <=> (float)$valor_b)
                    : strcasecmp((string)$valor_a, (string)$valor_b);

                if ($comparacion === 0) {
                    $comparacion = ((int)($a["id"] ?? 0)) <=> ((int)($b["id"] ?? 0));
                }

                if ($direccion === "DESC") {
                    $comparacion *= -1;
                }

                return $comparacion;
            });
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
                                global $container;
                                $usuarioRepository = $container->get(\Ventas\Usuarios\Domain\Repositorios\UsuarioRepository::class);
                                if($usuarioRepository->existeUsuario($usuario))
                                    $error = "El usuario ya existe.";
                                else{
                                    $ok=false;
                                    try {
                                        $crearUsuario = $container->get(\Ventas\Usuarios\Application\CrearUsuario::class);
                                        $crearUsuario->ejecutar(new \Ventas\Usuarios\Domain\Entidades\Usuario(null, $usuario, password_hash($clave, PASSWORD_DEFAULT), $rol, $activo === 1, \Ventas\Usuarios\Domain\Entidades\PermisosUsuario::desdeLegacy($rol === "ADMIN" ? [] : $permisos)));
                                        $ok=true;
                                    } catch (Throwable $e) {
                                        registrar_log("ControladorUsuarios::crear", $e->getMessage());
                                    }
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
            global $container;
            $buscarUsuarioPorId = $container->get(\Ventas\Usuarios\Application\BuscarUsuarioPorId::class);
            $usuario_dominio = $buscarUsuarioPorId->ejecutar($id);
            $u = null;
            if ($usuario_dominio !== null) {
                $u = [
                    "id" => $usuario_dominio->id(),
                    "usuario" => $usuario_dominio->usuario(),
                    "rol" => $usuario_dominio->rol(),
                    "activo" => $usuario_dominio->activo() ? 1 : 0,
                    "creado_en" => $usuario_dominio->creadoEn(),
                ];
            }
            if($u===null){
                flash_error("Usuario no encontrado.");
                redirigir("index.php?c=usuarios&a=index");
            }else{
                $modo="editar";
                $permisos_usuario = $usuario_dominio->permisos()->comoArray();
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
                    global $container;
                    $buscarUsuarioPorId = $container->get(\Ventas\Usuarios\Application\BuscarUsuarioPorId::class);
                    $u_actual=$buscarUsuarioPorId->ejecutar($id);
                    if($u_actual===null)
                        $error="Usuario no encontrado.";
                    else{
                        if(texto_invalido($usuario))
                            $error = "Usuario inválido (vacíos o placeholders).";
                        else{
                            if(!in_array($rol, ["VENDEDOR", "ADMIN"], true))
                                $error = "Rol no válido.";
                            else{
                                $usuarioRepository = $container->get(\Ventas\Usuarios\Domain\Repositorios\UsuarioRepository::class);
                                if($usuarioRepository->existeUsuario($usuario, $id))
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
                                            try {
                                                $actualizarUsuario = $container->get(\Ventas\Usuarios\Application\ActualizarUsuario::class);
                                                $actualizarUsuario->ejecutar(new \Ventas\Usuarios\Domain\Entidades\Usuario($id, $usuario, $u_actual->claveHash(), $rol, $activo === 1, \Ventas\Usuarios\Domain\Entidades\PermisosUsuario::desdeLegacy($rol === "ADMIN" ? [] : $permisos), $u_actual->creadoEn()));
                                                $usuarioRepository->actualizarClave($id, $hash);
                                                $ok=true;
                                            } catch (Throwable $e) {
                                                registrar_log("ControladorUsuarios::actualizar", $e->getMessage());
                                            }
                                            if(!$ok)
                                                $error="No se pudo actualizar el usuario (ver logs).";
                                        }
                                    }else{
                                        try {
                                            $actualizarUsuario = $container->get(\Ventas\Usuarios\Application\ActualizarUsuario::class);
                                            $actualizarUsuario->ejecutar(new \Ventas\Usuarios\Domain\Entidades\Usuario($id, $usuario, $u_actual->claveHash(), $rol, $activo === 1, \Ventas\Usuarios\Domain\Entidades\PermisosUsuario::desdeLegacy($rol === "ADMIN" ? [] : $permisos), $u_actual->creadoEn()));
                                            $ok=true;
                                        } catch (Throwable $e) {
                                            registrar_log("ControladorUsuarios::actualizar", $e->getMessage());
                                        }
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
            global $container;
            $buscarUsuarioPorId = $container->get(\Ventas\Usuarios\Application\BuscarUsuarioPorId::class);
            $u=$buscarUsuarioPorId->ejecutar($id);
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
                    $eliminarUsuario = $container->get(\Ventas\Usuarios\Application\EliminarUsuario::class);
                    try {
                        $eliminarUsuario->ejecutar($id, $id_logueado);
                        flash_ok("Usuario eliminado correctamente.");
                        redirigir("index.php?c=usuarios&a=index");
                    } catch (\Ventas\Usuarios\Domain\Excepciones\UsuarioConVentasException $e) {
                        flash_error("No puedes eliminar un usuario con ventas asociadas.");
                        redirigir("index.php?c=usuarios&a=index");
                    } catch (\Ventas\Usuarios\Domain\Excepciones\UsuarioActualNoEliminableException $e) {
                        flash_error("No puedes eliminar a ti mismo.");
                        redirigir("index.php?c=usuarios&a=index");
                    } catch (\Ventas\Usuarios\Domain\Excepciones\UsuarioNoEncontradoException $e) {
                        flash_error("Usuario no encontrado.");
                        redirigir("index.php?c=usuarios&a=index");
                    } catch (Throwable $e) {
                        registrar_log("ControladorUsuarios::eliminar", $e->getMessage());
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
