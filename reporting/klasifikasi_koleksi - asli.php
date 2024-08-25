<?php
// Pastikan pengguna memiliki akses
defined('INDEX_AUTH') OR die('Direct access not allowed!');

// Sertakan file konfigurasi dan fungsi yang diperlukan
require SIMBIO.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO.'simbio_GUI/form_maker/simbio_form_table_AJAX.inc.php';
require SIMBIO.'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO.'simbio_DB/datagrid/simbio_dbgrid.inc.php';

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