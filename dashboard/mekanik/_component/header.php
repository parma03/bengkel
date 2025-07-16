<?php
// Process profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    try {
        $id_user = $_SESSION['id_user'];
        $nama = $_POST['nama'];
        $email = $_POST['email'];
        $password = $_POST['password'];
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

        // Update database
        $stmt = $pdo->prepare("UPDATE tb_user SET nama = ?, email = ?, password = ?, photo_profile = ?, updated_at = NOW() WHERE id_user = ?");
        $stmt->execute([$nama, $email, $password, $photo_profile, $id_user]);

        // Update session
        $_SESSION['nama'] = $nama;
        $_SESSION['email'] = $email;
        $_SESSION['photo_profile'] = $photo_profile;

        $_SESSION['alert_message'] = 'Profile berhasil diupdate!';
        $_SESSION['alert_type'] = 'success';
        $_SESSION['alert_title'] = 'Sukses!';
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

// Get user photo path
$user_photo = isset($_SESSION['photo_profile']) && $_SESSION['photo_profile']
    ? '../../assets/images/profile/' . $_SESSION['photo_profile']
    : '../../assets/images/profile/avatar-2.jpg';
?>

<header class="pc-header">
    <div class="header-wrapper flex max-sm:px-[15px] px-[25px] grow">
        <!-- [Mobile Media Block] start -->
        <div class="me-auto pc-mob-drp">
            <ul class="inline-flex *:min-h-header-height *:inline-flex *:items-center">
                <!-- ======= Menu collapse Icon ===== -->
                <li class="pc-h-item pc-sidebar-collapse max-lg:hidden lg:inline-flex">
                    <a href="#" class="pc-head-link ltr:!ml-0 rtl:!mr-0" id="sidebar-hide">
                        <i data-feather="menu"></i>
                    </a>
                </li>
                <li class="pc-h-item pc-sidebar-popup lg:hidden">
                    <a href="#" class="pc-head-link ltr:!ml-0 rtl:!mr-0" id="mobile-collapse">
                        <i data-feather="menu"></i>
                    </a>
                </li>
            </ul>
        </div>
        <!-- [Mobile Media Block end] -->
        <div class="ms-auto">
            <ul class="inline-flex *:min-h-header-height *:inline-flex *:items-center">
                <li class="dropdown pc-h-item">
                    <a class="pc-head-link dropdown-toggle me-0" data-pc-toggle="dropdown" href="#" role="button"
                        aria-haspopup="false" aria-expanded="false">
                        <i data-feather="sun"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
                        <a href="#!" class="dropdown-item" onclick="layout_change('dark')">
                            <i data-feather="moon"></i>
                            <span>Dark</span>
                        </a>
                        <a href="#!" class="dropdown-item" onclick="layout_change('light')">
                            <i data-feather="sun"></i>
                            <span>Light</span>
                        </a>
                        <a href="#!" class="dropdown-item" onclick="layout_change_default()">
                            <i data-feather="settings"></i>
                            <span>Default</span>
                        </a>
                    </div>
                </li>
                <li class="dropdown pc-h-item header-user-profile">
                    <a class="pc-head-link dropdown-toggle arrow-none me-0" data-pc-toggle="dropdown" href="#" role="button"
                        aria-haspopup="false" data-pc-auto-close="outside" aria-expanded="false">
                        <img src="<?= $user_photo ?>" alt="user-image" class="w-8 h-8 rounded-full object-cover" />
                    </a>
                    <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown p-2 overflow-hidden">
                        <div class="dropdown-header flex items-center justify-between py-4 px-5 bg-primary-500">
                            <div class="flex mb-1 items-center">
                                <div class="shrink-0">
                                    <img src="<?= $user_photo ?>" alt="user-image" class="w-10 h-10 rounded-full object-cover" />
                                </div>
                                <div class="grow ms-3">
                                    <h6 class="mb-1 text-white"><?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?></h6>
                                    <span class="text-white text-sm"><?= htmlspecialchars($_SESSION['email'] ?? 'email@example.com') ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown-body py-4 px-5">
                            <div class="profile-notification-scroll position-relative" style="max-height: calc(100vh - 225px)">
                                <a href="#" class="dropdown-item" onclick="openProfileModal()">
                                    <span>
                                        <svg class="pc-icon text-muted me-2 inline-block">
                                            <use xlink:href="#custom-setting-outline"></use>
                                        </svg>
                                        <span>Edit Profile</span>
                                    </span>
                                </a>
                                <a href="#" class="dropdown-item">
                                    <span>
                                        <svg class="pc-icon text-muted me-2 inline-block">
                                            <use xlink:href="#custom-lock-outline"></use>
                                        </svg>
                                        <span>Change Password</span>
                                    </span>
                                </a>

                                <div class="grid my-3">
                                    <a href="logout.php">
                                        <button class="btn btn-primary flex items-center justify-center">
                                            <svg class="pc-icon me-2 w-[22px] h-[22px]">
                                                <use xlink:href="#custom-logout-1-outline"></use>
                                            </svg>
                                            Logout
                                        </button>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</header>

<!-- Profile Edit Modal -->
<div id="profileModal" class="modal-overlay hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title">
                <i class="fas fa-user-edit"></i> Edit Profile
            </h4>
            <button type="button" class="close" onclick="closeProfileModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="profileForm" enctype="multipart/form-data" method="POST">
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="old_photo" value="<?= $_SESSION['photo_profile'] ?? '' ?>">

                <!-- Profile Photo Section -->
                <div class="form-group">
                    <label>Foto Profile</label>
                    <div class="file-input-wrapper">
                        <input type="file" id="profile_photo" name="photo_profile" accept="image/*" onchange="previewProfileImage(this)">
                        <label for="profile_photo" class="file-input-label">
                            <i class="fas fa-camera"></i>
                            <span>Pilih Foto Profile</span>
                        </label>
                    </div>
                    <img id="profileImagePreview" class="profile-preview <?= !isset($_SESSION['photo_profile']) || !$_SESSION['photo_profile'] ? 'hidden' : '' ?>"
                        src="<?= $user_photo ?>" alt="Preview">
                </div>

                <!-- Personal Information -->
                <div class="form-group">
                    <label for="profile_nama">
                        <i class="fas fa-user"></i> Nama Lengkap
                    </label>
                    <input type="text" class="form-control" id="profile_nama" name="nama"
                        value="<?= htmlspecialchars($_SESSION['nama'] ?? '') ?>" required placeholder="Masukkan nama lengkap">
                </div>

                <div class="form-group">
                    <label for="profile_email">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <input type="email" class="form-control" id="profile_email" name="email"
                        value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" required placeholder="Masukkan alamat email">
                </div>

                <div class="form-group">
                    <label for="profile_password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" class="form-control" id="profile_password" name="password"
                        required placeholder="Masukkan password baru">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeProfileModal()">
                <i class="fas fa-times"></i> Batal
            </button>
            <button type="submit" form="profileForm" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Profile
            </button>
        </div>
    </div>
</div>

<style>
    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1050;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .modal-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .modal-content {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        transform: scale(0.9) translateY(-50px);
        transition: transform 0.3s ease;
    }

    .modal-overlay.show .modal-content {
        transform: scale(1) translateY(0);
    }

    .modal-header {
        display: flex;
        justify-content: between;
        align-items: center;
        padding: 20px 25px;
        border-bottom: 1px solid #e5e7eb;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px 12px 0 0;
    }

    .modal-title {
        color: white;
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
    }

    .modal-header .close {
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0;
        margin-left: auto;
        opacity: 0.8;
        transition: opacity 0.2s;
    }

    .modal-header .close:hover {
        opacity: 1;
    }

    .modal-body {
        padding: 25px;
    }

    .modal-footer {
        padding: 20px 25px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        background-color: #f8f9fa;
        border-radius: 0 0 12px 12px;
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 20px;
        position: relative;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #374151;
        font-size: 0.9rem;
    }

    .form-group label i {
        color: #6b7280;
        margin-right: 5px;
    }

    .form-control {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background-color: #fff;
    }

    .form-control:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    /* File Input Styles */
    .file-input-wrapper {
        position: relative;
        display: inline-block;
        width: 100%;
    }

    .file-input-wrapper input[type="file"] {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }

    .file-input-label {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 16px;
        border: 2px dashed #d1d5db;
        border-radius: 8px;
        background-color: #f9fafb;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }

    .file-input-label:hover {
        border-color: #667eea;
        background-color: #f0f4ff;
        color: #667eea;
    }

    .profile-preview {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        margin-top: 10px;
        border: 3px solid #e5e7eb;
    }

    /* Button Styles */
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .btn-secondary {
        background-color: #6b7280;
        color: white;
    }

    .btn-secondary:hover {
        background-color: #4b5563;
    }

    .hidden {
        display: none;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .modal-content {
            width: 95%;
            margin: 20px;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 15px 20px;
        }
    }
</style>

<script>
    // Open profile modal
    function openProfileModal() {
        const modal = document.getElementById('profileModal');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
    }

    // Close profile modal
    function closeProfileModal() {
        const modal = document.getElementById('profileModal');
        modal.classList.remove('show');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Preview profile image
    function previewProfileImage(input) {
        const preview = document.getElementById('profileImagePreview');
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
            label.innerHTML = '<i class="fas fa-camera"></i><span>Pilih Foto Profile</span>';
            label.style.borderColor = '#d1d5db';
            label.style.color = '#6b7280';
        }
    }

    // Close modal when clicking outside
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('profileModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeProfileModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeProfileModal();
            }
        });
    });
</script>