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
        <!-- Display Messages -->
        <?php if (isset($_GET['update'])): ?>
            <div class="message <?php echo ($_GET['update'] == 'sukses') ? 'success' : 'error'; ?>">
                <?php if ($_GET['update'] == 'sukses'): ?>
                    ✅ Status tugas berhasil diperbarui!
                <?php else: ?>
                    ❌ Terjadi kesalahan saat memperbarui status tugas. <?php echo isset($_GET['error']) ? htmlspecialchars($_GET['error']) : ''; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="message error">
                <?php 
                switch($_GET['error']) {
                    case 'invalid_task':
                        echo "❌ Tipe tugas tidak valid.";
                        break;
                    case 'unauthorized':
                        echo "❌ Anda tidak memiliki akses untuk tugas ini.";
                        break;
                    default:
                        echo "❌ Terjadi kesalahan sistem.";
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if (empty($tugas_list)): ?>
            <div class="no-task">
                <p>Belum ada tugas untuk Anda saat ini.</p>
                <small style="margin-top: 1rem; display: block; color: var(--text-secondary);">Tugas baru akan muncul disini ketika admin menugaskan Anda</small>
            </div>
        <?php else: ?>
            <div class="task-grid">
                <?php foreach ($tugas_list as $index => $tugas): ?>
                    <div class="task-card <?php echo $tugas['tipe_tugas']; ?>" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                        <div class="task-header">
                            <h3><?php echo htmlspecialchars($tugas['judul_tugas']); ?></h3>
                            <span class="status <?php echo strtolower(str_replace(' ', '-', $tugas['status'])); ?>"><?php echo htmlspecialchars($tugas['status']); ?></span>
                        </div>
                        <div class="task-body">
                            <p><strong>Pelanggan:</strong> <?php echo htmlspecialchars($tugas['nama_user']); ?></p>
                            <p><strong>Telepon:</strong> <a href="tel:<?php echo htmlspecialchars($tugas['nomor_telepon']); ?>" style="color: var(--primary-green); text-decoration: none;"><?php echo htmlspecialchars($tugas['nomor_telepon']); ?></a></p>
                            <p><strong>Alamat:</strong> <?php echo htmlspecialchars($tugas['alamat_lengkap']); ?></p>
                            <?php if ($tugas['jadwal']): ?>
                                <p><strong>Jadwal:</strong> <?php echo date('d M Y, H:i', strtotime($tugas['jadwal'])); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="task-footer">
                            <form action="update_kurir_tugas.php" method="POST" onsubmit="return handleFormSubmit(this);">
                                <input type="hidden" name="tugas_id" value="<?php echo $tugas['tugas_id']; ?>">
                                <input type="hidden" name="tipe_tugas" value="<?php echo $tugas['tipe_tugas']; ?>">
                                
                                <?php if ($tugas['status'] == 'Dalam Penjemputan'): ?>
                                    <button type="submit" name="status_baru" value="Sampah Sedang Diantar" class="btn-action btn-pickup" onclick="return confirm('Konfirmasi bahwa Anda telah mengambil sampah?')">
                                        📦 Ambil Sampah
                                    </button>
                                    <button type="submit" name="status_baru" value="Dibatalkan" class="btn-action btn-cancel" onclick="return confirm('Apakah Anda yakin ingin membatalkan tugas ini?')">
                                        ❌ Batalkan
                                    </button>
                                <?php elseif ($tugas['status'] == 'Sampah Sedang Diantar'): ?>
                                    <button type="submit" name="status_baru" value="Selesai" class="btn-action btn-finish" onclick="return confirm('Konfirmasi bahwa tugas penjemputan telah selesai?')">
                                        ✅ Selesaikan Tugas
                                    </button>
                                <?php elseif ($tugas['status'] == 'Dalam Pengiriman'): ?>
                                    <button type="submit" name="status_baru" value="Selesai" class="btn-action btn-finish" onclick="return confirm('Konfirmasi bahwa hadiah telah terkirim?')">
                                        🎁 Hadiah Terkirim
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
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

        // Add staggered animation to task cards
        const taskCards = document.querySelectorAll('.task-card');
        taskCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });

        // Add ripple effect to buttons
        const buttons = document.querySelectorAll('.btn-action');
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.style.position = 'absolute';
                ripple.style.background = 'rgba(255,255,255,0.6)';
                ripple.style.borderRadius = '50%';
                ripple.style.transform = 'scale(0)';
                ripple.style.animation = 'ripple 0.6s linear';
                ripple.style.pointerEvents = 'none';
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(event) {
            if (event.altKey && event.key === 'r') {
                event.preventDefault();
                window.location.reload();
            }
        });

        // Add refresh notification
        let refreshTimeout;
        function showRefreshNotification() {
            const notification = document.createElement('div');
            notification.innerHTML = '🔄 Memuat tugas terbaru...';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--primary-green);
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                box-shadow: var(--shadow-lg);
                z-index: 1000;
                animation: slideIn 0.3s ease;
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Auto-refresh every 30 seconds
        setInterval(() => {
            showRefreshNotification();
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }, 30000);

        // Service Worker for offline support (if needed)
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(console.error);
        }
    });

    // Handle form submission with loading state
    function handleFormSubmit(form) {
        const submitButton = form.querySelector('button[type="submit"]:focus') || form.querySelector('button[type="submit"]');
        if (submitButton) {
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '⏳ Memproses...';
            submitButton.style.opacity = '0.7';
            submitButton.style.pointerEvents = 'none';
            
            // Add loading state to the entire task card
            const taskCard = form.closest('.task-card');
            if (taskCard) {
                taskCard.classList.add('loading');
            }
            
            // Restore button state if form submission fails
            setTimeout(() => {
                if (submitButton) {
                    submitButton.innerHTML = originalText;
                    submitButton.style.opacity = '1';
                    submitButton.style.pointerEvents = 'auto';
                }
                if (taskCard) {
                    taskCard.classList.remove('loading');
                }
            }, 10000); // 10 second timeout
        }
        return true;
    }

    // Add CSS for ripple effect
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .btn-action {
            position: relative;
            overflow: hidden;
        }
        
        .task-card {
            position: relative;
        }
        
        .task-card.loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
        
        .task-card.loading::before {
            content: '⏳';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 2rem;
            z-index: 11;
            animation: pulse 1s infinite;
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>