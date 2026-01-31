<?php
session_start();

include '../koneksi/koneksi.php';
include '../inc/functions.php';

check_login('admin');

include '../inc/dataadmin.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode_soal = mysqli_real_escape_string($koneksi, $_POST['kode_soal']);
    $nama_soal = mysqli_real_escape_string($koneksi, $_POST['nama_soal']);
    $mapel = mysqli_real_escape_string($koneksi, $_POST['mapel']);
    $kelas = mysqli_real_escape_string($koneksi, $_POST['kelas']);
    $tampilan_soal = mysqli_real_escape_string($koneksi, $_POST['tampilan_soal']);
    $waktu_ujian = mysqli_real_escape_string($koneksi, $_POST['waktu_ujian']);
    $tanggal = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $waktu = mysqli_real_escape_string($koneksi, $_POST['waktu']);
    $tanggal_ujian_susulan = mysqli_real_escape_string($koneksi, $_POST['tanggal_ujian_susulan']);
    $waktu_ujian_susulan = mysqli_real_escape_string($koneksi, $_POST['waktu_ujian_susulan']);

    // Cek duplikasi kode_soal
    $cek_kode = mysqli_query($koneksi, "SELECT * FROM soal WHERE kode_soal = '$kode_soal'");
    if (mysqli_num_rows($cek_kode) > 0) {
        $_SESSION['error'] = 'Kode Soal Sudah Ada! Harap pilih kode soal yang lain.';
        header('Location: soal.php');
        exit;
    }

    // Validasi tanggal harus hari ini atau setelahnya
    $today = date('Y-m-d');
    if ($tanggal < $today) {
        $_SESSION['error'] = 'Tanggal ujian harus hari ini atau setelah hari ini.';
        header('Location: tambah_soal.php');
        exit;
    }

    // Validasi tanggal dan waktu ujian susulan - harus diisi kedua-duanya atau tidak sama sekali
    if ((!empty($tanggal_ujian_susulan) && empty($waktu_ujian_susulan)) ||
        (empty($tanggal_ujian_susulan) && !empty($waktu_ujian_susulan))
    ) {
        $_SESSION['error'] = 'Tanggal dan Waktu Ujian Susulan harus diisi kedua-duanya atau tidak sama sekali.';
        header('Location: tambah_soal.php');
        exit;
    }

    // Jika tanggal ujian susulan diisi, validasi harus hari ini atau setelahnya
    if (!empty($tanggal_ujian_susulan) && $tanggal_ujian_susulan < $today) {
        $_SESSION['error'] = 'Tanggal ujian susulan harus hari ini atau setelah hari ini.';
        header('Location: tambah_soal.php');
        exit;
    }

    // Generate default values for missing fields
    $status = 'Nonaktif';
    $kunci = '';
    $token = '';

    // Jika tanggal ujian susulan kosong, set ke NULL
    $tanggal_ujian_susulan_value = !empty($tanggal_ujian_susulan) ? "'$tanggal_ujian_susulan'" : "NULL";
    $waktu_ujian_susulan_value = !empty($waktu_ujian_susulan) ? "'$waktu_ujian_susulan'" : "NULL";

    $query = "INSERT INTO soal (kode_soal, nama_soal, mapel, kelas, waktu_ujian, tampilan_soal, tanggal, waktu, 
                                tanggal_ujian_susulan, waktu_ujian_susulan, status, kunci, token)
              VALUES ('$kode_soal', '$nama_soal', '$mapel', '$kelas', '$waktu_ujian', '$tampilan_soal', '$tanggal', '$waktu',
                      $tanggal_ujian_susulan_value, $waktu_ujian_susulan_value, '$status', '$kunci', '$token')";

    if (mysqli_query($koneksi, $query)) {
        $_SESSION['success'] = 'Soal berhasil ditambahkan.';
    } else {
        $_SESSION['error'] = 'Gagal menambahkan soal: ' . mysqli_error($koneksi);
    }

    header('Location: soal.php');
    exit;
}

// Get today's date for the min attribute in HTML
$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Soal</title>
    <?php include '../inc/css.php'; ?>
</head>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="main">
            <?php include 'navbar.php'; ?>
            <main class="content">
                <div class="container-fluid p-0">
                    <div class="row">
                        <div class="col-12 col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Tambah Soal</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Ambil data kelas dari tabel siswa secara DISTINCT
                                    $query_kelas = "SELECT DISTINCT kelas FROM siswa ORDER BY kelas ASC";
                                    $result_kelas = mysqli_query($koneksi, $query_kelas);
                                    ?>
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label for="kode_soal" class="form-label">Kode Soal</label>
                                            <input type="text" class="form-control" id="kode_soal" name="kode_soal" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="nama_soal" class="form-label">Nama Soal</label>
                                            <input type="text" class="form-control" id="nama_soal" name="nama_soal" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="mapel" class="form-label">Mata Pelajaran</label>
                                            <input type="text" class="form-control" id="mapel" name="mapel" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="kelas" class="form-label">Kelas</label>
                                            <select class="form-control" id="kelas" name="kelas" required>
                                                <option value="">-- Pilih Kelas --</option>
                                                <?php while ($kelas_row = mysqli_fetch_assoc($result_kelas)): ?>
                                                    <option value="<?php echo $kelas_row['kelas']; ?>">
                                                        <?php echo $kelas_row['kelas']; ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="waktu_ujian" class="form-label">Waktu Ujian (Menit)</label>
                                            <input type="number" class="form-control" id="waktu_ujian" name="waktu_ujian" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="tampilan_soal" class="form-label">Tampilan Soal</label>
                                            <select class="form-control" id="tampilan_soal" name="tampilan_soal" required>
                                                <option value="">-- Pilih --</option>
                                                <option value="Acak">Acak</option>
                                                <option value="Urut">Urut</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="tanggal" class="form-label">Tanggal Ujian</label>
                                            <input type="date" class="form-control" id="tanggal" name="tanggal"
                                                min="<?php echo $today; ?>" required onclick="this.showPicker()">
                                            <small class="text-muted">Tanggal harus hari ini atau setelahnya</small>
                                        </div>
                                        <div class="mb-3">
                                            <label for="waktu" class="form-label">Waktu Ujian</label>
                                            <input type="time" class="form-control" id="waktu" name="waktu" required>
                                        </div>

                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h6 class="card-title mb-0">Ujian Susulan (Opsional)</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label for="tanggal_ujian_susulan" class="form-label">Tanggal Ujian Susulan</label>
                                                    <input type="date" class="form-control" id="tanggal_ujian_susulan"
                                                        name="tanggal_ujian_susulan" min="<?php echo $today; ?>"
                                                        onclick="this.showPicker()">
                                                    <small class="text-muted">Kosongkan jika tidak ada ujian susulan</small>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="waktu_ujian_susulan" class="form-label">Waktu Ujian Susulan</label>
                                                    <input type="time" class="form-control" id="waktu_ujian_susulan"
                                                        name="waktu_ujian_susulan">
                                                    <small class="text-muted">Harus diisi jika tanggal susulan diisi</small>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button>
                                        <a href="soal.php" class="btn btn-danger">Batal</a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <?php include '../inc/js.php'; ?>
</body>

</html>
