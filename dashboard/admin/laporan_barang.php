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
    } else if ($_SESSION['role'] === 'konsumen') {
        header("Location: ../dashboard/konsumen/index.php");
        exit();
    }
}


// Default date range (current month)
$start_date = date('Y-m-01');
$end_date = date('Y-m-d');

// Process date filter
if (isset($_POST['filter_date']) || isset($_GET['start_date'])) {
    $start_date = $_POST['start_date'] ?? $_GET['start_date'];
    $end_date = $_POST['end_date'] ?? $_GET['end_date'];
}

// Fetch barang data with date filter
$query = "
    SELECT 
        b.id_barang,
        b.nama_barang,
        b.stok_barang,
        b.harga_barang,
        b.foto_barang,
        b.created_at,
        b.updated_at,
        (b.stok_barang * b.harga_barang) as nilai_total
    FROM tb_barang b
    WHERE DATE(b.created_at) BETWEEN ? AND ?
    ORDER BY b.created_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute([$start_date, $end_date]);
$barang = $stmt->fetchAll();

// Calculate summary statistics
$total_barang = count($barang);
$total_stok = array_sum(array_column($barang, 'stok_barang'));
$total_nilai_inventori = array_sum(array_column($barang, 'nilai_total'));

// Get barang with low stock (less than 10)
$barang_stok_rendah = array_filter($barang, function ($b) {
    return $b['stok_barang'] < 10;
});
$count_stok_rendah = count($barang_stok_rendah);

// Get barang with zero stock
$barang_habis = array_filter($barang, function ($b) {
    return $b['stok_barang'] == 0;
});
$count_habis = count($barang_habis);

// Get average harga barang
$avg_harga = $total_barang > 0 ? array_sum(array_column($barang, 'harga_barang')) / $total_barang : 0;
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">

<head>
    <title>Laporan Data Barang | Bengkel Management System</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="../../assets/images/favicon.svg" type="image/x-icon" />

    <!-- [Font] Family -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../../assets/fonts/phosphor/duotone/style.css" />
    <link rel="stylesheet" href="../../assets/fonts/tabler-icons.min.css" />
    <link rel="stylesheet" href="../../assets/fonts/feather.css" />
    <link rel="stylesheet" href="../../assets/fonts/fontawesome.css" />
    <link rel="stylesheet" href="../../assets/fonts/material.css" />
    <link rel="stylesheet" href="../../assets/css/style.css" id="main-style-link" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 15px;
            color: white;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .summary-card.success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .summary-card.warning {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .summary-card.danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
        }

        .summary-card.info {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #333;
        }

        .summary-card.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .summary-card h3 {
            margin: 0;
            font-size: 2rem;
            font-weight: 600;
        }

        .summary-card p {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .filter-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 150px;
        }

        .filter-group label {
            font-weight: 500;
            margin-bottom: 5px;
            color: #333;
        }

        .filter-group input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .stock-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .stock-tinggi {
            background: #d4edda;
            color: #155724;
        }

        .stock-sedang {
            background: #fff3cd;
            color: #856404;
        }

        .stock-rendah {
            background: #f8d7da;
            color: #721c24;
        }

        .stock-habis {
            background: #f8d7da;
            color: #721c24;
        }

        .print-section {
            text-align: center;
            margin: 20px 0;
        }

        .btn-print {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #ddd;
        }

        .no-image {
            width: 50px;
            height: 50px;
            background: #f8f9fa;
            border: 2px solid #ddd;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #6c757d;
        }

        /* Print styles */
        @media print {
            body * {
                visibility: hidden;
            }

            .print-content,
            .print-content * {
                visibility: visible;
            }

            .print-content {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .no-print {
                display: none !important;
            }

            .print-header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #333;
                padding-bottom: 20px;
            }

            .print-header h1 {
                font-size: 24px;
                margin: 0;
                color: #333;
            }

            .print-header p {
                margin: 5px 0;
                color: #666;
            }

            .print-summary {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 20px;
                margin-bottom: 30px;
            }

            .print-summary-item {
                text-align: center;
                padding: 15px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }

            .print-summary-item h3 {
                margin: 0;
                font-size: 18px;
                color: #333;
            }

            .print-summary-item p {
                margin: 5px 0 0 0;
                font-size: 12px;
                color: #666;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }

            th,
            td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
                font-size: 12px;
            }

            th {
                background-color: #f8f9fa;
                font-weight: bold;
            }

            .product-image,
            .no-image {
                display: none;
            }
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
            <div class="page-header no-print">
                <div class="page-block">
                    <div class="page-header-title">
                        <h5 class="mb-0 font-medium">Laporan Data Barang</h5>
                    </div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item" aria-current="page">Laporan</li>
                        <li class="breadcrumb-item"><a href="laporan-barang.php">Data Barang</a></li>
                    </ul>
                </div>
            </div>
            <!-- [ breadcrumb ] end -->

            <!-- Filter Section -->
            <div class="filter-section no-print">
                <h6 class="mb-3"><i class="fas fa-filter"></i> Filter Laporan</h6>
                <form method="POST" class="filter-form">
                    <div class="filter-group">
                        <label for="start_date">Tanggal Mulai</label>
                        <input type="date" id="start_date" name="start_date" value="<?= $start_date ?>" required>
                    </div>
                    <div class="filter-group">
                        <label for="end_date">Tanggal Akhir</label>
                        <input type="date" id="end_date" name="end_date" value="<?= $end_date ?>" required>
                    </div>
                    <div class="filter-group">
                        <button type="submit" name="filter_date" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Print Section -->
            <div class="print-section no-print">
                <button type="button" class="btn-print" onclick="printReport()">
                    <i class="fas fa-print"></i> Cetak Laporan PDF
                </button>
            </div>

            <!-- Print Content -->
            <div class="print-content">
                <!-- Print Header -->
                <div class="print-header" style="display: none;">
                    <h1>LAPORAN DATA BARANG BENGKEL</h1>
                    <p>Periode: <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></p>
                    <p>Dicetak pada: <?= date('d/m/Y H:i:s') ?></p>
                </div>

                <!-- Summary Cards -->
                <div class="summary-cards">
                    <div class="summary-card primary">
                        <h3><?= $total_barang ?></h3>
                        <p>Total Jenis Barang</p>
                    </div>
                    <div class="summary-card success">
                        <h3><?= $total_stok ?></h3>
                        <p>Total Stok Barang</p>
                    </div>
                    <div class="summary-card info">
                        <h3>Rp <?= number_format($total_nilai_inventori, 0, ',', '.') ?></h3>
                        <p>Nilai Total Inventori</p>
                    </div>
                    <div class="summary-card warning">
                        <h3><?= $count_stok_rendah ?></h3>
                        <p>Stok Rendah (<10)< /p>
                    </div>
                    <div class="summary-card danger">
                        <h3><?= $count_habis ?></h3>
                        <p>Barang Habis</p>
                    </div>
                    <div class="summary-card info">
                        <h3>Rp <?= number_format($avg_harga, 0, ',', '.') ?></h3>
                        <p>Rata-rata Harga</p>
                    </div>
                </div>

                <!-- Print Summary -->
                <div class="print-summary" style="display: none;">
                    <div class="print-summary-item">
                        <h3><?= $total_barang ?></h3>
                        <p>Total Jenis Barang</p>
                    </div>
                    <div class="print-summary-item">
                        <h3><?= $total_stok ?></h3>
                        <p>Total Stok Barang</p>
                    </div>
                    <div class="print-summary-item">
                        <h3>Rp <?= number_format($total_nilai_inventori, 0, ',', '.') ?></h3>
                        <p>Nilai Total Inventori</p>
                    </div>
                    <div class="print-summary-item">
                        <h3><?= $count_stok_rendah ?></h3>
                        <p>Stok Rendah</p>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="grid grid-cols-12 gap-x-6">
                    <div class="col-span-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Data Barang (<?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?>)</h5>
                            </div>
                            <div class="card-body">
                                <table id="barangTable" class="display" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Foto</th>
                                            <th>Nama Barang</th>
                                            <th>Stok</th>
                                            <th>Harga Satuan</th>
                                            <th>Nilai Total</th>
                                            <th>Status Stok</th>
                                            <th>Tanggal Dibuat</th>
                                            <th>Terakhir Update</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($barang as $index => $b): ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td>
                                                    <?php if (!empty($b['foto_barang'])): ?>
                                                        <img src="../../assets/images/barang/<?= htmlspecialchars($b['foto_barang']) ?>"
                                                            alt="<?= htmlspecialchars($b['nama_barang']) ?>"
                                                            class="product-image">
                                                    <?php else: ?>
                                                        <div class="no-image">No Image</div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($b['nama_barang']) ?></td>
                                                <td><?= number_format($b['stok_barang'], 0, ',', '.') ?></td>
                                                <td>Rp <?= number_format($b['harga_barang'], 0, ',', '.') ?></td>
                                                <td>Rp <?= number_format($b['nilai_total'], 0, ',', '.') ?></td>
                                                <td>
                                                    <?php
                                                    $stok = $b['stok_barang'];
                                                    if ($stok == 0) {
                                                        echo '<span class="stock-badge stock-habis">Habis</span>';
                                                    } elseif ($stok < 10) {
                                                        echo '<span class="stock-badge stock-rendah">Rendah</span>';
                                                    } elseif ($stok < 50) {
                                                        echo '<span class="stock-badge stock-sedang">Sedang</span>';
                                                    } else {
                                                        echo '<span class="stock-badge stock-tinggi">Tinggi</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?= date('d/m/Y H:i', strtotime($b['created_at'])) ?></td>
                                                <td><?= $b['updated_at'] ? date('d/m/Y H:i', strtotime($b['updated_at'])) : '-' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
        // Initialize DataTable
        $(document).ready(function() {
            $('#barangTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel'
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
                columnDefs: [{
                        orderable: false,
                        targets: 1
                    } // Disable sorting for image column
                ]
            });
        });

        // Print Report Function
        function printReport() {
            // Show print elements
            document.querySelector('.print-header').style.display = 'block';
            document.querySelector('.print-summary').style.display = 'grid';

            // Hide summary cards for print
            document.querySelector('.summary-cards').style.display = 'none';

            // Print
            window.print();

            // Restore original display after print
            setTimeout(function() {
                document.querySelector('.print-header').style.display = 'none';
                document.querySelector('.print-summary').style.display = 'none';
                document.querySelector('.summary-cards').style.display = 'grid';
            }, 1000);
        }

        // Set max date to today
        document.getElementById('start_date').setAttribute('max', new Date().toISOString().split('T')[0]);
        document.getElementById('end_date').setAttribute('max', new Date().toISOString().split('T')[0]);

        // Validate date range
        document.getElementById('start_date').addEventListener('change', function() {
            document.getElementById('end_date').setAttribute('min', this.value);
        });

        document.getElementById('end_date').addEventListener('change', function() {
            document.getElementById('start_date').setAttribute('max', this.value);
        });

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