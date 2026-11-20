<?php require 'config.php'; chk(); $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $id=trim($_POST['id']??'');$pw=$_POST['password']??'';
  // DATABASE: cari akun berdasarkan NIM atau Nama
  $s=db()->prepare('SELECT * FROM users WHERE nim=? OR nama=?');$s->execute([$id,$id]);
  foreach($s->fetchAll() as $x) if(password_verify($pw,$x['password'])){
    session_regenerate_id(true);
    $_SESSION['u']=['id'=>$x['id'],'nama'=>$x['nama'],'nim'=>$x['nim'],'role'=>$x['role']];
    go($x['role']==='user'?'absen.php':'admin_rekap.php');
  }
  $err='NIM/nama atau password salah.';
}
head('Masuk',2); ?>
<div class="split"><?=side('Selamat datang kembali.','Masuk ke akun Anda untuk mencatat kehadiran kegiatan di HAMKA.')?>
<form class="r" method="post"><?=csrf()?><h2>Masuk ke akun Anda</h2><p class="muted" style="margin:0">Isi NIM atau nama dan password yang terdaftar.</p>
<?php if($err):?><div class="err"><?=e($err)?></div><?php endif;?>
<label for="id">NIM / Nama</label><input type="text" id="id" name="id" required autofocus placeholder="Masukkan NIM atau nama">
<label for="pw">Password</label><input type="password" id="pw" name="password" required placeholder="Masukkan password">
<p><button class="btn full" style="margin-top:18px">Masuk</button></p>
<div class="links"><a href="forgot.php">Lupa password?</a><a href="register.php">Daftar akun</a></div></form></div>
<?php foot();
