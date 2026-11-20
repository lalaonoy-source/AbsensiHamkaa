const $ = id => document.getElementById(id);

// --- INISIALISASI DATA DI LOCALSTORAGE ---
function getUsers() {
  let u = JSON.parse(localStorage.getItem('hamka_users') || 'null');
  if (!u) {
    u = [{ id: 1, nama: 'Sekretaris Umum', nim: 'sekum1', telepon: '0000000000', password: 'Sekum#123', role: 'admin_utama' }];
    localStorage.setItem('hamka_users', JSON.stringify(u));
  }
  return u;
}
function saveUsers(u) { localStorage.setItem('hamka_users', JSON.stringify(u)); }

function getAbsensi() { return JSON.parse(localStorage.getItem('hamka_absensi') || '[]'); }
function saveAbsensi(a) { localStorage.setItem('hamka_absensi', JSON.stringify(a)); }

function me() { return JSON.parse(sessionStorage.getItem('hamka_me') || 'null'); }
function setMe(u) { u ? sessionStorage.setItem('hamka_me', JSON.stringify(u)) : sessionStorage.removeItem('hamka_me'); }

function esc(s) {
  return String(s ?? '').replace(/[&<>"']/g, m => ({
    '&': '\u0026amp;',
    '<': '\u0026lt;',
    '>': '\u0026gt;',
    '"': '\u0026quot;',
    "'": '\u0026#39;'
  }[m]));
}

// --- NAVIGASI HALAMAN ---
let stream = null, gOK = false, pOK = false, curLat = '', curLng = '', curAcc = 0, curLok = '', curFoto = '';

function stopCam() {
  if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
}

function nav(view) {
  stopCam();
  const u = me();

  // Proteksi halaman
  if (['absen', 'riwayat', 'sukses'].includes(view) && (!u || u.role !== 'user')) return nav(u ? 'rekap' : 'login');
  if (view === 'rekap' && (!u || u.role === 'user')) return nav(u ? 'absen' : 'login');
  if (view === 'admins' && (!u || u.role !== 'admin_utama')) return nav(u ? 'rekap' : 'login');

  document.querySelectorAll('.view').forEach(el => el.hidden = true);
  const target = $('view-' + view);
  if (target) target.hidden = false;

  // Navbar atas
  const topnav = $('topnav');
  if (!u) {
    topnav.innerHTML = `<a href="#" onclick="nav('login');return false">Masuk</a><a href="#" onclick="nav('register');return false">Daftar</a>`;
  } else if (u.role === 'user') {
    topnav.innerHTML = `
      <a href="#" onclick="nav('absen');return false">Absen</a>
      <a href="#" onclick="nav('riwayat');return false">Riwayat</a>
      <span class="chip"><i>${esc(u.nama.slice(0,2).toUpperCase())}</i>${esc(u.nama)}</span>
      <a href="#" onclick="logout();return false">Keluar</a>`;
  } else {
    topnav.innerHTML = `
      <a href="#" onclick="nav('rekap');return false">Rekap</a>
      ${u.role === 'admin_utama' ? `<a href="#" onclick="nav('admins');return false">Admin</a>` : ''}
      <span class="chip"><i>${esc(u.nama.slice(0,2).toUpperCase())}</i>${esc(u.nama)}</span>
      <a href="#" onclick="logout();return false">Keluar</a>`;
  }

  // Step bar
  const steps = { home: 1, login: 2, register: 2, forgot: 2, absen: 3, riwayat: 3, sukses: 4 };
  const st = steps[view] || 0;
  $('stepbar').hidden = !st;
  if (st) {
    [1, 2, 3].forEach(n => {
      const li = $('st' + n);
      li.className = n < st ? 'done' : (n === st ? 'now' : '');
      li.querySelector('span').textContent = n < st ? '✓' : '0' + n;
    });
  }

  // Inisialisasi tiap halaman
  if (view === 'absen') initAbsen();
  if (view === 'riwayat') renderRiwayat();
  if (view === 'rekap') renderRekap();
  if (view === 'admins') renderAdmins();
}

function logout() {
  setMe(null);
  nav('login');
}

// --- LOGIN, REGISTER, FORGOT ---
$('form-login').onsubmit = e => {
  e.preventDefault();
  const id = $('l-id').value.trim(), pw = $('l-pw').value;
  const user = getUsers().find(x => (x.nim === id || x.nama === id) && x.password === pw);
  if (!user) {
    $('err-login').hidden = false;
    $('err-login').textContent = 'NIM/nama atau password salah.';
    return;
  }
  $('err-login').hidden = true;
  setMe(user);
  nav(user.role === 'user' ? 'absen' : 'rekap');
};

$('form-register').onsubmit = e => {
  e.preventDefault();
  const nama = $('r-nama').value.trim(), nim = $('r-nim').value.trim(), tel = $('r-tel').value.trim();
  const pw = $('r-pw').value, pw2 = $('r-pw2').value;
  const err = $('err-reg');
  if (!nama || !nim || !/^[0-9+]{8,16}$/.test(tel)) {
    err.hidden = false; err.textContent = 'Lengkapi semua data. Nomor telepon hanya angka (8–16 digit).'; return;
  }
  if (pw.length < 6) { err.hidden = false; err.textContent = 'Password minimal 6 karakter.'; return; }
  if (pw !== pw2) { err.hidden = false; err.textContent = 'Konfirmasi password tidak sama.'; return; }
  const users = getUsers();
  if (users.some(x => x.nim === nim)) { err.hidden = false; err.textContent = 'NIM sudah terdaftar.'; return; }
  users.push({ id: Date.now(), nama, nim, telepon: tel, password: pw, role: 'user' });
  saveUsers(users);
  err.hidden = true;
  $('form-register').reset();
  nav('login');
};

$('form-forgot').onsubmit = e => {
  e.preventDefault();
  const tel = $('f-tel').value.trim(), id = $('f-id').value.trim(), pw = $('f-pw').value, pw2 = $('f-pw2').value;
  const err = $('err-fg'), ok = $('ok-fg');
  err.hidden = true; ok.hidden = true;
  if (pw.length < 6) { err.hidden = false; err.textContent = 'Password baru minimal 6 karakter.'; return; }
  if (pw !== pw2) { err.hidden = false; err.textContent = 'Konfirmasi password tidak sama.'; return; }
  const users = getUsers();
  const idx = users.findIndex(x => x.telepon === tel && (x.nim === id || x.nama === id));
  if (idx === -1) { err.hidden = false; err.textContent = 'Nomor telepon dan NIM/nama tidak cocok dengan akun mana pun.'; return; }
  users[idx].password = pw;
  saveUsers(users);
  ok.hidden = false; ok.textContent = 'Password berhasil diubah. Silakan masuk.';
  $('form-forgot').reset();
};

// --- FORM ABSENSI + GPS + KAMERA ---
function initAbsen() {
  const u = me();
  $('absen-greet').textContent = `Halo, ${u.nama}. Lengkapi data untuk kegiatan hari ini.`;
  $('a-nama').value = u.nama;
  $('a-tgl').value = new Date().toLocaleDateString('id-ID');
  $('err-absen').hidden = true;
  $('miss').hidden = true;
  gOK = false; pOK = false; curFoto = '';
  $('shot').hidden = true; $('redo').hidden = true; $('snap').hidden = false; $('snap').disabled = true;
  if (!window.isSecureContext) $('sec').hidden = false;
  getGPS();
  startCam();
}

function badGPS(m) {
  $('loc').className = 'loc bad';
  $('locn').textContent = 'Lokasi belum aktif';
  $('loca').textContent = m;
  $('bgps').hidden = false;
  $('bgps').textContent = 'Coba aktifkan lokasi lagi';
  gOK = false;
}

function getGPS(low) {
  if (!navigator.geolocation) { badGPS('Browser tidak mendukung GPS.'); return; }
  $('loc').className = 'loc';
  $('locn').textContent = 'Mencari lokasi Anda…';
  $('loca').textContent = 'Mohon tunggu sebentar.';
  $('bgps').hidden = true;

  navigator.geolocation.getCurrentPosition(async p => {
    curLat = p.coords.latitude;
    curLng = p.coords.longitude;
    curAcc = Math.round(p.coords.accuracy);
    let full = curLat.toFixed(5) + ', ' + curLng.toFixed(5), short = 'Koordinat ' + full;
    try {
      const r = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&zoom=18&accept-language=id&lat=${curLat}&lon=${curLng}`);
      const j = await r.json(), a = j.address || {};
      const parts = [a.amenity || a.building || a.leisure || a.road, a.suburb || a.village || a.neighbourhood, a.city || a.town || a.county || a.city_district].filter(Boolean);
      if (j.display_name) { full = j.display_name; short = parts.join(', ') || j.display_name; }
    } catch (e) {}
    curLok = full;
    $('loc').className = 'loc ok';
    $('locn').textContent = short;
    $('loca').textContent = `${full} · akurasi ±${curAcc} m`;
    gOK = true;
    $('miss').hidden = true;
  }, err => {
    if (!low && err.code !== 1) { getGPS(true); return; }
    badGPS({
      1: 'Izin lokasi ditolak. Klik ikon gembok di address bar, pilih Lokasi, lalu Izinkan.',
      2: 'Lokasi tidak tersedia. Nyalakan Location/GPS di perangkat Anda.',
      3: 'Pencarian lokasi kehabisan waktu.'
    }[err.code] || 'Lokasi gagal diambil.');
  }, { enableHighAccuracy: !low, timeout: low ? 20000 : 10000, maximumAge: 60000 });
}

function camErr(m) {
  $('cmsg').textContent = m;
  $('bcam').hidden = false;
  $('bcam').textContent = 'Coba aktifkan kamera lagi';
}

async function startCam() {
  $('cmsg').textContent = 'Meminta izin kamera…';
  $('bcam').hidden = true;
  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    camErr('Alamat ini tidak mendukung kamera. Buka lewat http://localhost atau HTTPS.');
    return;
  }
  try {
    try { stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false }); }
    catch (e) {
      if (e.name === 'NotAllowedError' || e.name === 'SecurityError') throw e;
      stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
    }
    const v = $('v');
    v.srcObject = stream; v.hidden = false; $('shot').hidden = true;
    try { await v.play(); } catch (e) {}
    $('snap').hidden = false; $('snap').disabled = false; $('redo').hidden = true;
    $('cmsg').textContent = 'Kamera aktif. Arahkan kamera lalu klik Ambil foto.';
  } catch (e) {
    camErr({
      NotAllowedError: 'Izin kamera ditolak. Klik ikon gembok di address bar, pilih Kamera, lalu Izinkan.',
      SecurityError: 'Kamera diblokir. Buka lewat http://localhost atau HTTPS.',
      NotFoundError: 'Kamera tidak ditemukan di perangkat ini.',
      NotReadableError: 'Kamera sedang dipakai aplikasi lain (mis. Zoom). Tutup dulu lalu coba lagi.'
    }[e.name] || 'Kamera gagal dibuka.');
  }
}

$('snap').onclick = () => {
  const v = $('v');
  if (!v.videoWidth) return;
  const c = document.createElement('canvas');
  c.width = v.videoWidth; c.height = v.videoHeight;
  const ctx = c.getContext('2d');
  ctx.drawImage(v, 0, 0);
  ctx.fillStyle = 'rgba(0,0,0,.55)'; ctx.fillRect(0, c.height - 28, c.width, 28);
  ctx.fillStyle = '#fff'; ctx.font = '14px sans-serif';
  ctx.fillText(new Date().toLocaleString('id-ID'), 8, c.height - 9);

  curFoto = c.toDataURL('image/jpeg', 0.7);
  $('shot').src = curFoto; $('shot').hidden = false; v.hidden = true;
  stopCam();
  $('snap').hidden = true; $('redo').hidden = false;
  $('cmsg').textContent = 'Foto tersimpan.';
  pOK = true;
  $('miss').hidden = true;
};

$('redo').onclick = () => { curFoto = ''; pOK = false; startCam(); };

$('form-absen').onsubmit = e => {
  e.preventDefault();
  const m = [];
  if (!gOK) m.push('lokasi belum aktif');
  if (!pOK) m.push('foto belum diambil dengan kamera');
  if (m.length) {
    $('miss').hidden = false;
    $('miss').textContent = 'Belum bisa dikirim: ' + m.join(' dan ') + '.';
    return;
  }

  const u = me();
  const dep = $('a-dep').value;
  const keg = $('a-keg').value.trim();
  const ket = document.querySelector('input[name="keterangan"]:checked').value;
  const todayISO = new Date().toISOString().slice(0, 10);

  const list = getAbsensi();
  if (list.some(x => x.user_id === u.id && x.kegiatan.toLowerCase() === keg.toLowerCase() && x.tanggal === todayISO)) {
    $('err-absen').hidden = false;
    $('err-absen').textContent = 'Anda sudah absen untuk kegiatan ini hari ini.';
    return;
  }

  const rec = {
    id: list.length ? Math.max(...list.map(x => x.id)) + 1 : 1,
    user_id: u.id,
    nama: u.nama,
    nim: u.nim,
    departemen: dep,
    kegiatan: keg,
    keterangan: ket,
    tanggal: todayISO,
    waktu: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
    created_at: new Date().toISOString(),
    latitude: curLat,
    longitude: curLng,
    akurasi: curAcc,
    lokasi: curLok,
    foto: curFoto
  };
  list.unshift(rec);
  saveAbsensi(list);
  $('form-absen').reset();
  $('sukses-msg').textContent = `Terima kasih, ${u.nama}. Kehadiran Anda telah tercatat.`;
  nav('sukses');
};

// --- RIWAYAT & REKAP ---
function renderRiwayat() {
  const u = me();
  const all = getAbsensi().filter(x => x.user_id === u.id);
  const sel = $('filter-user');
  const cur = sel.value;
  const kegs = [...new Set(all.map(x => x.kegiatan))].sort();
  sel.innerHTML = `<option value="">Semua kegiatan</option>` + kegs.map(k => `<option ${cur === k ? 'selected' : ''}>${esc(k)}</option>`).join('');

  const rows = cur ? all.filter(x => x.kegiatan === cur) : all;
  $('tb-riwayat').innerHTML = rows.length ? rows.map(r => `
    <tr>
      <td>${new Date(r.tanggal).toLocaleDateString('id-ID')}</td>
      <td>${esc(r.kegiatan)}</td>
      <td>${esc(r.departemen)}</td>
      <td><span class="tag ${esc(r.keterangan)}">${esc(r.keterangan)}</span></td>
      <td>${esc(r.waktu)}</td>
      <td>${esc(r.lokasi || r.latitude + ', ' + r.longitude)}</td>
      <td><img class="thumb" src="${r.foto}" alt=""></td>
      <td><a href="#" onclick="showBukti(${r.id});return false">Bukti</a></td>
    </tr>`).join('') : `<tr><td colspan="8" class="muted">Belum ada catatan. <a href="#" onclick="nav('absen');return false">Isi absensi pertama Anda</a>.</td></tr>`;
}

function renderRekap() {
  const all = getAbsensi();
  const sel = $('filter-admin');
  const cur = sel.value;
  const kegs = [...new Set(all.map(x => x.kegiatan))].sort();
  sel.innerHTML = `<option value="">Semua kegiatan</option>` + kegs.map(k => `<option ${cur === k ? 'selected' : ''}>${esc(k)}</option>`).join('');

  const rows = cur ? all.filter(x => x.kegiatan === cur) : all;
  const c = { Hadir: 0, Sakit: 0, Izin: 0 };
  rows.forEach(r => c[r.keterangan]++);
  $('admin-stats').innerHTML = `
    <div><b>${rows.length}</b>Total</div>
    <div><b>${c.Hadir}</b>Hadir</div>
    <div><b>${c.Sakit}</b>Sakit</div>
    <div><b>${c.Izin}</b>Izin</div>`;

  $('tb-rekap').innerHTML = rows.length ? rows.map(r => `
    <tr>
      <td>${new Date(r.created_at).toLocaleString('id-ID')}</td>
      <td>${esc(r.nama)}</td>
      <td>${esc(r.nim)}</td>
      <td>${esc(r.departemen)}</td>
      <td>${esc(r.kegiatan)}</td>
      <td><span class="tag ${esc(r.keterangan)}">${esc(r.keterangan)}</span></td>
      <td style="min-width:200px">${esc(r.lokasi || r.latitude + ', ' + r.longitude)}</td>
      <td><img class="thumb" src="${r.foto}" alt=""></td>
      <td><a href="#" onclick="showBukti(${r.id});return false">Bukti</a></td>
    </tr>`).join('') : `<tr><td colspan="9" class="muted">Belum ada data absensi.</td></tr>`;
}

function showBukti(id) {
  const r = getAbsensi().find(x => x.id === id);
  if (!r) return;
  $('mb-no').textContent = 'ABS-' + String(r.id).padStart(6, '0');
  $('mb-body').innerHTML = `
    <div class="detail" style="margin:0 0 12px">
      <div><span>Nama</span><b>${esc(r.nama)}</b></div>
      <div><span>NIM</span><b>${esc(r.nim)}</b></div>
      <div><span>Departemen</span><b>${esc(r.departemen)}</b></div>
      <div><span>Kegiatan</span><b>${esc(r.kegiatan)}</b></div>
      <div><span>Waktu</span><b>${new Date(r.created_at).toLocaleString('id-ID')} WIB</b></div>
      <div><span>Keterangan</span><b>${esc(r.keterangan)}</b></div>
      <div><span>Lokasi</span><b style="text-align:right;max-width:240px">${esc(r.lokasi)}</b></div>
    </div>
    <img src="${r.foto}" style="width:100%;max-height:220px;object-fit:cover;border-radius:8px" alt="Bukti foto">`;
  $('modal-bukti').style.display = 'grid';
}

// --- KELOLA ADMIN ---
function renderAdmins() {
  const admins = getUsers().filter(x => x.role === 'admin' || x.role === 'admin_utama');
  $('tb-admins').innerHTML = admins.map(r => `
    <tr>
      <td>${esc(r.nama)}</td>
      <td>${esc(r.nim)}</td>
      <td>${r.role === 'admin_utama' ? 'Admin utama (Sekretaris)' : 'Admin kedua'}</td>
      <td style="text-align:right">${r.role === 'admin' ? `<button class="btn red sm" onclick="delAdmin(${r.id})">Hapus</button>` : ''}</td>
    </tr>`).join('');
}

$('form-add-admin').onsubmit = e => {
  e.preventDefault();
  const nama = $('ad-nama').value.trim(), nim = $('ad-nim').value.trim(), tel = $('ad-tel').value.trim(), pw = $('ad-pw').value;
  const err = $('err-adm');
  if (!nama || !nim || pw.length < 6) { err.hidden = false; err.textContent = 'Lengkapi data. Password minimal 6 karakter.'; return; }
  const users = getUsers();
  if (users.some(x => x.nim === nim)) { err.hidden = false; err.textContent = 'Username/NIM sudah dipakai.'; return; }
  users.push({ id: Date.now(), nama, nim, telepon: tel, password: pw, role: 'admin' });
  saveUsers(users);
  err.hidden = true;
  $('form-add-admin').reset();
  renderAdmins();
};

function delAdmin(id) {
  if (!confirm('Hapus admin ini?')) return;
  saveUsers(getUsers().filter(x => x.id !== id));
  renderAdmins();
}

// Mulai aplikasi
nav('home');