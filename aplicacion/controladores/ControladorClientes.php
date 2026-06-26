<?php
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

    private function listaPrecioDominioAArray(\Ventas\ListasPrecios\Domain\Entidades\ListaPrecio $lista): array {
        $resultado = [
            "id" => $lista->id(),
            "nombre" => $lista->nombre(),
            "activo" => $lista->activo() ? 1 : 0,
            "creado_en" => $lista->creadoEn(),
        ];

        return $resultado;
    }

    private function listar_listas_precios_activas(): array {
        global $container;

        $listarListasPrecios = $container->get(\Ventas\ListasPrecios\Application\ListarListasPrecios::class);
        $resultado = [];

        foreach ($listarListasPrecios->ejecutar(true) as $lista_precio_dominio) {
            $resultado[] = $this->listaPrecioDominioAArray($lista_precio_dominio);
        }

        return $resultado;
    }

    private function id_lista_precio_predeterminada(): int {
        global $container;

        $obtenerListaPrecioPredeterminada = $container->get(\Ventas\ListasPrecios\Application\ObtenerListaPrecioPredeterminada::class);
        $resultado = $obtenerListaPrecioPredeterminada->ejecutar();

        return $resultado;
    }

    public function index(): void {
        if ($this->permiso()) {
            $orden_clientes = orden_parametros([
                "nombre" => "c.nombre",
                "cliente" => "c.nombre",
                "descripcion" => "c.nombre",
                "codigo" => "c.dni",
                "codigo_barras" => "c.dni",
                "fecha" => "c.creado_en"
            ], "nombre", "ASC");
            global $container;
            $listarClientes = $container->get(\Ventas\Clientes\Application\ListarClientes::class);
            $clientes_dominio = $listarClientes->ejecutar();
            $clientes = [];
            foreach ($clientes_dominio as $cliente_dominio) {
                $clientes[] = [
                    "id" => $cliente_dominio->id(),
                    "nombre" => $cliente_dominio->nombre(),
                    "dni" => $cliente_dominio->documento(),
                    "tipo_documento" => $cliente_dominio->tipoDocumento(),
                    "condicion_iva" => $cliente_dominio->condicionIva(),
                    "lista_precio_nombre" => $cliente_dominio->listaPrecioNombre(),
                    "email" => $cliente_dominio->email(),
                    "telefono" => $cliente_dominio->telefono(),
                    "direccion" => $cliente_dominio->direccion(),
                    "creado_en" => $cliente_dominio->creadoEn(),
                ];
            }
            usort($clientes, function (array $a, array $b) use ($orden_clientes): int {
                $campo = (string)($orden_clientes["campo"] ?? "nombre");
                $direccion = strtoupper((string)($orden_clientes["direccion"] ?? "ASC"));
                $columnas = [
                    "nombre" => "nombre",
                    "cliente" => "nombre",
                    "descripcion" => "nombre",
                    "codigo" => "dni",
                    "codigo_barras" => "dni",
                    "fecha" => "creado_en",
                ];
                $columna = $columnas[$campo] ?? "nombre";
                $comparacion = strcasecmp((string)($a[$columna] ?? ""), (string)($b[$columna] ?? ""));

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
            $c = ["id" => 0, "nombre" => "", "dni" => "", "tipo_documento" => "DNI", "condicion_iva" => "Consumidor Final", "email" => "", "telefono" => "", "direccion" => "", "id_lista_precio" => $this->id_lista_precio_predeterminada()];
            $listas_precios = $this->listar_listas_precios_activas();
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
                    $id_lista_precio = (int)obtener_post("id_lista_precio", $this->id_lista_precio_predeterminada());
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
                        global $container;
                        $clienteRepository = $container->get(\Ventas\Clientes\Domain\Repositorios\ClienteRepository::class);
                        if ($dni_db !== null && $clienteRepository->existeDocumento($dni_db, 0)) {
                            $error = "El documento ya existe.";
                        } else {
                            $ok = false;
                            try {
                                $crearCliente = $container->get(\Ventas\Clientes\Application\CrearCliente::class);
                                $crearCliente->ejecutar(new \Ventas\Clientes\Domain\Entidades\Cliente(null, $nombre, $dni_db, $tipo_documento, $condicion_iva, $email_db, $tel_db, $dir_db, $id_lista_precio > 0 ? $id_lista_precio : null));
                                $ok = true;
                            } catch (Throwable $e) {
                                registrar_log("ControladorClientes::crear", $e->getMessage());
                            }
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
                global $container;
                $buscarClientePorId = $container->get(\Ventas\Clientes\Application\BuscarClientePorId::class);
                $cliente_dominio = $buscarClientePorId->ejecutar($id);
                if ($cliente_dominio === null) {
                    flash_error("Cliente no encontrado.");
                    redirigir("index.php?c=clientes&a=index");
                } else {
                    $c = [
                        "id" => $cliente_dominio->id(),
                        "nombre" => $cliente_dominio->nombre(),
                        "dni" => $cliente_dominio->documento(),
                        "tipo_documento" => $cliente_dominio->tipoDocumento(),
                        "condicion_iva" => $cliente_dominio->condicionIva(),
                        "email" => $cliente_dominio->email(),
                        "telefono" => $cliente_dominio->telefono(),
                        "direccion" => $cliente_dominio->direccion(),
                        "id_lista_precio" => $cliente_dominio->idListaPrecio(),
                        "creado_en" => $cliente_dominio->creadoEn(),
                    ];
                    $modo = "editar";
                    $listas_precios = $this->listar_listas_precios_activas();
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
                        global $container;
                        $buscarClientePorId = $container->get(\Ventas\Clientes\Application\BuscarClientePorId::class);
                        $c_actual = $buscarClientePorId->ejecutar($id);
                        if ($c_actual === null) {
                            $error = "Cliente no encontrado.";
                        } else {
                            $nombre = trim((string)obtener_post("nombre", ""));
                            $dni = trim((string)obtener_post("dni", ""));
                            $tipo_documento = trim((string)obtener_post("tipo_documento", "DNI"));
                            $condicion_iva = trim((string)obtener_post("condicion_iva", "Consumidor Final"));
                            $email = trim((string)obtener_post("email", ""));
                            $id_lista_precio = (int)obtener_post("id_lista_precio", $this->id_lista_precio_predeterminada());
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

                                $clienteRepository = $container->get(\Ventas\Clientes\Domain\Repositorios\ClienteRepository::class);
                                if ($dni_db !== null && $clienteRepository->existeDocumento($dni_db, $id)) {
                                    $error = "El documento ya existe.";
                                } else {
                                    $ok = false;
                                    try {
                                        $actualizarCliente = $container->get(\Ventas\Clientes\Application\ActualizarCliente::class);
                                        $actualizarCliente->ejecutar(new \Ventas\Clientes\Domain\Entidades\Cliente($id, $nombre, $dni_db, $tipo_documento, $condicion_iva, $email_db, $tel_db, $dir_db, $id_lista_precio > 0 ? $id_lista_precio : null));
                                        $ok = true;
                                    } catch (Throwable $e) {
                                        registrar_log("ControladorClientes::actualizar", $e->getMessage());
                                    }
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
                global $container;
                $buscarClientePorId = $container->get(\Ventas\Clientes\Application\BuscarClientePorId::class);
                $c = $buscarClientePorId->ejecutar($id);
                if ($c === null) {
                    flash_error("Cliente no encontrado.");
                    redirigir("index.php?c=clientes&a=index");
                } else {
                    $eliminarCliente = $container->get(\Ventas\Clientes\Application\EliminarCliente::class);
                    try {
                        $eliminarCliente->ejecutar($id);
                        flash_ok("Cliente eliminado.");
                        redirigir("index.php?c=clientes&a=index");
                    } catch (\Ventas\Clientes\Domain\Excepciones\ClienteConVentasException $e) {
                        flash_error("No se puede eliminar: el cliente tiene ventas asociadas.");
                        redirigir("index.php?c=clientes&a=index");
                    } catch (\Ventas\Clientes\Domain\Excepciones\ClienteProtegidoException $e) {
                        flash_error("Consumidor Final (ID=1) no se puede eliminar.");
                        redirigir("index.php?c=clientes&a=index");
                    } catch (\Ventas\Clientes\Domain\Excepciones\ClienteNoEncontradoException $e) {
                        flash_error("Cliente no encontrado.");
                        redirigir("index.php?c=clientes&a=index");
                    } catch (Throwable $e) {
                        registrar_log("ControladorClientes::eliminar", $e->getMessage());
                        flash_error("No se pudo eliminar (ver logs).");
                        redirigir("index.php?c=clientes&a=index");
                    }
                }
            }
        }
    }
}
