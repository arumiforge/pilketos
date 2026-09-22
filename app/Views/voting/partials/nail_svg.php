<?php
/**
 * Paku coblos 2D (warna solid, tanpa gradient). Dipakai pada tombol "Ambil
 * paku" dan sebagai kursor fallback CSS bila WebGL tidak tersedia.
 * Ujung paku ada di (20, 148) pada viewBox.
 */
?>
<svg class="nail-svg" viewBox="0 0 40 150" width="40" height="150" aria-hidden="true" focusable="false">
  <g stroke="#2A2930" stroke-width="0.8" stroke-linejoin="round">
    <path d="M16.5 14h7v112l-3.5 22-3.5-22z" fill="#9DA1A7"/>
    <path d="M2 8v4a18 6 0 0 0 36 0V8a18 6 0 0 1-36 0z" fill="#72767C"/>
    <ellipse cx="20" cy="8" rx="18" ry="6" fill="#C9CCD1"/>
  </g>
  <rect x="18" y="15" width="1.6" height="110" fill="#E4E6E9"/>
  <rect x="21.4" y="15" width="1.6" height="110" fill="#6E7278"/>
  <path d="M18 127l2 19 0-19z" fill="#DADCE0"/>
  <ellipse cx="15" cy="6.5" rx="7" ry="1.8" fill="#EEF0F2"/>
</svg>
