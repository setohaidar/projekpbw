<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($conn) || $conn->connect_error) require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$riwayat_list = [];
$query = "SELECT p.id, p.tanggal_pengajuan, p.jenis_sampah, p.berat, p.status, u_kurir.nama_lengkap AS nama_kurir
          FROM pengajuan p
          LEFT JOIN users u_kurir ON p.kurir_id = u_kurir.id
          WHERE p.user_id = ? AND p.status IN ('Selesai', 'Dibatalkan', 'Ditolak')
          ORDER BY p.tanggal_pengajuan DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $riwayat_list[] = $row;
}
$stmt->close();
?>

<div class="content-header">
    <h1>Riwayat Pembuangan</h1>
</div>
<div class="content-wrapper">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tanggal</th>
                    <th>Detail Sampah</th>
                    <th>Kurir</th>
                    <th>Status Akhir</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($riwayat_list)): ?>
                    <tr><td colspan="5" style="text-align:center;">Tidak ada riwayat transaksi.</td></tr>
                <?php else: ?>
                    <?php foreach ($riwayat_list as $r): ?>
                        <tr>
                            <td>#<?php echo $r['id']; ?></td>
                            <td><?php echo date('d M Y', strtotime($r['tanggal_pengajuan'])); ?></td>
                            <td><?php echo htmlspecialchars($r['jenis_sampah']); ?> (<?php echo $r['berat']; ?> kg)</td>
                            <td><?php echo htmlspecialchars($r['nama_kurir'] ?? 'N/A'); ?></td>
                            <td><span class="status <?php echo strtolower(str_replace(' ', '-', $r['status'])); ?>"><?php echo htmlspecialchars($r['status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>