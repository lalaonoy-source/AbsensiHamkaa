<?php
// ===== Pembuat PDF sederhana (tanpa library tambahan) =====
class MiniPDF{
  public $W=595,$H=842,$pages=[],$cur=-1,$imgs=[];
  function __construct($landscape=false){ if($landscape){$this->W=842;$this->H=595;} }
  static function n($v){return sprintf('%.2F',$v);}
  function page(){$this->pages[]=['c'=>'','im'=>[]];$this->cur++;}
  function enc($s){
    $s=mb_convert_encoding((string)$s,'Windows-1252','UTF-8');
    return strtr($s,['\\'=>'\\\\','('=>'\\(',')'=>'\\)',"\r"=>'',"\n"=>' ']);
  }
  function t($x,$y,$s,$sz=10,$bold=false,$col='0.1 0.14 0.22'){
    $this->pages[$this->cur]['c'].='BT '.$col.' rg /F'.($bold?2:1).' '.self::n($sz).' Tf '.self::n($x).' '.self::n($this->H-$y).' Td ('.$this->enc($s).") Tj ET\n";
  }
  function rect($x,$y,$w,$h,$col){
    $this->pages[$this->cur]['c'].=$col.' rg '.self::n($x).' '.self::n($this->H-$y-$h).' '.self::n($w).' '.self::n($h)." re f\n";
  }
  function ln($x1,$y1,$x2,$y2){
    $this->pages[$this->cur]['c'].='0.88 0.9 0.94 RG 0.5 w '.self::n($x1).' '.self::n($this->H-$y1).' m '.self::n($x2).' '.self::n($this->H-$y2)." l S\n";
  }
  function img($jpeg,$x,$y,$w,$h){
    $id=count($this->imgs)+1;$i=getimagesizefromstring($jpeg);
    $this->imgs[$id]=[$jpeg,$i[0],$i[1]];$this->pages[$this->cur]['im'][$id]=1;
    $this->pages[$this->cur]['c'].='q '.self::n($w).' 0 0 '.self::n($h).' '.self::n($x).' '.self::n($this->H-$y-$h)." cm /Im$id Do Q\n";
  }
  function out(){
    $o=[1=>'<< /Type /Catalog /Pages 2 0 R >>',
        3=>'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
        4=>'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>'];
    $next=5;$imgId=[];
    foreach($this->imgs as $i=>$im){$imgId[$i]=$next;$o[$next++]="<< /Type /XObject /Subtype /Image /Width {$im[1]} /Height {$im[2]} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ".strlen($im[0])." >>\nstream\n".$im[0]."\nendstream";}
    $kids=[];
    foreach($this->pages as $p){
      $cid=$next++;$pid=$next++;
      $o[$cid]='<< /Length '.strlen($p['c'])." >>\nstream\n".$p['c']."endstream";
      $xo='';foreach($p['im'] as $i=>$_)$xo.="/Im$i {$imgId[$i]} 0 R ";
      $o[$pid]="<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$this->W} {$this->H}] /Contents $cid 0 R /Resources << /Font << /F1 3 0 R /F2 4 0 R >> /XObject << $xo>> >> >>";
      $kids[]="$pid 0 R";
    }
    $o[2]='<< /Type /Pages /Kids ['.implode(' ',$kids).'] /Count '.count($kids).' >>';
    ksort($o);$pdf="%PDF-1.4\n";$off=[];
    foreach($o as $id=>$b){$off[$id]=strlen($pdf);$pdf.="$id 0 obj\n$b\nendobj\n";}
    $max=max(array_keys($o))+1;$xr=strlen($pdf);
    $pdf.="xref\n0 $max\n0000000000 65535 f \n";
    for($i=1;$i<$max;$i++)$pdf.=sprintf("%010d 00000 n \n",$off[$i]);
    return $pdf."trailer\n<< /Size $max /Root 1 0 R >>\nstartxref\n$xr\n%%EOF";
  }
}

// DATABASE + PDF: buat PDF bukti absensi otomatis, simpan di folder dokumen/ (read-only) dan catat hash SHA-256
function simpan_pdf($id){
  $s=db()->prepare('SELECT a.*,u.nama,u.nim FROM absensi a JOIN users u ON u.id=a.user_id WHERE a.id=?');$s->execute([$id]);$a=$s->fetch();
  if(!$a||$a['pdf']) return false; // satu kali dibuat, tidak pernah ditimpa
  $navy='0.07 0.16 0.3';$P=new MiniPDF();$P->page();
  $P->rect(0,0,595,96,$navy);
  $P->t(40,46,'BUKTI ABSENSI',22,true,'1 1 1');
  $P->t(40,68,'HAMKA - Portal Kehadiran Anggota',10,false,'0.85 0.89 0.95');
  $P->t(400,46,'No. Dokumen',8,false,'0.85 0.89 0.95');$P->t(400,62,'ABS-'.str_pad($a['id'],6,'0',STR_PAD_LEFT),13,true,'1 1 1');
  $rows=[['Nama',$a['nama']],['NIM',$a['nim']],['Departemen',$a['departemen']],['Nama kegiatan',$a['kegiatan']],
    ['Tanggal',date('d/m/Y',strtotime($a['tanggal']))],['Waktu absen',date('d/m/Y H:i:s',strtotime($a['created_at'])).' WIB'],
    ['Keterangan',$a['keterangan']],['Koordinat',$a['latitude'].', '.$a['longitude'].($a['akurasi']?' (akurasi +/- '.$a['akurasi'].' m)':'')]];
  $y=136;
  foreach($rows as $r){$P->t(40,$y,$r[0],9,false,'0.42 0.46 0.54');$P->t(170,$y,mb_strimwidth($r[1],0,70,'...'),11,true);$P->ln(40,$y+9,555,$y+9);$y+=28;}
  $P->t(40,$y,'Lokasi absen',9,false,'0.42 0.46 0.54');
  foreach(array_slice(explode("\n",wordwrap($a['lokasi']?:'-',68,"\n",true)),0,4) as $l){$P->t(170,$y,$l,10,true);$y+=14;}
  $y+=14;$P->t(40,$y,'Bukti foto (kamera langsung)',9,false,'0.42 0.46 0.54');$y+=10;
  $jpg=@file_get_contents(__DIR__.'/'.$a['foto']);
  if($jpg&&($i=@getimagesizefromstring($jpg))){$sc=min(240/$i[0],240/$i[1]);$P->img($jpg,40,$y,$i[0]*$sc,$i[1]*$sc);}
  $P->rect(0,790,595,52,'0.96 0.97 0.98');
  $P->t(40,812,'Dokumen ini dibuat otomatis oleh sistem saat absensi dikirim dan tidak dapat diubah.',8,false,'0.42 0.46 0.54');
  $P->t(40,826,'Keaslian berkas diverifikasi dengan kode hash SHA-256 yang tersimpan di server.',8,false,'0.42 0.46 0.54');
  $bin=$P->out();
  $fn='dokumen/abs_'.$id.'_'.bin2hex(random_bytes(6)).'.pdf';
  file_put_contents(__DIR__.'/'.$fn,$bin);@chmod(__DIR__.'/'.$fn,0444);
  db()->prepare('UPDATE absensi SET pdf=?,pdf_hash=? WHERE id=? AND pdf IS NULL')->execute([$fn,hash('sha256',$bin),$id]);
  return true;
}
