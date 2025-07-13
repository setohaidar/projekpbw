<?php
// admin_stok_sampah.php (Versi Baru dengan Urutan Custom)
session_start();
require_once 'db_connect.php';

// Keamanan: Cek role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Query untuk mengambil data dari tabel stok_sampah dengan urutan custom
$stok_list = [];
$query = "
    SELECT id, jenis_sampah, total_berat 
    FROM stok_sampah 
    WHERE total_berat > 0 
    ORDER BY 
      CASE jenis_sampah
        WHEN 'Sampah Organik Kering' THEN 1
        WHEN 'Sampah Organik Basah' THEN 2
        WHEN 'Plastik' THEN 3
        WHEN 'Styrofoam' THEN 4
        WHEN 'Kaleng' THEN 5
        WHEN 'Beling/Kaca' THEN 6
        WHEN 'Tekstil' THEN 7
        WHEN 'Baterai' THEN 8
        WHEN 'Kabel' THEN 9
        WHEN 'Lampu' THEN 10
        WHEN 'Sampah Elektronik Lainnya' THEN 11
        WHEN 'Obat Kedaluwarsa' THEN 12
        ELSE 99
      END ASC
";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $stok_list[] = $row;
    }
}
$conn->close();

$current_page = 'stok_sampah'; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Stok Sampah</title>
    <link rel="stylesheet" href="dashboard_admin.css">
    <style>
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 25px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 12px; }
        .close-btn { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .modal-content h3 { margin-top: 0; }
        .modal-content .input-group { margin-bottom: 1rem; }
        .modal-content .input-group label { display: block; margin-bottom: 5px; }
        .modal-content .input-group input, .modal-content .input-group textarea { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
             <div class="sidebar-header">
                <div style="margin-bottom: 1rem; text-align: center;"><span style="font-size: 1rem; font-weight: 600; color: white;">Halo, <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>!</span></div>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
            <ul class="sidebar-nav">
                <li><a href="dashboard_admin.php">Manajemen Sampah</a></li>
                <li><a href="admin_penukaran_hadiah.php">Manajemen Hadiah</a></li>
                <li><a href="admin_stok_sampah.php" class="active">Stok Sampah</a></li>
            </ul>
        </aside>

        <main class="admin-main-content">
            <div class="content-header"><h1>Stok Sampah Siap Olah</h1></div>
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert" style="background-color: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
            <?php endif; ?>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Jenis Sampah</th><th>Total Stok (kg)</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php if (empty($stok_list)): ?>
                            <tr><td colspan="3" style="text-align:center;">Stok sampah kosong.</td></tr>
                        <?php else: ?>
                            <?php foreach ($stok_list as $stok): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stok['jenis_sampah']); ?></td>
                                    <td><?php echo number_format($stok['total_berat'], 2); ?> kg</td>
                                    <td><button class="btn-delete open-modal-btn" data-jenis="<?php echo htmlspecialchars($stok['jenis_sampah']); ?>" data-max-berat="<?php echo $stok['total_berat']; ?>">Olah/Hapus Data</button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div id="processModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span><h3>Proses Pengolahan Sampah</h3>
            <form action="proses_hapus_stok.php" method="POST" onsubmit="return confirm('Anda yakin ingin melanjutkan? Aksi ini akan mengurangi stok secara permanen.');">
                <input type="hidden" id="modalJenisSampah" name="jenis_sampah">
                <div class="input-group"><label for="modalJenis">Jenis Sampah:</label><input type="text" id="modalJenis" disabled style="background:#eee;"></div>
                <div class="input-group"><label for="modalBerat">Berat yang Diolah (kg):</label><input type="number" id="modalBerat" name="berat_diolah" step="0.1" required></div>
                <div class="input-group"><label for="modalCatatan">Catatan/Alasan Proses:</label><textarea id="modalCatatan" name="catatan" rows="3" required placeholder="Contoh: Didaur ulang menjadi biji plastik"></textarea></div>
                <button type="submit" class="btn-approve">Konfirmasi & Proses Stok</button>
            </form>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('processModal');
        const openModalButtons = document.querySelectorAll('.open-modal-btn');
        const closeModalBtn = document.querySelector('.close-btn');
        openModalButtons.forEach(button => {
            button.addEventListener('click', function() {
                const jenisSampah = this.getAttribute('data-jenis');
                const maxBerat = this.getAttribute('data-max-berat');
                document.getElementById('modalJenisSampah').value = jenisSampah;
                document.getElementById('modalJenis').value = jenisSampah;
                const beratInput = document.getElementById('modalBerat');
                beratInput.max = maxBerat;
                beratInput.placeholder = `Maksimal ${maxBerat} kg`;
                modal.style.display = 'block';
            });
        });
        closeModalBtn.onclick = function() { modal.style.display = 'none'; }
        window.onclick = function(event) { if (event.target == modal) { modal.style.display = 'none'; } }
    });
    </script>
</body>
</html>