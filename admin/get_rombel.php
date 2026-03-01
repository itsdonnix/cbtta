<?php
session_start();
include '../koneksi/koneksi.php';
include '../inc/functions.php';

// Check if admin is logged in
check_login('admin');

if (isset($_POST['kelas']) && !empty($_POST['kelas'])) {
    $kelas = mysqli_real_escape_string($koneksi, $_POST['kelas']);
    
    // Query to get distinct rombel for the selected kelas
    $query = "SELECT DISTINCT rombel FROM siswa WHERE kelas = '$kelas' AND rombel IS NOT NULL AND rombel != '' ORDER BY rombel ASC";
    $result = mysqli_query($koneksi, $query);
    
    $rombel_list = array();
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rombel_list[] = $row['rombel'];
        }
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($rombel_list);
} else {
    // Return empty array if no kelas selected
    header('Content-Type: application/json');
    echo json_encode([]);
}
?>
