<?php
session_start();
include '../koneksi/koneksi.php';
include '../inc/functions.php';
check_login('siswa');
include '../inc/datasiswa.php'; // diasumsikan variabel $id_siswa dan $kelas_siswa tersedia di sini

// Use prepared statement to prevent SQL injection
if (!empty($rombel_siswa)) {
    $query = mysqli_prepare($koneksi, "SELECT * FROM soal WHERE status='Aktif' AND kelas=? AND rombel=? ORDER BY tanggal DESC");
    mysqli_stmt_bind_param($query, "ss", $kelas_siswa, $rombel_siswa);
} else {
    $query = mysqli_prepare($koneksi, "SELECT * FROM soal WHERE status='Aktif' AND kelas=? AND rombel IS NULL ORDER BY tanggal DESC");
    mysqli_stmt_bind_param($query, "s", $kelas_siswa);
}

mysqli_stmt_execute($query);
$result = mysqli_stmt_get_result($query);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $kode_soal = $row['kode_soal'];

    // Use prepared statement for the check query as well
    $cek_nilai = mysqli_prepare($koneksi, "SELECT 1 FROM nilai WHERE id_siswa=? AND kode_soal=? LIMIT 1");
    mysqli_stmt_bind_param($cek_nilai, "ss", $id_siswa, $kode_soal);
    mysqli_stmt_execute($cek_nilai);
    $nilai_result = mysqli_stmt_get_result($cek_nilai);

    if (mysqli_num_rows($nilai_result) == 0) {
        // Belum mengerjakan, tambahkan ke data
        unset($row['token']);
        unset($row['kunci']);
        $data[] = $row;
    }
    mysqli_stmt_close($cek_nilai);
}

header('Content-Type: application/json');
echo json_encode($data);
?>
