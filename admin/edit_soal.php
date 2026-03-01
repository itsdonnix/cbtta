<?php
session_start();
include '../koneksi/koneksi.php';
include '../inc/functions.php';
check_login('admin');
include '../inc/dataadmin.php';

// Pastikan ID soal ada di URL
if (!isset($_GET['id_soal'])) {
    header('Location: soal.php');
    exit();
}

$id_soal = $_GET['id_soal'];

// Ambil data soal berdasarkan ID
$query = "SELECT * FROM soal WHERE id_soal = '$id_soal'";
$result = mysqli_query($koneksi, $query);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    echo "Soal tidak ditemukan!";
    exit();
}

// ✅ Jika soal status = aktif, tampilkan SweetAlert + redirect
if ($row['status'] == 'Aktif') {
    $_SESSION['warning_message'] = 'Soal ini sudah aktif dan tidak bisa diedit!';
    header('Location: soal.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $kode_soal = mysqli_real_escape_string($koneksi, $_POST['kode_soal']);
    $nama_soal = mysqli_real_escape_string($koneksi, $_POST['nama_soal']);
    $mapel = mysqli_real_escape_string($koneksi, $_POST['mapel']);
    $kelas = mysqli_real_escape_string($koneksi, $_POST['kelas']);
    $rombel = !empty($_POST['rombel']) ? mysqli_real_escape_string($koneksi, $_POST['rombel']) : NULL;
    $tampilan_soal = mysqli_real_escape_string($koneksi, $_POST['tampilan_soal']);
    $waktu_ujian = mysqli_real_escape_string($koneksi, $_POST['waktu_ujian']);
    $tanggal = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $waktu = mysqli_real_escape_string($koneksi, $_POST['waktu']);
    $tanggal_ujian_susulan = mysqli_real_escape_string($koneksi, $_POST['tanggal_ujian_susulan']);
    $waktu_ujian_susulan = mysqli_real_escape_string($koneksi, $_POST['waktu_ujian_susulan']);

    // Ambil tanggal asli dari database untuk perbandingan
    $tanggal_asli = mysqli_real_escape_string($koneksi, $_POST['tanggal_asli']);
    $tanggal_ujian_susulan_asli = mysqli_real_escape_string($koneksi, $_POST['tanggal_ujian_susulan_asli']);

    // Validasi kelas yang dipilih ada di tabel siswa
    $cek_kelas = mysqli_query($koneksi, "SELECT DISTINCT kelas FROM siswa WHERE kelas = '$kelas'");
    if (mysqli_num_rows($cek_kelas) == 0) {
        $_SESSION['error'] = 'Kelas yang dipilih tidak valid.';
        header("Location: edit_soal.php?id_soal=$id_soal");
        exit;
    }

    // Validasi relasi kelas dan rombel jika rombel diisi
    if (!empty($rombel)) {
        $cek_relasi = mysqli_query($koneksi, "SELECT * FROM siswa WHERE kelas = '$kelas' AND rombel = '$rombel' LIMIT 1");
        if (mysqli_num_rows($cek_relasi) == 0) {
            $_SESSION['error'] = 'Rombel yang dipilih tidak sesuai dengan kelas yang dipilih.';
            header("Location: edit_soal.php?id_soal=$id_soal");
            exit;
        }
    }

    // Validasi tanggal ujian - hanya jika tanggal diubah dari nilai asli
    $today = date('Y-m-d');
    if ($tanggal !== $tanggal_asli && $tanggal < $today) {
        $_SESSION['error'] = 'Tanggal ujian harus hari ini atau setelah hari ini jika diubah.';
        header("Location: edit_soal.php?id_soal=$id_soal");
        exit;
    }

    // Validasi tanggal dan waktu ujian susulan - harus diisi kedua-duanya atau tidak sama sekali
    if ((!empty($tanggal_ujian_susulan) && empty($waktu_ujian_susulan)) ||
        (empty($tanggal_ujian_susulan) && !empty($waktu_ujian_susulan))
    ) {
        $_SESSION['error'] = 'Tanggal dan Waktu Ujian Susulan harus diisi kedua-duanya atau tidak sama sekali.';
        header("Location: edit_soal.php?id_soal=$id_soal");
        exit;
    }

    // Jika tanggal ujian susulan diisi, validasi harus hari ini atau setelahnya
    // Hanya validasi jika tanggal susulan diubah dari nilai asli
    if (!empty($tanggal_ujian_susulan) && $tanggal_ujian_susulan !== $tanggal_ujian_susulan_asli && $tanggal_ujian_susulan < $today) {
        $_SESSION['error'] = 'Tanggal ujian susulan harus hari ini atau setelah hari ini jika diubah.';
        header("Location: edit_soal.php?id_soal=$id_soal");
        exit;
    }

    // Handle rombel NULL value
    $rombel_value = !empty($rombel) ? "'$rombel'" : "NULL";

    // Update data soal
    $update_query = "UPDATE soal SET 
                        kode_soal = '$kode_soal', 
                        nama_soal = '$nama_soal',
                        mapel = '$mapel', 
                        kelas = '$kelas',
                        rombel = $rombel_value,
                        tampilan_soal = '$tampilan_soal', 
                        waktu_ujian = '$waktu_ujian', 
                        tanggal = '$tanggal',
                        waktu = '$waktu',
                        tanggal_ujian_susulan = " . (!empty($tanggal_ujian_susulan) ? "'$tanggal_ujian_susulan'" : "NULL") . ",
                        waktu_ujian_susulan = " . (!empty($waktu_ujian_susulan) ? "'$waktu_ujian_susulan'" : "NULL") . "
                    WHERE id_soal = '$id_soal'";

    if (mysqli_query($koneksi, $update_query)) {
        $_SESSION['success_message'] = 'Data soal berhasil diupdate!';
        header('Location: soal.php');
        exit();
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}

// Get today's date for the min attribute in HTML
$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Soal</title>
    <?php include '../inc/css.php'; ?>
    <style>
        /* Loading indicator for rombel dropdown */
        .rombel-loading {
            display: none;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #007bff;
        }
        .form-group {
            position: relative;
        }
    </style>
    <script>
        function updateDateValidation() {
            const tanggalInput = document.getElementById('tanggal');
            const tanggalAsli = document.getElementById('tanggal_asli').value;
            const today = '<?php echo $today; ?>';

            // Jika tanggal sama dengan asli, hilangkan min attribute
            if (tanggalInput.value === tanggalAsli) {
                tanggalInput.removeAttribute('min');
                // Update help text
                const helpText = tanggalInput.nextElementSibling;
                if (helpText && helpText.classList.contains('text-muted')) {
                    helpText.textContent = 'Tanggal asli dipertahankan. Jika diubah, harus hari ini atau setelahnya.';
                }
            } else {
                // Jika tanggal diubah, set min attribute ke hari ini
                tanggalInput.setAttribute('min', today);
                // Update help text
                const helpText = tanggalInput.nextElementSibling;
                if (helpText && helpText.classList.contains('text-muted')) {
                    helpText.textContent = 'Tanggal harus hari ini atau setelahnya';
                }
            }
        }

        function updateSusulanDateValidation() {
            const tanggalSusulanInput = document.getElementById('tanggal_ujian_susulan');
            const tanggalSusulanAsli = document.getElementById('tanggal_ujian_susulan_asli').value;
            const today = '<?php echo $today; ?>';

            // Jika tanggal susulan sama dengan asli atau kosong, hilangkan min attribute
            if (tanggalSusulanInput.value === tanggalSusulanAsli || tanggalSusulanInput.value === '') {
                tanggalSusulanInput.removeAttribute('min');
                // Update help text
                const helpText = tanggalSusulanInput.nextElementSibling;
                if (helpText && helpText.classList.contains('text-muted')) {
                    if (tanggalSusulanInput.value === '') {
                        helpText.textContent = 'Kosongkan jika tidak ada ujian susulan';
                    } else {
                        helpText.textContent = 'Tanggal asli dipertahankan. Jika diubah, harus hari ini atau setelahnya.';
                    }
                }
            } else {
                // Jika tanggal susulan diubah, set min attribute ke hari ini
                tanggalSusulanInput.setAttribute('min', today);
                // Update help text
                const helpText = tanggalSusulanInput.nextElementSibling;
                if (helpText && helpText.classList.contains('text-muted')) {
                    helpText.textContent = 'Tanggal harus hari ini atau setelahnya';
                }
            }
        }

        // Initialize validation on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateDateValidation();
            updateSusulanDateValidation();
        });
    </script>
</head>

<body>
    <div class="wrapper">

        <?php include 'sidebar.php'; ?>

        <div class="main">
            <?php include 'navbar.php'; ?>

            <main class="content">
                <div class="container-fluid p-0">

                    <div class="row">
                        <div class="col-12 col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Edit Soal</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Ambil data kelas dari tabel siswa secara DISTINCT
                                    $query_kelas = "SELECT DISTINCT kelas FROM siswa ORDER BY kelas ASC";
                                    $result_kelas = mysqli_query($koneksi, $query_kelas);
                                    ?>
                                    <form method="POST">
                                        <!-- Hidden fields untuk menyimpan nilai asli -->
                                        <input type="hidden" id="tanggal_asli" name="tanggal_asli" value="<?php echo $row['tanggal']; ?>">
                                        <input type="hidden" id="tanggal_ujian_susulan_asli" name="tanggal_ujian_susulan_asli" value="<?php echo $row['tanggal_ujian_susulan']; ?>">

                                        <div class="mb-3">
                                            <h2>Kode Soal : <?php echo $row['kode_soal']; ?></h2>
                                            <input type="hidden" class="form-control" id="kode_soal" name="kode_soal" value="<?php echo $row['kode_soal']; ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="nama_soal" class="form-label">Nama Soal</label>
                                            <input type="text" class="form-control" id="nama_soal" name="nama_soal" value="<?php echo $row['nama_soal']; ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="mapel" class="form-label">Mata Pelajaran</label>
                                            <input type="text" class="form-control" id="mapel" name="mapel" value="<?php echo $row['mapel']; ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="kelas" class="form-label">Kelas</label>
                                            <select class="form-control" id="kelas" name="kelas" required>
                                                <option value="">-- Pilih Kelas --</option>
                                                <?php while ($kelas_row = mysqli_fetch_assoc($result_kelas)): ?>
                                                    <option value="<?php echo $kelas_row['kelas']; ?>" <?php echo ($kelas_row['kelas'] == $row['kelas']) ? 'selected' : ''; ?>>
                                                        <?php echo $kelas_row['kelas']; ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>

                                        <!-- Rombel Dropdown -->
                                        <div class="mb-3 form-group">
                                            <label for="rombel" class="form-label">Rombel (Opsional)</label>
                                            <select class="form-control" id="rombel" name="rombel">
                                                <option value="">-- Pilih Rombel (Opsional) --</option>
                                                <option value="">Semua Rombel</option>
                                                <!-- Options will be loaded dynamically based on kelas selection -->
                                            </select>
                                            <small class="text-muted">Kosongkan atau pilih "Semua Rombel" untuk berlaku di semua rombel dalam kelas yang dipilih</small>
                                            <div class="rombel-loading" id="rombelLoading">
                                                <i class="fa fa-spinner fa-spin"></i> Loading...
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="waktu_ujian" class="form-label">Waktu Ujian (Menit)</label>
                                            <input type="number" class="form-control" id="waktu_ujian" name="waktu_ujian" value="<?php echo $row['waktu_ujian']; ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="tampilan_soal" class="form-label">Tampilan Soal</label>
                                            <select class="form-control" id="tampilan_soal" name="tampilan_soal" required>
                                                <option value="<?php echo $row['tampilan_soal']; ?>"><?php echo $row['tampilan_soal']; ?></option>
                                                <option value="Acak">Acak</option>
                                                <option value="Urut">Urut</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="tanggal" class="form-label">Tanggal Ujian</label>
                                            <input type="date" class="form-control" id="tanggal" name="tanggal"
                                                value="<?php echo $row['tanggal']; ?>"
                                                onchange="updateDateValidation()" onclick="this.showPicker()">
                                            <small class="text-muted">Tanggal asli dipertahankan. Jika diubah, harus hari ini atau setelahnya.</small>
                                        </div>
                                        <div class="mb-3">
                                            <label for="waktu" class="form-label">Waktu Ujian</label>
                                            <input type="time" class="form-control" id="waktu" name="waktu"
                                                value="<?php echo $row['waktu']; ?>" required>
                                        </div>

                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h6 class="card-title mb-0">Ujian Susulan (Opsional)</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label for="tanggal_ujian_susulan" class="form-label">Tanggal Ujian Susulan</label>
                                                    <input type="date" class="form-control" id="tanggal_ujian_susulan"
                                                        name="tanggal_ujian_susulan"
                                                        value="<?php echo $row['tanggal_ujian_susulan']; ?>"
                                                        onchange="updateSusulanDateValidation()" onclick="this.showPicker()">
                                                    <small class="text-muted">Tanggal asli dipertahankan. Jika diubah, harus hari ini atau setelahnya.</small>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="waktu_ujian_susulan" class="form-label">Waktu Ujian Susulan</label>
                                                    <input type="time" class="form-control" id="waktu_ujian_susulan"
                                                        name="waktu_ujian_susulan"
                                                        value="<?php echo $row['waktu_ujian_susulan']; ?>">
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

    <!-- JavaScript for reactive rombel dropdown with previous value tracking -->
    <script>
    $(document).ready(function() {
        var previousRombel = '<?php echo $row['rombel']; ?>'; // Store the current rombel value from database
        
        // Store rombel value when it changes
        $('#rombel').change(function() {
            previousRombel = $(this).val();
        });
        
        // Function to load rombel based on selected kelas
        function loadRombel(kelas, selectedRombel) {
            var rombelDropdown = $('#rombel');
            var loading = $('#rombelLoading');
            
            if (kelas) {
                // Show loading indicator
                loading.show();
                rombelDropdown.prop('disabled', true);
                
                // Make AJAX call to fetch rombel based on selected kelas
                $.ajax({
                    url: 'get_rombel.php',
                    type: 'POST',
                    data: {kelas: kelas},
                    dataType: 'json',
                    success: function(data) {
                        // Clear current options
                        rombelDropdown.empty();
                        rombelDropdown.append('<option value="">-- Pilih Rombel (Opsional) --</option>');
                        rombelDropdown.append('<option value="">Semua Rombel</option>');
                        
                        // Add new options
                        $.each(data, function(index, rombel) {
                            rombelDropdown.append('<option value="' + rombel + '">' + rombel + '</option>');
                        });
                        
                        // Set the selected value
                        if (selectedRombel) {
                            rombelDropdown.val(selectedRombel);
                        }
                        
                        // Hide loading indicator
                        loading.hide();
                        rombelDropdown.prop('disabled', false);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching rombel:', error);
                        rombelDropdown.empty();
                        rombelDropdown.append('<option value="">-- Error loading rombel --</option>');
                        loading.hide();
                        rombelDropdown.prop('disabled', false);
                    }
                });
            } else {
                // If no kelas selected, disable and clear rombel dropdown
                rombelDropdown.empty();
                rombelDropdown.append('<option value="">-- Pilih Kelas Terlebih Dahulu --</option>');
                rombelDropdown.prop('disabled', true);
                loading.hide();
            }
        }
        
        // Initial load: Load rombel based on current kelas and set selected value
        var initialKelas = $('#kelas').val();
        if (initialKelas) {
            loadRombel(initialKelas, previousRombel);
        }
        
        // Handle kelas change
        $('#kelas').change(function() {
            var kelas = $(this).val();
            // When kelas changes, try to preserve the previous rombel value
            // but only if it's valid for the new kelas (will be checked on server side)
            loadRombel(kelas, previousRombel);
        });
    });
    </script>
</body>

</html>
