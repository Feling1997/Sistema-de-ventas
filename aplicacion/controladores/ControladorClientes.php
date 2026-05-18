<?php
require_once __DIR__ . "/../modelos/Cliente.php";
require_once __DIR__ . "/../modelos/ListaPrecio.php";
require_once __DIR__ . "/../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/../../configuraciones/csrf.php";

class ControladorClientes {

    private function permiso(): bool {
        $ok = false;
        if (!require_login()) {
            flash_error("Tenés que iniciar sesión.");
            redirigir("index.php?c=auth&a=login");
        } else {
            if (!require_rol(["ADMIN","VENDEDOR"])) {
                flash_error("No tenés permisos para Clientes.");
                redirigir("index.php?c=ventas&a=lista");
            } else
                $ok = true;            
        }
        return $ok;
    }

    public function index(): void {
        if ($this->permiso()) {
            $clientes = Cliente::listar_todos();
            $texto_buscar = trim((string)obtener_get("buscar", ""));
            $campo_buscar = trim((string)obtener_get("campo", "todos"));
            $metodo_buscar = trim((string)obtener_get("metodo", "contiene"));
            $campos_busqueda = [
                "id" => "ID",
                "nombre" => "Nombre",
                "dni" => "Documento",
                "tipo_documento" => "Tipo doc.",
                "condicion_iva" => "IVA",
                "lista_precio_nombre" => "Lista",
                "email" => "Email",
                "telefono" => "Teléfono",
                "direccion" => "Dirección",
                "creado_en" => "Fecha"
            ];
            $clientes = filtrar_registros_busqueda($clientes, $texto_buscar, $campo_buscar, $campos_busqueda, $metodo_buscar);
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/clientes/index.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function nuevo(): void {
        if ($this->permiso()) {
            $modo = "crear";
            $c = ["id" => 0, "nombre" => "", "dni" => "", "tipo_documento" => "DNI", "condicion_iva" => "Consumidor Final", "email" => "", "telefono" => "", "direccion" => "", "id_lista_precio" => ListaPrecio::id_predeterminada()];
            $listas_precios = ListaPrecio::listar(true);
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/clientes/formulario.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function crear(): void {
        if ($this->permiso()) {
            $error = "";
            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $csrf = obtener_post("csrf", "");
                if (!csrf_valido($csrf))
                    $error = "Token inválido. Recargá la página.";
                else {
                    $nombre = trim((string)obtener_post("nombre", ""));
                    $dni = trim((string)obtener_post("dni", ""));
                    $tipo_documento = trim((string)obtener_post("tipo_documento", "DNI"));
                    $condicion_iva = trim((string)obtener_post("condicion_iva", "Consumidor Final"));
                    $email = trim((string)obtener_post("email", ""));
                    $id_lista_precio = (int)obtener_post("id_lista_precio", ListaPrecio::id_predeterminada());
                    $telefono = trim((string)obtener_post("telefono", ""));
                    $direccion = trim((string)obtener_post("direccion", ""));
                    if (texto_invalido($nombre)) 
                        $error = "Nombre inválido (vacío o placeholder).";
                    else {
                        $dni_db = null;
                        $tel_db = null;
                        $dir_db = null;
                        $email_db = null;

                        if (!texto_invalido($dni) && $dni !== "")
                            $dni_db = $dni;
                        if (!texto_invalido($telefono) && $telefono !== "")
                            $tel_db = $telefono;                        
                        if (!texto_invalido($direccion) && $direccion !== "")
                            $dir_db = $direccion;
                        if (!texto_invalido($email) && $email !== "")
                            $email_db = $email;
                        if ($dni_db !== null && Cliente::dni_existe($dni_db, 0))
                            $error = "El documento ya existe.";
                        else {
                            $ok = Cliente::crear($nombre, $dni_db, $tel_db, $dir_db, $tipo_documento, $condicion_iva, $email_db, $id_lista_precio);
                            if ($ok) {
                                flash_ok("Cliente creado correctamente.");
                                redirigir("index.php?c=clientes&a=index");
                            } else
                                $error = "No se pudo crear el cliente (ver logs).";
                        }
                    }
                }
            } else
                $error = "Acceso inválido.";
            if ($error !== "") {
                flash_error($error);
                redirigir("index.php?c=clientes&a=nuevo");
            }
        }
    }

    public function editar(): void {
        if ($this->permiso()) {
            $id = (int)obtener_get("id", 0);

            if ($id === 1) {
                flash_error("Consumidor Final (ID=1) no se puede editar.");
                redirigir("index.php?c=clientes&a=index");
            } else {
                $c = Cliente::buscar_por_id($id);
                if ($c === null) {
                    flash_error("Cliente no encontrado.");
                    redirigir("index.php?c=clientes&a=index");
                } else {
                    $modo = "editar";
                    $listas_precios = ListaPrecio::listar(true);
                    include __DIR__ . "/../vistas/parciales/encabezado.php";
                    include __DIR__ . "/../vistas/clientes/formulario.php";
                    include __DIR__ . "/../vistas/parciales/pie.php";
                }
            }
        }
    }

    public function actualizar(): void {
        if ($this->permiso()) {
            $error = "";

            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $csrf = obtener_post("csrf", "");
                if (!csrf_valido($csrf)) {
                    $error = "Token inválido. Recargá la página.";
                } else {
                    $id = (int)obtener_post("id", 0);

                    if ($id === 1) {
                        $error = "Consumidor Final (ID=1) no se puede editar.";
                    } else {
                        $c_actual = Cliente::buscar_por_id($id);
                        if ($c_actual === null) {
                            $error = "Cliente no encontrado.";
                        } else {
                            $nombre = trim((string)obtener_post("nombre", ""));
                            $dni = trim((string)obtener_post("dni", ""));
                            $tipo_documento = trim((string)obtener_post("tipo_documento", "DNI"));
                            $condicion_iva = trim((string)obtener_post("condicion_iva", "Consumidor Final"));
                            $email = trim((string)obtener_post("email", ""));
                            $id_lista_precio = (int)obtener_post("id_lista_precio", ListaPrecio::id_predeterminada());
                            $telefono = trim((string)obtener_post("telefono", ""));
                            $direccion = trim((string)obtener_post("direccion", ""));

                            if (texto_invalido($nombre)) {
                                $error = "Nombre inválido (vacío o placeholder).";
                            } else {
                                $dni_db = null;
                                $tel_db = null;
                                $dir_db = null;
                                $email_db = null;

                                if (!texto_invalido($dni) && $dni !== "") {
                                    $dni_db = $dni;
                                }
                                if (!texto_invalido($telefono) && $telefono !== "") {
                                    $tel_db = $telefono;
                                }
                                if (!texto_invalido($direccion) && $direccion !== "") {
                                    $dir_db = $direccion;
                                }
                                if (!texto_invalido($email) && $email !== "") {
                                    $email_db = $email;
                                }

                                if ($dni_db !== null && Cliente::dni_existe($dni_db, $id)) {
                                    $error = "El documento ya existe.";
                                } else {
                                    $ok = Cliente::actualizar($id, $nombre, $dni_db, $tel_db, $dir_db, $tipo_documento, $condicion_iva, $email_db, $id_lista_precio);
                                    if ($ok) {
                                        flash_ok("Cliente actualizado correctamente.");
                                        redirigir("index.php?c=clientes&a=index");
                                    } else {
                                        $error = "No se pudo actualizar (ver logs).";
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                $error = "Acceso inválido.";
            }

            if ($error !== "") {
                flash_error($error);
                redirigir("index.php?c=clientes&a=index");
            }
        }
    }

    public function eliminar(): void {
        if ($this->permiso()) {
            $id = (int)obtener_get("id", 0);

            if ($id === 1) {
                flash_error("Consumidor Final (ID=1) no se puede eliminar.");
                redirigir("index.php?c=clientes&a=index");
            } else {
                $c = Cliente::buscar_por_id($id);
                if ($c === null) {
                    flash_error("Cliente no encontrado.");
                    redirigir("index.php?c=clientes&a=index");
                } else {
                    if (Cliente::esta_relacionado_con_ventas($id)) {
                        flash_error("No se puede eliminar: el cliente tiene ventas asociadas.");
                        redirigir("index.php?c=clientes&a=index");
                    } else {
                        $ok = Cliente::eliminar($id);
                        if ($ok) {
                            flash_ok("Cliente eliminado.");
                        } else {
                            flash_error("No se pudo eliminar (ver logs).");
                        }
                        redirigir("index.php?c=clientes&a=index");
                    }
                }
            }
        }
    }
}
