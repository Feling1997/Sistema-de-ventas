<?php
$config = $config ?? [];
$opciones_reparaciones = $opciones_reparaciones ?? [];

function cfg_valor(array $config, string $clave): string {
    return htmlspecialchars((string)($config[$clave] ?? ""));
}

function cfg_check(array $config, string $clave, string $defecto = "0"): string {
    $valor = (string)($config[$clave] ?? $defecto);
    return $valor === "1" ? "checked" : "";
}

// Opciones predeterminadas del menú de reparaciones
$opciones_default = [
    "consultar_reparaciones" => ["titulo" => "Consultar reparaciones", "descripcion" => "Ver todas las reparaciones registradas"],
    "nueva_reparacion" => ["titulo" => "Nueva reparación", "descripcion" => "Crear una nueva reparación"],
    "mis_reparaciones" => ["titulo" => "Mis reparaciones", "descripcion" => "Ver tus reparaciones activas"],
    "historial_reparaciones" => ["titulo" => "Historial", "descripcion" => "Ver reparaciones completadas"],
    "reportes_reparaciones" => ["titulo" => "Reportes", "descripcion" => "Estadísticas y análisis"],
    "seguimiento_tickets" => ["titulo" => "Seguimiento de tickets", "descripcion" => "Rastrear estado de tickets"],
];

// Usar configuración guardada o predeterminados
if (empty($opciones_reparaciones)) {
    $opciones_reparaciones = $opciones_default;
}

$mostrar_reparaciones = (string)($config["mostrar_reparaciones"] ?? "1");
$atajo_reparaciones = cfg_valor($config, "atajo_reparaciones");
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h3 class="mb-1">Configuración de Reparaciones</h3>
    <div class="text-muted small">Configura el módulo de reparaciones, opciones del menú y acceso rápido.</div>
  </div>
  <a class="btn btn-outline-secondary" href="index.php?c=ventas&a=inicio">Volver</a>
</div>

<form method="POST" action="index.php?c=configuraciones&a=guardar_reparaciones">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

  <!-- SECCIÓN 1: OPCIONES DEL MENÚ -->
  <div class="card form-shell mb-3">
    <div class="card-body p-4">
      <h4 class="mb-1">Opciones del menú de reparaciones</h4>
      <div class="text-muted small mb-4">Selecciona qué opciones aparecerán en el menú principal de reparaciones.</div>

      <div class="row g-3">
        <?php foreach ($opciones_default as $opcion_id => $opcion_info): ?>
          <?php 
            $checked = isset($opciones_reparaciones[$opcion_id]) && $opciones_reparaciones[$opcion_id] === "1";
          ?>
          <div class="col-md-6">
            <div class="card border-0 bg-light">
              <div class="card-body p-3">
                <div class="form-check mb-2">
                  <input 
                    class="form-check-input" 
                    type="checkbox" 
                    id="<?= htmlspecialchars($opcion_id) ?>" 
                    name="opciones_reparaciones[<?= htmlspecialchars($opcion_id) ?>]" 
                    value="1"
                    <?= $checked ? "checked" : "" ?>
                  >
                  <label class="form-check-label fw-500" for="<?= htmlspecialchars($opcion_id) ?>">
                    <?= htmlspecialchars($opcion_info["titulo"]) ?>
                  </label>
                </div>
                <small class="text-muted d-block">
                  <?= htmlspecialchars($opcion_info["descripcion"]) ?>
                </small>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <hr class="my-4">

      <h5 class="mb-3">Opciones personalizadas</h5>
      <div class="text-muted small mb-3">Agrega opciones adicionales separadas por saltos de línea. Formato: "titulo|descripcion|url_opcion"</div>
      
      <div class="mb-3">
        <label class="form-label">Opciones adicionales</label>
        <textarea 
          class="form-control" 
          name="opciones_reparaciones_custom" 
          rows="4" 
          placeholder="Ejemplo:&#10;Ver estadísticas|Gráficos de reparaciones|?c=reparaciones&a=estadisticas&#10;Clientes frecuentes|Clientes con más reparaciones|?c=reparaciones&a=clientes_frecuentes"
        ><?= htmlspecialchars((string)($config["opciones_reparaciones_custom"] ?? "")) ?></textarea>
      </div>
    </div>
  </div>

  <!-- SECCIÓN 2: CONFIGURACIÓN GENERAL -->
  <div class="card form-shell mb-3">
    <div class="card-body p-4">
      <h4 class="mb-1">Configuración general</h4>
      <div class="text-muted small mb-4">Acceso, visibilidad y comportamiento del módulo de reparaciones.</div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Mostrar módulo de reparaciones</label>
          <select class="form-select" name="mostrar_reparaciones">
            <option value="1" <?= $mostrar_reparaciones === "1" ? "selected" : "" ?>>Sí, mostrar en el menú</option>
            <option value="0" <?= $mostrar_reparaciones === "0" ? "selected" : "" ?>>No, ocultar</option>
          </select>
          <small class="text-muted d-block mt-2">Si lo ocultas, no aparecerá en la barra de navegación.</small>
        </div>

        <div class="col-md-6">
          <label class="form-label">Atajo de teclado rápido</label>
          <input 
            class="form-control" 
            name="atajo_reparaciones" 
            value="<?= $atajo_reparaciones ?>" 
            placeholder="Ej: F9, Ctrl+R, Alt+J"
            maxlength="20"
          >
          <small class="text-muted d-block mt-2">Tecla para abrir reparaciones rápidamente (ej: F9).</small>
        </div>

        <div class="col-md-6">
          <label class="form-label">URL del módulo de reparaciones</label>
          <input 
            class="form-control" 
            name="url_reparaciones" 
            value="<?= cfg_valor($config, "url_reparaciones") ?>"
            placeholder="Ej: index.php?c=reparaciones&a=index"
          >
          <small class="text-muted d-block mt-2">URL a la que dirigirse al acceder a reparaciones.</small>
        </div>

        <div class="col-md-6">
          <label class="form-label">Página de inicio de reparaciones</label>
          <select class="form-select" name="pagina_inicio_reparaciones">
            <option value="lista" <?= ((string)($config["pagina_inicio_reparaciones"] ?? "lista") === "lista") ? "selected" : "" ?>>
              Lista de reparaciones
            </option>
            <option value="nueva" <?= ((string)($config["pagina_inicio_reparaciones"] ?? "lista") === "nueva") ? "selected" : "" ?>>
              Crear nueva reparación
            </option>
            <option value="resumen" <?= ((string)($config["pagina_inicio_reparaciones"] ?? "lista") === "resumen") ? "selected" : "" ?>>
              Resumen/Dashboard
            </option>
          </select>
          <small class="text-muted d-block mt-2">Página que aparece al ingresar al módulo.</small>
        </div>

        <div class="col-md-6">
          <label class="form-label">Permitir crear reparaciones</label>
          <select class="form-select" name="crear_reparaciones_habilitado">
            <option value="1" <?= ((string)($config["crear_reparaciones_habilitado"] ?? "1") === "1") ? "selected" : "" ?>>Sí</option>
            <option value="0" <?= ((string)($config["crear_reparaciones_habilitado"] ?? "1") === "0") ? "selected" : "" ?>>No, solo ver</option>
          </select>
          <small class="text-muted d-block mt-2">Controla si los usuarios pueden crear nuevas reparaciones.</small>
        </div>

        <div class="col-md-6">
          <label class="form-label">Permitir editar reparaciones</label>
          <select class="form-select" name="editar_reparaciones_habilitado">
            <option value="1" <?= ((string)($config["editar_reparaciones_habilitado"] ?? "1") === "1") ? "selected" : "" ?>>Sí</option>
            <option value="0" <?= ((string)($config["editar_reparaciones_habilitado"] ?? "1") === "0") ? "selected" : "" ?>>No, solo Admin</option>
          </select>
          <small class="text-muted d-block mt-2">Controla quién puede modificar reparaciones existentes.</small>
        </div>

        <div class="col-12">
          <label class="form-label">Mostrar campos en lista de reparaciones</label>
          <div class="row g-2">
            <div class="col-md-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="campo_cliente" name="campos_lista_reparaciones[cliente]" value="1" <?= cfg_check($config, "campos_lista_reparaciones_cliente") ?>>
                <label class="form-check-label" for="campo_cliente">Cliente</label>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="campo_equipo" name="campos_lista_reparaciones[equipo]" value="1" <?= cfg_check($config, "campos_lista_reparaciones_equipo") ?>>
                <label class="form-check-label" for="campo_equipo">Equipo</label>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="campo_estado" name="campos_lista_reparaciones[estado]" value="1" <?= cfg_check($config, "campos_lista_reparaciones_estado", "1") ?>>
                <label class="form-check-label" for="campo_estado">Estado</label>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="campo_fecha" name="campos_lista_reparaciones[fecha]" value="1" <?= cfg_check($config, "campos_lista_reparaciones_fecha", "1") ?>>
                <label class="form-check-label" for="campo_fecha">Fecha</label>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="campo_tecnico" name="campos_lista_reparaciones[tecnico]" value="1" <?= cfg_check($config, "campos_lista_reparaciones_tecnico") ?>>
                <label class="form-check-label" for="campo_tecnico">Técnico asignado</label>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="campo_presupuesto" name="campos_lista_reparaciones[presupuesto]" value="1" <?= cfg_check($config, "campos_lista_reparaciones_presupuesto") ?>>
                <label class="form-check-label" for="campo_presupuesto">Presupuesto</label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex gap-2 justify-content-end">
    <a class="btn btn-outline-secondary" href="index.php?c=ventas&a=inicio">Cancelar</a>
    <button type="submit" class="btn btn-primary">
      <i class="bi bi-check2"></i> Guardar configuración
    </button>
  </div>
</form>

<style>
  .fw-500 {
    font-weight: 500;
  }
  
  .card.border-0.bg-light {
    transition: all 0.2s ease;
  }
  
  .form-check-input:checked ~ .fw-500 {
    color: #1f6f8b;
  }
</style>
