<?php
session_start();
include '../koneksi/koneksi.php';
include '../inc/functions.php';
check_login('siswa');
include '../inc/datasiswa.php'; // Mengandung $id_siswa

// Cek status sembunyikan_nilai dari tabel pengaturan
$cek = mysqli_query($koneksi, "SELECT sembunyikan_nilai FROM pengaturan LIMIT 1");
$row_cek = mysqli_fetch_assoc($cek);
$sembunyikan_nilai = (int) $row_cek['sembunyikan_nilai'];

// Ambil hasil ujian siswa, join dengan jawaban_siswa untuk jenis_ujian
$query = mysqli_query($koneksi, "
    SELECT 
        n.id_siswa,
        n.kode_soal,
        s.mapel,
        n.nilai, n.nilai_uraian,
        n.tanggal_ujian,
        m.nama_siswa,
        j.jenis_ujian
    FROM nilai n
    JOIN soal s ON n.kode_soal = s.kode_soal
    JOIN siswa m ON n.id_siswa = m.id_siswa
    LEFT JOIN jawaban_siswa j ON n.id_siswa = j.id_siswa AND n.kode_soal = j.kode_soal
    WHERE n.id_siswa = '$id_siswa'
    ORDER BY n.tanggal_ujian DESC
");

$data = [];
while ($row = mysqli_fetch_assoc($query)) {
    $nilai_otomatis = $row['nilai'];
    $nilai_uraian   = $row['nilai_uraian'];
    $nilai_akhir    = $nilai_otomatis + $nilai_uraian;
    $nilai_display  = $sembunyikan_nilai ? '-' : $nilai_akhir;

    // jenis ujian: value + label
    $jenis_value = ($row['jenis_ujian'] == 1) ? 'susulan' : 'utama';
    $jenis_label = ($row['jenis_ujian'] == 1) ? 'Susulan' : 'Utama';

    $data[] = [
        'id_siswa'           => $row['id_siswa'],
        'nama_siswa'         => $row['nama_siswa'],
        'kode_soal'          => $row['kode_soal'],
        'mapel'              => $row['mapel'],
        'jenis_ujian'        => $jenis_label,           // for display
        'jenis_ujian_value'  => $jenis_value,           // for logic / URL
        'nilai_uraian'       => $row['nilai_uraian'],   // for checking in JS
        'nilai'              => $nilai_display,
        'tanggal_ujian'      => date('d M Y, H:i', strtotime($row['tanggal_ujian']))
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'data' => $data,
    'sembunyikan_nilai' => $sembunyikan_nilai
]);
