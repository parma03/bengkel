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

// Process CRUD operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'create':
                    $nama_service = $_POST['nama_service'];
                    $harga_service = $_POST['harga_service'];

                    $stmt = $pdo->prepare("INSERT INTO tb_service (nama_service, harga_service, created_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$nama_service, $harga_service]);

                    $_SESSION['alert_message'] = 'Data service berhasil ditambahkan!';
                    $_SESSION['alert_type'] = 'success';
                    $_SESSION['alert_title'] = 'Sukses!';
                    $_SESSION['alert_icon'] = 'success';
                    break;

                case 'update':
                    $id_service = $_POST['id_service'];
                    $nama_service = $_POST['nama_service'];
                    $harga_service = $_POST['harga_service'];

                    $stmt = $pdo->prepare("UPDATE tb_service SET nama_service = ?, harga_service = ?, update_at = NOW() WHERE id_service = ?");
                    $stmt->execute([$nama_service, $harga_service, $id_service]);

                    $_SESSION['alert_message'] = 'Data service berhasil diupdate!';
                    $_SESSION['alert_type'] = 'success';
                    $_SESSION['alert_title'] = 'Sukses!';
                    $_SESSION['alert_icon'] = 'success';
                    break;

                case 'delete':
                    $id_service = $_POST['id_service'];

                    $stmt = $pdo->prepare("DELETE FROM tb_service WHERE id_service = ?");
                    $stmt->execute([$id_service]);

                    $_SESSION['alert_message'] = 'Data service berhasil dihapus!';
                    $_SESSION['alert_type'] = 'success';
                    $_SESSION['alert_title'] = 'Sukses!';
                    $_SESSION['alert_icon'] = 'success';
                    break;
            }

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

// Fetch all services
$stmt = $pdo->prepare("SELECT * FROM tb_service ORDER BY created_at DESC");
$stmt->execute();
$service_list = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
<!-- [Head] start -->

<head>
    <title>Data Service Management | Datta Able Dashboard Template</title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
        name="description"
        content="Datta Able is trending dashboard template made using Bootstrap 5 design framework. Datta Able is available in Bootstrap, React, CodeIgniter, Angular,  and .net Technologies." />
    <meta
        name="keywords"
        content="Bootstrap admin template, Dashboard UI Kit, Dashboard Template, Backend Panel, react dashboard, angular dashboard" />
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
                        <h5 class="mb-0 font-medium">Data Service Management</h5>
                    </div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item" aria-current="page">Master Data</li>
                        <li class="breadcrumb-item"><a href="service.php">Data Service</a></li>
                    </ul>
                </div>
            </div>
            <!-- [ breadcrumb ] end -->

            <!-- [ Main Content ] start -->
            <div class="grid grid-cols-12 gap-x-6">
                <div class="col-span-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Data Service</h5>
                            <button class="btn btn-primary" onclick="openCreateModal()">
                                <i class="fas fa-plus"></i> Tambah Service
                            </button>
                        </div>
                        <div class="card-body">
                            <table id="serviceTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Service</th>
                                        <th>Harga Service</th>
                                        <th>Tanggal Dibuat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($service_list as $index => $service): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($service['nama_service']) ?></td>
                                            <td>Rp <?= number_format($service['harga_service'], 0, ',', '.') ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($service['created_at'])) ?></td>
                                            <td>
                                                <button class="btn btn-success btn-sm" onclick="viewService(<?= $service['id_service'] ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-warning btn-sm" onclick="editService(<?= $service['id_service'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="deleteService(<?= $service['id_service'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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

    <!-- Enhanced Create/Edit Modal -->
    <div id="serviceModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalTitle">Tambah Service</h4>
                <button type="button" class="close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="serviceForm" method="POST">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id_service" id="serviceId">

                    <!-- Service Information -->
                    <div class="form-group">
                        <label for="nama_service">
                            <i class="fas fa-tools"></i> Nama Service
                        </label>
                        <input type="text" class="form-control" id="nama_service" name="nama_service" required placeholder="Masukkan nama service">
                    </div>

                    <div class="form-group">
                        <label for="harga_service">
                            <i class="fas fa-tag"></i> Harga Service
                        </label>
                        <input type="number" class="form-control" id="harga_service" name="harga_service" required min="0" step="0.01" placeholder="Masukkan harga service">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" form="serviceForm" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </div>
        </div>
    </div>

    <!-- Enhanced View Modal -->
    <div id="viewModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-eye"></i> Detail Service
                </h4>
                <button type="button" class="close" onclick="closeViewModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="viewContent" class="view-content">
                    <!-- Content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewModal()">
                    <i class="fas fa-times"></i> Tutup
                </button>
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
            $('#serviceTable').DataTable({
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
                }
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

        // Service data for JavaScript
        const service_data = <?= json_encode($service_list) ?>;

        // Open create modal
        function openCreateModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus"></i> Tambah Service';
            document.getElementById('formAction').value = 'create';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Simpan';
            document.getElementById('serviceForm').reset();

            const modal = document.getElementById('serviceModal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
        }

        // Close modal
        function closeModal() {
            const modal = document.getElementById('serviceModal');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        // Close view modal
        function closeViewModal() {
            const modal = document.getElementById('viewModal');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        // View service
        function viewService(id) {
            const service = service_data.find(s => s.id_service == id);
            if (service) {
                document.getElementById('viewContent').innerHTML = `
                    <div class="view-profile-section">
                        <div class="view-profile-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="view-profile-name">${service.nama_service}</div>
                        <div class="view-profile-role">Rp ${new Intl.NumberFormat('id-ID').format(service.harga_service)}</div>
                    </div>
                    <div class="view-details">
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-tools"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Nama Service</div>
                                <div class="detail-value">${service.nama_service}</div>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-tag"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Harga Service</div>
                                <div class="detail-value">Rp ${new Intl.NumberFormat('id-ID').format(service.harga_service)}</div>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Tanggal Dibuat</div>
                                <div class="detail-value">${new Date(service.created_at).toLocaleDateString('id-ID', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                })}</div>
                            </div>
                        </div>
                        ${service.update_at ? `
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-edit"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Terakhir Diupdate</div>
                                <div class="detail-value">${new Date(service.update_at).toLocaleDateString('id-ID', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                })}</div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                `;

                const modal = document.getElementById('viewModal');
                modal.classList.remove('hidden');
                setTimeout(() => {
                    modal.classList.add('show');
                }, 10);
            }
        }

        // Edit service
        function editService(id) {
            const service = service_data.find(s => s.id_service == id);
            if (service) {
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Service';
                document.getElementById('formAction').value = 'update';
                document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Update';
                document.getElementById('serviceId').value = service.id_service;
                document.getElementById('nama_service').value = service.nama_service;
                document.getElementById('harga_service').value = service.harga_service;

                const modal = document.getElementById('serviceModal');
                modal.classList.remove('hidden');
                setTimeout(() => {
                    modal.classList.add('show');
                }, 10);
            }
        }

        // Delete service
        function deleteService(id) {
            const service = service_data.find(s => s.id_service == id);
            Swal.fire({
                title: 'Konfirmasi Hapus',
                html: `
                    <div style="text-align: center; margin: 20px 0;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 4rem; color: #f39c12; margin-bottom: 20px;"></i>
                        <p style="font-size: 1.1rem; margin-bottom: 10px;">Apakah Anda yakin ingin menghapus service:</p>
                        <p style="font-size: 1.3rem; font-weight: 600; color: #333;">${service ? service.nama_service : 'ini'}</p>
                        <p style="color: #666; font-size: 0.9rem;">Data akan dihapus secara permanen dan tidak dapat dikembalikan!</p>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus!',
                cancelButtonText: '<i class="fas fa-times"></i> Batal',
                reverseButtons: true,
                customClass: {
                    popup: 'animated bounceIn',
                    confirmButton: 'btn-animated',
                    cancelButton: 'btn-animated'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Menghapus...',
                        text: 'Mohon tunggu sebentar',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Create form and submit
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id_service" value="${id}">
            `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            // Close modal when clicking outside
            document.getElementById('serviceModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });

            document.getElementById('viewModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeViewModal();
                }
            });

            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                    closeViewModal();
                }
            });

            // Form validation and styling
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });

                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('focused');
                    if (this.value.trim()) {
                        this.parentElement.classList.add('has-value');
                    } else {
                        this.parentElement.classList.remove('has-value');
                    }
                });
            });
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
<!-- [Body] end -->

</html>