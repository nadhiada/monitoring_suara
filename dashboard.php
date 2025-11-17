<?php  
date_default_timezone_set('Asia/Jakarta');
session_start();
include "koneksi.php";

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// Ambil data studio
$studios = mysqli_query($conn, "
    SELECT s.studio_id, s.studio_name,
        (SELECT sound_level FROM sensor_log 
         WHERE studio_id = s.studio_id 
         ORDER BY created_at DESC LIMIT 1) AS sound_level,
        (SELECT sound_status FROM sensor_log 
         WHERE studio_id = s.studio_id 
         ORDER BY created_at DESC LIMIT 1) AS sound_status,
        (SELECT created_at FROM sensor_log 
         WHERE studio_id = s.studio_id 
         ORDER BY created_at DESC LIMIT 1) AS last_update
    FROM studios s
    ORDER BY s.studio_id ASC
");

if (!$studios) {
    die("Error query studios: " . mysqli_error($conn));
}

$studio_data = [];
$total_studios = 0;
$active_studios = 0;
$noisy_studios = 0;

while ($s = mysqli_fetch_assoc($studios)) {

    $total_studios++;

    // Cek apakah aktif (update < 10 detik)
    $is_active = false;
    if ($s['last_update']) {
        if (time() - strtotime($s['last_update']) <= 10) {
            $is_active = true;
            $active_studios++;
        }
    }

    // Hitung berisik
    $sound_level = $s['sound_level'] ?? 0;
    if ($sound_level > 90) {
        $noisy_studios++;
    }

    $s['is_active'] = $is_active;

    $studio_data[] = $s;
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Monitoring Studio</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* CSS tetap sama seperti sebelumnya */
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
      --shadow: rgba(0, 0, 0, 0.3);
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
      --shadow: rgba(0, 0, 0, 0.1);
    }

    * { 
      margin: 0; 
      padding: 0; 
      box-sizing: border-box; 
      transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
    }

    body {
      font-family: 'Inter', Arial, sans-serif;
      background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
      color: var(--text-primary);
      min-height: 100vh;
      overflow-x: hidden;
    }

    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: 
        radial-gradient(circle at 20% 50%, rgba(99, 102, 241, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
      pointer-events: none;
      z-index: 0;
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
      box-shadow: 0 6px 20px var(--shadow);
      transition: all 0.3s ease;
    }

    .theme-btn:hover {
      transform: translateY(-3px) scale(1.1);
      box-shadow: 0 8px 25px var(--shadow);
    }

    .header {
      background: linear-gradient(135deg, rgba(99,102,241,0.15) 0%, rgba(139,92,246,0.15) 100%);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--border);
      padding: 20px 50px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 4px 20px var(--shadow);
      position: relative;
      z-index: 10;
    }

    .header-content {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .header h1 {
      font-size: 1.8em;
      font-weight: 700;
      background: linear-gradient(135deg, var(--primary), var(--accent));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 10px;
      color: var(--text-secondary);
      font-weight: 500;
    }

    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--accent));
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 600;
    }

    .logout-btn {
      padding: 10px 25px;
      border-radius: 10px;
      font-weight: 600;
      color: white;
      text-decoration: none;
      background: linear-gradient(135deg, var(--danger), #dc2626);
      box-shadow: 0 4px 15px rgba(239,68,68,0.3);
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .logout-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 25px rgba(239,68,68,0.5);
    }

    .container {
      width: 90%;
      max-width: 1400px;
      margin: 30px auto;
      position: relative;
      z-index: 1;
    }

    /* Statistics Cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: var(--bg-card);
      backdrop-filter: blur(20px);
      border: 1px solid var(--border);
      padding: 25px;
      border-radius: 16px;
      box-shadow: 0 8px 30px var(--shadow);
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      border-color: var(--primary);
      box-shadow: 0 12px 40px var(--shadow);
    }

    .stat-icon {
      width: 60px;
      height: 60px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5em;
      color: white;
    }

    .stat-icon.total { background: linear-gradient(135deg, var(--secondary), var(--primary)); }
    .stat-icon.active { background: linear-gradient(135deg, var(--success), #059669); }
    .stat-icon.noisy { background: linear-gradient(135deg, var(--warning), #d97706); }
    .stat-icon.offline { background: linear-gradient(135deg, var(--gray), #475569); }

    .stat-info h3 {
      font-size: 0.9em;
      color: var(--text-secondary);
      margin-bottom: 5px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .stat-info .value {
      font-size: 2em;
      font-weight: 700;
      color: var(--text-primary);
    }

    /* Main Content */
    .main-content {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 30px;
    }

    @media (max-width: 1024px) {
      .main-content {
        grid-template-columns: 1fr;
      }
    }

    /* Studios Section */
    .studios-section {
      background: var(--bg-card);
      backdrop-filter: blur(20px);
      border: 1px solid var(--border);
      padding: 30px;
      border-radius: 20px;
      box-shadow: 0 10px 40px var(--shadow);
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }

    .section-header h2 {
      font-size: 1.5em;
      font-weight: 600;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .studios-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 25px;
    }

    .studio-card {
      background: var(--bg-secondary);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 25px;
      transition: all 0.4s ease;
      position: relative;
      overflow: hidden;
    }

    .studio-card::before {
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

    .studio-card:hover::before {
      opacity: 1;
    }

    .studio-card:hover {
      transform: translateY(-8px);
      border-color: var(--primary);
      box-shadow: 0 15px 50px var(--shadow);
    }

    .studio-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 20px;
    }

    .studio-icon {
      font-size: 3em;
      margin-bottom: 15px;
      filter: drop-shadow(0 0 10px rgba(139, 92, 246, 0.3));
    }

    .studio-badge {
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.8em;
      font-weight: 600;
      text-transform: uppercase;
      color: white;
    }

    .badge-active { background: var(--success); }
    .badge-offline { background: var(--gray); }
    .badge-noisy { background: var(--danger); }

    .studio-info h3 {
      font-size: 1.4em;
      font-weight: 600;
      margin-bottom: 10px;
      color: var(--text-primary);
    }

    .studio-stats {
      display: flex;
      gap: 20px;
      margin: 15px 0;
    }

    .stat {
      text-align: center;
    }

    .stat .label {
      font-size: 0.8em;
      color: var(--text-secondary);
      margin-bottom: 5px;
    }

    .stat .value {
      font-size: 1.2em;
      font-weight: 600;
      color: var(--text-primary);
    }

    .status-indicator {
      display: flex;
      align-items: center;
      gap: 8px;
      margin: 15px 0;
      padding: 10px 15px;
      border-radius: 10px;
      font-weight: 500;
      background: var(--bg-secondary);
      border: 1px solid var(--border);
    }

    .pulse {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      animation: pulse 2s infinite;
    }

    .pulse-on { background: var(--success); }
    .pulse-off { background: var(--gray); }
    .pulse-noisy { background: var(--danger); }

    @keyframes pulse {
      0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
      70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
      100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    .studio-actions {
      display: flex;
      gap: 10px;
      margin-top: 20px;
    }

    .btn {
      padding: 10px 20px;
      border-radius: 10px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
      flex: 1;
      justify-content: center;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--secondary), var(--primary));
      color: white;
      box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 25px rgba(99, 102, 241, 0.6);
    }

    .btn-outline {
      background: transparent;
      color: var(--text-secondary);
      border: 1px solid var(--border);
    }

    .btn-outline:hover {
      background: rgba(139, 92, 246, 0.1);
      border-color: var(--primary);
      color: var(--text-primary);
    }

    /* Activity Sidebar */
    .activity-sidebar {
      background: var(--bg-card);
      backdrop-filter: blur(20px);
      border: 1px solid var(--border);
      padding: 30px;
      border-radius: 20px;
      box-shadow: 0 10px 40px var(--shadow);
    }

    .activity-list {
      margin-top: 20px;
    }

    .activity-item {
      display: flex;
      gap: 15px;
      padding: 15px 0;
      border-bottom: 1px solid var(--border);
    }

    .activity-item:last-child {
      border-bottom: none;
    }

    .activity-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(139, 92, 246, 0.1);
      color: var(--primary);
    }

    .activity-content h4 {
      font-size: 0.9em;
      color: var(--text-primary);
      margin-bottom: 5px;
    }

    .activity-content p {
      font-size: 0.8em;
      color: var(--text-secondary);
    }

    .activity-time {
      font-size: 0.7em;
      color: var(--gray);
      margin-top: 5px;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .header {
        padding: 15px 20px;
        flex-direction: column;
        gap: 15px;
      }
      
      .header-content {
        flex-direction: column;
        text-align: center;
      }
      
      .header h1 {
        font-size: 1.5em;
      }
      
      .container {
        width: 95%;
        margin: 20px auto;
      }
      
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .studios-grid {
        grid-template-columns: 1fr;
      }
      
      .studio-actions {
        flex-direction: column;
      }
      
      .theme-toggle {
        bottom: 20px;
        right: 20px;
      }
    }

    @media (max-width: 480px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }
      
      .studio-header {
        flex-direction: column;
        gap: 10px;
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

  <div class="header">
    <div class="header-content">
      <h1><i class="fas fa-music"></i> Dashboard Monitoring Studio</h1>
      <div class="user-info">
        <div class="user-avatar">
          <i class="fas fa-user"></i>
        </div>
        <span>Selamat datang, <?php echo $_SESSION['username'] ?? 'Admin'; ?>!</span>
      </div>
    </div>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <div class="container">
    <!-- Statistics Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon total">
          <i class="fas fa-microphone-alt"></i>
        </div>
        <div class="stat-info">
          <h3>Total Studio</h3>
          <div class="value"><?php echo $total_studios; ?></div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon active">
          <i class="fas fa-play-circle"></i>
        </div>
        <div class="stat-info">
          <h3>Aktif</h3>
          <div class="value"><?php echo $active_studios; ?></div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon noisy">
          <i class="fas fa-volume-up"></i>
        </div>
        <div class="stat-info">
          <h3>Berisik</h3>
          <div class="value"><?php echo $noisy_studios; ?></div>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon offline">
          <i class="fas fa-pause-circle"></i>
        </div>
        <div class="stat-info">
          <h3>Offline</h3>
          <div class="value"><?php echo $total_studios - $active_studios; ?></div>
        </div>
      </div>
    </div>

    <div class="main-content">
      <!-- Studios Section -->
      <div class="studios-section">
        <div class="section-header">
          <h2><i class="fas fa-sliders-h"></i> Kelola Studio</h2>
        </div>
        
        <div class="studios-grid">
          <?php 
          $i = 1;
          foreach ($studio_data as $studio):
            $studio_id   = $studio['studio_id'];
            $sound_level = $studio['sound_level'] ?? 0;
            $last_update = $studio['last_update'] ?? 'Belum ada data';
            
            // Tentukan status berdasarkan rentang baru
            $sound_status = 'RENDAH';
            if ($sound_level >= 50 && $sound_level <= 90) {
                $sound_status = 'SEDANG';
            } elseif ($sound_level > 90) {
                $sound_status = 'TINGGI';
            }
            
            // Tentukan status badge
            if (!$studio['is_active']) {
                $badge_class = "badge-offline";
                $status_text = "Offline";
                $pulse_class = "pulse-off";
            }
            else if ($sound_level > 90) {
                $badge_class = "badge-noisy";
                $status_text = "Berisik";
                $pulse_class = "pulse-noisy";
            }
            else {
                $badge_class = "badge-active";
                $status_text = "Aktif";
                $pulse_class = "pulse-on";
            }


            // Tentukan file detail berdasarkan studio_id
            $detail_file = "studio{$studio['studio_id']}_detail.php";
          ?>
            <div class="studio-card" id="studio-card-<?php echo $studio_id; ?>">
              <div class="studio-header">
                <div>
                  <div class="studio-icon">🎸</div>
                  <h3>Studio <?php echo $i; ?></h3>
                </div>
                <span class="studio-badge <?php echo $badge_class; ?>" id="badge-<?php echo $studio_id; ?>">
                  <?php echo $status_text; ?>
                </span>
              </div>
              
              <div class="studio-stats">
                <div class="stat">
                  <div class="label">Level Suara</div>
                  <div class="value" id="level-<?php echo $studio_id; ?>"><?php echo $sound_level; ?> dB</div>
                </div>
                <div class="stat">
                  <div class="label">Status</div>
                  <div class="value" id="status-<?php echo $studio_id; ?>"><?php echo $sound_status; ?></div>
                </div>
              </div>
              
              <div class="status-indicator">
                <span class="pulse <?php echo $pulse_class; ?>" id="pulse-<?php echo $studio_id; ?>"></span>
                <span><?php echo $status_text; ?></span>
              </div>
              
              <div class="studio-actions">
                <a href="<?php echo $detail_file; ?>" class="btn btn-primary">
                  <i class="fas fa-chart-line"></i> Detail
                </a>
                <button class="btn btn-outline" onclick="showStudioInfo(<?php echo $studio_id; ?>)">
                  <i class="fas fa-info-circle"></i> Info
                </button>
              </div>
            </div>
          <?php 
            $i++;
          endforeach; 
          ?>
        </div>
      </div>

      <!-- Activity Sidebar -->
      <div class="activity-sidebar">
        <div class="section-header">
          <h2><i class="fas fa-history"></i> Aktivitas Terbaru</h2>
        </div>
        
        <div class="activity-list">
          <?php
          // Ambil aktivitas terbaru
          $recent_activity = mysqli_query($conn, "
              SELECT sl.*, s.studio_name
              FROM sensor_log sl
              JOIN studios s ON sl.studio_id = s.studio_id
              ORDER BY sl.created_at DESC
              LIMIT 5
          ");

          
          while ($activity = mysqli_fetch_assoc($recent_activity)):
            // Tentukan status berdasarkan rentang baru
            $sound_level = $activity['sound_level'];
            $activity_status = 'RENDAH';
            if ($sound_level >= 50 && $sound_level <= 90) {
                $activity_status = 'SEDANG';
            } elseif ($sound_level > 90) {
                $activity_status = 'TINGGI';
            }
            
            $activity_icon = 'fas fa-volume-down';
            $activity_color = 'var(--success)';
            
            if ($activity_status == 'TINGGI') {
              $activity_icon = 'fas fa-exclamation-triangle';
              $activity_color = 'var(--danger)';
            } elseif ($activity_status == 'SEDANG') {
              $activity_icon = 'fas fa-volume-up';
              $activity_color = 'var(--warning)';
            }
          ?>
            <div class="activity-item">
              <div class="activity-icon" style="color: <?php echo $activity_color; ?>">
                <i class="<?php echo $activity_icon; ?>"></i>
              </div>
              <div class="activity-content">
                <h4><?php echo $activity['studio_name']; ?></h4>
                <p>Level suara: <?php echo $activity['sound_level']; ?> dB (<?php echo $activity_status; ?>)</p>
                <div class="activity-time">
                  <?php echo date('H:i', strtotime($activity['created_at'])); ?>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
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

    // Studio Info Function
    function showStudioInfo(studioId) {
      alert('Informasi Studio ' + studioId + '\n\nFitur ini akan menampilkan detail informasi studio.');
    }


    // Animasi untuk cards
    document.addEventListener('DOMContentLoaded', function() {
      const cards = document.querySelectorAll('.studio-card');
      cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
      });
    });

    setInterval(fetchRealtimeUpdate, 2000);

  function fetchRealtimeUpdate() {
      fetch("get_studio_status.php")
          .then(r => r.json())
          .then(data => updateDashboard(data))
          .catch(err => console.error("Realtime error:", err));
  }

  function updateDashboard(data) {
      data.forEach(s => {

          let level = parseInt(s.sound_level);
          let isActive = !s.offline;

          // Tentukan status level
          let statusLevel = "RENDAH";
          if (level >= 50 && level <= 90) statusLevel = "SEDANG";
          if (level > 90) statusLevel = "TINGGI";

          // Update Level Suara
          document.getElementById("level-" + s.studio_id).innerText = level + " dB";

          // Update Status Text
          document.getElementById("status-" + s.studio_id).innerText = statusLevel;

          // Update Badge
          let badge = document.getElementById("badge-" + s.studio_id);

          if (!isActive) {
              badge.className = "studio-badge badge-offline";
              badge.innerText = "Offline";
          } 
          else if (level > 90) {
              badge.className = "studio-badge badge-noisy";
              badge.innerText = "Berisik";
          } 
          else {
              badge.className = "studio-badge badge-active";
              badge.innerText = "Aktif";
          }

          // Update Pulse
          let pulse = document.getElementById("pulse-" + s.studio_id);

          if (!isActive) {
              pulse.className = "pulse pulse-off";
          }
          else if (level > 90) {
              pulse.className = "pulse pulse-noisy";
          }
          else {
              pulse.className = "pulse pulse-on";
          }

      });
  }


  </script>
</body>
</html>