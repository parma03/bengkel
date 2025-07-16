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
    if ($_SESSION['role'] === 'administrator') {
        header("Location: ../dashboard/admin/index.php");
        exit();
    } else if ($_SESSION['role'] === 'kasir') {
        header("Location: ../dashboard/kasir/index.php");
        exit();
    } else if ($_SESSION['role'] === 'konsumen') {
        header("Location: ../dashboard/konsumen/index.php");
        exit();
    }
}

// Ambil alert dari session dan hapus setelah digunakan
$alert_message = isset($_SESSION['alert_message']) ? $_SESSION['alert_message'] : '';
$alert_type = isset($_SESSION['alert_type']) ? $_SESSION['alert_type'] : '';
$alert_title = isset($_SESSION['alert_title']) ? $_SESSION['alert_title'] : '';
$alert_icon = isset($_SESSION['alert_icon']) ? $_SESSION['alert_icon'] : '';

// Hapus alert dari session setelah digunakan
unset($_SESSION['alert_message'], $_SESSION['alert_type'], $_SESSION['alert_title'], $_SESSION['alert_icon']);

// Fetch transaksi dengan status 'selesai' saja
$stmt = $pdo->prepare("SELECT t.*, u.nama, u.email, u.nohp FROM tb_transaksi t JOIN tb_user u ON t.id_user = u.id_user WHERE t.status_pembayaran = 'selesai' ORDER BY t.updated_at DESC");
$stmt->execute();
$transaksi = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
<!-- [Head] start -->

<head>
    <title>Transaksi Selesai | Datta Able Dashboard Template</title>
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
            border: 1px solid #ffeaa7;
        }

        .status-dikerjakan {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-selesai {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .selesai-counter {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }

        .selesai-counter h3 {
            margin: 0;
            font-size: 2rem;
            font-weight: bold;
        }

        .selesai-counter p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }

        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .completed-badge {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
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
                        <h5 class="mb-0 font-medium">Transaksi Selesai</h5>
                    </div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item" aria-current="page">Transaksi</li>
                        <li class="breadcrumb-item"><a href="selesai.php">Selesai</a></li>
                    </ul>
                </div>
            </div>
            <!-- [ breadcrumb ] end -->

            <!-- [ Main Content ] start -->
            <div class="grid grid-cols-12 gap-x-6">
                <!-- Counter Card -->
                <div class="col-span-12 md:col-span-4">
                    <div class="selesai-counter">
                        <h3><?= count($transaksi) ?></h3>
                        <p>Transaksi Selesai</p>
                    </div>
                </div>

                <div class="col-span-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-check-circle text-success"></i> Data Transaksi Selesai
                            </h5>
                            <div class="card-header-right">
                                <span class="badge bg-success">
                                    <i class="fas fa-check-double"></i> Selesai: <?= count($transaksi) ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($transaksi)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-clipboard-check text-success" style="font-size: 4rem; opacity: 0.3;"></i>
                                    <h4 class="mt-3 text-muted">Belum Ada Transaksi Selesai</h4>
                                    <p class="text-muted">Saat ini belum ada transaksi yang telah diselesaikan</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table id="selesaiTable" class="display" style="width:100%">
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
                                                <th>Tanggal Selesai</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($transaksi as $index => $item): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-success"><?= str_pad($index + 1, 3, '0', STR_PAD_LEFT) ?></span>
                                                    </td>
                                                    <td>
                                                        <strong><?= htmlspecialchars($item['order_id']) ?></strong>
                                                    </td>
                                                    <td><?= htmlspecialchars($item['nama']) ?></td>
                                                    <td><?= htmlspecialchars($item['email']) ?></td>
                                                    <td><?= htmlspecialchars($item['nohp']) ?></td>
                                                    <td>
                                                        <span class="badge bg-info"><?= htmlspecialchars($item['type_kendaraan']) ?></span>
                                                    </td>
                                                    <td>
                                                        <strong>Rp <?= number_format($item['total_harga'], 0, ',', '.') ?></strong>
                                                    </td>
                                                    <td>
                                                        <span class="completed-badge">
                                                            <i class="fas fa-check-circle"></i> Selesai
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?= date('d/m/Y H:i', strtotime($item['updated_at'])) ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php
                                                            $waktu_selesai = time() - strtotime($item['updated_at']);
                                                            if ($waktu_selesai < 3600) {
                                                                echo floor($waktu_selesai / 60) . ' menit lalu';
                                                            } elseif ($waktu_selesai < 86400) {
                                                                echo floor($waktu_selesai / 3600) . ' jam lalu';
                                                            } else {
                                                                echo floor($waktu_selesai / 86400) . ' hari lalu';
                                                            }
                                                            ?>
                                                        </small>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
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
            $('#selesaiTable').DataTable({
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
                ], // Sort by date column (index 8) descending (newest first)
                columnDefs: [{
                        targets: [6], // Total Harga column
                        className: 'text-right'
                    },
                    {
                        targets: [7], // Status column
                        className: 'text-center'
                    }
                ],
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50, 100]
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
    </script>

</body>
<!-- [Body] end -->

</html>