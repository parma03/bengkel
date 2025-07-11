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
                    $nama = $_POST['nama'];
                    $nohp = $_POST['nohp'];
                    $email = $_POST['email'];
                    $password = $_POST['password'];
                    $role = "konsumen";
                    $photo_profile = null;

                    // Handle file upload
                    if (isset($_FILES['photo_profile']) && $_FILES['photo_profile']['error'] == 0) {
                        $upload_dir = '../../assets/images/profile/';
                        $file_extension = pathinfo($_FILES['photo_profile']['name'], PATHINFO_EXTENSION);
                        $new_filename = uniqid() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;

                        if (move_uploaded_file($_FILES['photo_profile']['tmp_name'], $upload_path)) {
                            $photo_profile = $new_filename;
                        }
                    }

                    $stmt = $pdo->prepare("INSERT INTO tb_user (nama, nohp, email, password, role, photo_profile, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$nama, $nohp, $email, $password, $role, $photo_profile]);

                    $_SESSION['alert_message'] = 'Data konsumen berhasil ditambahkan!';
                    $_SESSION['alert_type'] = 'success';
                    $_SESSION['alert_title'] = 'Sukses!';
                    $_SESSION['alert_icon'] = 'success';
                    break;

                case 'update':
                    $id_user = $_POST['id_user'];
                    $nama = $_POST['nama'];
                    $nohp = $_POST['nohp'];
                    $email = $_POST['email'];
                    $password = $_POST['password'];
                    $role = "konsumen";
                    $old_photo = $_POST['old_photo'];
                    $photo_profile = $old_photo;

                    // Handle file upload
                    if (isset($_FILES['photo_profile']) && $_FILES['photo_profile']['error'] == 0) {
                        $upload_dir = '../../assets/images/profile/';
                        $file_extension = pathinfo($_FILES['photo_profile']['name'], PATHINFO_EXTENSION);
                        $new_filename = uniqid() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;

                        if (move_uploaded_file($_FILES['photo_profile']['tmp_name'], $upload_path)) {
                            // Delete old photo if exists
                            if ($old_photo && file_exists($upload_dir . $old_photo)) {
                                unlink($upload_dir . $old_photo);
                            }
                            $photo_profile = $new_filename;
                        }
                    }

                    $stmt = $pdo->prepare("UPDATE tb_user SET nama = ?, nohp = ?, email = ?, password = ?, role = ?, photo_profile = ?, updated_at = NOW() WHERE id_user = ?");
                    $stmt->execute([$nama, $nohp, $email, $password, $role, $photo_profile, $id_user]);

                    $_SESSION['alert_message'] = 'Data konsumen berhasil diupdate!';
                    $_SESSION['alert_type'] = 'success';
                    $_SESSION['alert_title'] = 'Sukses!';
                    $_SESSION['alert_icon'] = 'success';
                    break;

                case 'delete':
                    $id_user = $_POST['id_user'];

                    // Get photo filename before delete
                    $stmt = $pdo->prepare("SELECT photo_profile FROM tb_user WHERE id_user = ?");
                    $stmt->execute([$id_user]);
                    $user = $stmt->fetch();

                    // Delete user
                    $stmt = $pdo->prepare("DELETE FROM tb_user WHERE id_user = ?");
                    $stmt->execute([$id_user]);

                    // Delete photo file if exists
                    if ($user['photo_profile'] && file_exists('../../assets/images/profile/' . $user['photo_profile'])) {
                        unlink('../../assets/images/profile/' . $user['photo_profile']);
                    }

                    $_SESSION['alert_message'] = 'Data konsumen berhasil dihapus!';
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

// Fetch all konsumen
$stmt = $pdo->prepare("SELECT * FROM tb_user WHERE role = 'konsumen' ORDER BY created_at DESC");
$stmt->execute();
$konsumen = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
<!-- [Head] start -->

<head>
    <title>Konsumen Management | Datta Able Dashboard Template</title>
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

    <style>

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
                        <h5 class="mb-0 font-medium">Konsumen Management</h5>
                    </div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item" aria-current="page">Master Data</li>
                        <li class="breadcrumb-item" aria-current="page">Data User</li>
                        <li class="breadcrumb-item"><a href="konsumen.php">Konsumen</a></li>
                    </ul>
                </div>
            </div>
            <!-- [ breadcrumb ] end -->

            <!-- [ Main Content ] start -->
            <div class="grid grid-cols-12 gap-x-6">
                <div class="col-span-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Data Konsumen</h5>
                            <button class="btn btn-primary" onclick="openCreateModal()">
                                <i class="fas fa-plus"></i> Tambah Konsumen
                            </button>
                        </div>
                        <div class="card-body">
                            <table id="konsumenTable" class="display" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Foto</th>
                                        <th>Nama</th>
                                        <th>No HP</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Tanggal Dibuat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($konsumen as $index => $user): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td>
                                                <img src="<?= $user['photo_profile'] ? '../../assets/images/profile/' . $user['photo_profile'] : '../../assets/images/profile/avatar-2.jpg' ?>"
                                                    alt="Avatar" class="avatar">
                                            </td>
                                            <td><?= htmlspecialchars($user['nama']) ?></td>
                                            <td><?= htmlspecialchars($user['nohp']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><span class="badge bg-info"><?= ucfirst($user['role']) ?></span></td>
                                            <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                                            <td>
                                                <button class="btn btn-success btn-sm" onclick="viewKonsumen(<?= $user['id_user'] ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-warning btn-sm" onclick="editKonsumen(<?= $user['id_user'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="deleteKonsumen(<?= $user['id_user'] ?>)">
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
    <div id="konsumenModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalTitle">Tambah Konsumen</h4>
                <button type="button" class="close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="konsumenForm" enctype="multipart/form-data" method="POST">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id_user" id="konsumenId">
                    <input type="hidden" name="old_photo" id="oldPhoto">

                    <!-- Profile Photo Section -->
                    <div class="form-group">
                        <label>Foto Profile</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="photo_profile" name="photo_profile" accept="image/*" onchange="previewImage(this)">
                            <label for="photo_profile" class="file-input-label">
                                <i class="fas fa-camera"></i>
                                <span>Pilih Foto Profile</span>
                            </label>
                        </div>
                        <img id="imagePreview" class="profile-preview hidden" alt="Preview">
                    </div>

                    <!-- Personal Information -->
                    <div class="form-group">
                        <label for="nama">
                            <i class="fas fa-user"></i> Nama Lengkap
                        </label>
                        <input type="text" class="form-control" id="nama" name="nama" required placeholder="Masukkan nama lengkap">
                    </div>

                    <div class="form-group">
                        <label for="nohp">
                            <i class="fas fa-phone"></i> No HP
                        </label>
                        <input type="number" class="form-control" id="nohp" name="nohp" required placeholder="Masukkan nomor HP">
                    </div>

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email
                        </label>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="Masukkan alamat email">
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input type="password" class="form-control" id="password" name="password" required placeholder="Masukkan password">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" form="konsumenForm" class="btn btn-primary" id="submitBtn">
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
                    <i class="fas fa-eye"></i> Detail Konsumen
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
            $('#konsumenTable').DataTable({
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

        // Konsumen data for JavaScript
        const konsumen = <?= json_encode($konsumen) ?>;

        // Open create modal
        function openCreateModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus"></i> Tambah Konsumen';
            document.getElementById('formAction').value = 'create';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Simpan';
            document.getElementById('konsumenForm').reset();
            document.getElementById('imagePreview').classList.add('hidden');

            const modal = document.getElementById('konsumenModal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
        }

        // Close modal
        function closeModal() {
            const modal = document.getElementById('konsumenModal');
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
                label.innerHTML = '<i class="fas fa-camera"></i><span>Pilih Foto Profile</span>';
                label.style.borderColor = '#e1e5e9';
                label.style.color = '#6c757d';
            }
        }

        // View konsumen
        function viewKonsumen(id) {
            const user = konsumen.find(k => k.id_user == id);
            if (user) {
                const photoSrc = user.photo_profile ?
                    '../../assets/images/profile/' + user.photo_profile :
                    '../../assets/images/profile/avatar-2.jpg';

                document.getElementById('viewContent').innerHTML = `
            <div class="view-profile-section">
                <img src="${photoSrc}" alt="Profile" class="view-profile-image">
                <div class="view-profile-name">${user.nama}</div>
                <div class="view-profile-role">${user.role.charAt(0).toUpperCase() + user.role.slice(1)}</div>
            </div>
            <div class="view-details">
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">No HP</div>
                        <div class="detail-value">${user.nohp}</div>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Email</div>
                        <div class="detail-value">${user.email}</div>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Tanggal Dibuat</div>
                        <div class="detail-value">${new Date(user.created_at).toLocaleDateString('id-ID', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        })}</div>
                    </div>
                </div>
                ${user.updated_at ? `
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Terakhir Diupdate</div>
                        <div class="detail-value">${new Date(user.updated_at).toLocaleDateString('id-ID', {
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

        // Edit konsumen
        function editKonsumen(id) {
            const user = konsumen.find(k => k.id_user == id);
            if (user) {
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Konsumen';
                document.getElementById('formAction').value = 'update';
                document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Update';
                document.getElementById('konsumenId').value = user.id_user;
                document.getElementById('oldPhoto').value = user.photo_profile || '';
                document.getElementById('nama').value = user.nama;
                document.getElementById('nohp').value = user.nohp;
                document.getElementById('email').value = user.email;
                document.getElementById('password').value = user.password;

                // Show current photo
                const preview = document.getElementById('imagePreview');
                const label = document.querySelector('label[for="photo_profile"]');
                if (user.photo_profile) {
                    preview.src = '../../assets/images/profile/' + user.photo_profile;
                    preview.classList.remove('hidden');
                    label.innerHTML = '<i class="fas fa-check"></i><span>Foto saat ini</span>';
                    label.style.borderColor = '#28a745';
                    label.style.color = '#28a745';
                } else {
                    preview.src = '../../assets/images/profile/avatar-2.jpg';
                    preview.classList.remove('hidden');
                    label.innerHTML = '<i class="fas fa-camera"></i><span>Pilih Foto Profile</span>';
                }

                const modal = document.getElementById('konsumenModal');
                modal.classList.remove('hidden');
                setTimeout(() => {
                    modal.classList.add('show');
                }, 10);
            }
        }

        // Delete konsumen
        function deleteKonsumen(id) {
            const user = konsumen.find(k => k.id_user == id);
            Swal.fire({
                title: 'Konfirmasi Hapus',
                html: `
            <div style="text-align: center; margin: 20px 0;">
                <i class="fas fa-exclamation-triangle" style="font-size: 4rem; color: #f39c12; margin-bottom: 20px;"></i>
                <p style="font-size: 1.1rem; margin-bottom: 10px;">Apakah Anda yakin ingin menghapus konsumen:</p>
                <p style="font-size: 1.3rem; font-weight: 600; color: #333;">${user ? user.nama : 'ini'}</p>
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
                <input type="hidden" name="id_user" value="${id}">
            `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            // Close modal when clicking outside
            document.getElementById('konsumenModal').addEventListener('click', function(e) {
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