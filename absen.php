<?php require 'config.php'; need('user'); chk(); $x=me(); $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $kg=trim($_POST['kegiatan']??'');$dep=$_POST['departemen']??'';$ket=$_POST['keterangan']??'';
  $lat=$_POST['lat']??'';$lng=$_POST['lng']??'';$foto=$_POST['foto']??'';
  if($kg===''||mb_strlen($kg)>150||!in_array($dep,DEPTS)||!in_array($ket,['Hadir','Sakit','Izin'])) $err='Data formulir tidak lengkap.';
  elseif(!is_numeric($lat)||!is_numeric($lng)) $err='Lokasi belum aktif. Aktifkan GPS/lokasi perangkat Anda.';
  elseif(!preg_match('#^data:image/jpeg;base64,#',$foto)) $err='Foto wajib diambil langsung dengan kamera.';
  else{
    $bin=base64_decode(substr($foto,23),true);
    if(!$bin||strlen($bin)>3000000||substr($bin,0,2)!=="\xFF\xD8") $err='Foto tidak valid.';
    else{
      $tgl=date('Y-m-d');$d=db()->prepare('SELECT 1 FROM absensi WHERE user_id=? AND kegiatan=? AND tanggal=?');$d->execute([$x['id'],$kg,$tgl]);
      if($d->fetch()) $err='Anda sudah absen untuk kegiatan ini hari ini.';
      else{
        $fn='uploads/'.$x['id'].'_'.time().'_'.bin2hex(random_bytes(4)).'.jpg'; file_put_contents(__DIR__.'/'.$fn,$bin);
        // DATABASE: simpan data absensi (termasuk nama lokasi & koordinat)
        db()->prepare('INSERT INTO absensi(user_id,kegiatan,departemen,tanggal,keterangan,foto,latitude,longitude,akurasi,lokasi) VALUES(?,?,?,?,?,?,?,?,?,?)')
          ->execute([$x['id'],$kg,$dep,$tgl,$ket,$fn,$lat,$lng,(int)($_POST['acc']??0),mb_substr($_POST['lokasi']??'',0,250)]);
        $nid=db()->lastInsertId();
        require_once __DIR__.'/pdf_lib.php';
        try{simpan_pdf($nid);}catch(Throwable $t){} // data otomatis menjadi PDF (tidak bisa diubah)
        go('sukses.php?id='.$nid);
      }
    }
  }
}
head('Formulir absensi',3); ?>
<h2 style="font-size:24px">Formulir absensi</h2><p class="muted" style="margin:0">Halo, <?=e($x['nama'])?>. Lengkapi data untuk kegiatan hari ini.</p>
<?php if($err):?><div class="err"><?=e($err)?></div><?php endif;?>
<form method="post" class="fgrid"><?=csrf()?>
<div class="panel"><h2 style="font-size:15px">Daftar kehadiran</h2>
<div class="err" id="sec" hidden>Kamera dan lokasi diblokir browser pada alamat ini. Buka lewat <b>http://localhost</b> (atau <b>HTTPS</b>), bukan lewat alamat IP.</div>
<div class="row2"><div><label>Nama</label><input type="text" value="<?=e($x['nama'])?>" readonly></div><div><label>Tanggal</label><input type="text" value="<?=date('d/m/Y')?>" readonly></div></div>
<div class="row2"><div><label for="d">Departemen</label><select id="d" name="departemen" required><option value="">Pilih departemen</option><?php foreach(DEPTS as $d):?><option><?=e($d)?></option><?php endforeach;?></select></div>
<div><label for="k">Nama kegiatan</label><input type="text" id="k" name="kegiatan" required maxlength="150" placeholder="Ketik nama kegiatan" value="<?=e($_POST['kegiatan']??'')?>"></div></div>
<label>Keterangan</label><div class="radios"><?php foreach(['Hadir','Sakit','Izin'] as $i=>$k):?><label><input type="radio" name="keterangan" value="<?=$k?>" <?=$i?'':'checked'?>><?=$k?></label><?php endforeach;?></div>
<label>Lokasi Anda saat ini</label><div class="loc" id="loc"><?=pin()?><div><b id="locn">Meminta izin lokasi…</b><small id="loca">Klik <b>Izinkan</b> saat browser menanyakan akses lokasi.</small><button type="button" class="btn sm" id="bgps" style="margin-top:8px" onclick="getGPS()">Aktifkan lokasi</button></div></div>
<label>Bukti foto (kamera langsung)</label><div class="cam"><video id="v" autoplay playsinline muted hidden></video><img id="shot" alt="" hidden>
<button type="button" class="btn sm" id="bcam" onclick="startCam()">Aktifkan kamera</button> <button type="button" class="btn ghost sm" id="snap" disabled>Ambil foto</button> <button type="button" class="btn ghost sm" id="redo" hidden>Ulangi</button><div class="muted" id="cmsg" style="font-size:11px;margin-top:6px">Klik <b>Izinkan</b> saat browser menanyakan akses kamera. Foto hanya dari kamera langsung, tanpa unggah file.</div></div>
<input type="hidden" name="foto" id="foto"><input type="hidden" name="lat" id="lat"><input type="hidden" name="lng" id="lng"><input type="hidden" name="acc" id="acc"><input type="hidden" name="lokasi" id="lok">
<div class="err" id="miss" hidden></div>
<p style="text-align:right"><button class="btn" id="go">Kirim absensi</button></p></div>
<aside><div class="side"><b style="margin-top:0">Sebelum mengirim</b><b>Periksa data Anda</b><p>Pastikan nama, departemen, dan tanggal sudah sesuai.</p><b>Aktifkan GPS dan kamera</b><p>Izinkan akses lokasi dan kamera agar nama tempat dan foto Anda tercatat.</p></div>
<div class="note"><b>Satu kegiatan, satu absensi</b><br>Absensi hanya bisa dikirim sekali untuk setiap kegiatan per hari.</div></aside></form>
<?php
$js=<<<'JS'
<script>
const $=i=>document.getElementById(i);let gOK=false,pOK=false,stream=null;
if(!window.isSecureContext)$("sec").hidden=false;
function bad(m){$("loc").className="loc bad";$("locn").textContent="Lokasi belum aktif";$("loca").textContent=m;$("bgps").hidden=false;$("bgps").textContent="Coba aktifkan lokasi lagi";gOK=false}
function getGPS(low){
 if(!navigator.geolocation){bad("Browser tidak mendukung GPS.");return}
 $("loc").className="loc";$("locn").textContent="Mencari lokasi Anda…";$("loca").textContent="Mohon tunggu sebentar.";$("bgps").hidden=true;
 navigator.geolocation.getCurrentPosition(async p=>{
  const la=p.coords.latitude,lo=p.coords.longitude;
  $("lat").value=la;$("lng").value=lo;$("acc").value=Math.round(p.coords.accuracy);
  let full=la.toFixed(5)+", "+lo.toFixed(5),short="Koordinat "+full;
  try{
   const r=await fetch("https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&zoom=18&accept-language=id&lat="+la+"&lon="+lo);
   const j=await r.json(),a=j.address||{};
   const parts=[a.amenity||a.building||a.leisure||a.road,a.suburb||a.village||a.neighbourhood,a.city||a.town||a.county||a.city_district].filter(Boolean);
   if(j.display_name){full=j.display_name;short=parts.join(", ")||j.display_name}
  }catch(e){}
  $("lok").value=full;$("loc").className="loc ok";$("locn").textContent=short;
  $("loca").textContent=full+" · akurasi ±"+Math.round(p.coords.accuracy)+" m";gOK=true;$("miss").hidden=true},
 err=>{
  if(!low&&err.code!==1){getGPS(true);return}
  bad({1:"Izin lokasi ditolak. Klik ikon gembok di address bar, pilih Lokasi, lalu Izinkan.",2:"Lokasi tidak tersedia. Nyalakan Location/GPS di perangkat (Windows: Settings > Privacy > Location).",3:"Pencarian lokasi kehabisan waktu."}[err.code]||"Lokasi gagal diambil.")},
 {enableHighAccuracy:!low,timeout:low?20000:10000,maximumAge:60000})}
function camErr(m){$("cmsg").textContent=m;$("bcam").hidden=false;$("bcam").textContent="Coba aktifkan kamera lagi"}
async function startCam(){
 $("cmsg").textContent="Meminta izin kamera…";$("bcam").hidden=true;
 if(!navigator.mediaDevices||!navigator.mediaDevices.getUserMedia){camErr("Alamat ini tidak mendukung kamera. Buka lewat http://localhost atau HTTPS.");return}
 try{
  try{stream=await navigator.mediaDevices.getUserMedia({video:{facingMode:"user"},audio:false})}
  catch(e){if(e.name==="NotAllowedError"||e.name==="SecurityError")throw e;stream=await navigator.mediaDevices.getUserMedia({video:true,audio:false})}
  const v=$("v");v.srcObject=stream;v.hidden=false;$("shot").hidden=true;try{await v.play()}catch(e){}
  $("snap").hidden=false;$("snap").disabled=false;$("redo").hidden=true;$("cmsg").textContent="Kamera aktif. Arahkan kamera lalu klik Ambil foto."
 }catch(e){camErr({NotAllowedError:"Izin kamera ditolak. Klik ikon gembok di address bar, pilih Kamera, lalu Izinkan.",SecurityError:"Kamera diblokir. Buka lewat http://localhost atau HTTPS.",NotFoundError:"Kamera tidak ditemukan di perangkat ini.",NotReadableError:"Kamera sedang dipakai aplikasi lain (mis. Zoom). Tutup dulu lalu coba lagi."}[e.name]||"Kamera gagal dibuka.")}
}
$("snap").onclick=()=>{const v=$("v");if(!v.videoWidth)return;const c=document.createElement("canvas");c.width=v.videoWidth;c.height=v.videoHeight;const x=c.getContext("2d");x.drawImage(v,0,0);
 x.fillStyle="rgba(0,0,0,.55)";x.fillRect(0,c.height-28,c.width,28);x.fillStyle="#fff";x.font="14px sans-serif";x.fillText(new Date().toLocaleString("id-ID"),8,c.height-9);
 $("foto").value=c.toDataURL("image/jpeg",.8);$("shot").src=$("foto").value;$("shot").hidden=false;v.hidden=true;
 stream&&stream.getTracks().forEach(t=>t.stop());$("snap").hidden=true;$("redo").hidden=false;$("cmsg").textContent="Foto tersimpan.";pOK=true;$("miss").hidden=true};
$("redo").onclick=()=>{$("foto").value="";pOK=false;startCam()};
document.querySelector("form.fgrid").addEventListener("submit",e=>{const m=[];if(!gOK)m.push("lokasi belum aktif");if(!pOK)m.push("foto belum diambil dengan kamera");if(m.length){e.preventDefault();$("miss").hidden=false;$("miss").textContent="Belum bisa dikirim: "+m.join(" dan ")+"."}});
getGPS();startCam();
</script>
JS;
foot($js);
