<?php
$modulos = $modulos ?? [];
?>
<section class="home-hero mb-4">
  <div class="home-hero-content">
    <div>
      <div class="hero-brand mb-3">
        <span class="hero-brand-mark">VEN</span>
        <div class="hero-brand-text">
          <strong>Ventas</strong>
          <small>Sistema de gestion</small>
        </div>
      </div>
      <h1 class="home-title">Panel principal</h1>
      <p class="home-subtitle">Entra al modulo que necesitas desde un panel simple, visual y facil de entender.</p>
    </div>
    <div class="home-badge">
      <span>Acceso rapido</span>
    </div>
  </div>
</section>

<section class="desktop-grid">
  <?php foreach ($modulos as $modulo): ?>
    <a class="desktop-tile <?= htmlspecialchars($modulo["clase"]) ?>" href="<?= htmlspecialchars($modulo["url"]) ?>">
      <div class="desktop-icon">
        <?php if (($modulo["clase"] ?? "") === "modulo-exportaciones"): ?>
          <span class="export-module-mark" aria-hidden="true">
            <span></span><span></span><span></span>
          </span>
        <?php else: ?>
          <i class="bi <?= htmlspecialchars($modulo["icono"]) ?>"></i>
        <?php endif; ?>
      </div>
      <div class="desktop-title"><?= htmlspecialchars($modulo["titulo"]) ?></div>
      <div class="desktop-text"><?= htmlspecialchars($modulo["texto"]) ?></div>
    </a>
  <?php endforeach; ?>
</section>
