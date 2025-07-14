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

// Proses login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);

  // Validasi input
  if (empty($email) || empty($password)) {
    $_SESSION['alert_type'] = 'warning';
    $_SESSION['alert_title'] = 'Peringatan';
    $_SESSION['alert_message'] = 'Email dan password harus diisi!';
    $_SESSION['alert_icon'] = 'fas fa-exclamation-triangle';
    // Redirect ke halaman yang sama dengan GET request
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
  } else {
    try {
      // Query untuk mencari user berdasarkan email
      $stmt = $pdo->prepare("SELECT * FROM tb_user WHERE email = ? AND password=?");
      $stmt->execute([$email, $password]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($user) {
        // Login berhasil
        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['photo_profile'] = $user['photo_profile'];

        $_SESSION['alert_type'] = 'success';
        $_SESSION['alert_title'] = 'Berhasil';
        $_SESSION['alert_message'] = 'Login berhasil! Anda akan diarahkan ke dashboard.';
        $_SESSION['alert_icon'] = 'fas fa-check-circle';

        // Redirect berdasarkan role setelah 2 detik
        if ($user['role'] === 'administrator') {
          header("Location: dashboard/admin/index.php");
        } else if ($user['role'] === 'konsumen') {
          header("Location: dashboard/konsumen/index.php");
        } else if ($user['role'] === 'mekanik') {
          header("Location: dashboard/mekanik/index.php");
        } else if ($user['role'] === 'kasir') {
          header("Location: dashboard/kasir/index.php");
        }
        exit();
      } else {
        // Login gagal
        $_SESSION['alert_type'] = 'danger';
        $_SESSION['alert_title'] = 'Error';
        $_SESSION['alert_message'] = 'Email atau password salah!';
        $_SESSION['alert_icon'] = 'fas fa-times-circle';
        // Redirect ke halaman yang sama dengan GET request
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
      }
    } catch (PDOException $e) {
      // Error database
      $_SESSION['alert_type'] = 'danger';
      $_SESSION['alert_title'] = 'Error Database';
      $_SESSION['alert_message'] = 'Terjadi kesalahan pada sistem. Silakan coba lagi.';
      $_SESSION['alert_icon'] = 'fas fa-database';
      // Redirect ke halaman yang sama dengan GET request
      header("Location: " . $_SERVER['PHP_SELF']);
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
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
<!-- [Head] start -->

<head>
  <title>Login | Bengkel Padang</title>
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
                <a href="index.php"><img src="assets/images/logo-dark.svg" alt="img" class="mx-auto auth-logo" /></a>
              </div>
              <h4 class="text-center font-medium mb-4">Login</h4>
              <form method="POST" action="index.php" class="needs-validation" novalidate="">
                <div class="mb-3">
                  <input type="email" name="email" class="form-control" id="floatingInput" placeholder="Email Address" required />
                </div>
                <div class="mb-4">
                  <input type="password" name="password" class="form-control" id="floatingInput1" placeholder="Password" required />
                </div>
                <div class="mt-4 text-center">
                  <button type="submit" name="login" class="btn btn-primary mx-auto shadow-2xl">Login</button>
                </div>
              </form>
              <div class="flex justify-between items-end flex-wrap mt-4">
                <h6 class="font-medium mb-0">Don't have an Account?</h6>
                <a href="register.php" class="text-primary-500">Create Account</a>
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

</body>
<!-- [Body] end -->

</html>