<?php
$clientes_json = [];
foreach (($clientes ?? []) as $cliente) {
  $texto = (string)($cliente["nombre"] ?? "");
  if ((int)($cliente["id"] ?? 0) === 1 && $texto === "")
    $texto = "Consumidor Final";
  $tipo_doc = (string)($cliente["tipo_documento"] ?? "DNI");
  if (!empty($cliente["dni"]))
    $texto .= " - " . $tipo_doc . ": " . (string)$cliente["dni"];
  $clientes_json[] = [
    "id" => (int)($cliente["id"] ?? 0),
    "texto" => $texto,
    "dni" => (string)($cliente["dni"] ?? ""),
    "tipo_documento" => $tipo_doc,
    "condicion_iva" => (string)($cliente["condicion_iva"] ?? ""),
  ];
}
?>
<div class="d-flex justify-content-between align-items-center mb-3 section-heading">
  <div>
    <h3 class="mb-1">Recibo de anticipo</h3>
    <div class="text-muted small">Registra plata entregada por el cliente para usar como saldo a favor.</div>
  </div>
  <a class="btn btn-outline-secondary" href="index.php?c=cuentas_corrientes&a=index">Volver</a>
</div>

<div class="card form-shell mb-3">
  <div class="card-body p-4">
    <form method="POST" action="index.php?c=cuentas_corrientes&a=generar_anticipo" class="smart-form" id="cc-anticipo-form" target="_blank">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <input type="hidden" name="imprimir" value="0" id="cc-anticipo-imprimir">
      <div class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Cliente</label>
          <input id="anticipoClienteInput" class="form-control" list="anticipoClientesAuto" placeholder="Escribi nombre o DNI..." autocomplete="off" required>
          <input type="hidden" name="id_cliente" id="anticipoClienteId" value="">
          <div class="sales-client-panel d-none" id="anticipoClientePanel">
            <div id="anticipoClientePanelSelect" class="sales-client-list" role="listbox" aria-label="Lista de clientes">
              <?php foreach (($clientes ?? []) as $cliente): ?>
                <?php
                  $texto_doc = "";
                  if (!empty($cliente["dni"]))
                    $texto_doc = (string)($cliente["tipo_documento"] ?? "DNI") . ": " . (string)$cliente["dni"];
                ?>
                <button type="button" class="sales-client-option" data-id="<?= (int)$cliente["id"] ?>">
                  <strong><?= htmlspecialchars((string)$cliente["nombre"]) ?></strong>
                  <span><?= htmlspecialchars(trim($texto_doc . " " . (string)($cliente["condicion_iva"] ?? ""))) ?></span>
                </button>
              <?php endforeach; ?>
            </div>
          </div>
          <datalist id="anticipoClientesAuto">
            <?php foreach ($clientes_json as $cliente_auto): ?>
              <option value="<?= htmlspecialchars((string)$cliente_auto["texto"]) ?>"></option>
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="col-md-2">
          <label class="form-label">Monto</label>
          <input type="number" step="0.01" min="0.01" class="form-control" name="monto" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Forma de pago</label>
          <select class="form-select" name="forma_pago">
            <option value="contado">Contado</option>
            <option value="transferencia">Transferencia</option>
            <option value="tarjeta">Tarjeta</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Observacion</label>
          <input class="form-control" name="observacion" placeholder="Entrega adelantada, seña, etc.">
        </div>
        <div class="col-md-2">
          <button class="btn btn-outline-primary w-100" type="submit" data-imprimir="0">Generar</button>
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary w-100" type="submit" data-imprimir="1">Generar e imprimir</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const input = document.getElementById("cc-anticipo-imprimir");
  const clienteInput = document.getElementById("anticipoClienteInput");
  const clienteId = document.getElementById("anticipoClienteId");
  const clientePanel = document.getElementById("anticipoClientePanel");
  const clientePanelSelect = document.getElementById("anticipoClientePanelSelect");
  const clientesData = <?= json_encode($clientes_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  const panelHtml = clientePanelSelect ? clientePanelSelect.innerHTML : "";

  function textoClientePorId(id) {
    for (let i = 0; i < clientesData.length; i++) {
      if (String(clientesData[i].id) === String(id))
        return clientesData[i].texto;
    }
    return "";
  }

  function idClientePorTexto(texto) {
    const normalizado = (texto || "").toLowerCase().trim();
    if (normalizado === "")
      return "";
    for (let i = 0; i < clientesData.length; i++) {
      if ((clientesData[i].texto || "").toLowerCase() === normalizado)
        return String(clientesData[i].id);
    }
    return "";
  }

  function activarCliente(id) {
    if (!clientePanelSelect)
      return;
    clientePanelSelect.querySelectorAll(".sales-client-option").forEach(function (btn) {
      btn.classList.toggle("active", String(btn.dataset.id || "") === String(id));
    });
  }

  function seleccionarCliente(id) {
    if (!clienteId || !clienteInput)
      return;
    clienteId.value = String(id || "");
    clienteInput.value = textoClientePorId(id);
    activarCliente(id);
  }

  function enlazarBotonesCliente() {
    if (!clientePanelSelect)
      return;
    clientePanelSelect.querySelectorAll(".sales-client-option").forEach(function (btn) {
      if (btn.dataset.bound === "1")
        return;
      btn.dataset.bound = "1";
      btn.addEventListener("click", function () {
        seleccionarCliente(btn.dataset.id || "");
        if (clientePanel)
          clientePanel.classList.add("d-none");
      });
    });
  }

  function filtrarClientes(textoEntrada) {
    if (!clientePanelSelect)
      return;
    const texto = (textoEntrada || "").toLowerCase().trim();
    if (texto === "") {
      clientePanelSelect.innerHTML = panelHtml;
    } else {
      const temp = document.createElement("div");
      temp.innerHTML = panelHtml;
      clientePanelSelect.innerHTML = "";
      temp.querySelectorAll(".sales-client-option").forEach(function (op) {
        const contenido = (op.textContent || "").toLowerCase();
        if (contenido.includes(texto))
          clientePanelSelect.appendChild(op);
      });
    }
    activarCliente(clienteId ? clienteId.value : "");
    enlazarBotonesCliente();
  }

  function aplicarClienteDesdeTexto() {
    if (!clienteInput || !clienteId)
      return false;
    const id = idClientePorTexto(clienteInput.value);
    if (id !== "") {
      clienteId.value = id;
      activarCliente(id);
      return true;
    }
    return false;
  }

  if (clienteInput) {
    clienteInput.addEventListener("focus", function () {
      filtrarClientes(clienteInput.value);
      if (clientePanel)
        clientePanel.classList.remove("d-none");
    });
    clienteInput.addEventListener("click", function () {
      filtrarClientes(clienteInput.value);
      if (clientePanel)
        clientePanel.classList.remove("d-none");
    });
    clienteInput.addEventListener("input", function () {
      filtrarClientes(clienteInput.value);
      aplicarClienteDesdeTexto();
      if (clientePanel)
        clientePanel.classList.remove("d-none");
    });
    clienteInput.addEventListener("change", function () {
      if (!aplicarClienteDesdeTexto() && clienteId)
        clienteId.value = "";
    });
    clienteInput.addEventListener("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        if (!aplicarClienteDesdeTexto() && clientePanelSelect && clientePanelSelect.querySelectorAll(".sales-client-option").length === 1) {
          const unico = clientePanelSelect.querySelector(".sales-client-option");
          seleccionarCliente(unico.dataset.id || "");
          if (clientePanel)
            clientePanel.classList.add("d-none");
        }
      }
    });
  }

  const form = document.getElementById("cc-anticipo-form");
  if (form) {
    form.addEventListener("submit", function (e) {
      if (!aplicarClienteDesdeTexto() && (!clienteId || clienteId.value === "")) {
        e.preventDefault();
        alert("Selecciona un cliente de la lista.");
      }
    });
  }

  document.addEventListener("click", function (e) {
    if (clientePanel && !clientePanel.classList.contains("d-none")) {
      if (!(clientePanel.contains(e.target) || (clienteInput && clienteInput.contains(e.target))))
        clientePanel.classList.add("d-none");
    }
  });

  enlazarBotonesCliente();
  document.querySelectorAll("[data-imprimir]").forEach(function (boton) {
    boton.addEventListener("click", function () {
      if (input)
        input.value = boton.dataset.imprimir || "0";
      const clienteOk = aplicarClienteDesdeTexto() || (clienteId && clienteId.value !== "");
      if (!clienteOk || (form && !form.checkValidity()))
        return;
      setTimeout(function () {
        window.location.href = "index.php?c=cuentas_corrientes&a=index";
      }, 600);
    });
  });
});
</script>
