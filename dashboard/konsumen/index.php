<?php
session_start();
include '../../db/koneksi.php';

// Inisialisasi variabel untuk alert
$alert_message = '';
$alert_type = '';
$alert_title = '';
$alert_icon = '';

// Process booking
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'booking') {
        try {
            $id_user = $_SESSION['id_user'];
            $type_kendaraan = $_POST['type_kendaraan'];
            $status_pembayaran = 'menunggu';

            $stmt = $pdo->prepare("INSERT INTO tb_transaksi (id_user, type_kendaraan, status_pembayaran, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$id_user, $type_kendaraan, $status_pembayaran]);

            $_SESSION['alert_message'] = 'Booking berhasil dibuat! Anda akan dihubungi untuk konfirmasi jadwal.';
            $_SESSION['alert_type'] = 'success';
            $_SESSION['alert_title'] = 'Booking Berhasil!';
            $_SESSION['alert_icon'] = 'success';

            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } catch (Exception $e) {
            $_SESSION['alert_message'] = 'Terjadi kesalahan: ' . $e->getMessage();
            $_SESSION['alert_type'] = 'error';
            $_SESSION['alert_title'] = 'Error!';
            $_SESSION['alert_icon'] = 'error';
        }
    }
}

// Ambil alert dari session dan hapus setelah digunakan
$alert_message = isset($_SESSION['alert_message']) ? $_SESSION['alert_message'] : '';
$alert_type = isset($_SESSION['alert_type']) ? $_SESSION['alert_type'] : '';
$alert_title = isset($_SESSION['alert_title']) ? $_SESSION['alert_title'] : '';
$alert_icon = isset($_SESSION['alert_icon']) ? $_SESSION['alert_icon'] : '';

// Hapus alert dari session setelah digunakan
unset($_SESSION['alert_message'], $_SESSION['alert_type'], $_SESSION['alert_title'], $_SESSION['alert_icon']);

// Get user's current booking status
$stmt = $pdo->prepare("SELECT * FROM tb_transaksi WHERE id_user = ? AND status_pembayaran IN ('menunggu', 'dikerjakan') ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$_SESSION['id_user']]);
$current_booking = $stmt->fetch();

// Get waiting list (all bookings with status 'menunggu' and 'dikerjakan')
$stmt = $pdo->prepare("
    SELECT t.*, u.nama, u.nohp, u.email 
    FROM tb_transaksi t 
    JOIN tb_user u ON t.id_user = u.id_user 
    WHERE t.status_pembayaran IN ('menunggu', 'dikerjakan') 
    ORDER BY t.created_at ASC
");
$stmt->execute();
$waiting_list = $stmt->fetchAll();

// Find user's queue position
$queue_position = 0;
if ($current_booking) {
    foreach ($waiting_list as $index => $booking) {
        if ($booking['id_transaksi'] == $current_booking['id_transaksi']) {
            $queue_position = $index + 1;
            break;
        }
    }
}

// Get user info
$stmt = $pdo->prepare("SELECT * FROM tb_user WHERE id_user = ?");
$stmt->execute([$_SESSION['id_user']]);
$user_info = $stmt->fetch();
?>

<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">

<head>
    <title>Booking Service | Bengkel Management</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="../../assets/images/favicon.svg" type="image/x-icon" />

    <!-- [Font] Family -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <!-- [phosphor Icons] -->
    <link rel="stylesheet" href="../../assets/fonts/phosphor/duotone/style.css" />
    <!-- [Tabler Icons] -->
    <link rel="stylesheet" href="../../assets/fonts/tabler-icons.min.css" />
    <!-- [Feather Icons] -->
    <link rel="stylesheet" href="../../assets/fonts/feather.css" />
    <!-- [Font Awesome Icons] -->
    <link rel="stylesheet" href="../../assets/fonts/fontawesome.css" />
    <!-- [Material Icons] -->
    <link rel="stylesheet" href="../../assets/fonts/material.css" />
    <!-- [Template CSS Files] -->
    <link rel="stylesheet" href="../../assets/css/style.css" id="main-style-link" />

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        .booking-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .booking-form {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .queue-card {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .queue-number {
            font-size: 3rem;
            font-weight: bold;
            color: #ff6b6b;
            text-align: center;
            margin-bottom: 10px;
        }

        .waiting-list-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .vehicle-option {
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .vehicle-option:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .vehicle-option.selected {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .vehicle-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .vehicle-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #667eea;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-menunggu {
            background: #fff3cd;
            color: #856404;
        }

        .status-dikerjakan {
            background: #d4edda;
            color: #155724;
        }

        .status-selesai {
            background: #d1ecf1;
            color: #0c5460;
        }

        .queue-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .queue-item:last-child {
            border-bottom: none;
        }

        .queue-item.current-user {
            background: #f8f9ff;
            border-left: 4px solid #667eea;
        }

        .queue-number-small {
            background: #667eea;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            font-size: 14px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .form-control {
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .alert-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg fixed inset-0 bg-white dark:bg-themedark-cardbg z-[1034]">
        <div class="loader-track h-[5px] w-full inline-block absolute overflow-hidden top-0">
            <div class="loader-fill w-[300px] h-[5px] bg-primary-500 absolute top-0 left-0 animate-[hitZak_0.6s_ease-in-out_infinite_alternate]"></div>
        </div>
    </div>
    <!-- [ Pre-loader ] End -->

    <!-- [ Sidebar Menu ] start -->
    <?php include '_component/sidebar.php'; ?>
    <!-- [ Sidebar Menu ] end -->

    <!-- [ Header Topbar ] start -->
    <?php include '_component/header.php'; ?>
    <!-- [ Header ] end -->

    <!-- [ Main Content ] start -->
    <div class="pc-container">
        <div class="pc-content">
            <!-- [ breadcrumb ] start -->
            <div class="page-header">
                <div class="page-block">
                    <div class="page-header-title">
                        <h5 class="mb-0 font-medium">Booking Service</h5>
                    </div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item" aria-current="page">Booking Service</li>
                    </ul>
                </div>
            </div>
            <!-- [ breadcrumb ] end -->

            <!-- [ Main Content ] start -->
            <div class="row">
                <div class="col-lg-8">
                    <!-- Welcome Card -->
                    <div class="booking-card">
                        <h2 class="mb-3">
                            <i class="fas fa-wrench me-2"></i>
                            Selamat Datang, <?= htmlspecialchars($user_info['nama']) ?>!
                        </h2>
                        <p class="mb-0">Booking service kendaraan Anda dengan mudah dan cepat. Kami siap melayani kebutuhan perawatan kendaraan Anda.</p>
                    </div>

                    <!-- Current Booking Status -->
                    <?php if ($current_booking): ?>
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle me-2"></i>Status Booking Anda</h5>
                            <p class="mb-2">
                                <strong>Kendaraan:</strong> <?= htmlspecialchars($current_booking['type_kendaraan']) ?><br>
                                <strong>Status:</strong>
                                <span class="status-badge status-<?= $current_booking['status_pembayaran'] ?>">
                                    <?= ucfirst($current_booking['status_pembayaran']) ?>
                                </span><br>
                                <strong>Tanggal Booking:</strong> <?= date('d M Y H:i', strtotime($current_booking['created_at'])) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Booking Form -->
                    <?php if (!$current_booking): ?>
                        <div class="booking-form">
                            <h4 class="mb-4">
                                <i class="fas fa-calendar-plus me-2"></i>
                                Buat Booking Baru
                            </h4>

                            <form method="POST" id="bookingForm">
                                <input type="hidden" name="action" value="booking">

                                <div class="mb-4">
                                    <label class="form-label">Pilih Jenis Kendaraan</label>
                                    <div class="vehicle-options">
                                        <div class="vehicle-option" onclick="selectVehicle(this, 'Motor')">
                                            <input type="radio" name="type_kendaraan" value="Motor" required>
                                            <div class="text-center">
                                                <div class="vehicle-icon">
                                                    <i class="fas fa-motorcycle"></i>
                                                </div>
                                                <h5>Motor</h5>
                                                <p class="text-muted mb-0">Service motor, ganti oli, tune up</p>
                                            </div>
                                        </div>

                                        <div class="vehicle-option" onclick="selectVehicle(this, 'Mobil')">
                                            <input type="radio" name="type_kendaraan" value="Mobil" required>
                                            <div class="text-center">
                                                <div class="vehicle-icon">
                                                    <i class="fas fa-car"></i>
                                                </div>
                                                <h5>Mobil</h5>
                                                <p class="text-muted mb-0">Service mobil, perawatan berkala</p>
                                            </div>
                                        </div>

                                        <div class="vehicle-option" onclick="selectVehicle(this, 'Truk')">
                                            <input type="radio" name="type_kendaraan" value="Truk" required>
                                            <div class="text-center">
                                                <div class="vehicle-icon">
                                                    <i class="fas fa-truck"></i>
                                                </div>
                                                <h5>Truk</h5>
                                                <p class="text-muted mb-0">Service truk, maintenance berat</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-calendar-check me-2"></i>
                                        Buat Booking
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="booking-form">
                            <div class="text-center">
                                <i class="fas fa-clock text-warning" style="font-size: 3rem; margin-bottom: 20px;"></i>
                                <h4>Anda sudah memiliki booking aktif</h4>
                                <p class="text-muted">Silakan tunggu hingga booking selesai untuk membuat booking baru</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-lg-4">
                    <!-- Queue Position -->
                    <?php if ($current_booking && $queue_position > 0): ?>
                        <div class="queue-card">
                            <div class="text-center">
                                <h5 class="mb-3">Posisi Antrian Anda</h5>
                                <div class="queue-number"><?= $queue_position ?></div>
                                <p class="mb-0">dari <?= count($waiting_list) ?> antrian</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Waiting List -->
                    <div class="waiting-list-card">
                        <h5 class="mb-4">
                            <i class="fas fa-list me-2"></i>
                            Daftar Antrian
                        </h5>

                        <?php if (empty($waiting_list)): ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-clipboard-list" style="font-size: 2rem; margin-bottom: 10px;"></i>
                                <p>Tidak ada antrian saat ini</p>
                            </div>
                        <?php else: ?>
                            <div class="queue-list">
                                <?php foreach ($waiting_list as $index => $booking): ?>
                                    <div class="queue-item <?= ($booking['id_user'] == $_SESSION['id_user']) ? 'current-user' : '' ?>">
                                        <div class="queue-number-small"><?= $index + 1 ?></div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">
                                                <?= htmlspecialchars($booking['nama']) ?>
                                                <?= ($booking['id_user'] == $_SESSION['id_user']) ? '<span class="badge bg-primary">Anda</span>' : '' ?>
                                            </h6>
                                            <p class="text-muted mb-1">
                                                <i class="fas fa-car me-1"></i>
                                                <?= htmlspecialchars($booking['type_kendaraan']) ?>
                                            </p>
                                            <small class="text-muted">
                                                <?= date('d M Y H:i', strtotime($booking['created_at'])) ?>
                                            </small>
                                        </div>
                                        <div>
                                            <span class="status-badge status-<?= $booking['status_pembayaran'] ?>">
                                                <?= ucfirst($booking['status_pembayaran']) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- [ Main Content ] end -->
        </div>
    </div>
    <!-- [ Main Content ] end -->

    <?php include '_component/footer.php'; ?>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Required Js -->
    <script src="../../assets/js/plugins/simplebar.min.js"></script>
    <script src="../../assets/js/plugins/popper.min.js"></script>
    <script src="../../assets/js/icon/custom-icon.js"></script>
    <script src="../../assets/js/plugins/feather.min.js"></script>
    <script src="../../assets/js/component.js"></script>
    <script src="../../assets/js/theme.js"></script>
    <script src="../../assets/js/script.js"></script>

    <script>
        // Show alert if exists
        <?php if (!empty($alert_message)): ?>
            Swal.fire({
                icon: '<?= $alert_icon ?>',
                title: '<?= $alert_title ?>',
                text: '<?= $alert_message ?>',
                showConfirmButton: false,
                timer: 3000
            });
        <?php endif; ?>

        // Select vehicle function
        function selectVehicle(element, value) {
            // Remove selected class from all options
            document.querySelectorAll('.vehicle-option').forEach(option => {
                option.classList.remove('selected');
            });

            // Add selected class to clicked option
            element.classList.add('selected');

            // Check the radio button
            element.querySelector('input[type="radio"]').checked = true;
        }

        // Form submission with confirmation
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const selectedVehicle = document.querySelector('input[name="type_kendaraan"]:checked');

            if (!selectedVehicle) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pilih Kendaraan',
                    text: 'Silakan pilih jenis kendaraan terlebih dahulu',
                });
                return;
            }

            Swal.fire({
                title: 'Konfirmasi Booking',
                html: `
                    <div style="text-align: center;">
                        <i class="fas fa-${selectedVehicle.value === 'Motor' ? 'motorcycle' : selectedVehicle.value === 'Mobil' ? 'car' : 'truck'}" style="font-size: 3rem; color: #667eea; margin-bottom: 20px;"></i>
                        <p>Anda akan membuat booking untuk:</p>
                        <h4 style="color: #667eea; margin: 10px 0;">${selectedVehicle.value}</h4>
                        <p style="color: #666; font-size: 14px;">Setelah booking dibuat, Anda akan masuk ke dalam antrian dan akan dihubungi untuk konfirmasi jadwal.</p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#667eea',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: '<i class="fas fa-check"></i> Ya, Buat Booking',
                cancelButtonText: '<i class="fas fa-times"></i> Batal',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Sedang membuat booking Anda',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Submit form
                    this.submit();
                }
            });
        });

        // Auto refresh every 30 seconds to update queue
        setInterval(function() {
            location.reload();
        }, 30000);

        // Layout scripts
        layout_change('false');
        layout_theme_sidebar_change('dark');
        change_box_container('false');
        layout_caption_change('true');
        layout_rtl_change('false');
        preset_change('preset-1');
        main_layout_change('vertical');
    </script>
</body>

</html>