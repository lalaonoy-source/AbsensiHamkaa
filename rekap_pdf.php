<?php require 'config.php'; require 'pdf_lib.php';
$x=me(); if(!$x) go('login.php');
$k=trim($_GET['k']??'');$own=$x['role']==='user';$w=[];$pr=[];
if($own){$w[]='a.user_id=?';$pr[]=$x['id'];} if($k!==''){$w[]='a.kegiatan=?';$pr[]=$k;}
// DATABASE: ambil data absensi (milik sendiri untuk pengguna, semua untuk admin)
$s=db()->prepare('SELECT a.*,u.nama,u.nim FROM absensi a JOIN users u ON u.id=a.user_id'.($w?' WHERE '.implode(' AND ',$w):'').' ORDER BY a.created_at DESC');
$s->execute($pr);$rows=$s->fetchAll();
$c=['Hadir'=>0,'Sakit'=>0,'Izin'=>0];foreach($rows as $r)$c[$r['keterangan']]++;
$navy='0.07 0.16 0.3';$P=new MiniPDF(true);
$cols=[['Waktu',88],['Nama',105],['NIM',58],['Departemen',125],['Kegiatan',135],['Ket.',38],['Lokasi',233]];
$hd=function($y)use($P,$cols,$navy){$P->rect(30,$y,782,18,$navy);$cx=30;foreach($cols as $col){$P->t($cx+4,$y+12,$col[0],8,true,'1 1 1');$cx+=$col[1];}};
$P->page();
$P->rect(0,0,842,64,$navy);
$P->t(30,28,$own?'RIWAYAT ABSENSI HAMKA':'REKAP ABSENSI HAMKA',17,true,'1 1 1');
$P->t(30,48,($own?$x['nama'].' ('.$x['nim'].') | ':'').'Kegiatan: '.($k!==''?$k:'Semua kegiatan').' | Dicetak '.date('d/m/Y H:i'),9,false,'0.85 0.89 0.95');
$P->t(30,86,'Total '.count($rows).'   Hadir '.$c['Hadir'].'   Sakit '.$c['Sakit'].'   Izin '.$c['Izin'],10,true);
$y=98;$hd($y);$y+=18;
foreach($rows as $r){
  if($y>560){$P->page();$y=30;$hd($y);$y+=18;}
  $v=[date('d/m/Y H:i',strtotime($r['created_at'])),$r['nama'],$r['nim'],$r['departemen'],$r['kegiatan'],$r['keterangan'],$r['lokasi']?:$r['latitude'].', '.$r['longitude']];
  $cx=30;foreach($cols as $i=>$col){$P->t($cx+4,$y+11,mb_strimwidth($v[$i],0,(int)(($col[1]-6)/4.1),'...'),8);$cx+=$col[1];}
  $P->ln(30,$y+16,812,$y+16);$y+=17;
}
if(!$rows)$P->t(34,$y+12,'Belum ada data absensi.',9);
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="rekap-absensi-'.date('Ymd').'.pdf"');
echo $P->out();
