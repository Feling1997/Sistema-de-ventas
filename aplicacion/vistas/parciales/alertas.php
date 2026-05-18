<?php
$flash_ok = "";
$flash_error = "";

if (isset($_SESSION["flash_ok"])) {
    $flash_ok = (string)$_SESSION["flash_ok"];
    unset($_SESSION["flash_ok"]);
}
if (isset($_SESSION["flash_error"])) {
    $flash_error = (string)$_SESSION["flash_error"];
    unset($_SESSION["flash_error"]);
}
?>
<?php if ($flash_ok !== ""): ?>
  <div class="alert alert-success"><?= htmlspecialchars($flash_ok) ?></div>
<?php endif; ?>
<?php if ($flash_error !== ""): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($flash_error) ?></div>
<?php endif; ?>
