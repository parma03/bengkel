<?php
session_start();

// Simulasi koneksi database (gunakan file koneksi.php Anda)
include '../../db/koneksi.php';

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

// Inisialisasi variabel untuk alert
$alert_message = isset($_SESSION['alert_message']) ? $_SESSION['alert_message'] : '';
$alert_type = isset($_SESSION['alert_type']) ? $_SESSION['alert_type'] : '';
$alert_title = isset($_SESSION['alert_title']) ? $_SESSION['alert_title'] : '';
$alert_icon = isset($_SESSION['alert_icon']) ? $_SESSION['alert_icon'] : '';

unset($_SESSION['alert_message'], $_SESSION['alert_type'], $_SESSION['alert_title'], $_SESSION['alert_icon']);

// Query untuk mendapatkan statistik transaksi
$stats_query = "SELECT 
    COUNT(CASE WHEN status_pembayaran = 'menunggu' THEN 1 END) as menunggu,
    COUNT(CASE WHEN status_pembayaran = 'dikerjakan' THEN 1 END) as dikerjakan,
    COUNT(CASE WHEN status_pembayaran = 'selesai' THEN 1 END) as selesai,
    COUNT(*) as total
FROM tb_transaksi 
WHERE status_pembayaran IN ('menunggu', 'dikerjakan', 'selesai')";

$stats = $pdo->query($stats_query)->fetch();

// Query untuk mendapatkan transaksi terbaru
$transaksi_query = "SELECT 
    t.id_transaksi,
    t.order_id,
    t.type_kendaraan,
    t.total_harga,
    t.status_pembayaran,
    t.created_at,
    u.nama as nama_customer,
    u.nohp
FROM tb_transaksi t
JOIN tb_user u ON t.id_user = u.id_user
WHERE t.status_pembayaran IN ('menunggu', 'dikerjakan', 'selesai')
ORDER BY t.created_at DESC
LIMIT 10";

$transaksi_list = $pdo->query($transaksi_query)->fetchAll();

// Query untuk mendapatkan total pendapatan bulan ini
$revenue_query = "SELECT 
    SUM(CASE WHEN status_pembayaran = 'selesai' AND MONTH(created_at) = MONTH(CURRENT_DATE()) THEN total_harga ELSE 0 END) as revenue_bulan_ini,
    SUM(CASE WHEN status_pembayaran = 'selesai' AND YEAR(created_at) = YEAR(CURRENT_DATE()) THEN total_harga ELSE 0 END) as revenue_tahun_ini
FROM tb_transaksi";

$revenue = $pdo->query($revenue_query)->fetch();

// Function untuk format currency
function formatCurrency($amount)
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Function untuk format status
function getStatusBadge($status)
{
    switch ($status) {
        case 'menunggu':
            return '<span class="badge bg-warning-500 text-white text-[12px]">Menunggu</span>';
        case 'dikerjakan':
            return '<span class="badge bg-primary-500 text-white text-[12px]">Dikerjakan</span>';
        case 'selesai':
            return '<span class="badge bg-success-500 text-white text-[12px]">Selesai</span>';
        default:
            return '<span class="badge bg-secondary-500 text-white text-[12px]">' . ucfirst($status) . '</span>';
    }
}
?>

<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">

<head>
    <title>Dashboard Mekanik | Bengkel Management System</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Dashboard Mekanik - Bengkel Management System" />
    <meta name="author" content="Bengkel Team" />

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
                        <h5 class="mb-0 font-medium">Dashboard Mekanik</h5>
                    </div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item">Mekanik</li>
                    </ul>
                </div>
            </div>
            <!-- [ breadcrumb ] end -->

            <!-- [ Main Content ] start -->
            <div class="grid grid-cols-12 gap-x-6">
                <!-- Status Cards -->
                <div class="col-span-12 xl:col-span-4 md:col-span-6">
                    <div class="card">
                        <div class="card-header !pb-0 !border-b-0">
                            <h5>Transaksi Menunggu</h5>
                        </div>
                        <div class="card-body">
                            <div class="flex items-center justify-between gap-3 flex-wrap">
                                <h3 class="font-light flex items-center mb-0">
                                    <i class="feather icon-clock text-warning-500 text-[30px] mr-1.5"></i>
                                    <?= $stats['menunggu'] ?>
                                </h3>
                                <p class="mb-0 text-warning-500">Menunggu</p>
                            </div>
                            <div class="w-full bg-theme-bodybg rounded-lg h-1.5 mt-6 dark:bg-themedark-bodybg">
                                <div class="bg-warning-500 h-full rounded-lg shadow-[0_10px_20px_0_rgba(0,0,0,0.3)]" role="progressbar"
                                    style="width: <?= $stats['total'] > 0 ? ($stats['menunggu'] / $stats['total'] * 100) : 0 ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 xl:col-span-4 md:col-span-6">
                    <div class="card">
                        <div class="card-header !pb-0 !border-b-0">
                            <h5>Sedang Dikerjakan</h5>
                        </div>
                        <div class="card-body">
                            <div class="flex items-center justify-between gap-3 flex-wrap">
                                <h3 class="font-light flex items-center mb-0">
                                    <i class="feather icon-tool text-primary-500 text-[30px] mr-1.5"></i>
                                    <?= $stats['dikerjakan'] ?>
                                </h3>
                                <p class="mb-0 text-primary-500">Dikerjakan</p>
                            </div>
                            <div class="w-full bg-theme-bodybg rounded-lg h-1.5 mt-6 dark:bg-themedark-bodybg">
                                <div class="bg-primary-500 h-full rounded-lg shadow-[0_10px_20px_0_rgba(0,0,0,0.3)]" role="progressbar"
                                    style="width: <?= $stats['total'] > 0 ? ($stats['dikerjakan'] / $stats['total'] * 100) : 0 ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 xl:col-span-4">
                    <div class="card">
                        <div class="card-header !pb-0 !border-b-0">
                            <h5>Transaksi Selesai</h5>
                        </div>
                        <div class="card-body">
                            <div class="flex items-center justify-between gap-3 flex-wrap">
                                <h3 class="font-light flex items-center mb-0">
                                    <i class="feather icon-check-circle text-success-500 text-[30px] mr-1.5"></i>
                                    <?= $stats['selesai'] ?>
                                </h3>
                                <p class="mb-0 text-success-500">Selesai</p>
                            </div>
                            <div class="w-full bg-theme-bodybg rounded-lg h-1.5 mt-6 dark:bg-themedark-bodybg">
                                <div class="bg-success-500 h-full rounded-lg shadow-[0_10px_20px_0_rgba(0,0,0,0.3)]" role="progressbar"
                                    style="width: <?= $stats['total'] > 0 ? ($stats['selesai'] / $stats['total'] * 100) : 0 ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Cards -->
                <div class="col-span-12 xl:col-span-6">
                    <div class="card card-social">
                        <div class="card-body border-b border-theme-border dark:border-themedark-border">
                            <div class="flex items-center justify-center">
                                <div class="shrink-0">
                                    <i class="fas fa-chart-line text-success-500 text-[36px]"></i>
                                </div>
                                <div class="grow ltr:text-right rtl:text-left">
                                    <h3 class="mb-2"><?= formatCurrency($revenue['revenue_bulan_ini'] ?? 0) ?></h3>
                                    <h5 class="text-success-500 mb-0">Revenue Bulan Ini</h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="grid grid-cols-12 gap-x-6">
                                <div class="col-span-12">
                                    <h6 class="text-center mb-2.5">
                                        <span class="text-muted">Target Bulanan: </span>
                                        <?= formatCurrency(5000000) ?>
                                    </h6>
                                    <div class="w-full bg-theme-bodybg rounded-lg h-1.5 dark:bg-themedark-bodybg">
                                        <div class="bg-success-500 h-full rounded-lg shadow-[0_10px_20px_0_rgba(0,0,0,0.3)]" role="progressbar"
                                            style="width: <?= min(($revenue['revenue_bulan_ini'] / 5000000) * 100, 100) ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 xl:col-span-6">
                    <div class="card card-social">
                        <div class="card-body border-b border-theme-border dark:border-themedark-border">
                            <div class="flex items-center justify-center">
                                <div class="shrink-0">
                                    <i class="fas fa-calendar-alt text-primary-500 text-[36px]"></i>
                                </div>
                                <div class="grow ltr:text-right rtl:text-left">
                                    <h3 class="mb-2"><?= formatCurrency($revenue['revenue_tahun_ini'] ?? 0) ?></h3>
                                    <h5 class="text-primary-500 mb-0">Revenue Tahun Ini</h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="grid grid-cols-12 gap-x-6">
                                <div class="col-span-12">
                                    <h6 class="text-center mb-2.5">
                                        <span class="text-muted">Target Tahunan: </span>
                                        <?= formatCurrency(50000000) ?>
                                    </h6>
                                    <div class="w-full bg-theme-bodybg rounded-lg h-1.5 dark:bg-themedark-bodybg">
                                        <div class="bg-primary-500 h-full rounded-lg shadow-[0_10px_20px_0_rgba(0,0,0,0.3)]" role="progressbar"
                                            style="width: <?= min(($revenue['revenue_tahun_ini'] / 50000000) * 100, 100) ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Work Status Overview -->
                <div class="col-span-12 xl:col-span-4">
                    <div class="card user-list">
                        <div class="card-header">
                            <h5>Status Pekerjaan</h5>
                        </div>
                        <div class="card-body">
                            <div class="flex items-center justify-between gap-1 mb-5">
                                <h2 class="font-light flex items-center m-0">
                                    <?= $stats['total'] ?>
                                    <i class="fas fa-tasks text-[16px] ml-2.5 text-primary-500"></i>
                                </h2>
                                <h6 class="flex items-center m-0 text-success-500">
                                    Total Tugas
                                </h6>
                            </div>

                            <div class="flex items-center justify-between gap-2 mb-2">
                                <h6 class="flex items-center gap-1">
                                    <i class="fas fa-clock text-[10px] mr-2.5 text-warning-500"></i>
                                    Menunggu
                                </h6>
                                <h6><?= $stats['menunggu'] ?></h6>
                            </div>
                            <div class="w-full bg-theme-bodybg rounded-lg h-1.5 mb-6 mt-3 dark:bg-themedark-bodybg">
                                <div class="bg-warning-500 h-full rounded-lg shadow-[0_10px_20px_0_rgba(0,0,0,0.3)]" role="progressbar"
                                    style="width: <?= $stats['total'] > 0 ? ($stats['menunggu'] / $stats['total'] * 100) : 0 ?>%"></div>
                            </div>

                            <div class="flex items-center justify-between gap-2 mb-2">
                                <h6 class="flex items-center gap-1">
                                    <i class="fas fa-cogs text-[10px] mr-2.5 text-primary-500"></i>
                                    Dikerjakan
                                </h6>
                                <h6><?= $stats['dikerjakan'] ?></h6>
                            </div>
                            <div class="w-full bg-theme-bodybg rounded-lg h-1.5 mb-6 mt-3 dark:bg-themedark-bodybg">
                                <div class="bg-primary-500 h-full rounded-lg shadow-[0_10px_20px_0_rgba(0,0,0,0.3)]" role="progressbar"
                                    style="width: <?= $stats['total'] > 0 ? ($stats['dikerjakan'] / $stats['total'] * 100) : 0 ?>%"></div>
                            </div>

                            <div class="flex items-center justify-between gap-2 mb-2">
                                <h6 class="flex items-center gap-1">
                                    <i class="fas fa-check-circle text-[10px] mr-2.5 text-success-500"></i>
                                    Selesai
                                </h6>
                                <h6><?= $stats['selesai'] ?></h6>
                            </div>
                            <div class="w-full bg-theme-bodybg rounded-lg h-1.5 mt-4 dark:bg-themedark-bodybg">
                                <div class="bg-success-500 h-full rounded-lg shadow-[0_10px_20px_0_rgba(0,0,0,0.3)]" role="progressbar"
                                    style="width: <?= $stats['total'] > 0 ? ($stats['selesai'] / $stats['total'] * 100) : 0 ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="col-span-12 xl:col-span-8">
                    <div class="card table-card">
                        <div class="card-header">
                            <h5>Transaksi Terbaru</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Kendaraan</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Tanggal</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($transaksi_list)): ?>
                                            <?php foreach ($transaksi_list as $transaksi): ?>
                                                <tr class="<?= $transaksi['status_pembayaran'] == 'menunggu' ? 'unread' : '' ?>">
                                                    <td>
                                                        <h6 class="mb-1"><?= htmlspecialchars($transaksi['order_id']) ?></h6>
                                                    </td>
                                                    <td>
                                                        <h6 class="mb-1"><?= htmlspecialchars($transaksi['nama_customer']) ?></h6>
                                                        <p class="m-0 text-muted"><?= htmlspecialchars($transaksi['nohp']) ?></p>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info-500 text-white text-[12px]">
                                                            <?= htmlspecialchars($transaksi['type_kendaraan']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <h6 class="mb-0"><?= formatCurrency($transaksi['total_harga']) ?></h6>
                                                    </td>
                                                    <td>
                                                        <?= getStatusBadge($transaksi['status_pembayaran']) ?>
                                                    </td>
                                                    <td>
                                                        <h6 class="text-muted mb-0">
                                                            <?= date('d M Y H:i', strtotime($transaksi['created_at'])) ?>
                                                        </h6>
                                                    </td>
                                                    <td>
                                                        <?php if ($transaksi['status_pembayaran'] == 'menunggu'): ?>
                                                            <a href="#!" class="badge bg-primary-500 text-white text-[12px] mx-1">Mulai</a>
                                                        <?php elseif ($transaksi['status_pembayaran'] == 'dikerjakan'): ?>
                                                            <a href="#!" class="badge bg-success-500 text-white text-[12px] mx-1">Selesai</a>
                                                        <?php endif; ?>
                                                        <a href="#!" class="badge bg-theme-bg-2 text-white text-[12px]">Detail</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">
                                                    <p class="text-muted">Tidak ada transaksi ditemukan</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- [ Main Content ] end -->
        </div>
    </div>
    <!-- [ Main Content ] end -->

    <?php include '_component/footer.php'; ?>

    <!-- Required Js -->
    <script src="../../assets/js/plugins/simplebar.min.js"></script>
    <script src="../../assets/js/plugins/popper.min.js"></script>
    <script src="../../assets/js/icon/custom-icon.js"></script>
    <script src="../../assets/js/plugins/feather.min.js"></script>
    <script src="../../assets/js/component.js"></script>
    <script src="../../assets/js/theme.js"></script>
    <script src="../../assets/js/script.js"></script>

    <div class="floting-button fixed bottom-[50px] right-[30px] z-[1030]">
    </div>

    <script>
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