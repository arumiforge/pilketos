<?php
/**
 * Halaman error 404 (Stage 4): bahasa Indonesia, gaya netral aplikasi,
 * mandiri (tanpa CSS/JS eksternal). Detail teknis hanya tampil di luar production.
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Halaman tidak ditemukan — Pemilihan OSIS SMP 1 Dawe</title>
    <style <?= function_exists('csp_style_nonce') ? csp_style_nonce() : '' ?>>
        :root { --ink: #15141A; --paper: #FAF9F6; --line: #DCD8CD; --muted: #514E45; }
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px 16px;
            background: var(--paper);
            color: var(--ink);
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.55;
        }
        main { width: 100%; max-width: 560px; border-top: 6px double var(--ink); padding-top: 20px; }
        .kicker { margin: 0; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: var(--muted); }
        h1 { margin: 8px 0 0; font-family: Newsreader, Georgia, "Times New Roman", serif; font-size: clamp(2.2rem, 8vw, 3.4rem); font-weight: 500; line-height: 1.02; letter-spacing: -0.02em; }
        p { margin: 16px 0 0; }
        .detail { padding: 12px 14px; border: 1px solid var(--line); background: #FFFFFF; font-size: 0.875rem; overflow-wrap: anywhere; }
        a { color: var(--ink); font-weight: 600; text-underline-offset: 0.2em; }
        a:focus-visible { outline: 2px solid var(--ink); outline-offset: 2px; }
        .foot { margin-top: 28px; padding-top: 12px; border-top: 1px solid var(--line); font-size: 0.8125rem; color: var(--muted); }
    </style>
</head>
<body>
<main>
    <p class="kicker">Kesalahan 404</p>
    <h1>Halaman tidak ditemukan</h1>
    <p>Alamat yang dibuka tidak ada atau data yang dicari sudah tidak tersedia. Periksa kembali alamatnya, atau kembali ke beranda.</p>
    <?php if (ENVIRONMENT !== 'production' && isset($message) && $message !== ''): ?>
        <p class="detail"><?= nl2br(esc($message)) ?></p>
    <?php endif; ?>
    <p class="foot">Pemilihan Ketua &amp; Wakil Ketua OSIS SMP 1 Dawe 2026 &middot; <a href="<?= esc(function_exists('base_url') ? base_url('/') : '/', 'attr') ?>">Kembali ke beranda</a></p>
</main>
</body>
</html>
