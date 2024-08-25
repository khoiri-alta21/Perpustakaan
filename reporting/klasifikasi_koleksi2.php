<?php
// Periksa izin akses
defined('INDEX_AUTH') OR die('Direct access not allowed!');

// Fungsi utama untuk menampilkan laporan
function showKlasifikasiKoleksi() {
    global $dbs;
    
    // Ambil data klasifikasi koleksi dari database
    $sql = "SELECT classification, COUNT(*) as total FROM biblio GROUP BY classification ORDER BY total DESC";
    $query = $dbs->query($sql);
    $klasifikasi_data = [];
    while ($row = $query->fetch_assoc()) {
        $klasifikasi_data[] = $row;
    }

    // Konversi data untuk Chart.js
    $labels = array_column($klasifikasi_data, 'classification');
    $data = array_column($klasifikasi_data, 'total');

    // Tampilkan laporan
    ob_start();
    ?>
    <div class="menuBox">
        <div class="menuBoxInner reportIcon">
            <div class="per_title">
                <h2>Laporan Klasifikasi Koleksi</h2>
            </div>
        </div>
    </div>

    <div id="chartContainer" style="width: 100%; height: 400px;">
        <canvas id="klasifikasiChart"></canvas>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('klasifikasiChart').getContext('2d');
        var chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Jumlah Koleksi',
                    data: <?= json_encode($data) ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Jumlah Koleksi'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Klasifikasi'
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Laporan Klasifikasi Koleksi'
                    }
                }
            }
        });
    });
    </script>
    <?php
    $content = ob_get_clean();
    return $content;
}

// Panggil fungsi jika tidak ada aksi khusus
if (isset($_GET['action']) && $_GET['action'] == 'detail') {
    die();
} else {
    echo showKlasifikasiKoleksi();
}