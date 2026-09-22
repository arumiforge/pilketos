<?php
$error   = session()->getFlashdata('error');
$success = session()->getFlashdata('success');
$errors  = session()->getFlashdata('errors');
?>
<?php if ($error || $success || $errors): ?>
<div class="container" style="padding-top: var(--space-4);">
  <?php if ($error): ?>
    <div class="form-alert" data-flash role="alert"><?= esc($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="form-alert" data-flash role="status" style="border-color: var(--success); color: var(--success);"><?= esc($success) ?></div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="form-alert" data-flash role="alert">
      <ul class="form-alert--list">
        <?php foreach ($errors as $err): ?>
          <li><?= esc($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>
