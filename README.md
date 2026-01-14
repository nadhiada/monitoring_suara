---

# 🎧 Sistem Monitoring Suara Studio Musik Berbasis Web & IoT

**Workshop Aplikasi Berbasis Web**

---

## 📌 Informasi Umum

* **Nama Project** : Sistem Monitoring Suara Studio Musik
* **Jenis Aplikasi** : Aplikasi Web Berbasis IoT (Realtime Monitoring)
* **Mata Kuliah** : Workshop Aplikasi Berbasis Web
* **Teknologi Utama** : PHP, MySQL, JavaScript, ESP8266
* **Tahun Pengembangan** : 2025

---

## 👩‍💻 Identitas Pengembang

* **Nama** : Nadhia Artifasari
* **Program Studi** : (isi sesuai prodi kamu)
* **Institusi** : (isi sesuai kampus kamu)
* **Mata Kuliah** : Workshop Aplikasi Berbasis Web

---

## 📖 Deskripsi Project

Sistem Monitoring Suara Studio Musik merupakan aplikasi **berbasis web** yang terintegrasi dengan **perangkat IoT (ESP8266 + sensor suara)** untuk memantau tingkat kebisingan suara di beberapa studio musik secara **realtime**.

Aplikasi ini dirancang untuk:

* Menerima data suara langsung dari perangkat IoT
* Menyimpan data sensor ke dalam database
* Menampilkan data dalam bentuk dashboard web
* Menentukan status studio secara otomatis (Aktif, Berisik, Offline)
* Menyajikan grafik dan riwayat data suara

Project ini dikembangkan sebagai **implementasi nyata** dari konsep:

* Web Backend
* Web Frontend
* Realtime Data
* Integrasi Hardware & Software

---

## 🎯 Tujuan Pengembangan

1. Menerapkan konsep **aplikasi berbasis web** secara utuh
2. Mengintegrasikan **perangkat IoT dengan backend web**
3. Menampilkan data sensor secara **realtime tanpa refresh**
4. Mengelola data menggunakan **database relasional**
5. Memberikan visualisasi data yang informatif
6. Memenuhi capaian pembelajaran **Workshop Aplikasi Berbasis Web**

---

## 🧠 Konsep Sistem

Sistem ini bekerja dengan prinsip **client–server** yang diperluas dengan **IoT device**.

### Komponen Sistem:

* **IoT Device** → ESP8266 + Sensor Suara
* **Web Server** → Apache + PHP
* **Database Server** → MySQL
* **Client** → Browser (Admin Dashboard)

---

## 🔌 Integrasi IoT

### Perangkat IoT yang Digunakan

| Komponen   | Keterangan             |
| ---------- | ---------------------- |
| ESP8266    | Microcontroller WiFi   |
| KY-038     | Sensor suara           |
| LED Merah  | Indikator suara tinggi |
| LED Kuning | Indikator suara sedang |
| LED Hijau  | Indikator suara rendah |

---

### Cara Kerja IoT

1. Sensor membaca intensitas suara
2. ESP8266 mengolah data suara
3. Menentukan level suara & status
4. Menyalakan LED indikator
5. Mengirim data ke server melalui HTTP GET
6. Server menyimpan data ke database

---

### Kategori Level Suara

| Rentang dB | Status | Lampu  |
| ---------- | ------ | ------ |
| < 50 dB    | RENDAH | Hijau  |
| 50 – 90 dB | SEDANG | Kuning |
| > 90 dB    | TINGGI | Merah  |

---

## 🌐 Fitur Aplikasi Web

### Fitur Utama

* 🔐 Login Admin
* 🎛 Dashboard Monitoring Studio
* 🔊 Monitoring Suara Realtime
* 📊 Grafik Level Suara (Chart.js)
* 🗂 Riwayat Data Sensor
* 📥 Export Data (CSV)
* 🌗 Dark Mode & Light Mode
* 🔄 Auto Refresh Data (Realtime)
* 📴 Deteksi Studio Offline Otomatis

---

### Status Studio

| Status  | Keterangan                  |
| ------- | --------------------------- |
| Aktif   | Update data < 10 detik      |
| Berisik | Level suara > 90 dB         |
| Offline | Tidak ada update > 10 detik |

---

## 🛠️ Teknologi yang Digunakan

### Frontend

* HTML5
* CSS3
* JavaScript
* Fetch API
* Chart.js
* Font Awesome

### Backend

* PHP Native
* MySQL
* Apache (XAMPP)

### IoT

* Arduino IDE
* ESP8266WiFi Library
* HTTPClient Library

---

## 🗄️ Struktur Database

### Tabel `studios`

| Field       | Tipe    | Keterangan  |
| ----------- | ------- | ----------- |
| studio_id   | INT     | Primary Key |
| studio_name | VARCHAR | Nama studio |

---

### Tabel `sensor_log`

| Field        | Tipe     | Keterangan       |
| ------------ | -------- | ---------------- |
| id           | INT      | Primary Key      |
| studio_id    | INT      | ID studio        |
| lamp_id      | INT      | Indikator lampu  |
| sound_level  | INT      | Level suara (dB) |
| sound_status | VARCHAR  | Status suara     |
| created_at   | DATETIME | Waktu data masuk |

---

## 🔄 Alur Sistem (Flow Sistem)

1. ESP8266 membaca sensor suara
2. Data dikirim ke `insert.php`
3. Backend memvalidasi data
4. Data disimpan ke MySQL
5. Dashboard mengambil data via AJAX
6. Tampilan diperbarui otomatis
7. Sistem menghitung status aktif/offline

---

## 📁 Struktur Folder Project

```
monitoring-suara/
│
├── koneksi.php
├── insert.php
├── get_studio_status.php
├── get_log.php
├── get_chart.php
├── export_csv.php
│
├── login.php
├── logout.php
├── dashboard.php
│
├── studio1_detail.php
├── studio2_detail.php
├── studio3_detail.php
│
├── iot/
│   └── esp8266_monitoring_suara.ino
│
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
└── README.md
```

---

## ⚙️ Konfigurasi Penting

### Timezone PHP

Agar deteksi **offline / aktif** akurat:

```php
date_default_timezone_set('Asia/Jakarta');
```

---

## 🚀 Cara Menjalankan Aplikasi

### 1. Setup Web

1. Install XAMPP
2. Aktifkan Apache & MySQL
3. Letakkan project di folder `htdocs`
4. Import database MySQL

### 2. Setup IoT

1. Install Arduino IDE
2. Tambahkan board ESP8266
3. Upload program ke ESP8266
4. Sesuaikan SSID, Password, dan IP server

### 3. Akses Aplikasi

```
http://localhost/monitoring-suara/dashboard.php
```

---

## 🧪 Pengujian Sistem

* Pengujian koneksi IoT ke server
* Pengujian realtime update dashboard
* Pengujian status aktif & offline
* Pengujian grafik realtime
* Pengujian multi studio

---

## 📌 Kelebihan Project

* Menggunakan **IoT real (bukan dummy)**
* Realtime tanpa reload halaman
* Tampilan modern & responsif
* Terintegrasi penuh backend & frontend
* Cocok untuk studi kasus Web + IoT

---

## 🧾 Kesimpulan

Sistem Monitoring Suara Studio Musik berhasil mengimplementasikan:

* Konsep **aplikasi web dinamis**
* Integrasi **IoT dengan web backend**
* Monitoring data secara realtime
* Visualisasi data yang informatif

Project ini memenuhi seluruh aspek pembelajaran pada mata kuliah **Workshop Aplikasi Berbasis Web**.

