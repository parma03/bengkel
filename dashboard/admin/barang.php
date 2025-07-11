<?php
session_start();
include '../../db/koneksi.php';

// Inisialisasi variabel untuk alert
$alert_message = '';
$alert_type = '';
$alert_title = '';
$alert_icon = '';

// Process CRUD operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'create':
                    $nama_barang = $_POST['nama_barang'];
                    $stok_barang = $_POST['stok_barang'];
                    $harga_barang = $_POST['harga_barang'];
                    $foto_barang = null;

                    // Handle file upload
                    if (isset($_FILES['foto_barang']) && $_FILES['foto_barang']['error'] == 0) {
                        $upload_dir = '../../assets/images/barang/';
                        $file_extension = pathinfo($_FILES['foto_barang']['name'], PATHINFO_EXTENSION);
                        $new_filename = uniqid() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;

                        if (move_uploaded_file($_FILES['foto_barang']['tmp_name'], $upload_path)) {
                            $foto_barang = $new_filename;
                        }
                    }

                    $stmt = $pdo->prepare("INSERT INTO tb_barang (nama_barang, stok_barang, harga_barang, foto_barang, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$nama_barang, $stok_barang, $harga_barang, $foto_barang]);

                    $_SESSION['alert_message'] = 'Data barang berhasil ditambahkan!';
                    $_SESSION['alert_type'] = 'success';
                    $_SESSION['alert_title'] = 'Sukses!';
                    $_SESSION['alert_icon'] = 'success';
                    break;

                case 'update':
                    $id_barang = $_POST['id_barang'];
                    $nama_barang = $_POST['nama_barang'];
                    $stok_barang = $_POST['stok_barang'];
                    $harga_barang = $_POST['harga_barang'];
                    $old_foto = $_POST['old_foto'];
                    $foto_barang = $old_foto;

                    // Handle file upload
                    if (isset($_FILES['foto_barang']) && $_FILES['foto_barang']['error'] == 0) {
                        $upload_dir = '../../assets/images/barang/';
                        $file_extension = pathinfo($_FILES['foto_barang']['name'], PATHINFO_EXTENSION);
                        $new_filename = uniqid() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;

                        if (move_uploaded_file($_FILES['foto_barang']['tmp_name'], $upload_path)) {
                            // Delete old photo if exists
                            if ($old_foto && file_exists($upload_dir . $old_foto)) {
                                unlink($upload_dir . $old_foto);
                            }
                            $foto_barang = $new_filename;
                        }
                    }

                    $stmt = $pdo->prepare("UPDATE tb_barang SET nama_barang = ?, stok_barang = ?, harga_barang = ?, foto_barang = ?, updated_at = NOW() WHERE id_barang = ?");
                    $stmt->execute([$nama_barang, $stok_barang, $harga_barang, $foto_barang, $id_barang]);

                    $_SESSION['alert_message'] = 'Data barang berhasil diupdate!';
                    $_SESSION['alert_type'] = 'success';
                    $_SESSION['alert_title'] = 'Sukses!';
                    $_SESSION['alert_icon'] = 'success';
                    break;

                case 'delete':
                    $id_barang = $_POST['id_barang'];

                    // Get foto filename before delete
                    $stmt = $pdo->prepare("SELECT foto_barang FROM tb_barang WHERE id_barang = ?");
                    $stmt->execute([$id_barang]);
                    $barang = $stmt->fetch();

                    // Delete barang
                    $stmt = $pdo->prepare("DELETE FROM tb_barang WHERE id_barang = ?");
                    $stmt->execute([$id_barang]);

                    // Delete foto file if exists
                    if ($barang['foto_barang'] && file_exists('../../assets/images/barang/' . $barang['foto_barang'])) {
                        unlink('../../assets/images/barang/' . $barang['foto_barang']);
                    }

                    $_SESSION['alert_message'] = 'Data barang berhasil dihapus!';
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

// Fetch all barang
$stmt = $pdo->prepare("SELECT * FROM tb_barang ORDER BY created_at DESC");
$stmt->execute();
$barang_list = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
<!-- [Head] start -->

<head>
    <title>Data Barang Management | Datta Able Dashboard Template</title>
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
                        <h5 class="mb-0 font-medium">Data Barang Management</h5>
                    </div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item" aria-current="page">Master Data</li>
                        <li class="breadcrumb-item"><a href="barang.php">Data Barang</a></li>
                    </ul>
                </div>
            </div>
            <!-- [ breadcrumb ] end -->

            <!-- [ Main Content ] start -->
            <div class="grid grid-cols-12 gap-x-6">
                <div class="col-span-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Data Barang</h5>
                            <button class="btn btn-primary" onclick="openCreateModal()">
                                <i class="fas fa-plus"></i> Tambah Barang
                            </button>
                        </div>
                        <div class="card-body">
                            <table id="barangTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Foto</th>
                                        <th>Nama Barang</th>
                                        <th>Stok</th>
                                        <th>Harga</th>
                                        <th>Tanggal Dibuat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($barang_list as $index => $barang): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td>
                                                <img src="<?= $barang['foto_barang'] ? '../../assets/images/barang/' . $barang['foto_barang'] : '../../assets/images/barang/img-coupon.png' ?>"
                                                    alt="Product Image" class="avatar">
                                            </td>
                                            <td><?= htmlspecialchars($barang['nama_barang']) ?></td>
                                            <td>
                                                <span class="badge <?= $barang['stok_barang'] > 10 ? 'bg-success' : ($barang['stok_barang'] > 5 ? 'bg-warning' : 'bg-danger') ?>">
                                                    <?= $barang['stok_barang'] ?> Unit
                                                </span>
                                            </td>
                                            <td>Rp <?= number_format($barang['harga_barang'], 0, ',', '.') ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($barang['created_at'])) ?></td>
                                            <td>
                                                <button class="btn btn-success btn-sm" onclick="viewBarang(<?= $barang['id_barang'] ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-warning btn-sm" onclick="editBarang(<?= $barang['id_barang'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="deleteBarang(<?= $barang['id_barang'] ?>)">
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
    <div id="barangModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalTitle">Tambah Barang</h4>
                <button type="button" class="close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="barangForm" enctype="multipart/form-data" method="POST">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id_barang" id="barangId">
                    <input type="hidden" name="old_foto" id="oldFoto">

                    <!-- Product Photo Section -->
                    <div class="form-group">
                        <label>Foto Barang</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="foto_barang" name="foto_barang" accept="image/*" onchange="previewImage(this)">
                            <label for="foto_barang" class="file-input-label">
                                <i class="fas fa-camera"></i>
                                <span>Pilih Foto Barang</span>
                            </label>
                        </div>
                        <img id="imagePreview" class="profile-preview hidden" alt="Preview">
                    </div>

                    <!-- Product Information -->
                    <div class="form-group">
                        <label for="nama_barang">
                            <i class="fas fa-box"></i> Nama Barang
                        </label>
                        <input type="text" class="form-control" id="nama_barang" name="nama_barang" required placeholder="Masukkan nama barang">
                    </div>

                    <div class="form-group">
                        <label for="stok_barang">
                            <i class="fas fa-warehouse"></i> Stok Barang
                        </label>
                        <input type="number" class="form-control" id="stok_barang" name="stok_barang" required min="0" placeholder="Masukkan jumlah stok">
                    </div>

                    <div class="form-group">
                        <label for="harga_barang">
                            <i class="fas fa-tag"></i> Harga Barang
                        </label>
                        <input type="number" class="form-control" id="harga_barang" name="harga_barang" required min="0" step="0.01" placeholder="Masukkan harga barang">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" form="barangForm" class="btn btn-primary" id="submitBtn">
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
                    <i class="fas fa-eye"></i> Detail Barang
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
            $('#barangTable').DataTable({
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

        // Barang data for JavaScript
        const barang_data = <?= json_encode($barang_list) ?>;

        // Open create modal
        function openCreateModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus"></i> Tambah Barang';
            document.getElementById('formAction').value = 'create';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Simpan';
            document.getElementById('barangForm').reset();
            document.getElementById('imagePreview').classList.add('hidden');

            const modal = document.getElementById('barangModal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
        }

        // Close modal
        function closeModal() {
            const modal = document.getElementById('barangModal');
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

        // Preview image
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const label = input.nextElementSibling;

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    label.innerHTML = '<i class="fas fa-check"></i><span>Foto berhasil dipilih</span>';
                    label.style.borderColor = '#28a745';
                    label.style.color = '#28a745';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.classList.add('hidden');
                label.innerHTML = '<i class="fas fa-camera"></i><span>Pilih Foto Barang</span>';
                label.style.borderColor = '#e1e5e9';
                label.style.color = '#6c757d';
            }
        }

        // View barang
        function viewBarang(id) {
            const barang = barang_data.find(b => b.id_barang == id);
            if (barang) {
                const fotoSrc = barang.foto_barang ?
                    '../../assets/images/barang/' + barang.foto_barang :
                    '../../assets/images/barang/default-product.png';

                const stockStatus = barang.stok_barang > 10 ? 'success' :
                    (barang.stok_barang > 5 ? 'warning' : 'danger');
                const stockText = barang.stok_barang > 10 ? 'Stok Aman' :
                    (barang.stok_barang > 5 ? 'Stok Menipis' : 'Stok Habis');

                document.getElementById('viewContent').innerHTML = `
                    <div class="view-profile-section">
                        <img src="${fotoSrc}" alt="Product" class="view-profile-image">
                        <div class="view-profile-name">${barang.nama_barang}</div>
                        <div class="view-profile-role">Rp ${new Intl.NumberFormat('id-ID').format(barang.harga_barang)}</div>
                    </div>
                    <div class="view-details">
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-warehouse"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Stok Barang</div>
                                <div class="detail-value">
                                    <span class="badge bg-${stockStatus}">${barang.stok_barang} Unit - ${stockText}</span>
                                </div>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-tag"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Harga Barang</div>
                                <div class="detail-value">Rp ${new Intl.NumberFormat('id-ID').format(barang.harga_barang)}</div>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Tanggal Dibuat</div>
                                <div class="detail-value">${new Date(barang.created_at).toLocaleDateString('id-ID', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                })}</div>
                            </div>
                        </div>
                        ${barang.updated_at ? `
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-edit"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Terakhir Diupdate</div>
                                <div class="detail-value">${new Date(barang.updated_at).toLocaleDateString('id-ID', {
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

        // Edit barang
        function editBarang(id) {
            const barang = barang_data.find(b => b.id_barang == id);
            if (barang) {
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Barang';
                document.getElementById('formAction').value = 'update';
                document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Update';
                document.getElementById('barangId').value = barang.id_barang;
                document.getElementById('oldFoto').value = barang.foto_barang || '';
                document.getElementById('nama_barang').value = barang.nama_barang;
                document.getElementById('stok_barang').value = barang.stok_barang;
                document.getElementById('harga_barang').value = barang.harga_barang;

                // Show current photo
                const preview = document.getElementById('imagePreview');
                const label = document.querySelector('label[for="foto_barang"]');
                if (barang.foto_barang) {
                    preview.src = '../../assets/images/barang/' + barang.foto_barang;
                    preview.classList.remove('hidden');
                    label.innerHTML = '<i class="fas fa-check"></i><span>Foto saat ini</span>';
                    label.style.borderColor = '#28a745';
                    label.style.color = '#28a745';
                } else {
                    preview.src = '../../assets/images/barang/default-product.png';
                    preview.classList.remove('hidden');
                    label.innerHTML = '<i class="fas fa-camera"></i><span>Pilih Foto Barang</span>';
                }

                const modal = document.getElementById('barangModal');
                modal.classList.remove('hidden');
                setTimeout(() => {
                    modal.classList.add('show');
                }, 10);
            }
        }

        // Delete barang
        function deleteBarang(id) {
            const barang = barang_data.find(b => b.id_barang == id);
            Swal.fire({
                title: 'Konfirmasi Hapus',
                html: `
                    <div style="text-align: center; margin: 20px 0;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 4rem; color: #f39c12; margin-bottom: 20px;"></i>
                        <p style="font-size: 1.1rem; margin-bottom: 10px;">Apakah Anda yakin ingin menghapus barang:</p>
                        <p style="font-size: 1.3rem; font-weight: 600; color: #333;">${barang ? barang.nama_barang : 'ini'}</p>
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
                <input type="hidden" name="id_barang" value="${id}">
            `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            // Close modal when clicking outside
            document.getElementById('barangModal').addEventListener('click', function(e) {
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