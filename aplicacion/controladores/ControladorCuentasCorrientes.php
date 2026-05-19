<?php
require_once __DIR__ . "/../modelos/CuentaCorriente.php";
require_once __DIR__ . "/../modelos/Cliente.php";
require_once __DIR__ . "/../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/../../configuraciones/csrf.php";

class ControladorCuentasCorrientes {
    private function permiso(): bool {
        if (!require_login()) {
            flash_error("Tenes que iniciar sesion.");
            redirigir("index.php?c=auth&a=login");
        }
        if (!require_rol(["ADMIN","VENDEDOR"])) {
            flash_error("No tenes permisos para cuenta corriente.");
            redirigir("index.php?c=ventas&a=inicio");
        }
        return true;
    }

    public function index(): void {
        if ($this->permiso()) {
            $buscar = trim((string)obtener_get("buscar", ""));
            $estado = strtolower(trim((string)obtener_get("estado", "todos")));
            $orden = strtolower(trim((string)obtener_get("orden", "vencimiento")));
            if (!in_array($estado, ["todos", "vencidos", "proximos"], true))
                $estado = "todos";
            if (!in_array($orden, ["vencimiento", "cliente", "saldo", "estado"], true))
                $orden = "vencimiento";
            $cuotas = CuentaCorriente::cuotas_pendientes_detalle($buscar, $estado, $orden);
            $vencidas = array_values(array_filter($cuotas, fn($q) => (int)($q["vencida"] ?? 0) === 1));
            $proximas = array_values(array_filter($cuotas, fn($q) => (int)($q["vencida"] ?? 0) !== 1));
            $resumen = CuentaCorriente::resumen_general();
            $recibos = CuentaCorriente::listar_recibos(50);
            $saldos_favor = CuentaCorriente::saldos_favor_clientes();
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/cuentas_corrientes/index.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function pagar_cuota(): void {
        if ($this->permiso()) {
            $id = (int)obtener_get("id", 0);
            CuentaCorriente::marcar_cuota_pagada($id)
                ? flash_ok("Cuota marcada como pagada.")
                : flash_error("No se pudo pagar la cuota.");
            redirigir("index.php?c=cuentas_corrientes&a=index");
        }
    }

    public function cancelar(): void {
        if ($this->permiso()) {
            $id = (int)obtener_get("id", 0);
            CuentaCorriente::cancelar($id)
                ? flash_ok("Cuenta corriente cancelada.")
                : flash_error("No se pudo cancelar la cuenta.");
            redirigir("index.php?c=cuentas_corrientes&a=index");
        }
    }

    public function marcar_alertas_leidas(): void {
        if ($this->permiso()) {
            if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valido(obtener_post("csrf", ""))) {
                flash_error("Acceso invalido.");
                redirigir("index.php?c=cuentas_corrientes&a=index");
            }
            $id_usuario = (int)($_SESSION["usuario_logueado"]["id"] ?? 0);
            CuentaCorriente::marcar_alertas_leidas($id_usuario)
                ? flash_ok("Alertas marcadas como leidas.")
                : flash_error("No se pudieron marcar las alertas.");
            redirigir("index.php?c=cuentas_corrientes&a=index");
        }
    }

    public function recibo(): void {
        if ($this->permiso()) {
            $id = (int)obtener_get("id", 0);
            $cuenta = CuentaCorriente::buscar_cuenta($id);
            if ($cuenta === null || (float)$cuenta["saldo"] <= 0) {
                flash_error("Cuenta sin deuda pendiente.");
                redirigir("index.php?c=cuentas_corrientes&a=index");
            }
            $cuotas = CuentaCorriente::cuotas_pendientes($id);
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/cuentas_corrientes/recibo.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function anticipo(): void {
        if ($this->permiso()) {
            $clientes = Cliente::listar_todos();
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/cuentas_corrientes/anticipo.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function generar_anticipo(): void {
        if ($this->permiso()) {
            if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valido(obtener_post("csrf", ""))) {
                flash_error("Acceso invalido.");
                redirigir("index.php?c=cuentas_corrientes&a=index");
                return;
            }
            $id_cliente = (int)obtener_post("id_cliente", 0);
            $monto = parsear_numero_form(obtener_post("monto", 0), 0);
            $forma_pago = trim((string)obtener_post("forma_pago", "contado"));
            $observacion = trim((string)obtener_post("observacion", ""));
            if ($id_cliente <= 0 || Cliente::buscar_por_id($id_cliente) === null) {
                flash_error("Selecciona un cliente valido.");
                redirigir("index.php?c=cuentas_corrientes&a=anticipo");
                return;
            }
            if ($monto <= 0) {
                flash_error("El monto debe ser mayor a cero.");
                redirigir("index.php?c=cuentas_corrientes&a=anticipo");
                return;
            }
            $id_recibo = CuentaCorriente::registrar_anticipo($id_cliente, $monto, $forma_pago, $observacion);
            if ($id_recibo > 0) {
                $imprimir = (int)obtener_post("imprimir", 0) === 1 ? "&imprimir=1" : "";
                redirigir("index.php?c=cuentas_corrientes&a=ver_recibo&id=" . $id_recibo . $imprimir);
                return;
            }
            flash_error("No se pudo generar el recibo de anticipo.");
            redirigir("index.php?c=cuentas_corrientes&a=anticipo");
        }
    }

    public function generar_recibo(): void {
        if ($this->permiso()) {
            if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valido(obtener_post("csrf", ""))) {
                flash_error("Acceso invalido.");
                redirigir("index.php?c=cuentas_corrientes&a=index");
            }
            $id_cuenta = (int)obtener_post("id_cuenta", 0);
            $monto = parsear_numero_form(obtener_post("monto", 0), 0);
            $forma_pago = trim((string)obtener_post("forma_pago", "contado"));
            $observacion = trim((string)obtener_post("observacion", ""));
            $cuotas = $_POST["cuotas"] ?? [];
            if (!is_array($cuotas))
                $cuotas = [];
            $ids_cuotas = array_map("intval", $cuotas);
            $cuenta = CuentaCorriente::buscar_cuenta($id_cuenta);
            if ($cuenta === null || (float)$cuenta["saldo"] <= 0) {
                flash_error("Cuenta sin deuda pendiente.");
                redirigir("index.php?c=cuentas_corrientes&a=index");
                return;
            }
            $saldo = round((float)$cuenta["saldo"], 2);
            if ($monto > $saldo + 0.00001) {
                flash_error("El monto del recibo no puede superar el saldo pendiente de " . moneda_para_mostrar($saldo) . ".");
                redirigir("index.php?c=cuentas_corrientes&a=recibo&id=" . $id_cuenta);
                return;
            }
            $pendiente_cuotas = 0.0;
            $ids_seleccionadas = array_flip(array_filter($ids_cuotas, fn($id) => $id > 0));
            foreach (CuentaCorriente::cuotas_pendientes($id_cuenta) as $cuota) {
                if (count($ids_seleccionadas) > 0 && !isset($ids_seleccionadas[(int)$cuota["id"]]))
                    continue;
                $pendiente_cuotas += max(0, (float)($cuota["pendiente"] ?? 0));
            }
            $pendiente_cuotas = round($pendiente_cuotas, 2);
            if ($monto > $pendiente_cuotas + 0.00001) {
                flash_error("El monto del recibo no puede superar el pendiente de las cuotas seleccionadas: " . moneda_para_mostrar($pendiente_cuotas) . ".");
                redirigir("index.php?c=cuentas_corrientes&a=recibo&id=" . $id_cuenta);
                return;
            }
            $id_recibo = CuentaCorriente::registrar_pago($id_cuenta, $monto, $ids_cuotas, $forma_pago, $observacion);
            if ($id_recibo > 0) {
                $imprimir = (int)obtener_post("imprimir", 0) === 1 ? "&imprimir=1" : "";
                redirigir("index.php?c=cuentas_corrientes&a=ver_recibo&id=" . $id_recibo . $imprimir);
                return;
            }
            flash_error("No se pudo generar el recibo.");
            redirigir("index.php?c=cuentas_corrientes&a=recibo&id=" . $id_cuenta);
        }
    }

    public function ver_recibo(): void {
        if ($this->permiso()) {
            $id = (int)obtener_get("id", 0);
            $recibo = CuentaCorriente::buscar_recibo($id);
            if ($recibo === null) {
                flash_error("Recibo no encontrado.");
                redirigir("index.php?c=cuentas_corrientes&a=index");
            }
            echo $this->html_recibo($recibo, (int)obtener_get("imprimir", 0) === 1);
        }
    }

    private function html_recibo(array $recibo, bool $auto_imprimir = false): string {
        $id = (int)$recibo["id"];
        $cliente = htmlspecialchars((string)$recibo["cliente_nombre"]);
        $doc = htmlspecialchars((string)($recibo["cliente_documento"] ?? ""));
        $concepto = htmlspecialchars((string)$recibo["concepto"]);
        $fecha = htmlspecialchars((string)$recibo["fecha"]);
        $monto = htmlspecialchars(moneda_para_mostrar($recibo["monto"] ?? 0));
        $forma = htmlspecialchars((string)$recibo["forma_pago"]);
        $obs = htmlspecialchars((string)$recibo["observacion"]);
        $tipo = (string)($recibo["tipo"] ?? "PAGO_CUENTA");
        $titulo_importe = $tipo === "ANTICIPO" ? "Entrega a favor" : ($tipo === "APLICACION" ? "Saldo a favor aplicado" : "Importe recibido");
        $auto_print = $auto_imprimir ? "<script>window.addEventListener('load', function(){ window.print(); });</script>" : "";
        return "<!doctype html><html lang='es'><head><meta charset='utf-8'><title>Recibo #$id</title><style>body{font-family:Arial,sans-serif;margin:24px;color:#111}.box{max-width:720px;border:1px solid #ddd;padding:22px}.row{display:flex;justify-content:space-between;border-bottom:1px solid #eee;padding:8px 0}.total{font-size:22px;font-weight:800}.actions{margin-bottom:14px;display:flex;gap:8px}button,a{border:0;border-radius:6px;padding:8px 12px;background:#0e7490;color:white;font-weight:700;cursor:pointer;text-decoration:none;font-family:Arial,sans-serif;font-size:14px}.secondary{background:#64748b}@media print{.actions{display:none}body{margin:0}.box{border:0;max-width:none}}</style></head><body><div class='actions'><button type='button' onclick='window.print()'>Imprimir para cliente</button><a class='secondary' href='index.php?c=cuentas_corrientes&a=index'>Volver</a></div><div class='box'><h2>Recibo #$id</h2><div class='row'><span>Fecha</span><strong>$fecha</strong></div><div class='row'><span>Cliente</span><strong>$cliente</strong></div><div class='row'><span>Documento</span><strong>$doc</strong></div><div class='row'><span>Concepto</span><strong>$concepto</strong></div><div class='row'><span>Forma de pago</span><strong>$forma</strong></div><div class='row total'><span>$titulo_importe</span><strong>$monto</strong></div>" . ($obs !== "" ? "<p><b>Observacion:</b> $obs</p>" : "") . "</div>$auto_print</body></html>";
    }
}
