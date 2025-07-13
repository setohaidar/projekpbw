<?php
// dashboard_kurir.php
session_start();
require_once 'db_connect.php';

// Keamanan: Hanya kurir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'kurir') {
    header("Location: login.php");
    exit();
}

$kurir_id = $_SESSION['user_id'];

// Query diubah untuk mengambil semua status tugas aktif kurir
$tugas_list = [];
$query = "
    -- Tugas Penjemputan Sampah
    SELECT 
        p.id AS tugas_id, 'penjemputan' AS tipe_tugas, CONCAT('Jemput: ', p.jenis_sampah) AS judul_tugas,
        p.status, p.jadwal_penjemputan AS jadwal, u.nama_lengkap AS nama_user, u.nomor_telepon,
        CONCAT_WS(', ', a.alamat, a.no_rumah, a.kelurahan) AS alamat_lengkap
    FROM pengajuan p
    JOIN users u ON p.user_id = u.id
    JOIN alamat a ON p.alamat_id = a.id
    WHERE p.kurir_id = ? AND p.status IN ('Dalam Penjemputan', 'Sampah Sedang Diantar')

    UNION ALL

    -- Tugas Pengiriman Hadiah
    SELECT 
        pp.id AS tugas_id, 'pengiriman' AS tipe_tugas, CONCAT('Kirim: ', h.nama_hadiah) AS judul_tugas,
        pp.status, pp.tanggal_penukaran AS jadwal, u.nama_lengkap AS nama_user, u.nomor_telepon,
        CONCAT_WS(', ', a.alamat, a.no_rumah, a.kelurahan) AS alamat_lengkap
    FROM penukaran_poin pp
    JOIN users u ON pp.user_id = u.id
    JOIN alamat a ON pp.alamat_id = a.id
    JOIN hadiah h ON pp.hadiah_id = h.id
    WHERE pp.kurir_id = ? AND pp.status = 'Dalam Pengiriman'

    ORDER BY jadwal ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $kurir_id, $kurir_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $tugas_list[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kurir</title>
    <link rel="stylesheet" href="dashboard_kurir.css">
</head>
<body>
    <header class="kurir-header">
        <h1>Tugas Hari Ini</h1>
        <nav>
            <span>Halo, <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>!</span>
            <a href="logout.php" class="btn-logout">Logout</a>
        </nav>
    </header>

    <main class="kurir-container">
        <?php if (empty($tugas_list)): ?>
            <div class="no-task"><p>Belum ada tugas untuk Anda saat ini.</p></div>
        <?php else: ?>
            <div class="task-grid">
                <?php foreach ($tugas_list as $tugas): ?>
                    <div class="task-card <?php echo $tugas['tipe_tugas']; ?>">
                        <div class="task-header">
                            <h3><?php echo htmlspecialchars($tugas['judul_tugas']); ?></h3>
                            <span class="status <?php echo strtolower(str_replace(' ', '-', $tugas['status'])); ?>"><?php echo htmlspecialchars($tugas['status']); ?></span>
                        </div>
                        <div class="task-body">
                            <p><strong>Pelanggan:</strong> <?php echo htmlspecialchars($tugas['nama_user']); ?> (<?php echo htmlspecialchars($tugas['nomor_telepon']); ?>)</p>
                            <p><strong>Alamat:</strong> <?php echo htmlspecialchars($tugas['alamat_lengkap']); ?></p>
                        </div>
                        <div class="task-footer">
                            <form action="update_kurir_tugas.php" method="POST">
                                <input type="hidden" name="tugas_id" value="<?php echo $tugas['tugas_id']; ?>">
                                <input type="hidden" name="tipe_tugas" value="<?php echo $tugas['tipe_tugas']; ?>">
                                
                                <?php if ($tugas['status'] == 'Dalam Penjemputan'): ?>
                                    <button type="submit" name="status_baru" value="Sampah Sedang Diantar" class="btn-action btn-pickup">Ambil Sampah</button>
                                    <button type="submit" name="status_baru" value="Dibatalkan" class="btn-action btn-cancel">Batalkan</button>
                                <?php elseif ($tugas['status'] == 'Sampah Sedang Diantar'): ?>
                                    <button type="submit" name="status_baru" value="Selesai" class="btn-action btn-finish">Selesaikan Tugas</button>
                                <?php elseif ($tugas['status'] == 'Dalam Pengiriman'): ?>
                                    <button type="submit" name="status_baru" value="Selesai" class="btn-action btn-finish">Hadiah Terkirim</button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>