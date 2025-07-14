<?php
// File: config/midtrans.php
// Konfigurasi Midtrans

// Method 1: If using Composer (RECOMMENDED)
require_once dirname(__FILE__) . '/../vendor/autoload.php';

// Method 2: If using manual installation (alternative)
// require_once dirname(__FILE__) . '/../vendor/midtrans-php/Midtrans.php';

// After including the library, then configure
\Midtrans\Config::$serverKey = NULL;
\Midtrans\Config::$clientKey = NULL; // Ganti dengan client key yang benar
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;
