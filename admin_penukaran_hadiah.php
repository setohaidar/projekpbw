<?php
// admin_penukaran_hadiah.php
session_start();
require_once 'db_connect.php';

// Keamanan: Cek role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Ambil daftar kurir untuk dropdown
$kurir_list = [];
$result_kurir = $conn->query("SELECT id, nama_lengkap FROM users WHERE role = 'kurir'");
while ($row = $result_kurir->fetch_assoc()) {
    $kurir_list[] = $row;
}

// Ambil data penukaran poin
$penukaran_list = [];
$query = "
    SELECT 
        pp.id AS penukaran_id, pp.user_id, pp.status, pp.tanggal_penukaran,
        u_user.nama_lengkap AS nama_user, h.nama_hadiah,
        CONCAT_WS(', ', a.alamat, a.no_rumah, a.kelurahan, a.kecamatan, a.kota) as alamat_lengkap,
        u_kurir.nama_lengkap AS nama_kurir
    FROM penukaran_poin pp
    JOIN users u_user ON pp.user_id = u_user.id
    JOIN hadiah h ON pp.hadiah_id = h.id
    JOIN alamat a ON pp.alamat_id = a.id
    LEFT JOIN users u_kurir ON pp.kurir_id = u_kurir.id
    ORDER BY CASE WHEN pp.status = 'Menunggu Persetujuan' THEN 1 WHEN pp.status = 'Siap Dikirim' THEN 2 WHEN pp.status = 'Dalam Pengiriman' THEN 3 ELSE 4 END, pp.tanggal_penukaran ASC
";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $penukaran_list[] = $row;
    }
}
$conn->close();

$current_page = 'manajemen_hadiah';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manajemen Hadiah</title>
    <link rel="stylesheet" href="dashboard_admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <div style="margin-bottom: 1rem; text-align: center;">
                    <span style="font-size: 1rem; font-weight: 600; color: white;">Halo, <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>!</span>
                </div>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
            <ul class="sidebar-nav">
                <li>
                    <a href="dashboard_admin.php" class="">
                        Manajemen Sampah
                    </a>
                </li>
                <li>
                    <a href="admin_penukaran_hadiah.php" class="<?php echo ($current_page == 'manajemen_hadiah') ? 'active' : ''; ?>">
                        Manajemen Hadiah
                    </a>
                </li>
                <li>
                    <a href="admin_stok_sampah.php" class="">
                        Stok Sampah
                    </a>
                </li>
            </ul>
        </aside>

        <main class="admin-main-content">
            <div class="content-header">
                <h1>Manajemen Penukaran Hadiah</h1>
                <input type="text" id="searchInput" placeholder="Cari berdasarkan nama pengguna atau hadiah..." style="width: 100%; padding: 12px; margin-top: 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div class="filter-buttons">
                    <button class="filter-btn active" data-status="Semua">Semua</button>
                    <button class="filter-btn" data-status="Menunggu Persetujuan">Menunggu Persetujuan</button>
                    <button class="filter-btn" data-status="Siap Dikirim">Siap Dikirim</button>
                    <button class="filter-btn" data-status="Dalam Pengiriman">Dalam Pengiriman</button>
                </div>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th><th>Pengguna</th><th>Hadiah</th><th>Alamat Pengiriman</th><th>Kurir</th><th>Status</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="searchResultsBody">
                        <?php if (empty($penukaran_list)): ?>
                            <tr><td colspan="7" style="text-align:center;">Belum ada penukaran hadiah.</td></tr>
                        <?php else: ?>
                            <?php foreach ($penukaran_list as $p): ?>
                                <tr>
                                    <td>#<?php echo $p['penukaran_id']; ?></td>
                                    <td><?php echo htmlspecialchars($p['nama_user']); ?></td>
                                    <td><?php echo htmlspecialchars($p['nama_hadiah']); ?></td>
                                    <td><?php echo htmlspecialchars($p['alamat_lengkap']); ?></td>
                                    <td><?php echo htmlspecialchars($p['nama_kurir'] ?? 'Belum Ditugaskan'); ?></td>
                                    <td><span class="status <?php echo strtolower(str_replace(' ', '-', $p['status'])); ?>"><?php echo htmlspecialchars($p['status']); ?></span></td>
                                    <td class="action-cell">
                                        <?php if ($p['status'] == 'Menunggu Persetujuan'): ?>
                                            <form action="update_penukaran_status.php" method="POST" class="action-form">
                                                <input type="hidden" name="penukaran_id" value="<?php echo $p['penukaran_id']; ?>">
                                                <input type="hidden" name="user_id_penerima" value="<?php echo $p['user_id']; ?>">
                                                <button type="submit" name="status_baru" value="Siap Dikirim" class="btn-approve">Setujui</button>
                                                <button type="submit" name="status_baru" value="Ditolak" class="btn-reject">Tolak</button>
                                            </form>
                                        <?php elseif ($p['status'] == 'Siap Dikirim'): ?>
                                            <form action="assign_kurir_hadiah.php" method="POST">
                                                <input type="hidden" name="penukaran_id" value="<?php echo $p['penukaran_id']; ?>">
                                                <input type="hidden" name="user_id_penerima" value="<?php echo $p['user_id']; ?>">
                                                <select name="kurir_id" required>
                                                    <option value="">-- Pilih Kurir --</option>
                                                    <?php foreach ($kurir_list as $kurir): ?>
                                                        <option value="<?php echo $kurir['id']; ?>"><?php echo htmlspecialchars($kurir['nama_lengkap']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit">Tugaskan</button>
                                            </form>
                                        <?php else: echo '-'; endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchInput');
            const filterButtons = document.querySelectorAll('.filter-btn');

            function performSearch() {
                const query = searchInput.value;
                const activeFilter = document.querySelector('.filter-btn.active').getAttribute('data-status');
                const resultsBody = document.getElementById("searchResultsBody");

                const xhr = new XMLHttpRequest();
                xhr.open("POST", "live_search_hadiah.php", true);
                xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

                xhr.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        resultsBody.innerHTML = this.responseText;
                    }
                };
                
                xhr.send("query=" + encodeURIComponent(query) + "&status=" + encodeURIComponent(activeFilter));
            }

            searchInput.addEventListener('keyup', performSearch);

            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    performSearch();
                });
            });
        });
    </script>
</body>
</html>