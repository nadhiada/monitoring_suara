<?php
session_start();
include "koneksi.php";

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: dashboard.php");
    exit();
}

$message = "";
$message_type = "";
$isRegister = isset($_GET['register']);

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    if ($isRegister) {
        // Cek username sudah ada atau belum
        $check = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
        if (mysqli_num_rows($check) > 0) {
            $message = "Username sudah ada!";
            $message_type = "error";
        } else {
            // Hash password sebelum disimpan
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, password) VALUES ('$username', '$hashed_password')";
            if (mysqli_query($conn, $sql)) {
                $message = "Registrasi berhasil! Silakan login.";
                $message_type = "success";
                $isRegister = false;
            } else {
                $message = "Registrasi gagal! " . mysqli_error($conn);
                $message_type = "error";
            }
        }
    } else {
        // Login - cek username dulu
        $sql = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
        if (mysqli_num_rows($sql) > 0) {
            $user = mysqli_fetch_assoc($sql);
            
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['username'] = $username;
                
                if ($password === $user['password']) {
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    mysqli_query($conn, "UPDATE users SET password='$new_hash' WHERE username='$username'");
                }
                
                header("Location: dashboard.php");
                exit();
            } else {
                $message = "Username atau password salah!";
                $message_type = "error";
            }
        } else {
            $message = "Username atau password salah!";
            $message_type = "error";
        }
    }
}

$showForm = isset($_GET['action']) || $_SERVER['REQUEST_METHOD'] == "POST";
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $showForm ? ($isRegister ? "Register" : "Login") : "Monitoring Studio Musik"; ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      /* Dark Theme */
      --primary: #8b5cf6;
      --primary-dark: #7c3aed;
      --secondary: #6366f1;
      --accent: #f472b6;
      --danger: #ef4444;
      --warning: #f59e0b;
      --success: #10b981;
      --dark: #0f172a;
      --darker: #0a0e1a;
      --light: #f8fafc;
      --gray: #64748b;
      --gray-light: #cbd5e1;
      --bg-primary: #0f172a;
      --bg-secondary: #1e293b;
      --bg-card: rgba(30, 41, 59, 0.95);
      --text-primary: #f8fafc;
      --text-secondary: #cbd5e1;
      --border: rgba(139, 92, 246, 0.2);
    }

    [data-theme="light"] {
      --primary: #7c3aed;
      --primary-dark: #6d28d9;
      --secondary: #4f46e5;
      --accent: #ec4899;
      --danger: #dc2626;
      --warning: #d97706;
      --success: #059669;
      --dark: #f8fafc;
      --darker: #f1f5f9;
      --light: #0f172a;
      --gray: #94a3b8;
      --gray-light: #64748b;
      --bg-primary: #ffffff;
      --bg-secondary: #f8fafc;
      --bg-card: rgba(255, 255, 255, 0.95);
      --text-primary: #0f172a;
      --text-secondary: #475569;
      --border: rgba(99, 102, 241, 0.2);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
      color: var(--text-primary);
      overflow-x: hidden;
      min-height: 100vh;
    }

    /* Theme Toggle - POSISI DI POJOK BAWAH */
    .theme-toggle {
      position: fixed;
      bottom: 25px;
      right: 25px;
      z-index: 1000;
    }

    .theme-btn {
      width: 55px;
      height: 55px;
      border-radius: 50%;
      background: var(--bg-card);
      backdrop-filter: blur(20px);
      border: 1px solid var(--border);
      color: var(--text-primary);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3em;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
      transition: all 0.3s ease;
    }

    .theme-btn:hover {
      transform: translateY(-3px) scale(1.1);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }

    /* Landing Page Styles */
    .landing-page {
      display: <?php echo $showForm ? 'none' : 'block'; ?>;
    }

    /* Navigation */
    .navbar {
      background: var(--bg-card);
      backdrop-filter: blur(20px);
      padding: 20px 50px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
      border-bottom: 1px solid var(--border);
      transition: all 0.3s ease;
    }

    .navbar.scrolled {
      background: var(--bg-card);
      padding: 15px 50px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 1.5em;
      font-weight: 700;
      background: linear-gradient(135deg, var(--primary), var(--accent));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .nav-links {
      display: flex;
      gap: 30px;
    }

    .nav-links a {
      color: var(--text-secondary);
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
      position: relative;
    }

    .nav-links a:hover {
      color: var(--text-primary);
    }

    .nav-links a::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 0;
      width: 0;
      height: 2px;
      background: linear-gradient(90deg, var(--primary), var(--accent));
      transition: width 0.3s ease;
    }

    .nav-links a:hover::after {
      width: 100%;
    }

    .nav-buttons {
      display: flex;
      gap: 15px;
    }

    /* Hero Section */
    .hero {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 0 20px;
      position: relative;
      overflow: hidden;
    }

    .hero::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: 
        radial-gradient(circle at 20% 50%, rgba(99, 102, 241, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 40% 80%, rgba(244, 114, 182, 0.1) 0%, transparent 50%);
      pointer-events: none;
    }

    .hero-content {
      max-width: 800px;
      position: relative;
      z-index: 2;
    }

    .hero h1 {
      font-size: 3.5em;
      font-weight: 700;
      margin-bottom: 20px;
      background: linear-gradient(135deg, var(--text-primary), var(--primary));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      line-height: 1.2;
      animation: fadeInDown 0.8s ease;
    }

    .hero p {
      font-size: 1.2em;
      color: var(--text-secondary);
      margin-bottom: 40px;
      line-height: 1.6;
      animation: fadeIn 1s ease 0.3s backwards;
    }

    @keyframes fadeInDown {
      from { opacity: 0; transform: translateY(-30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .hero-buttons {
      display: flex;
      gap: 20px;
      justify-content: center;
      animation: fadeInUp 1s ease 0.5s backwards;
    }

    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .btn {
      padding: 15px 40px;
      font-size: 1.1em;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 10px;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--secondary), var(--primary));
      color: white;
      box-shadow: 0 4px 20px rgba(99, 102, 241, 0.4);
    }

    .btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 30px rgba(99, 102, 241, 0.6);
    }

    .btn-outline {
      background: transparent;
      color: var(--text-primary);
      border: 2px solid var(--primary);
    }

    .btn-outline:hover {
      background: rgba(139, 92, 246, 0.1);
      transform: translateY(-3px);
    }

    /* Features Section */
    .features {
      padding: 100px 50px;
      background: var(--bg-secondary);
    }

    .section-title {
      text-align: center;
      margin-bottom: 60px;
    }

    .section-title h2 {
      font-size: 2.5em;
      margin-bottom: 15px;
      background: linear-gradient(135deg, var(--primary), var(--accent));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .section-title p {
      color: var(--text-secondary);
      font-size: 1.1em;
      max-width: 600px;
      margin: 0 auto;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 30px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .feature-card {
      background: var(--bg-card);
      backdrop-filter: blur(20px);
      padding: 40px 30px;
      border-radius: 20px;
      border: 1px solid var(--border);
      text-align: center;
      transition: all 0.4s ease;
      position: relative;
      overflow: hidden;
    }

    .feature-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, transparent, var(--primary), transparent);
      opacity: 0;
      transition: opacity 0.4s ease;
    }

    .feature-card:hover::before {
      opacity: 1;
    }

    .feature-card:hover {
      transform: translateY(-10px);
      border-color: var(--primary);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
    }

    .feature-icon {
      font-size: 3.5em;
      margin-bottom: 25px;
      background: linear-gradient(135deg, var(--primary), var(--accent));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      filter: drop-shadow(0 0 20px rgba(139, 92, 246, 0.3));
    }

    .feature-card h3 {
      font-size: 1.4em;
      margin-bottom: 15px;
      color: var(--text-primary);
    }

    .feature-card p {
      color: var(--text-secondary);
      line-height: 1.6;
    }

    /* About Section */
    .about {
      padding: 100px 50px;
      text-align: center;
      background: var(--bg-primary);
    }

    .about-content {
      max-width: 800px;
      margin: 0 auto;
    }

    .about p {
      font-size: 1.1em;
      color: var(--text-secondary);
      line-height: 1.8;
      margin-bottom: 30px;
    }

    /* Footer */
    footer {
      background: var(--bg-secondary);
      padding: 40px 50px;
      text-align: center;
      border-top: 1px solid var(--border);
    }

    footer p {
      color: var(--text-secondary);
      margin-bottom: 10px;
    }

    .highlight {
      color: var(--primary);
      font-weight: 600;
    }

    /* Login/Register Form Styles */
    .auth-page {
      display: <?php echo $showForm ? 'flex' : 'none'; ?>;
      min-height: 100vh;
      justify-content: center;
      align-items: center;
      padding: 20px;
      position: relative;
    }

    .auth-page::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: 
        radial-gradient(circle at 20% 50%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(139, 92, 246, 0.15) 0%, transparent 50%);
      pointer-events: none;
      z-index: 0;
    }

    .back-home {
      position: absolute;
      top: 30px;
      left: 30px;
      color: var(--text-secondary);
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s;
      z-index: 10;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .back-home:hover {
      color: var(--primary);
    }

    .auth-container {
      background: var(--bg-card);
      backdrop-filter: blur(20px);
      padding: 50px 40px;
      border-radius: 24px;
      width: 100%;
      max-width: 450px;
      text-align: center;
      box-shadow: 0 25px 80px rgba(0, 0, 0, 0.1);
      border: 1px solid var(--border);
      position: relative;
      z-index: 1;
      animation: slideUp 0.5s ease;
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .auth-logo {
      font-size: 4em;
      margin-bottom: 15px;
      background: linear-gradient(135deg, var(--primary), var(--accent));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      filter: drop-shadow(0 0 20px rgba(139, 92, 246, 0.3));
    }

    .auth-container h2 {
      background: linear-gradient(135deg, var(--primary), var(--accent));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      font-size: 2.2em;
      margin-bottom: 10px;
      font-weight: 700;
    }

    .auth-subtitle {
      color: var(--text-secondary);
      margin-bottom: 30px;
      font-size: 1em;
    }

    .message {
      padding: 15px;
      border-radius: 12px;
      margin-bottom: 25px;
      font-weight: 500;
      animation: fadeIn 0.3s ease;
      text-align: left;
      border: 1px solid;
    }

    .message.error {
      background: rgba(239, 68, 68, 0.1);
      border-color: rgba(239, 68, 68, 0.3);
      color: var(--danger);
    }

    .message.success {
      background: rgba(16, 185, 129, 0.1);
      border-color: rgba(16, 185, 129, 0.3);
      color: var(--success);
    }

    .input-group {
      margin-bottom: 20px;
      text-align: left;
    }

    .input-group label {
      display: block;
      margin-bottom: 8px;
      color: var(--text-secondary);
      font-weight: 500;
      font-size: 0.95em;
    }

    .input-wrapper {
      position: relative;
    }

    .input-wrapper i {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--gray);
    }

    .auth-input {
      width: 100%;
      padding: 16px 16px 16px 45px;
      border: 1px solid var(--border);
      border-radius: 12px;
      background: var(--bg-secondary);
      color: var(--text-primary);
      font-size: 1em;
      transition: all 0.3s ease;
      font-family: 'Inter', Arial, sans-serif;
    }

    .auth-input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }

    .auth-input::placeholder {
      color: var(--gray);
    }

    .auth-btn {
      width: 100%;
      padding: 16px;
      background: linear-gradient(135deg, var(--secondary), var(--primary));
      border: none;
      border-radius: 12px;
      color: white;
      cursor: pointer;
      font-weight: 600;
      font-size: 1.05em;
      transition: all 0.3s ease;
      margin-top: 10px;
      box-shadow: 0 4px 20px rgba(99, 102, 241, 0.4);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .auth-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 30px rgba(99, 102, 241, 0.6);
    }

    .toggle-link {
      margin-top: 25px;
      color: var(--text-secondary);
      font-size: 0.95em;
    }

    .toggle-link a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s;
    }

    .toggle-link a:hover {
      color: var(--accent);
      text-decoration: underline;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .navbar {
        padding: 15px 20px;
        flex-direction: column;
        gap: 15px;
      }

      .nav-links {
        gap: 20px;
      }

      .hero h1 {
        font-size: 2.5em;
      }

      .hero p {
        font-size: 1.1em;
      }

      .hero-buttons {
        flex-direction: column;
        align-items: center;
      }

      .btn {
        width: 200px;
        justify-content: center;
      }

      .features, .about {
        padding: 60px 20px;
      }

      .features-grid {
        grid-template-columns: 1fr;
      }

      .auth-container {
        padding: 40px 25px;
      }

      .auth-container h2 {
        font-size: 1.8em;
      }

      .back-home {
        top: 20px;
        left: 20px;
      }

      .theme-toggle {
        bottom: 20px;
        right: 20px;
      }
    }

    @media (max-width: 480px) {
      .hero h1 {
        font-size: 2em;
      }

      .section-title h2 {
        font-size: 2em;
      }

      .feature-card {
        padding: 30px 20px;
      }

      .auth-container {
        padding: 30px 20px;
      }
    }
  </style>
</head>
<body>

<!-- Theme Toggle - POJOK BAWAH -->
<div class="theme-toggle">
  <button class="theme-btn" id="themeToggle">
    <i class="fas fa-moon"></i>
  </button>
</div>

<!-- ===== LANDING PAGE ===== -->
<div class="landing-page">
  <nav class="navbar" id="navbar">
    <div class="logo">
      <i class="fas fa-music"></i>
      <span>Monitoring Studio</span>
    </div>
    
    <div class="nav-links">
      <a href="#home">Beranda</a>
      <a href="#features">Fitur</a>
      <a href="#about">Tentang</a>
    </div>
    
    <div class="nav-buttons">
      <a href="?action=login" class="btn btn-outline">Masuk</a>
      <a href="?action=login&register" class="btn btn-primary">Daftar</a>
    </div>
  </nav>

  <section class="hero" id="home">
    <div class="hero-content">
      <h1>Sistem Monitoring Kebisingan Studio Musik</h1>
      <p>Pantau tingkat kebisingan studio secara real-time menggunakan sensor suara KY-038 untuk menjaga kenyamanan dan kualitas rekaman pelanggan.</p>
      
      <div class="hero-buttons">
        <a href="?action=login" class="btn btn-primary">
          <i class="fas fa-sign-in-alt"></i> Mulai Sekarang
        </a>
        <a href="#features" class="btn btn-outline">
          <i class="fas fa-info-circle"></i> Pelajari Lebih Lanjut
        </a>
      </div>
    </div>
  </section>

  <section class="features" id="features">
    <div class="section-title">
      <h2>Fitur Unggulan</h2>
      <p>Semua yang Anda butuhkan untuk memantau studio musik dengan efisien</p>
    </div>
    
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-chart-line"></i>
        </div>
        <h3>Monitoring Real-time</h3>
        <p>Pantau tingkat kebisingan studio secara langsung dengan update data secara real-time</p>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-chart-bar"></i>
        </div>
        <h3>Visualisasi Data</h3>
        <p>Data ditampilkan dalam bentuk grafik dan chart yang mudah dipahami dan dianalisis</p>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-microphone-alt"></i>
        </div>
        <h3>Multi Studio</h3>
        <p>Kelola dan pantau beberapa studio musik sekaligus dalam satu dashboard terpadu</p>
      </div>
    </div>
  </section>

  <section class="about" id="about">
    <div class="section-title">
      <h2>Tentang Sistem</h2>
    </div>
    <div class="about-content">
      <p>
        Sistem monitoring kebisingan studio musik ini dikembangkan untuk membantu pengelola studio 
        dalam memantau dan mengatur tingkat kebisingan secara efektif.
      </p>
      
      <div style="margin-top: 40px;">
        <a href="?action=login" class="btn btn-primary">
          <i class="fas fa-rocket"></i> Coba Sekarang
        </a>
      </div>
    </div>
  </section>

  <footer>
    <p>© 2025 <span class="highlight">Monitoring Studio Musik</span>. Semua Hak Dilindungi.</p>
  </footer>
</div>

<!-- ===== LOGIN/REGISTER FORM ===== -->
<div class="auth-page">
  <a href="./" class="back-home">
    <i class="fas fa-arrow-left"></i> Kembali ke Beranda
  </a>
  
  <div class="auth-container">
    <div class="auth-logo">
      <i class="fas fa-music"></i>
    </div>
    <h2><?php echo $isRegister ? "Buat Akun Baru" : "Selamat Datang Kembali"; ?></h2>
    <p class="auth-subtitle">
      <?php echo $isRegister ? "Daftar untuk mulai memantau studio Anda" : "Masuk ke dashboard monitoring studio"; ?>
    </p>
    
    <?php if($message): ?>
      <div class="message <?php echo $message_type; ?>">
        <i class="fas fa-<?php echo $message_type == 'error' ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
        <?php echo $message; ?>
      </div>
    <?php endif; ?>
    
    <form method="POST">
      <div class="input-group">
        <label for="username"><i class="fas fa-user"></i> Username</label>
        <div class="input-wrapper">
          <i class="fas fa-user"></i>
          <input type="text" id="username" name="username" class="auth-input" placeholder="Masukkan username" required>
        </div>
      </div>
      
      <div class="input-group">
        <label for="password"><i class="fas fa-lock"></i> Password</label>
        <div class="input-wrapper">
          <i class="fas fa-lock"></i>
          <input type="password" id="password" name="password" class="auth-input" placeholder="Masukkan password" required>
        </div>
      </div>
      
      <button type="submit" class="auth-btn">
        <?php if($isRegister): ?>
          <i class="fas fa-user-plus"></i> Daftar Sekarang
        <?php else: ?>
          <i class="fas fa-sign-in-alt"></i> Masuk ke Dashboard
        <?php endif; ?>
      </button>
    </form>
    
    <div class="toggle-link">
      <?php if($isRegister): ?>
        Sudah punya akun? <a href="?action=login">Masuk di sini</a>
      <?php else: ?>
        Belum punya akun? <a href="?action=login&register">Daftar di sini</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
  // Theme Toggle
  const themeToggle = document.getElementById('themeToggle');
  const themeIcon = themeToggle.querySelector('i');
  
  // Check for saved theme or prefer color scheme
  const savedTheme = localStorage.getItem('theme') || 'dark';
  document.documentElement.setAttribute('data-theme', savedTheme);
  updateThemeIcon(savedTheme);
  
  themeToggle.addEventListener('click', () => {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeIcon(newTheme);
  });
  
  function updateThemeIcon(theme) {
    if (theme === 'dark') {
      themeIcon.className = 'fas fa-sun';
    } else {
      themeIcon.className = 'fas fa-moon';
    }
  }

  // Navbar scroll effect
  window.addEventListener('scroll', function() {
    const navbar = document.getElementById('navbar');
    if (window.scrollY > 50) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  });

  // Smooth scrolling for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });
</script>

</body>
</html>