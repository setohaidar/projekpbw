<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($conn) || $conn->connect_error) require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$penukaran_list = [];
$query = "SELECT pp.id, h.nama_hadiah, pp.poin_digunakan, pp.status, pp.tanggal_penukaran 
          FROM penukaran_poin pp
          JOIN hadiah h ON pp.hadiah_id = h.id
          WHERE pp.user_id = ?
          ORDER BY pp.tanggal_penukaran DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $penukaran_list[] = $row;
}
$stmt->close();
?>

<div class="content-header">
    <h1>Status Penukaran Hadiah</h1>
</div>
<div class="content-wrapper">

    <?php if(isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
        <div class="alert-success">
            Penukaran poin berhasil! Mohon tunggu konfirmasi dari admin.
        </div>
    <?php endif; ?>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tanggal</th>
                    <th>Hadiah</th>
                    <th>Poin</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($penukaran_list)): ?>
                    <tr><td colspan="5" style="text-align:center;">Belum ada riwayat penukaran.</td></tr>
                <?php else: ?>
                    <?php foreach ($penukaran_list as $item): ?>
                        <tr>
                            <td>#<?php echo $item['id']; ?></td>
                            <td><?php echo date('d M Y', strtotime($item['tanggal_penukaran'])); ?></td>
                            <td><?php echo htmlspecialchars($item['nama_hadiah']); ?></td>
                            <td><?php echo number_format($item['poin_digunakan']); ?></td>
                            <td><span class="status <?php echo strtolower(str_replace(' ', '-', $item['status'])); ?>"><?php echo htmlspecialchars($item['status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>