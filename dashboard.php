<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($conn) || $conn->connect_error) require_once 'db_connect.php';

$user_id = $_SESSION['user_id'];

$stmt_poin = $conn->prepare("SELECT poin FROM users WHERE id = ?");
$stmt_poin->bind_param("i", $user_id);
$stmt_poin->execute();
$poin_pengguna = $stmt_poin->get_result()->fetch_assoc()['poin'];
$stmt_poin->close();
?>

<div class="content-header">
    <h1>Dashboard</h1>
</div>
<div class="content-wrapper">
    <h3>Poin Anda Saat Ini: <strong><?php echo number_format($poin_pengguna); ?></strong></h3>
    <p>Selamat datang di dasbor Anda. Gunakan menu di samping untuk menavigasi aplikasi.</p>
</div>