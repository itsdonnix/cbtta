<?php
session_start();
include '../koneksi/koneksi.php';
include '../inc/functions.php';
check_login('admin');
include '../inc/dataadmin.php';

$id_siswa = $_POST['id_siswa'] ?? '';
$kode_soal = $_POST['kode_soal'] ?? '';

if (empty($id_siswa) || empty($kode_soal)) {
    die("Data tidak valid!");
}

// ===============================
// Ambil jenis ujian dari jawaban_siswa
// ===============================
$qJenis = mysqli_query($koneksi, "
    SELECT jenis_ujian 
    FROM jawaban_siswa 
    WHERE id_siswa = '$id_siswa'
    AND kode_soal = '$kode_soal'
    LIMIT 1
");

$dataJenis   = mysqli_fetch_assoc($qJenis);
$jenis_ujian = $dataJenis['jenis_ujian'] ?? 0; // default utama

// ===============================
// Hitung total soal SESUAI jenis ujian
// ===============================
$query_total = mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS total_soal 
     FROM butir_soal 
     WHERE kode_soal = '$kode_soal'
     AND jenis_ujian = '$jenis_ujian'"
);

$total_soal = mysqli_fetch_assoc($query_total)['total_soal'] ?? 0;
$nilai_per_soal = $total_soal > 0 ? (100 / $total_soal) : 0;
$nilai_format = number_format($nilai_per_soal, 2, '.', '');

// ===============================
// Ambil jawaban siswa
// ===============================
$hasil = mysqli_fetch_assoc(mysqli_query(
    $koneksi,
    "SELECT jawaban_siswa, detail_uraian, nama_siswa 
    FROM nilai 
    WHERE id_siswa = '$id_siswa' 
    AND kode_soal = '$kode_soal'"
));
$nama_siswa = $hasil['nama_siswa'];
$jawaban = [];
if (!empty($hasil['jawaban_siswa'])) {
    preg_match_all('/\[(\d+):([^\]]+)\]/', $hasil['jawaban_siswa'], $matches);
    foreach ($matches[1] as $key => $nomer) {
        $jawaban[$nomer] = $matches[2][$key];
    }
}

// 3. Parse nilai uraian
$nilai_uraian = [];
if (!empty($hasil['detail_uraian'])) {
    preg_match_all('/\[(\d+):([0-9.]+)\]/', $hasil['detail_uraian'], $matches);
    foreach ($matches[1] as $key => $nomer) {
        $nilai_uraian[$nomer] = $matches[2][$key];
    }
}

// ===============================
// Ambil SOAL URAIAN sesuai jenis ujian
// ===============================
$soal = mysqli_query(
    $koneksi,
    "SELECT nomer_soal, pertanyaan 
     FROM butir_soal 
     WHERE kode_soal = '$kode_soal' 
     AND tipe_soal = 'Uraian'
     AND jenis_ujian = '$jenis_ujian'
     ORDER BY nomer_soal"
);

$labelUjian = ($jenis_ujian == 1) ? 'Susulan' : 'Utama';

// ambil class warna seperti di mulaiujian.php
$bgUjianClass = ($jenis_ujian == 1)
    ? 'bg-warning text-dark'
    : 'bg-primary text-white';

// ===============================
// Header info (UPDATED)
// ===============================
echo '
<div class="mb-3 p-2 rounded ' . $bgUjianClass . '">
    <strong>Nama:</strong> ' . $nama_siswa . ' |
    <strong>Kode Soal:</strong> ' . $kode_soal . ' |
    <strong>Jenis Ujian:</strong> 
        <span class="badge bg-light text-dark ms-1">
            ' . $labelUjian . '
        </span> |
    <strong>Total Soal:</strong> ' . $total_soal . ' |
    <strong>Nilai per Soal:</strong> ' . number_format($nilai_per_soal, 2) . '
</div>';

while ($s = mysqli_fetch_assoc($soal)) {
    $nomer = $s['nomer_soal'];
    $pertanyaan = nl2br(strip_tags($s['pertanyaan'], '<b><i><u><strong><em><img><br><p>'));
    $jawaban_siswa = nl2br(htmlspecialchars($jawaban[$nomer] ?? '-'));
    $nilai_awal = $nilai_uraian[$nomer] ?? 0;

    echo "<div class='card mb-4 shadow-sm'>
            <div class='card-body soal'>
                <h5 class='card-title'>Soal Nomor {$nomer}</h5>
                <p class='card-text'><strong>Pertanyaan:</strong><br>{$pertanyaan}</p>
                <p class='card-text'><strong>Jawaban Siswa:</strong><br>{$jawaban_siswa}</p>
                
                <div class='mb-2'>
                    <label class='form-label'><strong>Nilai (Max: {$nilai_format})</strong></label>
                    <div class='d-block d-md-flex align-items-center gap-3' style='max-width:400px;'>
                        <input type='range' 
                            min='0' 
                            max='{$nilai_format}' 
                            step='0.01' 
                            class='form-range flex-grow-1 nilai-slider' 
                            data-target='nilai-input-{$nomer}'
                            value='{$nilai_awal}'>
                        
                        <input type='number' 
                            min='0' 
                            max='{$nilai_format}' 
                            step='0.01' 
                            name='nilai[{$nomer}]' 
                            class='form-control nilai-input' 
                            id='nilai-input-{$nomer}' 
                            value='{$nilai_awal}' 
                            style='max-width: 100px;' 
                            required>
                    </div>
                    <small class='text-muted'>Bobot maksimum: {$nilai_format}</small>
                </div>
            </div>
          </div>";
}

echo "<input type='hidden' name='id_siswa' value='{$id_siswa}'>";
echo "<input type='hidden' name='kode_soal' value='{$kode_soal}'>";
?>

<!-- Script sinkronisasi slider & input -->
<script>
    document.querySelectorAll('.nilai-slider').forEach(slider => {
        const inputId = slider.getAttribute('data-target');
        const inputBox = document.getElementById(inputId);

        slider.addEventListener('input', () => {
            inputBox.value = slider.value;
        });

        inputBox.addEventListener('input', () => {
            slider.value = inputBox.value;
        });
    });
</script>

<style>
    input[type=range]::-webkit-slider-runnable-track {
        height: 4px;
        background: rgb(90, 90, 90);
        border-radius: 4px;
    }

    input[type=range]::-moz-range-track {
        height: 4px;
        background: rgb(90, 90, 90);
        border-radius: 4px;
    }

    .soal img {
        height: auto;
        width: auto;
        object-fit: contain;
        max-width: 400px !important;
        max-height: 400px !important;
        display: block;
    }
</style>
