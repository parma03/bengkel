<?php
session_start();
include 'db/koneksi.php';

// Inisialisasi variabel untuk alert
$alert_message = '';
$alert_type = '';
$alert_title = '';
$alert_icon = '';

// Pengecekan session untuk redirect jika sudah login
if (isset($_SESSION['role'])) {
  if ($_SESSION['role'] === 'administrator') {
    header("Location: dashboard/admin/index.php");
    exit();
  } else if ($_SESSION['role'] === 'konsumen') {
    header("Location: dashboard/konsumen/index.php");
    exit();
  } else if ($_SESSION['role'] === 'mekanik') {
    header("Location: dashboard/mekanik/index.php");
    exit();
  } else if ($_SESSION['role'] === 'kasir') {
    header("Location: dashboard/kasir/index.php");
    exit();
  }
}

// Proses register
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
  $first_name = trim($_POST['first_name']);
  $last_name = trim($_POST['last_name']);
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);
  $confirm_password = trim($_POST['confirm_password']);

  // Validasi input
  if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password)) {
    $_SESSION['alert_type'] = 'warning';
    $_SESSION['alert_title'] = 'Peringatan';
    $_SESSION['alert_message'] = 'Semua field harus diisi!';
    $_SESSION['alert_icon'] = 'fas fa-exclamation-triangle';
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
  }

  // Validasi password konfirmasi
  if ($password !== $confirm_password) {
    $_SESSION['alert_type'] = 'warning';
    $_SESSION['alert_title'] = 'Peringatan';
    $_SESSION['alert_message'] = 'Password dan konfirmasi password tidak cocok!';
    $_SESSION['alert_icon'] = 'fas fa-exclamation-triangle';
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
  }

  // Validasi panjang password
  if (strlen($password) < 6) {
    $_SESSION['alert_type'] = 'warning';
    $_SESSION['alert_title'] = 'Peringatan';
    $_SESSION['alert_message'] = 'Password minimal 6 karakter!';
    $_SESSION['alert_icon'] = 'fas fa-exclamation-triangle';
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
  }

  // Validasi format email
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['alert_type'] = 'warning';
    $_SESSION['alert_title'] = 'Peringatan';
    $_SESSION['alert_message'] = 'Format email tidak valid!';
    $_SESSION['alert_icon'] = 'fas fa-exclamation-triangle';
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
  }

  try {
    // Cek apakah email sudah terdaftar
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_user WHERE email = ?");
    $stmt->execute([$email]);
    $email_exists = $stmt->fetchColumn();

    if ($email_exists > 0) {
      $_SESSION['alert_type'] = 'warning';
      $_SESSION['alert_title'] = 'Peringatan';
      $_SESSION['alert_message'] = 'Email sudah terdaftar! Silakan gunakan email lain.';
      $_SESSION['alert_icon'] = 'fas fa-exclamation-triangle';
      header("Location: " . $_SERVER['PHP_SELF']);
      exit();
    }

    // Gabungkan first name dan last name
    $nama_lengkap = $first_name . ' ' . $last_name;

    // Insert user baru ke database
    $stmt = $pdo->prepare("INSERT INTO tb_user (nama, email, password, role, photo_profile) VALUES (?, ?, ?, ?, ?)");
    $result = $stmt->execute([$nama_lengkap, $email, $password, 'konsumen', 'default.jpg']);

    if ($result) {
      // Registrasi berhasil
      $_SESSION['alert_type'] = 'success';
      $_SESSION['alert_title'] = 'Berhasil';
      $_SESSION['alert_message'] = 'Registrasi berhasil! Silakan login dengan akun Anda.';
      $_SESSION['alert_icon'] = 'fas fa-check-circle';
      header("Location: index.php");
      exit();
    } else {
      $_SESSION['alert_type'] = 'danger';
      $_SESSION['alert_title'] = 'Error';
      $_SESSION['alert_message'] = 'Registrasi gagal! Silakan coba lagi.';
      $_SESSION['alert_icon'] = 'fas fa-times-circle';
      header("Location: " . $_SERVER['PHP_SELF']);
      exit();
    }
  } catch (PDOException $e) {
    // Error database
    $_SESSION['alert_type'] = 'danger';
    $_SESSION['alert_title'] = 'Error Database';
    $_SESSION['alert_message'] = 'Terjadi kesalahan pada sistem. Silakan coba lagi.';
    $_SESSION['alert_icon'] = 'fas fa-database';
    header("Location: " . $_SERVER['PHP_SELF']);
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
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
<!-- [Head] start -->

<head>
  <title>Register | Bengkel Padang</title>
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
  <link rel="icon" href="assets/images/favicon.svg" type="image/x-icon" />
  <!-- [Font] Family -->
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <!-- [phosphor Icons] https://phosphoricons.com/ -->
  <link rel="stylesheet" href="assets/fonts/phosphor/duotone/style.css" />
  <!-- [Tabler Icons] https://tablericons.com -->
  <link rel="stylesheet" href="assets/fonts/tabler-icons.min.css" />
  <!-- [Feather Icons] https://feathericons.com -->
  <link rel="stylesheet" href="assets/fonts/feather.css" />
  <!-- [Font Awesome Icons] https://fontawesome.com/icons -->
  <link rel="stylesheet" href="assets/fonts/fontawesome.css" />
  <!-- [Material Icons] https://fonts.google.com/icons -->
  <link rel="stylesheet" href="assets/fonts/material.css" />
  <!-- [Template CSS Files] -->
  <link rel="stylesheet" href="assets/css/style.css" id="main-style-link" />

</head>
<!-- [Head] end -->
<!-- [Body] Start -->

<body>
  <!-- Alert Container di pojok kanan atas -->
  <?php if (!empty($alert_message)): ?>
    <div class="alert-container">
      <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show fade-in" role="alert">
        <div class="alert-icon">
          <i class="<?php echo $alert_icon; ?>"></i>
        </div>
        <div class="alert-body">
          <div class="alert-title"><?php echo $alert_title; ?></div>
          <?php echo $alert_message; ?>
        </div>
        <button type="button" class="close" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    </div>
  <?php endif; ?>
  <!-- [ Pre-loader ] start -->
  <div class="loader-bg fixed inset-0 bg-white dark:bg-themedark-cardbg z-[1034]">
    <div class="loader-track h-[5px] w-full inline-block absolute overflow-hidden top-0">
      <div class="loader-fill w-[300px] h-[5px] bg-primary-500 absolute top-0 left-0 animate-[hitZak_0.6s_ease-in-out_infinite_alternate]"></div>
    </div>
  </div>
  <!-- [ Pre-loader ] End -->

  <div class="auth-main relative">
    <div class="auth-wrapper v1 flex items-center w-full h-full min-h-screen">
      <div class="auth-form flex items-center justify-center grow flex-col min-h-screen relative p-6 ">
        <div class="w-full max-w-[350px] relative">
          <div class="auth-bg ">
            <span class="absolute top-[-100px] right-[-100px] w-[300px] h-[300px] block rounded-full bg-theme-bg-1 animate-[floating_7s_infinite]"></span>
            <span class="absolute top-[150px] right-[-150px] w-5 h-5 block rounded-full bg-primary-500 animate-[floating_9s_infinite]"></span>
            <span class="absolute left-[-150px] bottom-[150px] w-5 h-5 block rounded-full bg-theme-bg-1 animate-[floating_7s_infinite]"></span>
            <span class="absolute left-[-100px] bottom-[-100px] w-[300px] h-[300px] block rounded-full bg-theme-bg-2 animate-[floating_9s_infinite]"></span>
          </div>
          <div class="card sm:my-12  w-full shadow-none">
            <div class="card-body !p-10">
              <div class="text-center mb-8">
                <a href="register.php"><img src="assets/images/logo-dark.svg" alt="img" class="mx-auto auth-logo" /></a>
              </div>
              <h4 class="text-center font-medium mb-4">Sign up</h4>
              <form method="POST" action="register.php" class="needs-validation" novalidate="">
                <div class="grid grid-cols-12 gap-3 mb-3">
                  <div class="col-span-12 sm:col-span-6">
                    <input type="text" name="first_name" class="form-control" placeholder="First Name" required>
                  </div>
                  <div class="col-span-12 sm:col-span-6">
                    <input type="text" name="last_name" class="form-control" placeholder="Last Name" required>
                  </div>
                </div>
                <div class="mb-3">
                  <input type="email" name="email" class="form-control" placeholder="Email Address" required>
                </div>
                <div class="mb-3">
                  <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                <div class="mb-4">
                  <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                </div>
                <div class="mt-4 text-center">
                  <button type="submit" name="register" class="btn btn-primary mx-auto shadow-2xl">Sign up</button>
                </div>
              </form>
              <div class="flex justify-between items-end flex-wrap mt-4">
                <h6 class="font-medium mb-0">Already have an Account?</h6>
                <a href="index.php" class="text-primary-500">Login</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- [ Main Content ] end -->
  <!-- Required Js -->
  <script src="assets/js/plugins/simplebar.min.js"></script>
  <script src="assets/js/plugins/popper.min.js"></script>
  <script src="assets/js/icon/custom-icon.js"></script>
  <script src="assets/js/plugins/feather.min.js"></script>
  <script src="assets/js/component.js"></script>
  <script src="assets/js/theme.js"></script>
  <script src="assets/js/script.js"></script>

  <div class="floting-button fixed bottom-[50px] right-[30px] z-[1030]">
  </div>


  <script>
    layout_change('false');
  </script>


  <script>
    layout_theme_sidebar_change('dark');
  </script>


  <script>
    change_box_container('false');
  </script>

  <script>
    layout_caption_change('true');
  </script>

  <script>
    layout_rtl_change('false');
  </script>

  <script>
    preset_change('preset-1');
  </script>

  <script>
    main_layout_change('vertical');
  </script>

  <!-- Script untuk auto-hide alert -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const alertContainer = document.querySelector('.alert-container');
      if (alertContainer) {
        // Auto hide alert after 5 seconds
        setTimeout(function() {
          alertContainer.style.opacity = '0';
          setTimeout(function() {
            alertContainer.remove();
          }, 300);
        }, 5000);

        // Manual close button
        const closeButton = alertContainer.querySelector('.close');
        if (closeButton) {
          closeButton.addEventListener('click', function() {
            alertContainer.style.opacity = '0';
            setTimeout(function() {
              alertContainer.remove();
            }, 300);
          });
        }
      }
    });
  </script>

  <!-- CSS untuk Alert -->
  <style>
    .alert-container {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
      min-width: 300px;
      max-width: 400px;
    }

    .alert {
      display: flex;
      align-items: flex-start;
      padding: 15px;
      margin-bottom: 0;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
    }

    .alert-icon {
      margin-right: 12px;
      font-size: 20px;
      margin-top: 2px;
    }

    .alert-body {
      flex: 1;
    }

    .alert-title {
      font-weight: 600;
      margin-bottom: 4px;
      font-size: 14px;
    }

    .alert-success {
      background-color: #d4edda;
      border-color: #c3e6cb;
      color: #155724;
    }

    .alert-warning {
      background-color: #fff3cd;
      border-color: #ffeaa7;
      color: #856404;
    }

    .alert-danger {
      background-color: #f8d7da;
      border-color: #f5c6cb;
      color: #721c24;
    }

    .close {
      background: none;
      border: none;
      font-size: 20px;
      cursor: pointer;
      color: inherit;
      opacity: 0.7;
      margin-left: 10px;
    }

    .close:hover {
      opacity: 1;
    }

    .fade-in {
      animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>

</body>
<!-- [Body] end -->

</html>