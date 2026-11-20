<?php require 'config.php'; need('admin'); $k=trim($_GET['k']??'');
// DATABASE: rekap SEMUA anggota yang sudah mengisi absensi, filter per nama kegiatan
$s=db()->prepare('SELECT a.*,u.nama,u.nim FROM absensi a JOIN users u ON u.id=a.user_id'.($k!==''?' WHERE a.kegiatan=?':'').' ORDER BY a.created_at DESC');
$s->execute($k!==''?[$k]:[]);$rows=$s->fetchAll();
$keg=db()->query('SELECT DISTINCT kegiatan FROM absensi ORDER BY kegiatan')->fetchAll(PDO::FETCH_COLUMN);
$c=['Hadir'=>0,'Sakit'=>0,'Izin'=>0];foreach($rows as $r)$c[$r['keterangan']]++;
head('Rekap absensi'); ?>
<div class="printhead"><h2>Rekap Absensi HAMKA</h2>Kegiatan: <?=e($k!==''?$k:'Semua kegiatan')?> — dicetak <?=date('d/m/Y H:i')?></div>
<h2 class="noprint" style="font-size:24px">Rekap absensi</h2><p class="muted noprint" style="margin:0">Semua anggota yang sudah mengisi absensi tampil di sini. Halaman diperbarui otomatis tiap 30 detik.</p>
<form class="bar noprint" method="get"><div><label for="k">Filter nama kegiatan</label><select id="k" name="k" onchange="this.form.submit()"><option value="">Semua kegiatan</option><?php foreach($keg as $v):?><option <?=$k===$v?'selected':''?>><?=e($v)?></option><?php endforeach;?></select></div><a class="btn" href="rekap_pdf.php?k=<?=urlencode($k)?>" target="_blank">Unduh PDF</a></form>
<div class="stats"><div><b><?=count($rows)?></b>Total</div><div><b><?=$c['Hadir']?></b>Hadir</div><div><b><?=$c['Sakit']?></b>Sakit</div><div><b><?=$c['Izin']?></b>Izin</div></div>
<div class="tw"><table><tr><th>Waktu</th><th>Nama</th><th>NIM</th><th>Departemen</th><th>Kegiatan</th><th>Ket.</th><th>Lokasi absen</th><th>Foto</th><th>Dokumen</th></tr>
<?php foreach($rows as $r): $new=time()-strtotime($r['created_at'])<600;?><tr class="<?=$new?'new':''?>"><td><?=date('d/m/Y H:i',strtotime($r['created_at']))?><?=$new?' <span class="tag">Baru</span>':''?></td><td><?=e($r['nama'])?></td><td><?=e($r['nim'])?></td><td><?=e($r['departemen'])?></td><td><?=e($r['kegiatan'])?></td><td><span class="tag <?=e($r['keterangan'])?>"><?=e($r['keterangan'])?></span></td><td style="min-width:200px"><?=e($r['lokasi']?:$r['latitude'].', '.$r['longitude'])?></td><td><img class="thumb" src="<?=e($r['foto'])?>" alt=""></td><td><a href="pdf.php?id=<?=$r['id']?>" target="_blank">PDF</a></td></tr>
<?php endforeach; if(!$rows):?><tr><td colspan="9" class="muted">Belum ada data absensi.</td></tr><?php endif;?></table></div>
<?php foot('<script>setTimeout(()=>location.reload(),30000)</script>');
