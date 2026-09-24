<?php
$error   = session()->getFlashdata('error');
$success = session()->getFlashdata('success');
$errors  = session()->getFlashdata('errors');
$warning = session()->getFlashdata('warning'); // Stage 11: peringatan tidak menghalangi (mis. absen ganda)
?>
<?php if ($error || $success || $errors || $warning): ?>
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
  <?php if ($warning): ?>
    <div class="alert alert--warning" role="status"><?= esc($warning) ?></div>
  <?php endif; ?>
</div>
<?php endif; ?>
