<?php require 'config.php'; need('user'); $x=me(); $k=trim($_GET['k']??'');
// DATABASE: riwayat absensi milik pengguna, filter per nama kegiatan
$s=db()->prepare('SELECT * FROM absensi WHERE user_id=?'.($k!==''?' AND kegiatan=?':'').' ORDER BY created_at DESC');
$s->execute($k!==''?[$x['id'],$k]:[$x['id']]);$rows=$s->fetchAll();
$g=db()->prepare('SELECT DISTINCT kegiatan FROM absensi WHERE user_id=? ORDER BY kegiatan');$g->execute([$x['id']]);$keg=$g->fetchAll(PDO::FETCH_COLUMN);
head('Riwayat absensi',3); ?>
<div class="printhead"><h2>Riwayat Absensi HAMKA</h2><?=e($x['nama'])?> (<?=e($x['nim'])?>) — dicetak <?=date('d/m/Y H:i')?></div>
<h2 class="noprint" style="font-size:24px">Riwayat absensi</h2><p class="muted noprint" style="margin:0">Catatan kehadiran harian Anda.</p>
<form class="bar noprint" method="get"><div><label for="k">Filter nama kegiatan</label><select id="k" name="k" onchange="this.form.submit()"><option value="">Semua kegiatan</option><?php foreach($keg as $v):?><option <?=$k===$v?'selected':''?>><?=e($v)?></option><?php endforeach;?></select></div>
<a class="btn" href="rekap_pdf.php?k=<?=urlencode($k)?>" target="_blank">Unduh PDF</a><a class="btn ghost" href="absen.php">Absen baru</a></form>
<div class="tw"><table><tr><th>Tanggal</th><th>Kegiatan</th><th>Departemen</th><th>Keterangan</th><th>Waktu</th><th>Lokasi</th><th>Foto</th><th>Dokumen</th></tr>
<?php foreach($rows as $r):?><tr><td><?=date('d/m/Y',strtotime($r['tanggal']))?></td><td><?=e($r['kegiatan'])?></td><td><?=e($r['departemen'])?></td><td><span class="tag <?=e($r['keterangan'])?>"><?=e($r['keterangan'])?></span></td><td><?=date('H:i',strtotime($r['created_at']))?></td><td><?=e($r['lokasi']?:$r['latitude'].', '.$r['longitude'])?></td><td><img class="thumb" src="<?=e($r['foto'])?>" alt=""></td><td><a href="pdf.php?id=<?=$r['id']?>" target="_blank">PDF</a></td></tr>
<?php endforeach; if(!$rows):?><tr><td colspan="8" class="muted">Belum ada catatan. <a href="absen.php">Isi absensi pertama Anda</a>.</td></tr><?php endif;?></table></div>
<?php foot();
