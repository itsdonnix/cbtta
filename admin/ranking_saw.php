<?php
session_start();
include '../koneksi/koneksi.php';
include '../inc/functions.php';
check_login('admin');
include '../inc/dataadmin.php';

$kode_soal = $_GET['kode_soal'] ?? '';
$bobot_nilai = $_GET['bobot_nilai'] ?? 70;
$bobot_waktu = $_GET['bobot_waktu'] ?? 30;

$results = [];
$max_nilai = 0;
$max_waktu = 0;

if (!empty($kode_soal)) {
    // Ambil data nilai dan waktu sisa
    // Kita asumsikan Waktu Sisa adalah Benefit (Semakin banyak sisa waktu, semakin cepat/baik)
    $query = "SELECT 
                s.nama_siswa, 
                s.kelas, 
                s.rombel,
                (n.nilai + IFNULL(n.nilai_uraian, 0)) as nilai_akhir,
                js.waktu_sisa
              FROM nilai n
              JOIN jawaban_siswa js ON n.id_siswa = js.id_siswa AND n.kode_soal = js.kode_soal
              JOIN siswa s ON n.id_siswa = s.id_siswa
              WHERE n.kode_soal = '$kode_soal'";

    $data = mysqli_query($koneksi, $query);

    $candidates = [];

    while ($row = mysqli_fetch_assoc($data)) {
        $row['nilai_akhir'] = (float) $row['nilai_akhir'];
        $row['waktu_sisa'] = (int) $row['waktu_sisa'];

        // Cari nilai maksimum untuk normalisasi (Benefit)
        if ($row['nilai_akhir'] > $max_nilai) $max_nilai = $row['nilai_akhir'];
        if ($row['waktu_sisa'] > $max_waktu) $max_waktu = $row['waktu_sisa'];

        $candidates[] = $row;
    }

    // Proses Normalisasi dan Perangkingan SAW
    foreach ($candidates as $c) {
        // Rumus Normalisasi Benefit: r = nilai / max_nilai
        $r_nilai = ($max_nilai > 0) ? $c['nilai_akhir'] / $max_nilai : 0;
        $r_waktu = ($max_waktu > 0) ? $c['waktu_sisa'] / $max_waktu : 0;

        // Hitung Nilai Preferensi (V)
        // V = (BobotNilai * R_Nilai) + (BobotWaktu * R_Waktu)
        $v = ($r_nilai * ($bobot_nilai / 100)) + ($r_waktu * ($bobot_waktu / 100));

        $c['r_nilai'] = $r_nilai;
        $c['r_waktu'] = $r_waktu;
        $c['total_poin'] = $v;

        $results[] = $c;
    }

    // Urutkan berdasarkan Total Poin (Descending)
    usort($results, function ($a, $b) {
        return $b['total_poin'] <=> $a['total_poin'];
    });
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perangkingan SAW</title>
    <?php include '../inc/css.php'; ?>
</head>

<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="main">
            <?php include 'navbar.php'; ?>
            <main class="content">
                <div class="container-fluid p-0">
                    <div class="card shadow">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Perangkingan Metode SAW</h5>
                            <a href="hasil.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3 mb-4 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label">Pilih Kode Ujian</label>
                                    <select class="form-select" name="kode_soal" required onchange="this.form.submit()">
                                        <option value="">-- Pilih Soal --</option>
                                        <?php
                                        $qSoal = mysqli_query($koneksi, "SELECT DISTINCT kode_soal FROM nilai");
                                        while ($soal = mysqli_fetch_assoc($qSoal)) {
                                            $selected = ($kode_soal == $soal['kode_soal']) ? 'selected' : '';
                                            echo "<option value='{$soal['kode_soal']}' $selected>{$soal['kode_soal']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Bobot Nilai (%)</label>
                                    <input type="number" class="form-control" name="bobot_nilai" value="<?= $bobot_nilai ?>" min="0" max="100">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Bobot Waktu (%)</label>
                                    <input type="number" class="form-control" name="bobot_waktu" value="<?= $bobot_waktu ?>" min="0" max="100">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-calculator"></i> Hitung</button>
                                </div>
                            </form>

                            <?php if (!empty($results)): ?>
                                <div class="alert alert-info">
                                    <strong>Info Kriteria:</strong><br>
                                    1. Nilai Akhir (Benefit) - Bobot: <?= $bobot_nilai ?>%<br>
                                    2. Waktu Sisa (Benefit) - Bobot: <?= $bobot_waktu ?>% (Semakin banyak sisa waktu, semakin tinggi poin)
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Rank</th>
                                                <th>Nama Siswa</th>
                                                <th>Kelas</th>
                                                <th>Nilai Akhir (C1)</th>
                                                <th>Waktu Sisa (C2)</th>
                                                <th>Normalisasi (R1)</th>
                                                <th>Normalisasi (R2)</th>
                                                <th>Total Poin (V)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $rank = 1;
                                            foreach ($results as $res):
                                                $menit = floor($res['waktu_sisa'] / 60);
                                                $detik = $res['waktu_sisa'] % 60;
                                                $waktu_fmt = sprintf("%02d:%02d", $menit, $detik);
                                            ?>
                                                <tr>
                                                    <td class="text-center fw-bold"><?= $rank++ ?></td>
                                                    <td><?= htmlspecialchars($res['nama_siswa']) ?></td>
                                                    <td><?= htmlspecialchars($res['kelas'] . ' ' . $res['rombel']) ?></td>
                                                    <td><?= number_format($res['nilai_akhir'], 2) ?></td>
                                                    <td><?= $waktu_fmt ?> <small class="text-muted">(<?= $res['waktu_sisa'] ?>s)</small></td>
                                                    <td><?= number_format($res['r_nilai'], 3) ?></td>
                                                    <td><?= number_format($res['r_waktu'], 3) ?></td>
                                                    <td class="fw-bold text-primary"><?= number_format($res['total_poin'], 4) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php elseif (!empty($kode_soal)): ?>
                                <div class="alert alert-warning">Tidak ada data nilai untuk kode soal ini.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <?php include '../inc/js.php'; ?>
</body>

</html>
