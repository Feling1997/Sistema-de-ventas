<div class="container mt-5" style="max-width:420px;">
  <h3 class="mb-3">Iniciar sesión</h3>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="index.php?c=auth&a=login">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

    <div class="mb-3">
      <label class="form-label">Usuario</label>
      <input class="form-control" name="usuario" placeholder="Ingresar usuario">
    </div>

    <div class="mb-3">
      <label class="form-label">Contraseña</label>
      <input type="password" class="form-control" name="clave" placeholder="Ingresar contraseña">
    </div>

    <button class="btn btn-primary w-100">Entrar</button>
  </form>
</div>
