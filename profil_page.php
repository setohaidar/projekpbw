<?php
// profil_page.php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($conn) || $conn->connect_error) require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Mengambil 'kode_pos' dari database
$stmt = $conn->prepare("
    SELECT u.nama_lengkap, u.email, u.nomor_telepon, a.alamat, a.no_rumah, a.rt, a.rw, a.kelurahan, a.kecamatan, a.kota, a.provinsi, a.kode_pos
    FROM users u
    LEFT JOIN alamat a ON u.id = a.user_id
    WHERE u.id = ? LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

// Memeriksa field alamat, termasuk 'kode_pos'
$alamat_fields = ['alamat', 'no_rumah', 'rt', 'rw', 'kelurahan', 'kecamatan', 'kota', 'provinsi', 'kode_pos'];

foreach ($alamat_fields as $field) {
    if (!isset($user_data[$field]) || $user_data[$field] === null) {
        $user_data[$field] = '';
    }
}
?>

<div class="content-header">
    <h1>Profil Saya</h1>
</div>
<div class="content-wrapper">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <form action="update_profil.php" method="POST">
        <div class="input-group">
            <label for="nama_lengkap">Nama Lengkap</label>
            <input type="text" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($user_data['nama_lengkap']); ?>" required>
        </div>
        <div class="input-group">
            <label for="nomor_telepon">Nomor Telepon</label>
            <input type="tel" id="nomor_telepon" name="nomor_telepon" value="<?php echo htmlspecialchars($user_data['nomor_telepon']); ?>" required>
        </div>
        <div class="input-group">
            <label for="email">Email (tidak dapat diubah)</label>
            <input type="email" id="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" readonly>
        </div>
        
        <h3 style="margin-top: 2rem; margin-bottom: 1rem; border-top: 1px solid #e2e8f0; padding-top: 1rem;">Alamat Pengiriman/Penjemputan</h3>
        
        <div class="input-group">
            <label for="alamat">Alamat</label>
            <input type="text" id="alamat" name="alamat" placeholder="Contoh: Jalan Otto Iskandardinata" value="<?php echo htmlspecialchars($user_data['alamat']); ?>">
        </div>
        <div class="input-group">
            <label for="no_rumah">Nomor Rumah</label>
            <input type="text" id="no_rumah" name="no_rumah" placeholder="Contoh: 64C" value="<?php echo htmlspecialchars($user_data['no_rumah']); ?>">
        </div>
        <div class="input-group">
            <label for="rt">RT</label>
            <input type="text" id="rt" name="rt" placeholder="Contoh: 001" value="<?php echo htmlspecialchars($user_data['rt']); ?>">
        </div>
        <div class="input-group">
            <label for="rw">RW</label>
            <input type="text" id="rw" name="rw" placeholder="Contoh: 004" value="<?php echo htmlspecialchars($user_data['rw']); ?>">
        </div>
        <div class="input-group">
            <label for="kelurahan">Kelurahan / Desa</label>
            <input type="text" id="kelurahan" name="kelurahan" placeholder="Contoh: Bidara Cina" value="<?php echo htmlspecialchars($user_data['kelurahan']); ?>">
        </div>
        <div class="input-group">
            <label for="kecamatan">Kecamatan</label>
            <input type="text" id="kecamatan" name="kecamatan" placeholder="Contoh: Jatinegara" value="<?php echo htmlspecialchars($user_data['kecamatan']); ?>">
        </div>
        <div class="input-group">
            <label for="kota">Kota / Kabupaten</label>
            <input type="text" id="kota" name="kota" placeholder="Contoh: Jakarta Timur" value="<?php echo htmlspecialchars($user_data['kota']); ?>">
        </div>
        
        <div class="input-group">
            <label for="provinsi">Provinsi</label>
            <input type="text" id="provinsi" name="provinsi" placeholder="Contoh: DKI Jakarta" value="<?php echo htmlspecialchars($user_data['provinsi']); ?>">
        </div>

        <div class="input-group">
            <label for="kode_pos">Kode Pos</label>
            <input type="number" id="kode_pos" name="kode_pos" placeholder="Contoh: 12530" value="<?php echo htmlspecialchars($user_data['kode_pos']); ?>">
        </div>
        
        <button type="submit" class="btn-submit">Simpan Perubahan</button>
    </form>
</div>