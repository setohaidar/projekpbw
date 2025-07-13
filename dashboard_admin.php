<?php
// dashboard_admin.php
session_start();
require_once 'db_connect.php';

// Keamanan: Cek apakah pengguna sudah login dan rolenya admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Ambil daftar kurir untuk dropdown penugasan
$kurir_list = [];
$result_kurir = $conn->query("SELECT id, nama_lengkap FROM users WHERE role = 'kurir'");
while ($row = $result_kurir->fetch_assoc()) {
    $kurir_list[] = $row;
}

// Ambil data pengajuan sampah
$pengajuan_list = [];
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
    ORDER BY 
        CASE 
            WHEN p.status = 'Menunggu Persetujuan' THEN 1
            WHEN p.status = 'Jadwal Ditentukan' THEN 2 
            ELSE 3 
        END, 
        p.tanggal_pengajuan ASC
";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $pengajuan_list[] = $row;
}
$conn->close();

// Variabel untuk menandai halaman aktif di sidebar
$current_page = 'manajemen_sampah';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manajemen Sampah</title>
    <link rel="stylesheet" href="dashboard_admin.css">
</head>
<body>
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>

    <div class="admin-wrapper">
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <div style="margin-bottom: 1rem; text-align: center;">
                    <span style="font-size: 1rem; font-weight: 600; color: white;">Halo, <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>!</span>
                </div>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
            <ul class="sidebar-nav">
                <li>
                    <a href="dashboard_admin.php" class="<?php echo ($current_page == 'manajemen_sampah') ? 'active' : ''; ?>">
                        Manajemen Sampah
                    </a>
                </li>
                <li>
                    <a href="admin_penukaran_hadiah.php" class="">
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
            <!-- Display Messages -->
            <?php if (isset($_GET['update'])): ?>
                <div class="message <?php echo ($_GET['update'] == 'sukses') ? 'success' : 'error'; ?>">
                    <?php if ($_GET['update'] == 'sukses'): ?>
                        ✅ Status berhasil diperbarui!
                    <?php else: ?>
                        ❌ Terjadi kesalahan saat memperbarui status.
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['assign'])): ?>
                <div class="message <?php echo ($_GET['assign'] == 'sukses') ? 'success' : 'error'; ?>">
                    <?php if ($_GET['assign'] == 'sukses'): ?>
                        ✅ Kurir berhasil ditugaskan!
                    <?php else: ?>
                        ❌ Terjadi kesalahan saat menugaskan kurir.
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="content-header">
                <h1>Manajemen Pengajuan Sampah</h1>
                <input type="text" id="searchInput" placeholder="Cari berdasarkan nama atau jenis sampah...">
                
                <div class="filter-buttons">
                    <button class="filter-btn active" data-status="Semua">Semua</button>
                    <button class="filter-btn" data-status="Menunggu Persetujuan">Menunggu Persetujuan</button>
                    <button class="filter-btn" data-status="Jadwal Ditentukan">Jadwal Ditentukan</button>
                </div>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pengguna</th>
                            <th>Detail Sampah</th>
                            <th>Jadwal</th>
                            <th>Kurir</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="searchResultsBody">
                        <?php if (empty($pengajuan_list)): ?>
                            <tr><td colspan="7" style="text-align:center; color: var(--text-secondary); padding: 3rem;">Belum ada pengajuan sampah.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pengajuan_list as $p): ?>
                                <tr class="fade-in">
                                    <td>#<?php echo $p['pengajuan_id']; ?></td>
                                    <td><?php echo htmlspecialchars($p['nama_user']); ?></td>
                                    <td><?php echo htmlspecialchars($p['jenis_sampah']); ?> (<?php echo $p['berat']; ?> kg)</td>
                                    <td><?php echo $p['jadwal_penjemputan'] ? date('d M Y, H:i', strtotime($p['jadwal_penjemputan'])) : '-'; ?></td>
                                    <td><?php echo $p['nama_kurir'] ?? 'Belum Ditugaskan'; ?></td>
                                    <td><span class="status <?php echo strtolower(str_replace(' ', '-', $p['status'])); ?>"><?php echo htmlspecialchars($p['status']); ?></span></td>
                                    <td class="action-cell">
                                        <?php if ($p['status'] == 'Menunggu Persetujuan'): ?>
                                            <form action="update_status.php" method="POST" class="action-form">
                                                <input type="hidden" name="pengajuan_id" value="<?php echo $p['pengajuan_id']; ?>">
                                                <input type="hidden" name="user_id_penerima" value="<?php echo $p['user_id']; ?>">
                                                <button type="submit" name="status_baru" value="Disetujui" class="btn-approve" onclick="return confirm('Apakah Anda yakin ingin menyetujui pengajuan ini?')">Setujui</button>
                                                <button type="submit" name="status_baru" value="Ditolak" class="btn-reject" onclick="return confirm('Apakah Anda yakin ingin menolak pengajuan ini?')">Tolak</button>
                                            </form>
                                        <?php elseif ($p['status'] == 'Jadwal Ditentukan'): ?>
                                            <form action="assign_kurir.php" method="POST" class="action-form">
                                                <input type="hidden" name="pengajuan_id" value="<?php echo $p['pengajuan_id']; ?>">
                                                <select name="kurir_id" required>
                                                    <option value="">-- Pilih Kurir --</option>
                                                    <?php foreach ($kurir_list as $kurir): ?>
                                                        <option value="<?php echo $kurir['id']; ?>"><?php echo htmlspecialchars($kurir['nama_lengkap']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menugaskan kurir ini?')">Tugaskan</button>
                                            </form>
                                        <?php else: echo '<span style="color: var(--text-secondary);">-</span>'; endif; ?>
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
    // Mobile menu functionality
    function toggleMobileMenu() {
        const sidebar = document.getElementById('adminSidebar');
        sidebar.classList.toggle('mobile-open');
    }

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('adminSidebar');
        const menuBtn = document.querySelector('.mobile-menu-btn');
        
        if (!sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
            sidebar.classList.remove('mobile-open');
        }
    });

    // Close mobile menu when window is resized to desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            document.getElementById('adminSidebar').classList.remove('mobile-open');
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const filterButtons = document.querySelectorAll('.filter-btn');
        const resultsBody = document.getElementById("searchResultsBody");
        let searchTimeout;

        // Debounced search function
        function performSearch() {
            const query = searchInput.value.trim();
            const activeFilter = document.querySelector('.filter-btn.active').getAttribute('data-status');
            
            // Add loading state
            resultsBody.classList.add('loading');
            
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "live_search.php", true);
                xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

                xhr.onreadystatechange = function() {
                    if (this.readyState == 4) {
                        resultsBody.classList.remove('loading');
                        if (this.status == 200) {
                            resultsBody.innerHTML = this.responseText;
                            // Add fade-in animation to new results
                            const newRows = resultsBody.querySelectorAll('tr');
                            newRows.forEach((row, index) => {
                                row.style.animationDelay = `${index * 0.05}s`;
                                row.classList.add('fade-in');
                            });
                        } else {
                            resultsBody.innerHTML = '<tr><td colspan="7" style="text-align:center; color: var(--status-rejected-text);">Terjadi kesalahan saat memuat data.</td></tr>';
                        }
                    }
                };
                
                xhr.send("query=" + encodeURIComponent(query) + "&status=" + encodeURIComponent(activeFilter));
            }, 300); // 300ms delay
        }

        // Search input event
        searchInput.addEventListener('keyup', performSearch);
        searchInput.addEventListener('input', performSearch);

        // Filter buttons
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');
                // Perform search with new filter
                performSearch();
            });
        });

        // Add keyboard navigation for accessibility
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                // Close mobile menu on escape
                document.getElementById('adminSidebar').classList.remove('mobile-open');
            }
        });

        // Auto-hide success/error messages after 5 seconds
        const messages = document.querySelectorAll('.message');
        messages.forEach(message => {
            setTimeout(() => {
                message.style.opacity = '0';
                message.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    message.style.display = 'none';
                }, 300);
            }, 5000);
        });

        // Add loading animation to forms
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.style.opacity = '0.7';
                    submitBtn.style.pointerEvents = 'none';
                    submitBtn.innerHTML = submitBtn.innerHTML.replace(/^/, '⏳ ');
                }
            });
        });
    });
    </script>
</body>
</html>