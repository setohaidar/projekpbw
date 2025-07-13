<?php
session_start();
require_once 'db_connect.php';

// Keamanan: Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    exit('Akses ditolak');
}

// Ambil daftar kurir lagi untuk form
$kurir_list = [];
$result_kurir = $conn->query("SELECT id, nama_lengkap FROM users WHERE role = 'kurir'");
while ($row = $result_kurir->fetch_assoc()) {
    $kurir_list[] = $row;
}

// Ambil parameter dari AJAX
$query_param = isset($_POST['query']) ? $_POST['query'] : '';
$status_filter = isset($_POST['status']) ? $_POST['status'] : 'Semua';

$search_term = "%" . $query_param . "%";

// Bangun query SQL secara dinamis
$sql_params = [$search_term, $search_term];
$sql_types = "ss";

$query = "
    SELECT 
        p.id AS pengajuan_id, 
        p.user_id, 
        p.jenis_sampah, 
        p.berat, 
        p.status, 
        p.jadwal_penjemputan,
        u.nama_lengkap AS nama_user,
        k.nama_lengkap AS nama_kurir
    FROM pengajuan p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN users k ON p.kurir_id = k.id
    WHERE (u.nama_lengkap LIKE ? OR p.jenis_sampah LIKE ?)
";

// Tambahkan filter status jika bukan 'Semua'
if ($status_filter !== 'Semua') {
    $query .= " AND p.status = ?";
    $sql_params[] = $status_filter;
    $sql_types .= "s";
}

$query .= "
    ORDER BY 
        CASE 
            WHEN p.status = 'Menunggu Persetujuan' THEN 1
            WHEN p.status = 'Jadwal Ditentukan' THEN 2 
            ELSE 3 
        END, 
        p.tanggal_pengajuan ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param($sql_types, ...$sql_params);
$stmt->execute();
$result = $stmt->get_result();

// Tampilkan hasil
if ($result->num_rows > 0) {
    while ($p = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>#" . $p['pengajuan_id'] . "</td>";
        echo "<td>" . htmlspecialchars($p['nama_user']) . "</td>";
        echo "<td>" . htmlspecialchars($p['jenis_sampah']) . " (" . $p['berat'] . " kg)</td>";
        echo "<td>" . ($p['jadwal_penjemputan'] ? date('d M Y, H:i', strtotime($p['jadwal_penjemputan'])) : '-') . "</td>";
        echo "<td>" . ($p['nama_kurir'] ?? 'Belum Ditugaskan') . "</td>";
        echo "<td><span class='status " . strtolower(str_replace(' ', '-', $p['status'])) . "'>" . htmlspecialchars($p['status']) . "</span></td>";
        echo "<td class='action-cell'>";
        
        if ($p['status'] == 'Menunggu Persetujuan') {
            echo '<form action="update_status.php" method="POST" class="action-form">';
            echo '<input type="hidden" name="pengajuan_id" value="' . $p['pengajuan_id'] . '">';
            echo '<input type="hidden" name="user_id_penerima" value="' . $p['user_id'] . '">';
            echo '<button type="submit" name="status_baru" value="Disetujui" class="btn-approve">Setujui</button>';
            echo '<button type="submit" name="status_baru" value="Ditolak" class="btn-reject">Tolak</button>';
            echo '</form>';
        } elseif ($p['status'] == 'Jadwal Ditentukan') {
            echo '<form action="assign_kurir.php" method="POST">';
            echo '<input type="hidden" name="pengajuan_id" value="' . $p['pengajuan_id'] . '">';
            echo '<select name="kurir_id" required>';
            echo '<option value="">-- Pilih Kurir --</option>';
            foreach ($kurir_list as $kurir) {
                echo '<option value="' . $kurir['id'] . '">' . htmlspecialchars($kurir['nama_lengkap']) . '</option>';
            }
            echo '</select>';
            echo '<button type="submit">Tugaskan</button>';
            echo '</form>';
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