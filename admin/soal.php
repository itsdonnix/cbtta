<?php
session_start();
include '../koneksi/koneksi.php';
include '../inc/functions.php';
check_login('admin');
include '../inc/dataadmin.php';
$query = "
    SELECT 
        s.id_soal, s.kode_soal, s.nama_soal, s.mapel, s.kelas, s.rombel, s.tampilan_soal, s.status, s.tanggal, s.waktu_ujian, s.token,
        COUNT(b.id_soal) AS jumlah_butir
    FROM soal s
    LEFT JOIN butir_soal b ON s.kode_soal = b.kode_soal
    GROUP BY s.id_soal, s.kode_soal, s.nama_soal, s.mapel, s.kelas, s.rombel, s.status, s.tanggal, s.waktu_ujian, s.token
";

$result = mysqli_query($koneksi, $query);

// Check if the query was successful
if (!$result) {
    // If there's an error with the query, display the error message
    die('Error with the query: ' . mysqli_error($koneksi));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Soal</title>
    <?php include '../inc/css.php'; ?>
    <style>
        .table-wrapper {
            overflow-x: auto;
            /* Enable horizontal scrolling */
            -webkit-overflow-scrolling: touch;
            /* Smooth scrolling for mobile */
        }

        table th,
        table td {
            text-align: left !important;
        }
        
        /* Copy button styling */
        .copy-btn {
            opacity: 0;
            transition: opacity 0.2s ease-in-out;
            cursor: pointer;
            color: #6c757d;
            padding: 0 5px;
            font-size: 14px;
        }
        
        .copy-btn:hover {
            color: #007bff;
        }
        
        .kode-soal-cell, .token-cell {
            position: relative;
            white-space: nowrap;
        }
        
        .kode-soal-cell:hover .copy-btn,
        .token-cell:hover .copy-btn {
            opacity: 1;
        }
        
        .kode-soal-text, .token-text {
            margin-right: 5px;
        }
        
        /* Toast notification for copy */
        .copy-toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            z-index: 9999;
            display: none;
            animation: slideIn 0.3s ease-in-out;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
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
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Daftar Soal</h5>
                                </div>
                                <div class="card-body table-wrapper">
                                    <a href="tambah_soal.php" class="btn btn-primary mb-3"><i class="fas fa-plus"></i>
                                        Tambah Soal Baru</a>
                                    <table id="soalTable" class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Kode Soal</th>
                                                <th>Mapel</th>
                                                <th>Kelas</th>
                                                <th>Jml Soal</th>
                                                <th>Durasi (menit)</th>
                                                <th>Tgl Ujian</th>
                                                <th>Tampilan</th>
                                                <th>Status</th>
                                                <th>Token</th>
                                                <th>Generate</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1;
                                            while ($row = mysqli_fetch_assoc($result)) { ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td class="kode-soal-cell">
                                                        <span class="kode-soal-text"><?php echo $row['kode_soal']; ?></span>
                                                        <span class="copy-btn" onclick="copyToClipboard('<?php echo $row['kode_soal']; ?>', 'kode soal')" title="Salin kode soal">
                                                            <i class="fa fa-copy"></i>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $row['mapel']; ?></td>
                                                    <td>
                                                        <?php
                                                        // Display kelas alone if rombel is empty, otherwise show "kelas rombel"
                                                        if (!empty($row['rombel'])) {
                                                            echo $row['kelas'] . ' ' . $row['rombel'];
                                                        } else {
                                                            echo $row['kelas'];
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo $row['jumlah_butir']; ?></td>
                                                    <td><i class="fa fa-clock" aria-hidden="true"></i>
                                                        <?php echo $row['waktu_ujian']; ?></td>
                                                    <td><i class="fa fa-calendar-alt" aria-hidden="true"></i>
                                                        <?php echo date('d M Y', strtotime($row['tanggal'])); ?></td>
                                                    <td><?php echo $row['tampilan_soal']; ?></td>
                                                    <td>
                                                        <?php if ($row['status'] == 'Aktif') { ?>
                                                            <span class="badge bg-success">Aktif</span>
                                                        <?php } else { ?>
                                                            <span class="badge bg-danger">Nonaktif</span>
                                                        <?php } ?>
                                                    </td>
                                                    <td class="token-cell">
                                                        <?php if (!empty($row['token'])) { ?>
                                                            <span class="token-text"><?php echo $row['token']; ?></span>
                                                            <span class="copy-btn" onclick="copyToClipboard('<?php echo $row['token']; ?>', 'token')" title="Salin token">
                                                                <i class="fa fa-copy"></i>
                                                            </span>
                                                        <?php } else { ?>
                                                            <span class="text-muted">-</span>
                                                        <?php } ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($row['status'] == 'Aktif') { ?>
                                                            <a href="generate_token.php?id_soal=<?php echo $row['id_soal']; ?>"
                                                                class="btn btn-sm btn-outline-secondary"><i
                                                                    class="fa fa-history"></i> Token</a>
                                                        <?php } else { ?>
                                                        <?php } ?>
                                                        <?php if ($row['status'] == 'Aktif') { ?>
                                                            <a href="ubah_status_soal.php?id_soal=<?= $row['id_soal']; ?>&aksi=nonaktif"
                                                                class="btn btn-sm btn-secondary"><i class="fa fa-toggle-off"
                                                                    aria-hidden="true"></i> Nonaktifkan</a>
                                                        <?php } else { ?>
                                                            <a href="ubah_status_soal.php?id_soal=<?= $row['id_soal']; ?>&aksi=aktif"
                                                                class="btn btn-sm btn-info"><i class="fa fa-toggle-on"
                                                                    aria-hidden="true"></i> Aktifkan</a>
                                                        <?php } ?>
                                                    </td>
                                                    <td>
                                                        <a href="preview_soal.php?kode_soal=<?php echo $row['kode_soal']; ?>"
                                                            class="btn btn-sm btn-outline-secondary"><i
                                                                class="fa fa-eye"></i> Preview</a>
                                                        <a href="edit_soal.php?id_soal=<?php echo $row['id_soal']; ?>"
                                                            class="btn btn-sm btn-primary"><i class="fa fa-edit"></i>
                                                            Edit</a>
                                                        <a href="#" class="btn btn-sm btn-info btn-duplicate" data-kode="<?php echo $row['kode_soal']; ?>">
                                                            <i class="fa fa-copy"></i> Duplikat
                                                        </a>
                                                        <a href="daftar_butir_soal.php?kode_soal=<?php echo $row['kode_soal']; ?>"
                                                            class="btn btn-sm btn-success"><i class="fa fa-plus"></i> Input
                                                            Soal</a>
                                                        <button class="btn btn-danger btn-sm btn-hapus"
                                                            data-kode="<?= $row['kode_soal']; ?>">
                                                            <i class="fa fa-close" aria-hidden="true"></i>
                                                            Hapus
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Toast notification for copy -->
    <div id="copyToast" class="copy-toast">
        <i class="fa fa-check-circle"></i> <span id="toastMessage">Berhasil disalin!</span>
    </div>
    
    <?php include '../inc/js.php'; ?>
    <script>
        // Function to copy text to clipboard
        function copyToClipboard(text, type) {
            // Create a temporary textarea element
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            
            // Select and copy the text
            textarea.select();
            textarea.setSelectionRange(0, 99999); // For mobile devices
            
            try {
                // Execute copy command
                document.execCommand('copy');
                
                // Update toast message based on what was copied
                const toastMessage = document.getElementById('toastMessage');
                if (type === 'kode soal') {
                    toastMessage.textContent = 'Kode soal berhasil disalin!';
                } else {
                    toastMessage.textContent = 'Token berhasil disalin!';
                }
                
                // Show toast notification
                const toast = document.getElementById('copyToast');
                toast.style.display = 'block';
                
                // Hide toast after 2 seconds
                setTimeout(() => {
                    toast.style.display = 'none';
                }, 2000);
            } catch (err) {
                console.error('Failed to copy text: ', err);
                
                // Show error notification
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: `Gagal menyalin ${type}`,
                    timer: 1500,
                    showConfirmButton: false
                });
            }
            
            // Remove the temporary textarea
            document.body.removeChild(textarea);
        }

        // Tambahkan di bagian script yang sudah ada
        document.querySelectorAll('.btn-duplicate').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const oldKode = this.getAttribute('data-kode');

                Swal.fire({
                    title: 'Duplikasi Soal',
                    input: 'text',
                    inputLabel: 'Masukkan Kode Soal Baru',
                    inputPlaceholder: 'Kode unik untuk soal duplikat',
                    showCancelButton: true,
                    confirmButtonText: 'Duplikat',
                    cancelButtonText: 'Batal',
                    inputValidator: (value) => {
                        if (!value) {
                            return 'Kode soal baru harus diisi!';
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const newKode = result.value;

                        // Kirim permintaan AJAX
                        fetch('duplicate_soal.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `old_kode=${encodeURIComponent(oldKode)}&new_kode=${encodeURIComponent(newKode)}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil!',
                                        text: data.message,
                                        timer: 2000,
                                        showConfirmButton: false
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal!',
                                        text: data.message
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire('Error', 'Terjadi kesalahan saat memproses permintaan.', 'error');
                            });
                    }
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTables
            $(document).ready(function() {
                $('#soalTable').DataTable({
                    paging: true,
                    lengthChange: true,
                    searching: true,
                    ordering: true,
                    info: true,
                    autoWidth: false,
                    responsive: true
                });
            });
            
            document.querySelectorAll('.btn-hapus').forEach(function(button) {
                button.addEventListener('click', function() {
                    const kodeSoal = this.getAttribute('data-kode');

                    Swal.fire({
                        title: 'Konfirmasi Hapus',
                        html: 'Ketik <strong>HAPUS</strong> untuk menghapus data soal ini.',
                        input: 'text',
                        inputPlaceholder: 'Ketik HAPUS di sini',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Hapus',
                        confirmButtonColor: '#d33',
                        cancelButtonText: 'Batal',
                        preConfirm: (inputValue) => {
                            if (inputValue !== 'HAPUS') {
                                Swal.showValidationMessage(
                                    'Anda harus mengetik "HAPUS" dengan benar (huruf besar semua)'
                                );
                            }
                            return inputValue;
                        }
                    }).then((result) => {
                        if (result.isConfirmed && result.value === 'HAPUS') {
                            window.location.href = 'hapus_soal.php?kode_soal=' +
                                encodeURIComponent(kodeSoal);
                        }
                    });
                });
            });


        });
    </script>
    <?php if (isset($_SESSION['success'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: '<?php echo addslashes($_SESSION['success']); ?>',
                showConfirmButton: false,
                timer: 2000
            });
        </script>
    <?php unset($_SESSION['success']);
    endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: '<?php echo addslashes($_SESSION['error']); ?>',
                showConfirmButton: false,
                timer: 2000
            });
        </script>
    <?php unset($_SESSION['error']);
    endif; ?>
    <?php if (isset($_SESSION['success_message'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Berhasil!',
                    text: '<?php echo $_SESSION['success_message']; ?>',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            });
        </script>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['warning_message'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tidak Bisa Diedit!',
                    text: '<?php echo $_SESSION['warning_message']; ?>',
                    showConfirmButton: false,
                    timer: 2000
                });
            });
        </script>
        <?php unset($_SESSION['warning_message']); ?>
    <?php endif; ?>
</body>

</html>
