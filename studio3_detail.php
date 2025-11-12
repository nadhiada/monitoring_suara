<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// Tentukan studio_id berdasarkan file yang diakses
$current_file = basename($_SERVER['PHP_SELF']);
$studio_id = (int) filter_var($current_file, FILTER_SANITIZE_NUMBER_INT);

// Ambil data studio
$studio_query = mysqli_query($conn, "
    SELECT l.*, 
           (SELECT sound_level FROM sensor_log WHERE lamp_id = l.lamp_id ORDER BY created_at DESC LIMIT 1) as sound_level,
           (SELECT created_at FROM sensor_log WHERE lamp_id = l.lamp_id ORDER BY created_at DESC LIMIT 1) as last_update
    FROM lampu l 
    WHERE l.lamp_id = $studio_id
");

$studio = mysqli_fetch_assoc($studio_query);

// Tentukan status berdasarkan rentang baru
$sound_level = $studio['sound_level'] ?? 0;
$sound_status = 'RENDAH';
if ($sound_level >= 50 && $sound_level <= 90) {
    $sound_status = 'SEDANG';
} elseif ($sound_level > 90) {
    $sound_status = 'TINGGI';
}

// Ambil data untuk chart (24 jam terakhir)
$chart_data = mysqli_query($conn, "
    SELECT sound_level, created_at 
    FROM sensor_log 
    WHERE lamp_id = $studio_id 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY created_at ASC
");

$chart_labels = [];
$chart_values = [];

while ($row = mysqli_fetch_assoc($chart_data)) {
    $chart_labels[] = date('H:i', strtotime($row['created_at']));
    $chart_values[] = $row['sound_level'];
}

// Ambil statistik hari ini
$today_stats = mysqli_query($conn, "
    SELECT 
        AVG(sound_level) as avg_level,
        MAX(sound_level) as max_level,
        MIN(sound_level) as min_level,
        COUNT(*) as total_records
    FROM sensor_log 
    WHERE lamp_id = $studio_id 
    AND DATE(created_at) = CURDATE()
");

$stats = mysqli_fetch_assoc($today_stats);

// Ambil statistik minggu ini
$week_stats = mysqli_query($conn, "
    SELECT 
        AVG(sound_level) as avg_week,
        MAX(sound_level) as max_week,
        COUNT(*) as total_week
    FROM sensor_log 
    WHERE lamp_id = $studio_id 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");

$week_data = mysqli_fetch_assoc($week_stats);

// Ambil riwayat terbaru
$history = mysqli_query($conn, "
    SELECT * FROM sensor_log 
    WHERE lamp_id = $studio_id 
    ORDER BY created_at DESC 
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detail Studio <?php echo $studio_id; ?> - Monitoring Studio</title>
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

    .btn {
      padding: 10px 20px;
      border-radius: 10px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
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

    .btn-success {
      background: linear-gradient(135deg, var(--success), #059669);
      color: white;
      box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
    }

    .btn-success:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 25px rgba(16, 185, 129, 0.6);
    }

    .btn-warning {
      background: linear-gradient(135deg, var(--warning), #d97706);
      color: white;
      box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
    }

    .btn-warning:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 25px rgba(245, 158, 11, 0.6);
    }

    .container {
      width: 90%;
      max-width: 1200px;
      margin: 30px auto;
      position: relative;
      z-index: 1;
    }

    .content-grid {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 30px;
    }

    @media (max-width: 1024px) {
      .content-grid {
        grid-template-columns: 1fr;
      }
    }

    .card {
      background: var(--bg-card);
      backdrop-filter: blur(20px);
      border: 1px solid var(--border);
      padding: 30px;
      border-radius: 20px;
      box-shadow: 0 10px 40px var(--shadow);
      margin-bottom: 30px;
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .card-header h2 {
      font-size: 1.5em;
      font-weight: 600;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .studio-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .studio-info h2 {
      font-size: 2em;
      margin-bottom: 10px;
      color: var(--text-primary);
    }

    .studio-info p {
      color: var(--text-secondary);
      margin-bottom: 5px;
    }

    .studio-stats {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      margin: 25px 0;
    }

    .stat-item {
      text-align: center;
      padding: 20px;
      background: var(--bg-secondary);
      border-radius: 12px;
      border: 1px solid var(--border);
    }

    .stat-value {
      font-size: 2em;
      font-weight: 700;
      color: var(--primary);
      margin-bottom: 5px;
    }

    .stat-label {
      font-size: 0.9em;
      color: var(--text-secondary);
    }

    .chart-container {
      height: 400px;
    }

    .history-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    .history-table th,
    .history-table td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid var(--border);
    }

    .history-table th {
      background: rgba(139, 92, 246, 0.2);
      color: var(--text-primary);
      font-weight: 600;
    }

    .history-table td {
      color: var(--text-secondary);
    }

    .status-badge {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.8em;
      font-weight: 600;
      color: white;
    }

    .status-tinggi { background: var(--danger); }
    .status-sedang { background: var(--warning); }
    .status-rendah { background: var(--success); }

    .quick-actions {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .quick-actions .btn {
      width: 100%;
      justify-content: center;
    }

    .threshold-settings {
      margin-top: 20px;
    }

    .threshold-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 0;
      border-bottom: 1px solid var(--border);
    }

    .threshold-item:last-child {
      border-bottom: none;
    }

    .threshold-info h4 {
      color: var(--text-primary);
      margin-bottom: 5px;
    }

    .threshold-info p {
      color: var(--text-secondary);
      font-size: 0.9em;
    }

    .threshold-value {
      font-weight: 600;
      color: var(--primary);
    }

    @media (max-width: 768px) {
      .header {
        padding: 15px 20px;
        flex-direction: column;
        gap: 15px;
        text-align: center;
      }
      
      .theme-toggle {
        bottom: 20px;
        right: 20px;
      }
      
      .studio-stats {
        grid-template-columns: 1fr;
      }
      
      .studio-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
      }
      
      .chart-container {
        height: 300px;
      }
      
      .threshold-item {
        flex-direction: column;
        align-items: flex-start;
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
    <h1><i class="fas fa-music"></i> Detail Studio <?php echo $studio_id; ?></h1>
    <div>
      <a href="dashboard.php" class="btn btn-outline">
        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
      </a>
    </div>
  </div>

  <div class="container">
    <div class="content-grid">
      <div>
        <!-- Info Studio -->
        <div class="card">
          <div class="studio-header">
            <div class="studio-info">
              <h2>Studio <?php echo $studio_id; ?></h2>
              <p>Status: <strong><?php echo $studio['status']; ?></strong></p>
              <p>Level Suara Terakhir: <strong><?php echo $sound_level; ?> dB</strong></p>
              <p>Status Suara: <strong><?php echo $sound_status; ?></strong></p>
              <p>Update Terakhir: <?php echo $studio['last_update'] ?? 'Belum ada data'; ?></p>
            </div>
            <div style="font-size: 4em;">🎸</div>
          </div>

          <div class="studio-stats">
            <div class="stat-item">
              <div class="stat-value"><?php echo number_format($stats['avg_level'] ?? 0, 1); ?></div>
              <div class="stat-label">Rata-rata (dB)</div>
            </div>
            <div class="stat-item">
              <div class="stat-value"><?php echo $stats['max_level'] ?? 0; ?></div>
              <div class="stat-label">Maksimum (dB)</div>
            </div>
            <div class="stat-item">
              <div class="stat-value"><?php echo $stats['total_records'] ?? 0; ?></div>
              <div class="stat-label">Data Hari Ini</div>
            </div>
          </div>
        </div>

        <!-- Grafik -->
        <div class="card">
          <div class="card-header">
            <h2><i class="fas fa-chart-line"></i> Grafik Level Suara</h2>
            <span style="color: var(--text-secondary); font-size: 0.9em;">24 Jam Terakhir</span>
          </div>
          <div class="chart-container">
            <canvas id="soundChart"></canvas>
          </div>
        </div>
      </div>

      <div>
        <!-- Statistik Mingguan -->
        <div class="card">
          <div class="card-header">
            <h2><i class="fas fa-chart-bar"></i> Statistik Mingguan</h2>
          </div>
          <div class="studio-stats">
            <div class="stat-item">
              <div class="stat-value"><?php echo number_format($week_data['avg_week'] ?? 0, 1); ?></div>
              <div class="stat-label">Rata-rata (dB)</div>
            </div>
            <div class="stat-item">
              <div class="stat-value"><?php echo $week_data['max_week'] ?? 0; ?></div>
              <div class="stat-label">Maksimum (dB)</div>
            </div>
            <div class="stat-item">
              <div class="stat-value"><?php echo $week_data['total_week'] ?? 0; ?></div>
              <div class="stat-label">Total Data</div>
            </div>
          </div>
        </div>

        <!-- Pengaturan Threshold -->
        <div class="card">
          <div class="card-header">
            <h2><i class="fas fa-sliders-h"></i> Pengaturan Threshold</h2>
          </div>
          <div class="threshold-settings">
            <div class="threshold-item">
              <div class="threshold-info">
                <h4>Level Rendah</h4>
                <p>Suara normal (< 50 dB)</p>
              </div>
              <div class="threshold-value">< 50 dB</div>
            </div>
            <div class="threshold-item">
              <div class="threshold-info">
                <h4>Level Sedang</h4>
                <p>Perhatian (50-90 dB)</p>
              </div>
              <div class="threshold-value">50-90 dB</div>
            </div>
            <div class="threshold-item">
              <div class="threshold-info">
                <h4>Level Tinggi</h4>
                <p>Berisik (>90 dB)</p>
              </div>
              <div class="threshold-value">> 90 dB</div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
          <div class="card-header">
            <h2><i class="fas fa-bolt"></i> Aksi Cepat</h2>
          </div>
          <div class="quick-actions">
            <button class="btn btn-success" onclick="resetData()">
              <i class="fas fa-sync"></i> Reset Data Harian
            </button>
            <button class="btn btn-warning" onclick="exportData()">
              <i class="fas fa-download"></i> Export Data
            </button>
            <button class="btn btn-outline" onclick="showNotifications()">
              <i class="fas fa-bell"></i> Atur Notifikasi
            </button>
          </div>
        </div>

        <!-- Riwayat Terbaru -->
        <div class="card">
          <div class="card-header">
            <h2><i class="fas fa-history"></i> Riwayat Terbaru</h2>
          </div>
          <table class="history-table">
            <thead>
              <tr>
                <th>Waktu</th>
                <th>Level</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($record = mysqli_fetch_assoc($history)): 
                // Tentukan status berdasarkan rentang baru
                $record_level = $record['sound_level'];
                $record_status = 'RENDAH';
                if ($record_level >= 50 && $record_level <= 90) {
                    $record_status = 'SEDANG';
                } elseif ($record_level > 90) {
                    $record_status = 'TINGGI';
                }
                
                $status_class = 'status-rendah';
                if ($record_status == 'TINGGI') $status_class = 'status-tinggi';
                elseif ($record_status == 'SEDANG') $status_class = 'status-sedang';
              ?>
                <tr>
                  <td><?php echo date('H:i', strtotime($record['created_at'])); ?></td>
                  <td><?php echo $record['sound_level']; ?> dB</td>
                  <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $record_status; ?></span></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
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
      
      // Update chart colors based on theme
      updateChartColors(newTheme);
    });
    
    function updateThemeIcon(theme) {
      if (theme === 'dark') {
        themeIcon.className = 'fas fa-sun';
      } else {
        themeIcon.className = 'fas fa-moon';
      }
    }

    // Chart initialization
    const ctx = document.getElementById('soundChart').getContext('2d');
    let soundChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
          label: 'Level Suara (dB)',
          data: <?php echo json_encode($chart_values); ?>,
          borderColor: '#8b5cf6',
          backgroundColor: 'rgba(139, 92, 246, 0.1)',
          borderWidth: 3,
          fill: true,
          tension: 0.4,
          pointBackgroundColor: '#8b5cf6',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            labels: {
              color: getComputedStyle(document.documentElement).getPropertyValue('--text-primary'),
              font: {
                size: 14
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: getComputedStyle(document.documentElement).getPropertyValue('--border')
            },
            ticks: {
              color: getComputedStyle(document.documentElement).getPropertyValue('--text-secondary')
            }
          },
          x: {
            grid: {
              color: getComputedStyle(document.documentElement).getPropertyValue('--border')
            },
            ticks: {
              color: getComputedStyle(document.documentElement).getPropertyValue('--text-secondary'),
              maxTicksLimit: 10
            }
          }
        }
      }
    });

    function updateChartColors(theme) {
      const textColor = theme === 'dark' ? '#f8fafc' : '#0f172a';
      const gridColor = theme === 'dark' ? 'rgba(139, 92, 246, 0.2)' : 'rgba(99, 102, 241, 0.2)';
      
      soundChart.options.plugins.legend.labels.color = textColor;
      soundChart.options.scales.y.ticks.color = textColor;
      soundChart.options.scales.x.ticks.color = textColor;
      soundChart.options.scales.y.grid.color = gridColor;
      soundChart.options.scales.x.grid.color = gridColor;
      
      soundChart.update();
    }

    // Quick Actions Functions
    function resetData() {
      if (confirm('Apakah Anda yakin ingin mereset data harian?')) {
        alert('Data harian berhasil direset!');
        // Di sini bisa ditambahkan AJAX untuk reset data
      }
    }

    function exportData() {
      alert('Fitur export data akan segera tersedia!');
      // Di sini bisa ditambahkan fungsi export ke CSV/Excel
    }

    function showNotifications() {
      alert('Pengaturan notifikasi akan segera tersedia!');
      // Di sini bisa ditambahkan modal untuk pengaturan notifikasi
    }

    // Auto refresh setiap 1 menit
    setTimeout(() => {
      location.reload();
    }, 60000);
  </script>
</body>
</html>