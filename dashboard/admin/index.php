<?php
session_start();
include '../../db/koneksi.php';

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

// Inisialisasi variabel untuk alert
$alert_message = isset($_SESSION['alert_message']) ? $_SESSION['alert_message'] : '';
$alert_type = isset($_SESSION['alert_type']) ? $_SESSION['alert_type'] : '';
$alert_title = isset($_SESSION['alert_title']) ? $_SESSION['alert_title'] : '';
$alert_icon = isset($_SESSION['alert_icon']) ? $_SESSION['alert_icon'] : '';

unset($_SESSION['alert_message'], $_SESSION['alert_type'], $_SESSION['alert_title'], $_SESSION['alert_icon']);

// Query untuk statistik utama
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM tb_user WHERE role != 'administrator') as total_users,
    (SELECT COUNT(*) FROM tb_barang) as total_barang,
    (SELECT COUNT(*) FROM tb_service) as total_service,
    (SELECT COUNT(*) FROM tb_transaksi) as total_transaksi,
    (SELECT COUNT(*) FROM tb_transaksi WHERE status_pembayaran = 'menunggu') as transaksi_menunggu,
    (SELECT COUNT(*) FROM tb_transaksi WHERE status_pembayaran = 'dikerjakan') as transaksi_dikerjakan,
    (SELECT COUNT(*) FROM tb_transaksi WHERE status_pembayaran = 'selesai') as transaksi_selesai,
    (SELECT SUM(total_harga) FROM tb_transaksi WHERE status_pembayaran = 'selesai') as total_revenue";

$stats = $pdo->query($stats_query)->fetch();

// Query untuk statistik user berdasarkan role
$user_stats_query = "SELECT 
    COUNT(CASE WHEN role = 'kasir' THEN 1 END) as total_kasir,
    COUNT(CASE WHEN role = 'mekanik' THEN 1 END) as total_mekanik,
    COUNT(CASE WHEN role = 'konsumen' THEN 1 END) as total_konsumen
FROM tb_user";

$user_stats = $pdo->query($user_stats_query)->fetch();

// Query untuk revenue bulanan dan tahunan
$revenue_query = "SELECT 
    SUM(CASE WHEN status_pembayaran = 'selesai' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) THEN total_harga ELSE 0 END) as revenue_bulan_ini,
    SUM(CASE WHEN status_pembayaran = 'selesai' AND YEAR(created_at) = YEAR(CURRENT_DATE()) THEN total_harga ELSE 0 END) as revenue_tahun_ini
FROM tb_transaksi";

$revenue = $pdo->query($revenue_query)->fetch();

// Query untuk transaksi terbaru
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
ORDER BY t.created_at DESC
LIMIT 8";

$transaksi_list = $pdo->query($transaksi_query)->fetchAll();

// Query untuk stok barang rendah
$stok_rendah_query = "SELECT 
    nama_barang,
    stok_barang,
    harga_barang
FROM tb_barang 
WHERE stok_barang < 50 
ORDER BY stok_barang ASC 
LIMIT 5";

$stok_rendah = $pdo->query($stok_rendah_query)->fetchAll();

// Query untuk top services
$top_services_query = "SELECT 
    s.nama_service,
    s.harga_service,
    COUNT(p.id_pengerjaan) as jumlah_penggunaan
FROM tb_service s
LEFT JOIN tb_pengerjaan p ON s.id_service = p.id_barang_or_service
GROUP BY s.id_service
ORDER BY jumlah_penggunaan DESC
LIMIT 5";

$top_services = $pdo->query($top_services_query)->fetchAll();

// Function untuk format currency
function formatCurrency($amount)
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Function untuk format status
function getStatusBadge($status)
{
    switch ($status) {
        case 'pending':
            return '<span class="badge bg-secondary-500 text-white text-[12px]">Pending</span>';
        case 'paid':
            return '<span class="badge bg-info-500 text-white text-[12px]">Paid</span>';
        case 'menunggu':
            return '<span class="badge bg-warning-500 text-white text-[12px]">Menunggu</span>';
        case 'dikerjakan':
            return '<span class="badge bg-primary-500 text-white text-[12px]">Dikerjakan</span>';
        case 'selesai':
            return '<span class="badge bg-success-500 text-white text-[12px]">Selesai</span>';
        case 'failed':
            return '<span class="badge bg-danger-500 text-white text-[12px]">Failed</span>';
        case 'cancelled':
            return '<span class="badge bg-dark-500 text-white text-[12px]">Cancelled</span>';
        default:
            return '<span class="badge bg-secondary-500 text-white text-[12px]">' . ucfirst($status) . '</span>';
    }
}

// Function untuk role badge
function getRoleBadge($role)
{
    switch ($role) {
        case 'administrator':
            return '<span class="badge bg-danger-500 text-white text-[12px]">Administrator</span>';
        case 'kasir':
            return '<span class="badge bg-primary-500 text-white text-[12px]">Kasir</span>';
        case 'mekanik':
            return '<span class="badge bg-success-500 text-white text-[12px]">Mekanik</span>';
        case 'konsumen':
            return '<span class="badge bg-info-500 text-white text-[12px]">Konsumen</span>';
        default:
            return '<span class="badge bg-secondary-500 text-white text-[12px]">' . ucfirst($role) . '</span>';
    }
}
?>

<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">

<head>
    <title>Dashboard Admin | Bengkel Management System</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Dashboard Admin - Bengkel Management System" />
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
                        <h5 class="mb-0 font-medium">Dashboard Admin</h5>
                    </div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item">Admin</li>
                    </ul>
                </div>
            </div>
            <!-- [ breadcrumb ] end -->

            <!-- [ Main Content ] start -->
            <div class="grid grid-cols-12 gap-x-6">
                <!-- Overview Cards -->
                <div class="col-span-12 xl:col-span-3 md:col-span-6">
                    <div class="card">
                        <div class="card-header !pb-0 !border-b-0">
                            <h5>Total Users</h5>
                        </div>
                        <div class="card-body">
                            <div class="flex items-center justify-between gap-3 flex-wrap">
                                <h3 class="font-light flex items-center mb-0">
                                    <i class="feather icon-users text-primary-500 text-[30px] mr-1.5"></i>
                                    <?= $stats['total_users'] ?>
                                </h3>
                                <p class="mb-0 text-primary-500">Users</p>
                            </div>
                            <div class="flex items-center justify-between mt-3">
                                <small class="text-muted">Kasir: <?= $user_stats['total_kasir'] ?></small>
                                <small class="text-muted">Mekanik: <?= $user_stats['total_mekanik'] ?></small>
                                <small class="text-muted">Konsumen: <?= $user_stats['total_konsumen'] ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 xl:col-span-3 md:col-span-6">
                    <div class="card">
                        <div class="card-header !pb-0 !border-b-0">
                            <h5>Total Barang</h5>
                        </div>
                        <div class="card-body">
                            <div class="flex items-center justify-between gap-3 flex-wrap">
                                <h3 class="font-light flex items-center mb-0">
                                    <i class="feather icon-package text-warning-500 text-[30px] mr-1.5"></i>
                                    <?= $stats['total_barang'] ?>
                                </h3>
                                <p class="mb-0 text-warning-500">Items</p>
                            </div>
                            <div class="mt-3">
                                <small class="text-muted">Stok Rendah: <?= count($stok_rendah) ?> items</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 xl:col-span-3 md:col-span-6">
                    <div class="card">
                        <div class="card-header !pb-0 !border-b-0">
                            <h5>Total Services</h5>
                        </div>
                        <div class="card-body">
                            <div class="flex items-center justify-between gap-3 flex-wrap">
                                <h3 class="font-light flex items-center mb-0">
                                    <i class="feather icon-tool text-success-500 text-[30px] mr-1.5"></i>
                                    <?= $stats['total_service'] ?>
                                </h3>
                                <p class="mb-0 text-success-500">Services</p>
                            </div>
                            <div class="mt-3">
                                <small class="text-muted">Layanan Tersedia</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 xl:col-span-3">
                    <div class="card">
                        <div class="card-header !pb-0 !border-b-0">
                            <h5>Total Transaksi</h5>
                        </div>
                        <div class="card-body">
                            <div class="flex items-center justify-between gap-3 flex-wrap">
                                <h3 class="font-light flex items-center mb-0">
                                    <i class="feather icon-shopping-cart text-info-500 text-[30px] mr-1.5"></i>
                                    <?= $stats['total_transaksi'] ?>
                                </h3>
                                <p class="mb-0 text-info-500">Orders</p>
                            </div>
                            <div class="flex items-center justify-between mt-3">
                                <small class="text-warning-500">Menunggu: <?= $stats['transaksi_menunggu'] ?></small>
                                <small class="text-primary-500">Dikerjakan: <?= $stats['transaksi_dikerjakan'] ?></small>
                                <small class="text-success-500">Selesai: <?= $stats['transaksi_selesai'] ?></small>
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
                                        <?= formatCurrency(10000000) ?>
                                    </h6>
                                    <div class="w-full bg-theme-bodybg rounded-lg h-1.5 dark:bg-themedark-bodybg">
                                        <div class="bg-success-500 h-full rounded-lg shadow-[0_10px_20px_0_rgba(0,0,0,0.3)]" role="progressbar"
                                            style="width: <?= min(($revenue['revenue_bulan_ini'] / 10000000) * 100, 100) ?>%"></div>
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
                                        <?= formatCurrency(100000000) ?>
                                    </h6>
                                    <div class="w-full bg-theme-bodybg rounded-lg h-1.5 dark:bg-themedark-bodybg">
                                        <div class="bg-primary-500 h-full rounded-lg shadow-[0_10px_20px_0_rgba(0,0,0,0.3)]" role="progressbar"
                                            style="width: <?= min(($revenue['revenue_tahun_ini'] / 100000000) * 100, 100) ?>%"></div>
                                    </div>
                                </div>
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
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($transaksi_list)): ?>
                                            <?php foreach ($transaksi_list as $transaksi): ?>
                                                <tr>
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
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">
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

                <!-- Low Stock Alert -->
                <div class="col-span-12 xl:col-span-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Stok Rendah</h5>
                            <div class="card-header-right">
                                <a href="barang.php" class="btn btn-warning btn-sm">
                                    <i class="feather icon-package mr-1"></i> Kelola Stok
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($stok_rendah)): ?>
                                <?php foreach ($stok_rendah as $barang): ?>
                                    <div class="flex items-center justify-between py-2 border-b">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($barang['nama_barang']) ?></h6>
                                            <p class="text-muted mb-0"><?= formatCurrency($barang['harga_barang']) ?></p>
                                        </div>
                                        <div class="text-right">
                                            <span class="badge bg-danger-500 text-white text-[12px]">
                                                <?= $barang['stok_barang'] ?> unit
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-3">
                                    <i class="feather icon-check-circle text-success-500 text-[48px] mb-2"></i>
                                    <p class="text-muted">Semua stok barang mencukupi</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="col-span-12 xl:col-span-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="grid grid-cols-2 gap-4">
                                <a href="user.php" class="btn btn-primary btn-block">
                                    <i class="feather icon-users mr-2"></i>
                                    Kelola User
                                </a>
                                <a href="barang.php" class="btn btn-warning btn-block">
                                    <i class="feather icon-package mr-2"></i>
                                    Kelola Barang
                                </a>
                                <a href="service.php" class="btn btn-success btn-block">
                                    <i class="feather icon-tool mr-2"></i>
                                    Kelola Service
                                </a>
                                <a href="transaksi.php" class="btn btn-info btn-block">
                                    <i class="feather icon-shopping-cart mr-2"></i>
                                    Kelola Transaksi
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Services -->
                <div class="col-span-12 xl:col-span-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Top Services</h5>
                            <div class="card-header-right">
                                <a href="service.php" class="btn btn-success btn-sm">
                                    <i class="feather icon-tool mr-1"></i> Kelola Service
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($top_services)): ?>
                                <?php foreach ($top_services as $service): ?>
                                    <div class="flex items-center justify-between py-2 border-b">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($service['nama_service']) ?></h6>
                                            <p class="text-muted mb-0"><?= formatCurrency($service['harga_service']) ?></p>
                                        </div>
                                        <div class="text-right">
                                            <span class="badge bg-primary-500 text-white text-[12px]">
                                                <?= $service['jumlah_penggunaan'] ?> kali
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-3">
                                    <i class="feather icon-tool text-muted text-[48px] mb-2"></i>
                                    <p class="text-muted">Belum ada data penggunaan service</p>
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