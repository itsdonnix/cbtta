<?php
session_start();
include '../koneksi/koneksi.php';
include '../inc/functions.php';
check_login('admin');
include '../inc/dataadmin.php';

if (!isset($_GET['kode_soal'], $_GET['id_siswa'], $_GET['jenis_ujian'])) {
    echo "Parameter tidak lengkap.";
    exit;
}

$kode_soal   = $_GET['kode_soal'];
$id_siswa    = $_GET['id_siswa'];
$jenis_ujian = $_GET['jenis_ujian']; // utama | susulan
$jenis_ujian_int   = ($jenis_ujian === 'susulan') ? 1 : 0;
$jenis_ujian_label = ($jenis_ujian_int === 1) ? 'Susulan' : 'Utama';

/**
 * ===== AMBIL DATA JAWABAN (SAMA DENGAN PREVIEW_HASIL) =====
 */
$query = mysqli_query($koneksi, "
    SELECT 
        js.jawaban_siswa,
        js.waktu_dijawab,
        js.jenis_ujian,
        n.nilai,
        n.nilai_uraian,
        n.detail_uraian,
        s.nama_siswa
    FROM jawaban_siswa js
    JOIN siswa s ON s.id_siswa = js.id_siswa
    LEFT JOIN nilai n 
        ON n.id_siswa = js.id_siswa 
        AND n.kode_soal = js.kode_soal
    WHERE js.id_siswa   = '$id_siswa'
      AND js.kode_soal  = '$kode_soal'
      AND js.jenis_ujian = $jenis_ujian_int
    LIMIT 1
");

if (!$query || mysqli_num_rows($query) === 0) {
    echo "Jawaban siswa untuk jenis ujian ini tidak ditemukan.";
    exit;
}

$row = mysqli_fetch_assoc($query);

// ===== DATA UTAMA =====
$jawaban_siswa_raw = $row['jawaban_siswa'];
$nama_siswa        = $row['nama_siswa'];
$tanggal_ujian     = $row['waktu_dijawab'];

$nilai_otomatis = (float)($row['nilai'] ?? 0);
$nilai_uraian   = (float)($row['nilai_uraian'] ?? 0);
$nilai_siswa    = $nilai_otomatis + $nilai_uraian;

// ===== PARSE DETAIL URAIAN =====
$skor_uraian = [];
if (!empty($row['detail_uraian'])) {
    preg_match_all('/\[(\d+):([\d.]+)\]/', $row['detail_uraian'], $m);
    $skor_uraian = array_combine($m[1], $m[2]);
}

// ===== PARSE JAWABAN SISWA =====
function parseJawabanSiswa($str)
{
    preg_match_all('/\[(\d+):([^\]]*)\]/', $str, $m, PREG_SET_ORDER);
    $out = [];
    foreach ($m as $x) {
        $out[(int)$x[1]] = trim($x[2]);
    }
    return $out;
}
$jawaban_siswa = parseJawabanSiswa($jawaban_siswa_raw);

// ===== SOAL (FILTER JENIS UJIAN) =====
$query_soal = mysqli_query(
    $koneksi,
    "SELECT *
     FROM butir_soal
     WHERE kode_soal = '$kode_soal'
       AND jenis_ujian = $jenis_ujian_int
     ORDER BY nomer_soal ASC"
);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Jawaban Siswa</title>
    <?php include '../inc/css.php'; ?>
    <style>
        /* style tambahan untuk header 2 kolom */
        .header-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
        }

        .header-left,
        .header-right {
            width: 48%;
            font-weight: bold;
            font-size: 16px;
            line-height: 1.5;
        }

        .header-right {
            border: 1px solid #aaa;
            padding: 10px;
            height: 72px;
            /* kira-kira 3 baris dengan line-height 1.5 * 16px font-size */
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            background-color: #f7f7f7;
        }

        .card img {
            height: auto;
            width: auto;
            object-fit: contain;
            max-width: 450px !important;
            max-height: 300px !important;
            display: block;
        }

        @media (max-width: 768px) {
            .card img {
                width: 100% !important;

            }
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            border: 1px solid #aaa;
            padding: 6px;
        }

        .pembahasan {
            background-color: rgb(213, 213, 213);
            background-image: radial-gradient(rgb(255, 255, 255) 1px, transparent 1px);
            background-size: 20px 20px;
            padding: 10px;
            margin-top: 10px;
            border-radius: 5px;
            color: rgb(0, 0, 0);
            font-style: italic;
            white-space: pre-wrap;
        }

        .skor-soal {
            background-color: #e8f4f8;
            padding: 8px;
            margin-top: 5px;
            border-radius: 5px;
            font-weight: bold;
        }

        ul {
            list-style-type: none;
            padding-left: 0;
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
                    <h1>Preview Jawaban Siswa</h1>
                    <div class="row mb-4">
                        <div class="card-header">
                            <button type="button" class="btn btn-outline-danger" onclick="exportPDF()"><i
                                    class="fa-solid fa-file-pdf"></i> Download PDF</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="printModalContent()"><i
                                    class="fa fa-print"></i> Print</button>
                            <a href="hasil.php"><button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">Kembali</button></a>
                        </div>
                    </div>
                    <div class="col-lg-9">
                        <div class="col-12 card-utama" id="canvas_div_pdf">
                            <!-- HEADER 2 KOLOM -->
                            <div class="row mb-4"
                                style="max-height:300px;background-color: #444; color: white; border-radius: 10px; padding: 20px;">
                                <div class="col-md-9 col-6">
                                    <p><strong>Nama Siswa:</strong> <?= htmlspecialchars($nama_siswa) ?></p>
                                    <p><strong>Kode Soal:</strong> <?= htmlspecialchars($kode_soal) ?></p>
                                    <p><strong>Jenis Ujian:</strong> <?= htmlspecialchars($jenis_ujian_label) ?></p>
                                    <p><strong>Tanggal Ujian:</strong> <?= htmlspecialchars($tanggal_ujian) ?></p>
                                </div>
                                <div
                                    class="col-md-3 col-6 text-center d-flex align-items-center justify-content-center">
                                    <div
                                        style="background-color: white; color: black; padding: 20px; border-radius: 15px; width: 100%; height: 100%;">
                                        <h4 class="mb-0">Nilai</h4>
                                        <h1 style="font-size: 30px;"><?= $nilai_siswa ?></h1>
                                    </div>
                                </div>
                            </div>

                            <?php while ($soal = mysqli_fetch_assoc($query_soal)):
                                $no = (int)$soal['nomer_soal'];
                                $jawab = isset($jawaban_siswa[$no]) ? $jawaban_siswa[$no] : '';
                                $tipe = $soal['tipe_soal'];
                                $opsi_huruf = ['A', 'B', 'C', 'D'];
                            ?>
                                <div class="row">
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h5>No. <?= $no ?> (<?= $tipe ?>)</h5>
                                            <p><?= $soal['pertanyaan'] ?></p>
                                            <?php if (!empty($soal['gambar'])): ?>
                                                <img src="../assets/img/butir_soal/<?= $soal['gambar'] ?>" alt="Gambar Soal" />
                                            <?php endif; ?>

                                            <h6>Jawaban Siswa:</h6>
                                            <?php
                                            switch ($tipe) {
                                                case 'Pilihan Ganda':
                                                    echo "<ul>";
                                                    for ($i = 1; $i <= 4; $i++) {
                                                        $huruf = $opsi_huruf[$i - 1];
                                                        $checked = ($jawab == "pilihan_$i") ? "✓" : "";
                                                        echo "<li>$huruf. " . $soal["pilihan_$i"] . " $checked</li>";
                                                    }
                                                    echo "</ul>";
                                                    $benar_num = (int)str_replace("pilihan_", "", $soal['jawaban_benar']);
                                                    $benar_huruf = $opsi_huruf[$benar_num - 1];
                                                    echo '<div class="pembahasan"><strong>Pembahasan:</strong><br>Jawaban benar: ' . $benar_huruf . '</div>';
                                                    break;

                                                case 'Pilihan Ganda Kompleks':
                                                    $jawaban_arr = array_map('trim', explode(',', $jawab));
                                                    echo "<ul>";
                                                    for ($i = 1; $i <= 4; $i++) {
                                                        $huruf = $opsi_huruf[$i - 1];
                                                        $checked = in_array("pilihan_$i", $jawaban_arr) ? "✓" : "";
                                                        echo "<li>$huruf. " . $soal["pilihan_$i"] . " $checked</li>";
                                                    }
                                                    echo "</ul>";
                                                    $kunci_arr = array_map('trim', explode(',', $soal['jawaban_benar']));
                                                    $huruf_benar = [];
                                                    foreach ($kunci_arr as $k) {
                                                        $num = (int)str_replace("pilihan_", "", $k);
                                                        $huruf_benar[] = $opsi_huruf[$num - 1];
                                                    }
                                                    echo '<div class="pembahasan"><strong>Pembahasan:</strong><br>Jawaban benar: ' . implode(', ', $huruf_benar) . '</div>';
                                                    break;

                                                case 'Benar/Salah':
                                                    $pernyataan = [];
                                                    for ($i = 1; $i <= 4; $i++) {
                                                        if (!empty($soal["pilihan_$i"])) {
                                                            $pernyataan[] = $soal["pilihan_$i"];
                                                        }
                                                    }
                                                    $jawab_arr = explode('|', $jawab);
                                                    echo "<table><thead><tr><th>#</th><th>Pernyataan</th><th>Benar</th><th>Salah</th></tr></thead><tbody>";
                                                    foreach ($pernyataan as $i => $text) {
                                                        $val = isset($jawab_arr[$i]) ? $jawab_arr[$i] : '';
                                                        echo "<tr><td>" . ($i + 1) . "</td><td>" . $text . "</td><td>" . ($val == "Benar" ? "✓" : "") . "</td><td>" . ($val == "Salah" ? "✓" : "") . "</td></tr>";
                                                    }
                                                    echo "</tbody></table>";

                                                    $kunci_arr = explode('|', $soal['jawaban_benar']);
                                                    echo '<div class="pembahasan"><strong>Pembahasan:</strong><br>';
                                                    foreach ($pernyataan as $i => $text) {
                                                        $nilai = $kunci_arr[$i] ?? '-';
                                                        echo "Pernyataan " . ($i + 1) . ": " . htmlspecialchars($nilai) . "<br>";
                                                    }
                                                    echo '</div>';
                                                    break;

                                                case 'Menjodohkan':
                                                    // Tampilkan jawaban siswa dalam tabel
                                                    $pairs = explode('|', $jawab);
                                                    echo "<table border='1' cellpadding='5' cellspacing='0'><thead><tr><th>#</th><th>Pilihan</th><th>Pasangan</th></tr></thead><tbody>";
                                                    foreach ($pairs as $i => $pair) {
                                                        list($a, $b) = explode(':', $pair) + [null, null];
                                                        echo "<tr><td>" . ($i + 1) . "</td><td>" . htmlspecialchars($a) . "</td><td>" . htmlspecialchars($b) . "</td></tr>";
                                                    }
                                                    echo "</tbody></table>";

                                                    // Tampilkan pembahasan (kunci jawaban) juga dalam tabel
                                                    $kunci_pairs = explode('|', $soal['jawaban_benar']);
                                                    echo '<div class="pembahasan"><strong>Pembahasan:</strong>';
                                                    echo "<table border='1' cellpadding='5' cellspacing='0' style='margin-top:10px;'>";
                                                    echo "<thead><tr><th>#</th><th>Pilihan</th><th>Pasangan</th></tr></thead><tbody>";
                                                    foreach ($kunci_pairs as $i => $pair) {
                                                        list($a, $b) = explode(':', $pair) + [null, null];
                                                        echo "<tr><td>" . ($i + 1) . "</td><td>" . htmlspecialchars($a) . "</td><td>" . htmlspecialchars($b) . "</td></tr>";
                                                    }
                                                    echo "</tbody></table>";
                                                    echo '</div>';
                                                    break;


                                                case 'Uraian':
                                                    echo "<div class='border p-2 mb-2'>" . nl2br(htmlspecialchars($jawab)) . "</div>";
                                                    echo '<div class="pembahasan"><strong>Pembahasan:</strong><br>' . nl2br(htmlspecialchars($soal['jawaban_benar'])) . '</div>';
                                                    break;

                                                default:
                                                    echo '<div>Jawaban tidak tersedia untuk tipe soal ini.</div>';
                                                    break;
                                            }
                                            ?>
                                            <!-- Tambahkan skor per soal di sini -->
                                            <div class="skor-soal">
                                                <strong>Skor:</strong> <?= number_format($skor_uraian[$no] ?? 0, 2) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                            <p class="text-center" id="encr" style="font-size:11px;color:grey;"></p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <?php include '../inc/js.php'; ?>
    <script src="../assets/html2pdf.js/dist/html2pdf.bundle.min.js"></script>
    <script>
        function exportPDF() {
            var element = document.getElementById('canvas_div_pdf');
            html2pdf().set({
                margin: 0.2,
                filename: '<?php echo $kode_soal; ?>_<?php echo $nama_siswa; ?>.pdf',
                image: {
                    type: 'jpeg',
                    quality: 1
                },
                html2canvas: {
                    scale: 2,
                    logging: true
                },
                jsPDF: {
                    unit: 'in',
                    format: 'a4',
                    orientation: 'portrait'
                }
            }).from(element).save();
        }

        document.addEventListener("DOMContentLoaded", function() {
            const images = document.querySelectorAll('.card-utama img');

            images.forEach(function(img) {
                img.style.maxWidth = '200px';
                img.style.maxHeight = '200px';
            });
        });

        function printModalContent() {
            const modalBody = document.querySelector('.card-utama').innerHTML;
            const printWindow = window.open('', '', 'width=1000,height=700');

            printWindow.document.write(`
                <html>
                    <head>
                        <title>Print Preview</title>
                        <link rel="stylesheet" href="../inc/print-soal.css" type="text/css" />
                    </head>
                    <body onload="window.print(); window.close();">
                        ${modalBody}
                    </body>
                </html>
            `);

            printWindow.document.close();
        }
    </script>
</body>

</html>
