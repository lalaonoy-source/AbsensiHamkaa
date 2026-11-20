<?php require 'config.php'; require 'pdf_lib.php';
$x=me(); if(!$x) go('login.php');
$id=(int)($_GET['id']??0);
$s=db()->prepare('SELECT * FROM absensi WHERE id=?');$s->execute([$id]);$a=$s->fetch();
if(!$a||($x['role']==='user'&&$a['user_id']!=$x['id'])){http_response_code(404);exit('Dokumen tidak ditemukan.');}
if(!$a['pdf']){simpan_pdf($id);$s->execute([$id]);$a=$s->fetch();}
$path=__DIR__.'/'.$a['pdf'];
// Verifikasi keaslian: hash berkas harus sama dengan hash yang dicatat di database
if(!is_file($path)||!hash_equals((string)$a['pdf_hash'],hash_file('sha256',$path))){http_response_code(409);exit('Dokumen tidak valid: berkas telah berubah atau hilang.');}
header('Content-Type: application/pdf');
header('Content-Disposition: '.(isset($_GET['dl'])?'attachment':'inline').'; filename="bukti-absensi-'.$id.'.pdf"');
header('Content-Length: '.filesize($path));
readfile($path);
