<section class="config-panel" data-panel="inicio">
  <div class="config-panel-head">
    <div>
      <h4>Configuracion</h4>
      <p>Elegir una categoria no recarga la pagina. El panel se abre en esta misma pantalla.</p>
    </div>
  </div>
  <div class="config-card-grid">
    <?php foreach ($cards_inicio as $clave => $card): ?>
      <button class="config-big-card" type="button" data-open-panel="<?= htmlspecialchars($clave) ?>">
        <div class="icon-circle"><i class="bi <?= htmlspecialchars($card["icono"]) ?>"></i></div>
        <strong><?= htmlspecialchars($card["titulo"]) ?></strong>
        <span><?= htmlspecialchars($card["texto"]) ?></span>
        <small><?= htmlspecialchars($card["detalle"]) ?></small>
      </button>
    <?php endforeach; ?>
  </div>
</section>
