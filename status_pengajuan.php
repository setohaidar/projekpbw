<?php
// status_pengajuan.php (Konten)
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($conn) || $conn->connect_error) require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$pengajuan_list = [];
$query = "SELECT p.id, p.tanggal_pengajuan, p.jenis_sampah, p.berat, p.status, p.jadwal_penjemputan 
          FROM pengajuan p
          WHERE p.user_id = ? AND p.status NOT IN ('Selesai', 'Dibatalkan', 'Ditolak')
          ORDER BY p.tanggal_pengajuan DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pengajuan_list[] = $row;
}
$stmt->close();
?>

<div class="content-header">
    <h1>Status Pengajuan</h1>
</div>
<div class="content-wrapper">
    <?php if(isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
        <div class="alert alert-success">Pengajuan Anda berhasil dikirim! Mohon tunggu konfirmasi dari admin.</div>
    <?php elseif(isset($_GET['jadwal']) && $_GET['jadwal'] == 'sukses'): ?>
        <div class="alert alert-success">Jadwal penjemputan berhasil diatur!</div>
    <?php endif; ?>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tanggal</th>
                    <th>Detail Sampah</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pengajuan_list)): ?>
                    <tr><td colspan="5" style="text-align:center;">Tidak ada pengajuan aktif.</td></tr>
                <?php else: ?>
                    <?php foreach ($pengajuan_list as $p): ?>
                        <tr>
                            <td>#<?php echo $p['id']; ?></td>
                            <td><?php echo date('d M Y', strtotime($p['tanggal_pengajuan'])); ?></td>
                            <td><?php echo htmlspecialchars($p['jenis_sampah']); ?> (<?php echo $p['berat']; ?> kg)</td>
                            <td><span class="status <?php echo strtolower(str_replace(' ', '-', $p['status'])); ?>"><?php echo htmlspecialchars($p['status']); ?></span></td>
                            <td class="action-cell">
                                <?php if ($p['status'] == 'Disetujui'): ?>
                                    <form action="set_jadwal.php" method="POST" class="jadwal-form">
                                        <input type="hidden" name="pengajuan_id" value="<?php echo $p['id']; ?>">
                                        <input type="datetime-local" name="jadwal_penjemputan" required>
                                        <button type="submit">Atur Jadwal</button>
                                    </form>
                                <?php else: echo '-'; endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>