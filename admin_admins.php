<?php require 'config.php'; need('utama'); chk(); $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(($_POST['a']??'')==='add'){
    $n=trim($_POST['nama']);$id=trim($_POST['nim']);$tel=trim($_POST['telepon']);$p=$_POST['password'];
    $c=db()->prepare('SELECT 1 FROM users WHERE nim=?');$c->execute([$id]);
    if(!$n||!$id||strlen($p)<6) $err='Lengkapi data. Password minimal 6 karakter.';
    elseif($c->fetch()) $err='Username/NIM sudah dipakai.';
    else db()->prepare("INSERT INTO users(nama,nim,telepon,password,role) VALUES(?,?,?,?,'admin')")->execute([$n,$id,$tel,password_hash($p,PASSWORD_DEFAULT)]); // DATABASE
  }
  if(($_POST['a']??'')==='del') db()->prepare("DELETE FROM users WHERE id=? AND role='admin'")->execute([(int)$_POST['id']]);
  if(!$err) go('admin_admins.php');
}
$rows=db()->query("SELECT * FROM users WHERE role IN('admin','admin_utama') ORDER BY role='admin_utama' DESC,id")->fetchAll();
head('Admin'); ?>
<h2 style="font-size:24px">Admin</h2><p class="muted" style="margin:0">Admin utama (sekum1) dapat menambah admin kedua bila dibutuhkan.</p>
<?php if($err):?><div class="err"><?=e($err)?></div><?php endif;?>
<form class="panel" method="post" style="margin:16px 0"><?=csrf()?><input type="hidden" name="a" value="add"><div class="row2"><div><label>Nama</label><input type="text" name="nama" required></div><div><label>Username / NIM</label><input type="text" name="nim" required></div><div><label>No. telepon</label><input type="tel" name="telepon" required></div><div><label>Password</label><input type="password" name="password" required></div></div><p><button class="btn">Tambah admin kedua</button></p></form>
<div class="tw"><table><tr><th>Nama</th><th>Username</th><th>Peran</th><th></th></tr>
<?php foreach($rows as $r):?><tr><td><?=e($r['nama'])?></td><td><?=e($r['nim'])?></td><td><?=$r['role']==='admin_utama'?'Admin utama (Sekretaris)':'Admin kedua'?></td><td style="text-align:right"><?php if($r['role']==='admin'):?><form method="post"><?=csrf()?><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn red sm" name="a" value="del" onclick="return confirm('Hapus admin ini?')">Hapus</button></form><?php endif;?></td></tr><?php endforeach;?></table></div>
<?php foot();
