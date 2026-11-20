<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
header('Permissions-Policy: camera=(self), geolocation=(self)'); // izinkan kamera & lokasi untuk website ini
// ===== DATABASE: pengaturan koneksi MySQL (phpMyAdmin) =====
const DB_HOST='localhost', DB_NAME='absensi_hamka', DB_USER='root', DB_PASS='';
const DEPTS=['Departemen Organisasi','Departemen Penelitian dan Pengembangan','Departemen Eksternal','Departemen Komunikasi dan Informasi'];

function db(){
  static $p; if($p) return $p;
  try{
    $x=new PDO('mysql:host='.DB_HOST.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    // DATABASE: buat database + tabel otomatis dari database.sql
    $sql=preg_replace('/^--.*$/m','',file_get_contents(__DIR__.'/database.sql'));
    foreach(array_filter(array_map('trim',explode(';',$sql))) as $q) $x->exec($q);
    // DATABASE: admin utama (sekretaris umum). Password awal: Sekum#123
    if(!$x->query("SELECT COUNT(*) FROM users WHERE nim='sekum1'")->fetchColumn())
      $x->prepare("INSERT INTO users(nama,nim,telepon,password,role) VALUES('Sekretaris Umum','sekum1','0000000000',?,'admin_utama')")->execute([password_hash('Sekum#123',PASSWORD_DEFAULT)]);
    // DATABASE: tambah kolom PDF jika tabel lama sudah ada
    foreach(['pdf'=>'VARCHAR(255) NULL','pdf_hash'=>'CHAR(64) NULL'] as $c=>$t) if(!$x->query("SHOW COLUMNS FROM absensi LIKE '$c'")->fetch()) $x->exec("ALTER TABLE absensi ADD $c $t");
    $dd=__DIR__.'/dokumen'; // PDF disimpan di sini, tidak bisa diakses langsung
    if(!is_dir($dd)){mkdir($dd,0755);file_put_contents($dd.'/.htaccess',"Require all denied\n");}
    $d=__DIR__.'/uploads';
    if(!is_dir($d)){mkdir($d,0755);file_put_contents($d.'/.htaccess',"php_flag engine off\nRemoveHandler .php\n");}
  }catch(PDOException $ex){
    http_response_code(500);
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Database belum aktif</title><body style="font:15px/1.6 system-ui;max-width:560px;margin:60px auto;padding:0 20px;color:#1b2437"><h2 style="color:#12294d">Database belum bisa dihubungi</h2><p>Website sudah berjalan, tetapi MySQL belum siap:</p><ol><li>Buka <b>XAMPP Control Panel</b>, klik <b>Start</b> pada <b>MySQL</b>.</li><li>Jika MySQL memakai password, isi <code>DB_PASS</code> di <code>config.php</code>.</li><li>Muat ulang halaman ini.</li></ol><p style="color:#6b768a;font-size:13px">Detail: '.htmlspecialchars($ex->getMessage()).'</p></body>';
    exit;
  }
  return $p=$x;
}
function e($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function me(){return $_SESSION['u']??null;}
function go($f){header('Location: '.$f);exit;}
function need($r='user'){
  $x=me(); if(!$x) go('login.php');
  if($r==='user'&&$x['role']!=='user') go('admin_rekap.php');
  if($r==='admin'&&$x['role']==='user') go('absen.php');
  if($r==='utama'&&$x['role']!=='admin_utama') go('admin_rekap.php');
}
function csrf(){$_SESSION['t']=$_SESSION['t']??bin2hex(random_bytes(16));return '<input type="hidden" name="t" value="'.$_SESSION['t'].'">';}
function chk(){if($_SERVER['REQUEST_METHOD']==='POST'&&!hash_equals($_SESSION['t']??'',$_POST['t']??''))die('Sesi tidak valid. Muat ulang halaman.');}
function pin(){return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>';}

function head($t,$step=0){
  $x=me();
  echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($t).' — Absensi HAMKA</title><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="style.css"></head><body>
<header class="top noprint"><a class="brand" href="index.php"><img src="hamka.png" alt="HAMKA"><span><b>Absensi HAMKA</b><small>Portal kehadiran anggota</small></span></a><nav>';
  if($x&&$x['role']==='user') echo '<a href="absen.php">Absen</a><a href="riwayat.php">Riwayat</a>';
  if($x&&$x['role']!=='user') echo '<a href="admin_rekap.php">Rekap</a>'.($x['role']==='admin_utama'?'<a href="admin_admins.php">Admin</a>':'');
  if($x) echo '<span class="chip"><i>'.e(strtoupper(substr($x['nama'],0,2))).'</i>'.e($x['nama']).'</span><a href="logout.php">Keluar</a>';
  echo '</nav></header>';
  if($step){echo '<ol class="steps noprint">';foreach(['Selamat datang','Masuk','Isi absensi'] as $i=>$s){$n=$i+1;$c=$n<$step?'done':($n==$step?'now':'');echo '<li class="'.$c.'"><span>'.($n<$step?'✓':'0'.$n).'</span>'.$s.'</li>';}echo '</ol>';}
  echo '<main>';
}
function foot($js=''){echo '</main><footer class="noprint">© 2026 HAMKA. Seluruh hak cipta dilindungi.</footer>'.$js.'</body></html>';}
function side($h,$p){return '<div class="l"><div class="logo"><img src="hamka.png" alt=""></div><h2>'.$h.'</h2><p>'.$p.'</p></div>';}
