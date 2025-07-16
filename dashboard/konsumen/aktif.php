<?php
session_start();
include '../../db/koneksi.php';

// Inisialisasi variabel untuk alert
$alert_message = '';
$alert_type = '';
$alert_title = '';
$alert_icon = '';

// Pengecekan session untuk redirect jika sudah login
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'mekanik') {
        header("Location: ../dashboard/mekanik/index.php");
        exit();
    } else if ($_SESSION['role'] === 'kasir') {
        header("Location: ../dashboard/kasir/index.php");
        exit();
    } else if ($_SESSION['role'] === 'administrator') {
        header("Location: ../dashboard/admin/index.php");
        exit();
    }
}

// Include Midtrans configuration
require_once '../../config/midtrans.php';

// Process payment with Midtrans
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'bayar') {
        try {
            $id_transaksi = $_POST['id_transaksi'];

            // Get transaction details
            $stmt = $pdo->prepare("SELECT t.*, u.nama, u.email, u.nohp FROM tb_transaksi t JOIN tb_user u ON t.id_user = u.id_user WHERE t.id_transaksi = ?");
            $stmt->execute([$id_transaksi]);
            $transaksi = $stmt->fetch();

            if (!$transaksi) {
                throw new Exception('Transaksi tidak ditemukan');
            }
            // Generate unique order ID
            $order_id = 'ORDER-' . $id_transaksi . '-' . time();

            // Midtrans transaction parameters
            $params = [
                'transaction_details' => [
                    'order_id' => $order_id,
                    'gross_amount' => (int)$transaksi['total_harga'],
                ],
                'customer_details' => [
                    'first_name' => $transaksi['nama'],
                    'email' => $transaksi['email'],
                    'phone' => $transaksi['nohp'],
                ],
                'item_details' => [
                    [
                        'id' => 'service-' . $id_transaksi,
                        'price' => (int)$transaksi['total_harga'],
                        'quantity' => 1,
                        'name' => 'Service ' . $transaksi['type_kendaraan'],
                    ]
                ],
                'enabled_payments' => ['gopay'],
                'custom_expiry' => [
                    'order_time' => date('Y-m-d H:i:s O'),
                    'expiry_duration' => 15, // 15 menit
                    'unit' => 'minute'
                ]
            ];

            // Create Snap token
            $snapToken = \Midtrans\Snap::getSnapToken($params);

            // Update transaction with order_id and snap_token
            $stmt = $pdo->prepare("UPDATE tb_transaksi SET order_id = ?, snap_token = ?, status_pembayaran = 'pending', updated_at = NOW() WHERE id_transaksi = ?");
            $stmt->execute([$order_id, $snapToken, $id_transaksi]);

            // Return success response
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'snap_token' => $snapToken,
                'order_id' => $order_id
            ]);
            exit();
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit();
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

// Fetch transaksi with specific status
$stmt = $pdo->prepare("SELECT t.*, u.nama, u.email, u.nohp FROM tb_transaksi t JOIN tb_user u ON t.id_user = u.id_user WHERE t.status_pembayaran IN ('menunggu', 'dikerjakan', 'selesai', 'pending') ORDER BY t.created_at DESC");
$stmt->execute();
$transaksi = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
<!-- [Head] start -->

<head>
    <title>Transaksi Management | Datta Able Dashboard Template</title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Datta Able is trending dashboard template made using Bootstrap 5 design framework." />
    <meta name="keywords" content="Bootstrap admin template, Dashboard UI Kit, Dashboard Template, Backend Panel" />
    <meta name="author" content="CodedThemes" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="../../assets/images/favicon.svg" type="image/x-icon" />

    <!-- [Font] Family -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <!-- [phosphor Icons] https://phosphoricons.com/ -->
    <link rel="stylesheet" href="../../assets/fonts/phosphor/duotone/style.css" />
    <!-- [Tabler Icons] https://tablericons.com -->
    <link rel="stylesheet" href="../../assets/fonts/tabler-icons.min.css" />
    <!-- [Feather Icons] https://feathericons.com -->
    <link rel="stylesheet" href="../../assets/fonts/feather.css" />
    <!-- [Font Awesome Icons] https://fontawesome.com/icons -->
    <link rel="stylesheet" href="../../assets/fonts/fontawesome.css" />
    <!-- [Material Icons] https://fonts.google.com/icons -->
    <link rel="stylesheet" href="../../assets/fonts/material.css" />
    <!-- [Template CSS Files] -->
    <link rel="stylesheet" href="../../assets/css/style.css" id="main-style-link" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="Mid-client-HxtMaomSfaOxOq09"></script>

    <style>
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-menunggu {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-dikerjakan {
            background-color: #d4edda;
            color: #155724;
        }

        .status-selesai {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .btn-bayar {
            background: linear-gradient(45deg, #00d4aa, #00b894);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .btn-bayar:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 212, 170, 0.3);
        }
    </style>
</head>
<!-- [Head] end -->
<!-- [Body] Start -->

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
                        <h5 class="mb-0 font-medium">Transaksi Management</h5>
                    </div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item" aria-current="page">Transaksi</li>
                        <li class="breadcrumb-item"><a href="aktif.php">Data Transaksi Aktif</a></li>
                    </ul>
                </div>
            </div>
            <!-- [ breadcrumb ] end -->

            <!-- [ Main Content ] start -->
            <div class="grid grid-cols-12 gap-x-6">
                <div class="col-span-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Data Transaksi</h5>
                            <div class="card-header-right">
                                <span class="badge bg-info">Total: <?= count($transaksi) ?> Transaksi</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="transaksiTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Order ID</th>
                                        <th>Nama Konsumen</th>
                                        <th>Email</th>
                                        <th>No HP</th>
                                        <th>Type Kendaraan</th>
                                        <th>Total Harga</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transaksi as $index => $item): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($item['order_id']) ?></td>
                                            <td><?= htmlspecialchars($item['nama']) ?></td>
                                            <td><?= htmlspecialchars($item['email']) ?></td>
                                            <td><?= htmlspecialchars($item['nohp']) ?></td>
                                            <td><?= htmlspecialchars($item['type_kendaraan']) ?></td>
                                            <td>Rp <?= number_format($item['total_harga'], 0, ',', '.') ?></td>
                                            <td>
                                                <span class="status-badge status-<?= $item['status_pembayaran'] ?>">
                                                    <?= ucfirst($item['status_pembayaran']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></td>
                                            <td>
                                                <?php if ($item['status_pembayaran'] == 'selesai'): ?>
                                                    <button class="btn btn-bayar btn-sm" onclick="bayarTransaksi(<?= $item['id_transaksi'] ?>)">
                                                        <i class="fas fa-qrcode"></i> Bayar QRIS
                                                    </button>
                                                <?php elseif ($item['status_pembayaran'] == 'pending'): ?>
                                                    <button class="btn btn-bayar btn-sm" onclick="bayarTransaksi(<?= $item['id_transaksi'] ?>)">
                                                        <i class="fas fa-qrcode"></i> Bayar QRIS
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
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

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

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
        $(document).ready(function() {
            $('#transaksiTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data per halaman",
                    zeroRecords: "Tidak ada data yang ditemukan",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                    infoFiltered: "(disaring dari _MAX_ total data)",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Selanjutnya",
                        previous: "Sebelumnya"
                    }
                },
                responsive: true,
                order: [
                    [8, 'desc']
                ], // Sort by date column (index 8) descending
                columnDefs: [{
                        targets: [6], // Total Harga column
                        className: 'text-right'
                    },
                    {
                        targets: [7], // Status column
                        className: 'text-center'
                    },
                    {
                        targets: [9], // Action column
                        className: 'text-center',
                        orderable: false
                    }
                ]
            });

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
        });

        // Function untuk bayar transaksi dengan QRIS
        function bayarTransaksi(idTransaksi) {
            Swal.fire({
                title: 'Konfirmasi Pembayaran',
                html: `
            <div style="text-align: center;">
                <i class="fas fa-qrcode" style="font-size: 3rem; color: #00d4aa; margin-bottom: 20px;"></i>
                <p>Anda akan melakukan pembayaran dengan <strong>QRIS</strong></p>
                <p style="color: #666; font-size: 14px;">Scan QR Code menggunakan aplikasi mobile banking atau e-wallet Anda</p>
            </div>
        `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#00d4aa',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: '<i class="fas fa-credit-card"></i> Bayar Sekarang',
                cancelButtonText: '<i class="fas fa-times"></i> Batal',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Memproses Pembayaran...',
                        text: 'Sedang menyiapkan pembayaran QRIS',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Send AJAX request to create payment
                    $.ajax({
                        url: window.location.href,
                        method: 'POST',
                        data: {
                            action: 'bayar',
                            id_transaksi: idTransaksi
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                // Close loading
                                Swal.close();

                                // Open Midtrans Snap
                                window.snap.pay(response.snap_token, {
                                    onSuccess: function(result) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Pembayaran Berhasil!',
                                            text: 'Terima kasih, pembayaran Anda telah berhasil diproses.',
                                            showConfirmButton: false,
                                            timer: 3000
                                        }).then(() => {
                                            location.reload();
                                        });
                                    },
                                    onPending: function(result) {
                                        Swal.fire({
                                            icon: 'info',
                                            title: 'Pembayaran Pending',
                                            text: 'Pembayaran Anda sedang diproses. Silakan cek status pembayaran secara berkala.',
                                            showConfirmButton: false,
                                            timer: 3000
                                        }).then(() => {
                                            location.reload();
                                        });
                                    },
                                    onError: function(result) {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Pembayaran Gagal!',
                                            text: 'Terjadi kesalahan dalam proses pembayaran. Silakan coba lagi.',
                                        });
                                    },
                                    onClose: function() {
                                        Swal.fire({
                                            icon: 'warning',
                                            title: 'Pembayaran Dibatalkan',
                                            text: 'Anda menutup halaman pembayaran. Silakan coba lagi jika ingin melanjutkan pembayaran.',
                                        });
                                    }
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: response.message || 'Terjadi kesalahan dalam memproses pembayaran'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Terjadi kesalahan koneksi. Silakan coba lagi.'
                            });
                        }
                    });
                }
            });
        }
    </script>

</body>
<!-- [Body] end -->

</html>