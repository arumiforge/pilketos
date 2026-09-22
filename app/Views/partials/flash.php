<?php
$error   = session()->getFlashdata('error');
$success = session()->getFlashdata('success');
$errors  = session()->getFlashdata('errors');
?>
<?php if ($error || $success || $errors): ?>
<div class="container flash-stack">
  <?php if ($error): ?>
    <div class="alert alert--error" role="alert"><?= esc($error) ?></div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="alert alert--error" role="alert">
      <ul class="alert__list">
        <?php foreach ((array) $errors as $err): ?>
          <li><?= esc($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert--success" role="status" data-flash-dismiss><?= esc($success) ?></div>
  <?php endif; ?>
</div>
<?php endif; ?>
