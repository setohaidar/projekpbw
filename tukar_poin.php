<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($conn) || $conn->connect_error) require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$hadiah_list = [];
$result = $conn->query("SELECT * FROM hadiah WHERE stok > 0 ORDER BY poin_dibutuhkan ASC");
while ($row = $result->fetch_assoc()) {
    $hadiah_list[] = $row;
}

$stmt_poin = $conn->prepare("SELECT poin FROM users WHERE id = ?");
$stmt_poin->bind_param("i", $user_id);
$stmt_poin->execute();
$poin_pengguna = $stmt_poin->get_result()->fetch_assoc()['poin'];
$stmt_poin->close();
?>

<div class="content-header">
    <h1>Tukar Poin</h1>
</div>
<div class="content-wrapper">
    <div class="points-info">
        Poin Anda: <strong><?php echo number_format($poin_pengguna); ?></strong>
    </div>

    <div class="rewards-grid">
        <?php if (empty($hadiah_list)): ?>
            <p>Belum ada hadiah yang tersedia.</p>
        <?php else: ?>
            <?php foreach ($hadiah_list as $hadiah): ?>
                <div class="reward-card <?php echo ($poin_pengguna < $hadiah['poin_dibutuhkan']) ? 'disabled' : ''; ?>">
                    <img src="<?php echo htmlspecialchars($hadiah['url_gambar']); ?>" alt="<?php echo htmlspecialchars($hadiah['nama_hadiah']); ?>">
                    <div class="reward-info">
                        <h3><?php echo htmlspecialchars($hadiah['nama_hadiah']); ?></h3>
                        <p class="points"><?php echo number_format($hadiah['poin_dibutuhkan']); ?> Poin</p>
                        <small>Stok: <?php echo $hadiah['stok']; ?></small>
                        <?php if ($poin_pengguna >= $hadiah['poin_dibutuhkan']): ?>
                            <a href="form_penukaran_poin.php?id=<?php echo $hadiah['id']; ?>" class="btn-tukar">Tukar</a>
                        <?php else: ?>
                            <button class="btn-tukar" disabled>Poin Kurang</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>