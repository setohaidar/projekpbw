<?php
session_start();
require_once 'db_connect.php';

// Keamanan: Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    exit('Akses ditolak');
}

// Ambil daftar kurir untuk form penugasan
$kurir_list = [];
$result_kurir_q = $conn->query("SELECT id, nama_lengkap FROM users WHERE role = 'kurir'");
while ($row = $result_kurir_q->fetch_assoc()) {
    $kurir_list[] = $row;
}

// Ambil parameter dari AJAX
$query_param = isset($_POST['query']) ? $_POST['query'] : '';
$status_filter = isset($_POST['status']) ? $_POST['status'] : 'Semua';
$search_term = "%" . $query_param . "%";

// Siapkan parameter dan tipe untuk prepared statement
$sql_params = [$search_term, $search_term];
$sql_types = "ss";

// Query dasar untuk mencari berdasarkan nama pengguna atau nama hadiah
$query = "
    SELECT 
        pp.id AS penukaran_id, pp.user_id, pp.status,
        u_user.nama_lengkap AS nama_user, h.nama_hadiah,
        CONCAT_WS(', ', a.alamat, a.no_rumah, a.kelurahan, a.kecamatan, a.kota) as alamat_lengkap,
        u_kurir.nama_lengkap AS nama_kurir
    FROM penukaran_poin pp
    JOIN users u_user ON pp.user_id = u_user.id
    JOIN hadiah h ON pp.hadiah_id = h.id
    JOIN alamat a ON pp.alamat_id = a.id
    LEFT JOIN users u_kurir ON pp.kurir_id = u_kurir.id
    WHERE (u_user.nama_lengkap LIKE ? OR h.nama_hadiah LIKE ?)
";

// Tambahkan filter status jika bukan 'Semua'
if ($status_filter !== 'Semua') {
    $query .= " AND pp.status = ?";
    $sql_params[] = $status_filter;
    $sql_types .= "s";
}

// Tambahkan pengurutan
$query .= " ORDER BY CASE WHEN pp.status = 'Menunggu Persetujuan' THEN 1 WHEN pp.status = 'Siap Dikirim' THEN 2 WHEN pp.status = 'Dalam Pengiriman' THEN 3 ELSE 4 END, pp.tanggal_penukaran ASC";

// Eksekusi query
$stmt = $conn->prepare($query);
$stmt->bind_param($sql_types, ...$sql_params);
$stmt->execute();
$result = $stmt->get_result();

// Buat HTML untuk baris tabel dan kirimkan kembali ke AJAX
if ($result->num_rows > 0) {
    while ($p = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>#" . $p['penukaran_id'] . "</td>";
        echo "<td>" . htmlspecialchars($p['nama_user']) . "</td>";
        echo "<td>" . htmlspecialchars($p['nama_hadiah']) . "</td>";
        echo "<td>" . htmlspecialchars($p['alamat_lengkap']) . "</td>";
        echo "<td>" . htmlspecialchars($p['nama_kurir'] ?? 'Belum Ditugaskan') . "</td>";
        echo "<td><span class='status " . strtolower(str_replace(' ', '-', $p['status'])) . "'>" . htmlspecialchars($p['status']) . "</span></td>";
        echo "<td class='action-cell'>";
        
        // PERBAIKAN DI SINI
        if ($p['status'] == 'Menunggu Persetujuan') {
            echo '<form action="update_penukaran_status.php" method="POST" class="action-form">
                    <input type="hidden" name="penukaran_id" value="' . $p['penukaran_id'] . '">
                    <input type="hidden" name="user_id_penerima" value="' . $p['user_id'] . '">
                    <button type="submit" name="status_baru" value="Siap Dikirim" class="btn-approve">Setujui</button>
                    <button type="submit" name="status_baru" value="Ditolak" class="btn-reject">Tolak</button>
                  </form>';
        } elseif ($p['status'] == 'Siap Dikirim') {
            echo '<form action="assign_kurir_hadiah.php" method="POST">
                    <input type="hidden" name="penukaran_id" value="' . $p['penukaran_id'] . '">
                    <input type="hidden" name="user_id_penerima" value="' . $p['user_id'] . '">
                    <select name="kurir_id" required>
                        <option value="">-- Pilih Kurir --</option>';
            foreach ($kurir_list as $kurir) {
                echo '<option value="' . $kurir['id'] . '">' . htmlspecialchars($kurir['nama_lengkap']) . '</option>';
            }
            echo    '</select>
                    <button type="submit">Tugaskan</button>
                  </form>';
        } else {
            echo '-';
        }

        echo "</td>";
        echo "</tr>";
    }
} else {
    echo '<tr><td colspan="7" style="text-align:center;">Tidak ada hasil ditemukan.</td></tr>';
}

$stmt->close();
$conn->close();
?>