<?php
session_start();
include '../koneksi/koneksi.php';
include '../inc/functions.php';
check_login('siswa');

if (isset($_GET['kode_soal']) && is_array($_GET['kode_soal'])) {
    $kodeSoalList = $_GET['kode_soal'];

    // Sanitize input
    $kodeSoalList = array_map(function ($kode) use ($koneksi) {
        return "'" . mysqli_real_escape_string($koneksi, $kode) . "'";
    }, $kodeSoalList);

    $kodeSoalString = implode(',', $kodeSoalList);

    // Check if any of these soal have essay questions (uraian)
    // You need to adjust this query based on your actual database structure
    // This assumes you have a table that stores question types
    $query = mysqli_query($koneksi, "
        SELECT s.kode_soal, 
               CASE WHEN COUNT(q.id_soal) > 0 THEN 1 ELSE 0 END as has_uraian
        FROM soal s
        LEFT JOIN butir_soal q ON s.kode_soal = q.kode_soal AND q.tipe_soal = 'uraian'
        WHERE s.kode_soal IN ($kodeSoalString)
        GROUP BY s.kode_soal
    ");

    $result = [];
    while ($row = mysqli_fetch_assoc($query)) {
        $result[$row['kode_soal']] = (bool)$row['has_uraian'];
    }

    header('Content-Type: application/json');
    echo json_encode($result);
} else {
    echo json_encode([]);
}
