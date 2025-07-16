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

// Process actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Simpan pengerjaan dan selesaikan transaksi
    if (isset($_POST['action']) && $_POST['action'] == 'selesaikan_transaksi') {
        try {
            $id_transaksi = $_POST['id_transaksi'];
            $barang_ids = isset($_POST['barang_ids']) ? $_POST['barang_ids'] : [];
            $service_ids = isset($_POST['service_ids']) ? $_POST['service_ids'] : [];

            // Validasi input
            if (empty($barang_ids) && empty($service_ids)) {
                throw new Exception('Minimal pilih satu barang atau service');
            }

            // Check if transaction exists and has correct status
            $stmt = $pdo->prepare("SELECT * FROM tb_transaksi WHERE id_transaksi = ? AND status_pembayaran = 'dikerjakan'");
            $stmt->execute([$id_transaksi]);
            $transaksi = $stmt->fetch();

            if (!$transaksi) {
                throw new Exception('Transaksi tidak ditemukan atau status tidak valid');
            }

            // Begin transaction
            $pdo->beginTransaction();

            // Calculate total harga
            $total_harga = 0;

            // Process barang
            if (!empty($barang_ids)) {
                foreach ($barang_ids as $barang_id) {
                    // Get barang price
                    $stmt = $pdo->prepare("SELECT harga_barang FROM tb_barang WHERE id_barang = ?");
                    $stmt->execute([$barang_id]);
                    $barang = $stmt->fetch();

                    if ($barang) {
                        $total_harga += $barang['harga_barang'];

                        // Insert ke tb_pengerjaan dengan id_barang positif
                        $stmt = $pdo->prepare("INSERT INTO tb_pengerjaan (id_transaksi, id_barang_or_service) VALUES (?, ?)");
                        $stmt->execute([$id_transaksi, $barang_id]);
                    }
                }
            }

            // Process service
            if (!empty($service_ids)) {
                foreach ($service_ids as $service_id) {
                    // Get service price
                    $stmt = $pdo->prepare("SELECT harga_service FROM tb_service WHERE id_service = ?");
                    $stmt->execute([$service_id]);
                    $service = $stmt->fetch();

                    if ($service) {
                        $total_harga += $service['harga_service'];

                        // Insert ke tb_pengerjaan dengan id_service negatif untuk membedakan dari barang
                        $stmt = $pdo->prepare("INSERT INTO tb_pengerjaan (id_transaksi, id_barang_or_service) VALUES (?, ?)");
                        $stmt->execute([$id_transaksi, -$service_id]);
                    }
                }
            }

            // Update transaksi status dan total harga
            $stmt = $pdo->prepare("UPDATE tb_transaksi SET status_pembayaran = 'selesai', total_harga = ?, updated_at = NOW() WHERE id_transaksi = ?");
            $stmt->execute([$total_harga, $id_transaksi]);

            // Commit transaction
            $pdo->commit();

            // Return success response
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Transaksi berhasil diselesaikan',
                'total_harga' => number_format($total_harga, 0, ',', '.')
            ]);
            exit();
        } catch (Exception $e) {
            // Rollback transaction
            if ($pdo->inTransaction()) {
                $pdo->rollback();
            }

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

// Fetch transaksi dengan status 'dikerjakan' saja
$stmt = $pdo->prepare("SELECT t.*, u.nama, u.email, u.nohp FROM tb_transaksi t JOIN tb_user u ON t.id_user = u.id_user WHERE t.status_pembayaran = 'dikerjakan' ORDER BY t.updated_at ASC");
$stmt->execute();
$transaksi = $stmt->fetchAll();

// Fetch all barang yang ada stok
$stmt = $pdo->prepare("SELECT * FROM tb_barang WHERE stok_barang > 0 ORDER BY nama_barang");
$stmt->execute();
$barang_list = $stmt->fetchAll();

// Fetch all service
$stmt = $pdo->prepare("SELECT * FROM tb_service ORDER BY nama_service");
$stmt->execute();
$service_list = $stmt->fetchAll();
?>

<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">

<head>
    <title>Transaksi Dikerjakan | Bengkel Dashboard</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="../../assets/images/favicon.svg" type="image/x-icon" />

    <!-- [Font] Family -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />

    <!-- [Template CSS Files] -->
    <link rel="stylesheet" href="../../assets/css/style.css" id="main-style-link" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        .swal2-container {
            z-index: 9999;
        }

        .select2-container {
            z-index: 9999;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #007bff;
            color: white;
            border: 1px solid #007bff;
            border-radius: 4px;
            padding: 2px 8px;
            margin: 2px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: white;
            margin-right: 5px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
            padding: 10px;
            border-radius: 5px;
        }

        .total-preview {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            border: 2px solid #28a745;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(40, 167, 69, 0.1);
        }

        .total-preview h5 {
            margin: 0 0 10px 0;
            color: #495057;
            font-weight: 600;
        }

        .total-preview .amount {
            font-size: 1.5em;
            font-weight: bold;
            color: #28a745;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }

        .btn-complete {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 13px;
        }

        .btn-complete:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
            background: linear-gradient(45deg, #20c997, #17a2b8);
        }

        .working-badge {
            background: linear-gradient(45deg, #ff9800, #f57c00);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }

            100% {
                opacity: 1;
            }
        }

        .dikerjakan-counter {
            background: linear-gradient(45deg, #f39c12, #e67e22);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }

        .dikerjakan-counter h3 {
            margin: 0;
            font-size: 2rem;
            font-weight: bold;
        }

        .dikerjakan-counter p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .select2-container--default .select2-selection--multiple {
            border-radius: 6px;
            border: 1px solid #ddd;
            min-height: 40px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #007bff;
            color: white;
            border: 1px solid #007bff;
            border-radius: 4px;
        }

        .total-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 10px;
            margin-top: 10px;
            text-align: center;
        }

        .total-preview h5 {
            margin: 0;
            color: #495057;
        }

        .total-preview .amount {
            font-size: 1.2em;
            font-weight: bold;
            color: #28a745;
        }

        .form-section {
            margin-bottom: 15px;
        }

        .form-section h6 {
            margin-bottom: 8px;
            color: #495057;
            font-weight: 600;
        }

        .item-info {
            font-size: 12px;
            color: #6c757d;
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
                        <h5 class="mb-0 font-medium">Transaksi Dikerjakan</h5>
                    </div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item" aria-current="page">Transaksi</li>
                        <li class="breadcrumb-item"><a href="dikerjakan.php">Dikerjakan</a></li>
                    </ul>
                </div>
            </div>
            <!-- [ breadcrumb ] end -->

            <!-- [ Main Content ] start -->
            <div class="grid grid-cols-12 gap-x-6">
                <!-- Counter Card -->
                <div class="col-span-12 md:col-span-4">
                    <div class="dikerjakan-counter">
                        <h3><?= count($transaksi) ?></h3>
                        <p>Transaksi Sedang Dikerjakan</p>
                    </div>
                </div>

                <div class="col-span-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-tools text-warning"></i> Data Transaksi Dikerjakan
                            </h5>
                            <div class="card-header-right">
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-cog fa-spin"></i> Dikerjakan: <?= count($transaksi) ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($transaksi)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-tools text-warning" style="font-size: 4rem; opacity: 0.3;"></i>
                                    <h4 class="mt-3 text-muted">Tidak Ada Transaksi Dikerjakan</h4>
                                    <p class="text-muted">Saat ini tidak ada transaksi yang sedang dikerjakan</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table id="dikerjakanTable" class="display" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Nama Konsumen</th>
                                                <th>Email</th>
                                                <th>No HP</th>
                                                <th>Type Kendaraan</th>
                                                <th>Status</th>
                                                <th>Mulai Dikerjakan</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($transaksi as $item): ?>
                                                <tr>
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
                                                        <span class="working-badge">
                                                            <i class="fas fa-cog fa-spin"></i> Dikerjakan
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?= date('d/m/Y H:i', strtotime($item['updated_at'])) ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php
                                                            $waktu_dikerjakan = time() - strtotime($item['updated_at']);
                                                            if ($waktu_dikerjakan < 3600) {
                                                                echo floor($waktu_dikerjakan / 60) . ' menit lalu';
                                                            } elseif ($waktu_dikerjakan < 86400) {
                                                                echo floor($waktu_dikerjakan / 3600) . ' jam lalu';
                                                            } else {
                                                                echo floor($waktu_dikerjakan / 86400) . ' hari lalu';
                                                            }
                                                            ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-complete btn-sm"
                                                            onclick="selesaikanTransaksi(<?= $item['id_transaksi'] ?>, '<?= htmlspecialchars($item['order_id']) ?>')">
                                                            <i class="fas fa-check-circle"></i> Selesaikan
                                                        </button>
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

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Required Js -->
    <script src="../../assets/js/plugins/simplebar.min.js"></script>
    <script src="../../assets/js/plugins/popper.min.js"></script>
    <script src="../../assets/js/component.js"></script>
    <script src="../../assets/js/theme.js"></script>
    <script src="../../assets/js/script.js"></script>

    <script>
        // Data barang dan service dari PHP
        const barangData = <?= json_encode($barang_list) ?>;
        const serviceData = <?= json_encode($service_list) ?>;

        $(document).ready(function() {
            // Initialize DataTable
            $('#dikerjakanTable').DataTable({
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
                    [6, 'asc']
                ], // Sort by date column
                columnDefs: [{
                        targets: [5], // Status column
                        className: 'text-center'
                    },
                    {
                        targets: [7], // Action column
                        className: 'text-center',
                        orderable: false
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

        // Function untuk selesaikan transaksi
        function selesaikanTransaksi(idTransaksi, orderId) {
            // Create form HTML
            let barangOptions = '';
            barangData.forEach(item => {
                barangOptions += `<option value="${item.id_barang}">${item.nama_barang} - Rp ${parseInt(item.harga_barang).toLocaleString('id-ID')} (Stok: ${item.stok_barang})</option>`;
            });

            let serviceOptions = '';
            serviceData.forEach(item => {
                serviceOptions += `<option value="${item.id_service}">${item.nama_service} - Rp ${parseInt(item.harga_service).toLocaleString('id-ID')}</option>`;
            });

            const formHtml = `
        <div style="text-align: left;">
            <div class="alert alert-info" style="margin-bottom: 20px;">
                <i class="fas fa-info-circle"></i> <strong>Order ID:</strong> ${orderId}
            </div>
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-box"></i> Pilih Barang yang Digunakan:</h6>
                    <select id="barang-select" class="form-control" multiple style="width: 100%;">
                        ${barangOptions}
                    </select>
                    <small class="text-muted">Pilih barang yang digunakan dalam pengerjaan</small>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-tools"></i> Pilih Service yang Dilakukan:</h6>
                    <select id="service-select" class="form-control" multiple style="width: 100%;">
                        ${serviceOptions}
                    </select>
                    <small class="text-muted">Pilih service yang telah dilakukan</small>
                </div>
            </div>
            <div class="total-preview" id="total-preview" style="display: none;">
                <h5>Total Biaya:</h5>
                <div class="amount" id="total-amount">Rp 0</div>
            </div>
        </div>
    `;

            Swal.fire({
                title: 'Selesaikan Transaksi',
                html: formHtml,
                width: '900px',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-check-circle"></i> Selesaikan Transaksi',
                cancelButtonText: '<i class="fas fa-times"></i> Batal',
                didOpen: () => {
                    // Initialize Select2
                    $('#barang-select').select2({
                        placeholder: 'Pilih barang yang digunakan...',
                        allowClear: true,
                        width: '100%'
                    });

                    $('#service-select').select2({
                        placeholder: 'Pilih service yang dilakukan...',
                        allowClear: true,
                        width: '100%'
                    });

                    // Calculate total on change
                    $('#barang-select, #service-select').on('change', function() {
                        calculateTotal();
                    });
                },
                preConfirm: () => {
                    const selectedBarang = $('#barang-select').val();
                    const selectedService = $('#service-select').val();

                    if ((!selectedBarang || selectedBarang.length === 0) &&
                        (!selectedService || selectedService.length === 0)) {
                        Swal.showValidationMessage('Minimal pilih satu barang atau service yang digunakan');
                        return false;
                    }

                    return {
                        barang: selectedBarang,
                        service: selectedService
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Konfirmasi sebelum menyimpan
                    const selectedBarang = result.value.barang || [];
                    const selectedService = result.value.service || [];

                    let confirmHtml = '<div style="text-align: left;">';

                    if (selectedBarang.length > 0) {
                        confirmHtml += '<h6><i class="fas fa-box"></i> Barang yang digunakan:</h6><ul>';
                        selectedBarang.forEach(id => {
                            const barang = barangData.find(item => item.id_barang == id);
                            if (barang) {
                                confirmHtml += `<li>${barang.nama_barang} - Rp ${parseInt(barang.harga_barang).toLocaleString('id-ID')}</li>`;
                            }
                        });
                        confirmHtml += '</ul>';
                    }

                    if (selectedService.length > 0) {
                        confirmHtml += '<h6><i class="fas fa-tools"></i> Service yang dilakukan:</h6><ul>';
                        selectedService.forEach(id => {
                            const service = serviceData.find(item => item.id_service == id);
                            if (service) {
                                confirmHtml += `<li>${service.nama_service} - Rp ${parseInt(service.harga_service).toLocaleString('id-ID')}</li>`;
                            }
                        });
                        confirmHtml += '</ul>';
                    }

                    // Calculate total for confirmation
                    let totalKonfirmasi = 0;
                    selectedBarang.forEach(id => {
                        const barang = barangData.find(item => item.id_barang == id);
                        if (barang) totalKonfirmasi += parseInt(barang.harga_barang);
                    });
                    selectedService.forEach(id => {
                        const service = serviceData.find(item => item.id_service == id);
                        if (service) totalKonfirmasi += parseInt(service.harga_service);
                    });

                    confirmHtml += `<div style="border-top: 1px solid #ddd; padding-top: 10px; margin-top: 10px;">
                <h5><strong>Total Biaya: Rp ${totalKonfirmasi.toLocaleString('id-ID')}</strong></h5>
            </div></div>`;

                    Swal.fire({
                        title: 'Konfirmasi Penyelesaian',
                        html: confirmHtml,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-check"></i> Ya, Selesaikan',
                        cancelButtonText: '<i class="fas fa-arrow-left"></i> Kembali'
                    }).then((confirmResult) => {
                        if (confirmResult.isConfirmed) {
                            // Show loading
                            Swal.fire({
                                title: 'Memproses...',
                                text: 'Sedang menyimpan data pengerjaan',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            // Send AJAX request
                            $.ajax({
                                url: window.location.href,
                                method: 'POST',
                                data: {
                                    action: 'selesaikan_transaksi',
                                    id_transaksi: idTransaksi,
                                    barang_ids: selectedBarang,
                                    service_ids: selectedService
                                },
                                dataType: 'json',
                                success: function(response) {
                                    if (response.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Transaksi Selesai!',
                                            html: `
                                        <div style="text-align: center;">
                                            <p><i class="fas fa-check-circle" style="color: #28a745; font-size: 2rem;"></i></p>
                                            <p>Transaksi <strong>${orderId}</strong> berhasil diselesaikan!</p>
                                            <p><strong>Total Biaya: Rp ${response.total_harga}</strong></p>
                                            <p style="color: #666; font-size: 0.9em;">Status transaksi telah diubah menjadi "Selesai"</p>
                                        </div>
                                    `,
                                            showConfirmButton: false,
                                            timer: 4000
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Gagal!',
                                            text: response.message || 'Terjadi kesalahan dalam menyelesaikan transaksi',
                                            confirmButtonColor: '#dc3545'
                                        });
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('AJAX Error:', error);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error Koneksi!',
                                        text: 'Terjadi kesalahan koneksi. Silakan coba lagi.',
                                        confirmButtonColor: '#dc3545'
                                    });
                                }
                            });
                        }
                    });
                }
            });
        }

        // Function to calculate total
        function calculateTotal() {
            let total = 0;

            // Calculate barang total
            const selectedBarang = $('#barang-select').val();
            if (selectedBarang && selectedBarang.length > 0) {
                selectedBarang.forEach(id => {
                    const barang = barangData.find(item => item.id_barang == id);
                    if (barang) {
                        total += parseInt(barang.harga_barang);
                    }
                });
            }

            // Calculate service total
            const selectedService = $('#service-select').val();
            if (selectedService && selectedService.length > 0) {
                selectedService.forEach(id => {
                    const service = serviceData.find(item => item.id_service == id);
                    if (service) {
                        total += parseInt(service.harga_service);
                    }
                });
            }

            // Show total
            if (total > 0) {
                $('#total-preview').show();
                $('#total-amount').text('Rp ' + total.toLocaleString('id-ID'));
            } else {
                $('#total-preview').hide();
            }
        }
    </script>

</body>

</html>