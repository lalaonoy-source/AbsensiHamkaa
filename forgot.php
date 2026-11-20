<?php require 'config.php'; chk(); $err='';$ok='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $tel=trim($_POST['telepon']);$id=trim($_POST['id']);$p=$_POST['password'];
  if(strlen($p)<6) $err='Password baru minimal 6 karakter.';
  elseif($p!==$_POST['konfirmasi']) $err='Konfirmasi password tidak sama.';
  else{ // DATABASE: cocokkan nomor telepon + NIM/nama, lalu ubah password
    $s=db()->prepare('SELECT id FROM users WHERE telepon=? AND (nim=? OR nama=?)');$s->execute([$tel,$id,$id]);
    if($x=$s->fetch()){db()->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($p,PASSWORD_DEFAULT),$x['id']]);$ok='Password berhasil diubah. Silakan masuk.';}
    else $err='Nomor telepon dan NIM/nama tidak cocok dengan akun mana pun.';
  }
}
head('Lupa password',2); ?>
<div class="split"><?=side('Atur ulang password.','Masukkan nomor telepon yang terdaftar, lalu buat password baru.')?>
<form class="r" method="post"><?=csrf()?><h2>Lupa password</h2>
<?php if($err):?><div class="err"><?=e($err)?></div><?php endif; if($ok):?><div class="okb"><?=e($ok)?></div><?php endif;?>
<label>No. telepon terdaftar</label><input type="tel" name="telepon" required>
<label>NIM / Nama</label><input type="text" name="id" required>
<label>Password baru</label><input type="password" name="password" required>
<label>Konfirmasi password baru</label><input type="password" name="konfirmasi" required>
<p><button class="btn full" style="margin-top:18px">Ubah password</button></p>
<div class="links"><a href="login.php">Kembali ke masuk</a></div></form></div>
<?php foot();
