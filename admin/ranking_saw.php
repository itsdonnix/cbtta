<?php
session_start();
include '../koneksi/koneksi.php';
include '../inc/functions.php';
check_login('admin');
include '../inc/dataadmin.php';

// Handle AJAX request for filtering
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_siswa = $_POST['nama_siswa'] ?? '';
    $kelas_rombel = $_POST['kelas_rombel'] ?? '';
    $kode_soal = $_POST['kode_soal'] ?? '';
    $bobot_nilai = $_POST['bobot_nilai'] ?? 70;
    $bobot_waktu = $_POST['bobot_waktu'] ?? 30;

    $where = "n.kode_soal IS NOT NULL"; // Kondisi awal

    // Filter Kelas Rombel
    if (!empty($kelas_rombel)) {
        list($kelas, $rombel) = explode(' - ', $kelas_rombel);
        $kelas = mysqli_real_escape_string($koneksi, $kelas);
        $rombel = mysqli_real_escape_string($koneksi, $rombel);
        $where .= " AND s.kelas = '$kelas' AND s.rombel = '$rombel'";
    }

    // Filter Kode Soal
    if (!empty($kode_soal)) {
        $kode_soal_filter = mysqli_real_escape_string($koneksi, $kode_soal);
        $where .= " AND n.kode_soal = '$kode_soal_filter'";
    }

    // Filter Nama Siswa
    if (!empty($nama_siswa)) {
        $nama_siswa = mysqli_real_escape_string($koneksi, $nama_siswa);
        $where .= " AND s.nama_siswa LIKE '%$nama_siswa%'";
    }

    $results = [];
    $max_nilai = 0;
    $max_waktu = 0;

    // Ambil data nilai dan waktu sisa dengan filter
    $query = "SELECT 
                s.nama_siswa, 
                s.kelas, 
                s.rombel,
                (n.nilai + IFNULL(n.nilai_uraian, 0)) as nilai_akhir,
                js.waktu_sisa,
                n.kode_soal
              FROM nilai n
              JOIN jawaban_siswa js ON n.id_siswa = js.id_siswa AND n.kode_soal = js.kode_soal
              JOIN siswa s ON n.id_siswa = s.id_siswa
              WHERE $where";

    $data = mysqli_query($koneksi, $query);

    if ($data && mysqli_num_rows($data) > 0) {
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

    // Output HTML table
    if (!empty($results)) {
        echo '<table class="table table-bordered table-striped table-hover" id="rankingTable">
                <thead class="table-dark">
                    <tr>
                        <th>Rank</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Kode Ujian</th>
                        <th>Nilai Akhir (C1)</th>
                        <th>Waktu Sisa (C2)</th>
                        <th>Normalisasi (R1)</th>
                        <th>Normalisasi (R2)</th>
                        <th>Total Poin (V)</th>
                    </tr>
                </thead>
                <tbody>';

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
                <td><?= htmlspecialchars($res['kode_soal']) ?></td>
                <td><?= number_format($res['nilai_akhir'], 2) ?></td>
                <td><?= $waktu_fmt ?> <small class="text-muted">(<?= $res['waktu_sisa'] ?>s)</small></td>
                <td><?= number_format($res['r_nilai'], 3) ?></td>
                <td><?= number_format($res['r_waktu'], 3) ?></td>
                <td class="fw-bold text-primary"><?= number_format($res['total_poin'], 4) ?></td>
            </tr>
<?php
        endforeach;

        echo '</tbody></table>';
    } else {
        echo '<div class="alert alert-warning">Tidak ada data yang ditemukan dengan filter yang dipilih.</div>';
    }
    exit;
}

// Original GET logic for initial page load
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
                js.waktu_sisa,
                n.kode_soal
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
    <style>
        .table-wrapper {
            max-height: 70vh;
            overflow-y: auto;
        }
    </style>
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
                            <form id="filterForm" method="POST" class="row g-3 mb-4 align-items-end">
                                <!-- Filter Nama Siswa -->
                                <div class="col-md-3">
                                    <label for="nama_siswa" class="form-label">Cari Siswa</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" name="nama_siswa" id="nama_siswa"
                                            placeholder="Ketikan nama siswa...">
                                    </div>
                                </div>

                                <!-- Filter Kelas Rombel -->
                                <div class="col-md-3">
                                    <label for="kelas_rombel" class="form-label">Kelas & Rombel</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-users"></i></span>
                                        <select class="form-select" name="kelas_rombel" id="kelas_rombel">
                                            <option value="">Semua Kelas</option>
                                            <?php
                                            $qKR = mysqli_query($koneksi, "SELECT DISTINCT CONCAT(kelas, ' - ', rombel) AS kelas_rombel 
                                                                            FROM siswa 
                                                                            WHERE kelas IS NOT NULL AND rombel IS NOT NULL 
                                                                            ORDER BY kelas, rombel");
                                            while ($kr = mysqli_fetch_assoc($qKR)) {
                                                echo "<option value='{$kr['kelas_rombel']}'>{$kr['kelas_rombel']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Filter Kode Soal -->
                                <div class="col-md-3">
                                    <label for="kode_soal" class="form-label">Kode Ujian</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-file-alt"></i></span>
                                        <select class="form-select" name="kode_soal" id="kode_soal">
                                            <option value="">Semua Kode</option>
                                            <?php
                                            $qSoal = mysqli_query($koneksi, "SELECT DISTINCT kode_soal FROM nilai");
                                            while ($soal = mysqli_fetch_assoc($qSoal)) {
                                                $selected = ($kode_soal == $soal['kode_soal']) ? 'selected' : '';
                                                echo "<option value='{$soal['kode_soal']}' $selected>{$soal['kode_soal']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Reset Button -->
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-light w-100" onclick="resetFilter()" title="Reset Filter">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                </div>

                                <!-- Bobot Controls -->
                                <div class="col-md-2">
                                    <label class="form-label">Bobot Nilai (%)</label>
                                    <input type="number" class="form-control" name="bobot_nilai" id="bobot_nilai"
                                        value="<?= $bobot_nilai ?>" min="0" max="100">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Bobot Waktu (%)</label>
                                    <input type="number" class="form-control" name="bobot_waktu" id="bobot_waktu"
                                        value="<?= $bobot_waktu ?>" min="0" max="100">
                                </div>

                                <!-- Submit Button -->
                                <div class="col-md-2 d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-calculator"></i> Hitung Ranking
                                    </button>
                                </div>
                            </form>

                            <!-- Info Panel -->
                            <div id="infoPanel" class="alert alert-info mb-3">
                                <strong>Info Kriteria:</strong><br>
                                1. Nilai Akhir (Benefit) - Bobot: <span id="bobotNilaiText"><?= $bobot_nilai ?></span>%<br>
                                2. Waktu Sisa (Benefit) - Bobot: <span id="bobotWaktuText"><?= $bobot_waktu ?></span>% (Semakin banyak sisa waktu, semakin tinggi poin)
                            </div>

                            <!-- Results Table -->
                            <div id="rankingResults" class="table-wrapper">
                                <?php if (!empty($results)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Rank</th>
                                                    <th>Nama Siswa</th>
                                                    <th>Kelas</th>
                                                    <th>Kode Ujian</th>
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
                                                        <td><?= htmlspecialchars($res['kode_soal']) ?></td>
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
                                <?php else: ?>
                                    <div class="alert alert-primary">Silakan pilih kode ujian dan atur bobot untuk melihat hasil ranking.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <?php include '../inc/js.php'; ?>
    <script>
        $(document).ready(function() {
            let delayTimer;

            // Live Search for student name
            $('#nama_siswa').on('input', function() {
                clearTimeout(delayTimer);
                delayTimer = setTimeout(() => $('#filterForm').submit(), 500);
            });

            // Auto Submit on Filter Change
            $('#kelas_rombel, #kode_soal, #bobot_nilai, #bobot_waktu').on('change', function() {
                updateBobotText();
                $('#filterForm').submit();
            });

            // Update bobot text in info panel
            function updateBobotText() {
                $('#bobotNilaiText').text($('#bobot_nilai').val());
                $('#bobotWaktuText').text($('#bobot_waktu').val());
            }

            // Handle Form Submit
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();

                // Validate bobot total = 100%
                const bobotNilai = parseInt($('#bobot_nilai').val());
                const bobotWaktu = parseInt($('#bobot_waktu').val());

                if (bobotNilai + bobotWaktu !== 100) {
                    Swal.fire('Peringatan', 'Total bobot harus 100% (Nilai: ' + bobotNilai + '% + Waktu: ' + bobotWaktu + '%)', 'warning');
                    return;
                }

                $.ajax({
                    url: '',
                    type: 'POST',
                    data: $(this).serialize(),
                    beforeSend: () => {
                        $('#rankingResults').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Memproses perangkingan...</p></div>');
                    },
                    success: function(response) {
                        $('#rankingResults').html(response);
                        initDataTable();
                    },
                    error: () => {
                        Swal.fire('Error', 'Gagal memuat data ranking', 'error');
                        $('#rankingResults').html('<div class="alert alert-danger">Terjadi kesalahan saat memproses data.</div>');
                    }
                });
            });

            // Initialize DataTable
            function initDataTable() {
                if ($.fn.DataTable.isDataTable('#rankingTable')) {
                    $('#rankingTable').DataTable().destroy();
                }

                $('#rankingTable').DataTable({
                    dom: '<"row"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"l><"col-sm-12 col-md-7 text-end"p>>',
                    buttons: [
                        'copy', 'excel',
                        {
                            extend: 'pdf',
                            text: 'PDF',
                            title: 'Laporan Ranking SAW',
                            messageTop: 'Metode SAW - Bobot Nilai: ' + $('#bobot_nilai').val() + '%, Bobot Waktu: ' + $('#bobot_waktu').val() + '%'
                        },
                        'print'
                    ],
                    responsive: true,
                    order: [
                        [0, 'asc']
                    ],
                    columnDefs: [{
                            targets: 0,
                            orderable: true,
                            searchable: false
                        },
                        {
                            targets: -1,
                            orderable: true,
                            searchable: false
                        }
                    ],
                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "Semua"]
                    ],
                    language: {
                        decimal: ",",
                        thousands: ".",
                        lengthMenu: "Tampilkan _MENU_ data",
                        search: "Cari:",
                        zeroRecords: "Data tidak ditemukan",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                        infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                        infoFiltered: "(disaring dari _MAX_ total data)",
                    },
                    drawCallback: function(settings) {
                        // Optional: Add styling to top 3 ranks
                        this.api().rows().every(function(rowIdx, tableLoop, rowLoop) {
                            const cell = this.cell(rowIdx, 0).node();
                            if (rowIdx < 3) {
                                $(cell).addClass('bg-warning bg-opacity-25');
                            }
                        });
                    }
                });
            }

            // Reset Filter
            window.resetFilter = () => {
                $('#filterForm')[0].reset();
                $('#bobot_nilai').val(70);
                $('#bobot_waktu').val(30);
                updateBobotText();
                $('#filterForm').submit();
            }

            // Initial bobot text update
            updateBobotText();
        });
    </script>
</body>

</html>
