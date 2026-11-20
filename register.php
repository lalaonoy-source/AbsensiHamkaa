<?php require 'config.php'; chk(); $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $n=trim($_POST['nama']);$nim=trim($_POST['nim']);$tel=trim($_POST['telepon']);$p=$_POST['password'];
  if(!$n||!$nim||!preg_match('/^[0-9+]{8,16}$/',$tel)) $err='Lengkapi semua data. Nomor telepon hanya angka (8–16 digit).';
  elseif(strlen($p)<6) $err='Password minimal 6 karakter.';
  elseif($p!==$_POST['konfirmasi']) $err='Konfirmasi password tidak sama.';
  else{
    $c=db()->prepare('SELECT 1 FROM users WHERE nim=?');$c->execute([$nim]);
    if($c->fetch()) $err='NIM sudah terdaftar.';
    else{ // DATABASE: simpan akun baru
      db()->prepare('INSERT INTO users(nama,nim,telepon,password) VALUES(?,?,?,?)')->execute([$n,$nim,$tel,password_hash($p,PASSWORD_DEFAULT)]);
      go('login.php');
    }
  }
}
head('Daftar',2); ?>
<div class="split"><?=side('Buat akun baru.','Daftar sekali, lalu catat kehadiran kapan saja.')?>
<form class="r" method="post"><?=csrf()?><h2>Daftar</h2>
<?php if($err):?><div class="err"><?=e($err)?></div><?php endif;?>
<label>Nama</label><input type="text" name="nama" required value="<?=e($_POST['nama']??'')?>">
<label>NIM</label><input type="text" name="nim" required value="<?=e($_POST['nim']??'')?>">
<label>No. telepon</label><input type="tel" name="telepon" required value="<?=e($_POST['telepon']??'')?>">
<label>Password</label><input type="password" name="password" required>
<label>Konfirmasi password</label><input type="password" name="konfirmasi" required>
<p><button class="btn full" style="margin-top:18px">Daftar</button></p>
<div class="links"><a href="login.php">Sudah punya akun? Masuk</a><a href="forgot.php">Lupa password?</a></div></form></div>
<?php foot();
