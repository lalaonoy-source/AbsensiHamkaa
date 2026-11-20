<?php require 'config.php'; need('user'); $x=me();
$s=db()->prepare('SELECT * FROM absensi WHERE id=? AND user_id=?');$s->execute([(int)($_GET['id']??0),$x['id']]);
$a=$s->fetch(); if(!$a) go('riwayat.php');
head('Absensi berhasil',4); ?>
<div class="success"><div class="check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></div><span class="gold" style="color:var(--ok)">ABSENSI BERHASIL DICATAT</span>
<h2 style="font-size:24px;margin-top:6px">Anda sudah absensi</h2><p class="muted" style="margin-bottom:0">Terima kasih, <?=e($x['nama'])?>. Kehadiran Anda telah tercatat.</p>
</div>
<?php foot();
