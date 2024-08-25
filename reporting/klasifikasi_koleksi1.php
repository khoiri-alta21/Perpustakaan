<?php
// Koneksi ke database
require 'config.php'; // Pastikan file ini berisi informasi koneksi database

// Ambil data dari database
$query = "SELECT classification, COUNT(*) AS jumlah FROM biblio GROUP BY classification";
$result = $koneksi_database->query($query);

$data_grafik = [];
$total_koleksi_buku = 0; // Inisialisasi total koleksi buku

while ($row = $result->fetch_assoc()) {
    $data_grafik[] = $row;
    $total_koleksi_buku += $row['jumlah']; // Tambahkan jumlah buku ke total
}

// Inisialisasi variabel untuk menghitung jumlah buku per klasifikasi
$karya_umum = 0;
$filsafat_psikologi = 0;
$agama = 0;
$ilmu_sosial = 0;
$bahasa = 0;
$ilmu_alam_matematika = 0;
$teknologi_terapan = 0;
$seni_hiburan_olahraga = 0;
$kesusastraan = 0;
$geografi_sejarah = 0;

// Klasifikasikan data
foreach ($data_grafik as $data) {
    $classification = intval($data['classification']);
    
    if ($classification >= 0 && $classification < 100) {
        $karya_umum += $data['jumlah'];
    } elseif ($classification >= 100 && $classification < 200) {
        $filsafat_psikologi += $data['jumlah'];
    } elseif ($classification >= 200 && $classification < 300) {
        $agama += $data['jumlah'];
    } elseif ($classification >= 300 && $classification < 400) {
        $ilmu_sosial += $data['jumlah'];
    } elseif ($classification >= 400 && $classification < 500) {
        $bahasa += $data['jumlah'];
    } elseif ($classification >= 500 && $classification < 600) {
        $ilmu_alam_matematika += $data['jumlah'];
    } elseif ($classification >= 600 && $classification < 700) {
        $teknologi_terapan += $data['jumlah'];
    } elseif ($classification >= 700 && $classification < 800) {
        $seni_hiburan_olahraga += $data['jumlah'];
    } elseif ($classification >= 800 && $classification < 900) {
        $kesusastraan += $data['jumlah'];
    } elseif ($classification >= 900 && $classification <= 1000) {
        $geografi_sejarah += $data['jumlah'];
    }
}

// Siapkan data untuk grafik
$koleksi_buku_per_klasifikasi = [
    ['Karya Umum', $karya_umum],
    ['Filsafat dan Psikologi', $filsafat_psikologi],
    ['Agama', $agama],
    ['Ilmu Sosial', $ilmu_sosial],
    ['Bahasa', $bahasa],
    ['Ilmu Alam dan Matematika', $ilmu_alam_matematika],
    ['Teknologi dan Ilmu Terapan', $teknologi_terapan],
    ['Seni, Hiburan, dan Olahraga', $seni_hiburan_olahraga],
    ['Kesusastraan', $kesusastraan],
    ['Geografi dan Sejarah', $geografi_sejarah],
];

// Hitung persentase untuk setiap klasifikasi
foreach ($koleksi_buku_per_klasifikasi as &$item) {
    $item[2] = round(($item[1] / $total_koleksi_buku) * 100); // Tambahkan persentase ke array
}

// Mengonversi data PHP ke format JSON untuk digunakan oleh JavaScript
$json_data_klasifikasi = json_encode($koleksi_buku_per_klasifikasi);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik Klasifikasi Koleksi Buku</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        #infoContainer {
            width: 100%;
            text-align: left;
            margin-bottom: 20px;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        #totalKoleksiBuku {
            font-size: 16px;
            font-weight: bold;
            color: #fff;
            background-color: #007bff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            display: inline-block;
            border: 1px solid #0056b3;
            margin-top: 30px;
            margin-right: 30px;
            margin-left: 10px;
            margin-bottom: 20px;
        }
        #exportContainer {
            position: relative;
            display: inline-block;
        }
        #exportButton {
            padding: 10px 20px;
            font-size: 16px;
            color: #fff;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        #exportButton:hover {
            background-color: #0056b3;
        }
        #chartWrapper {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
        }
        #chartTitle {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            background: #fff;
            padding: 10px;
            border: 2px solid #007bff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        .chart {
            width: 100%;
            height: 500px;
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.1);
            background: #f9f9f9;
            padding: 0;
            box-sizing: border-box;
            border: 2px solid #007bff;
            border-radius: 10px;
        }
        .chart .chartjs-legend li {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .chart .chartjs-legend li span {
            width: 15px; /* Ukuran bulat ikon */
            height: 15px; /* Ukuran bulat ikon */
            border-radius: 50%; /* Membuat ikon bulat */
            display: inline-block;
            margin-right: 10px; /* Jarak antara ikon dan label */
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0/dist/chartjs-plugin-datalabels.min.js"></script>

    <script>
        const ChartDataLabels = window.ChartDataLabels;
    </script>
</head>
<body>
    <!-- Info Container -->
    <div id="infoContainer">
        <!-- Total Koleksi Buku -->
        <div id="totalKoleksiBuku">Total Koleksi Buku: <?php echo $total_koleksi_buku; ?></div>

        <!-- Tombol Export -->
        <div id="exportContainer">
            <button id="exportButton">Export</button>
        </div>
    </div>

    <!-- Chart Wrapper -->
    <div id="chartWrapper">
        <!-- Judul Grafik -->
        <div id="chartTitle">Grafik Klasifikasi Koleksi Buku</div>
        <!-- Grafik Pie -->
        <div class="chart">
            <canvas id="pieChart"></canvas>
        </div>
    </div>

    <script>
        const originalData = <?php echo $json_data_klasifikasi; ?>;

        let pieChart;

        function createPieChart(data) {
    const ctx = document.getElementById('pieChart').getContext('2d');

    if (pieChart) {
        pieChart.destroy();
    }

    pieChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.map(item => item[0]),
            datasets: [{
                label: 'Jumlah Buku',
                data: data.map(item => item[1]),
                backgroundColor: [
                    '#F4A261',
                    '#2A9D8F',
                    '#E76F51',
                    '#F9C74F',
                    '#90BE6D',
                    '#577590',
                    '#FF6B6B',
                    '#6D28D9',
                    '#3B82F6',
                    '#34D399'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 15, /* Ukuran kotak ikon legenda */
                        padding: 10 /* Jarak antara ikon dan label */
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                            const percentage = ((value / total) * 100).toFixed(1); // Calculate percentage

                            return `${value} (${percentage}%)`; // Tooltip format tanpa label
                        }
                    }
                },
                datalabels: {
                    color: '#000',
                    display: true,
                    formatter: (value, ctx) => {
                        const total = ctx.dataset.data.reduce((acc, val) => acc + val, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return `${percentage}%`;
                    },
                    anchor: 'end',
                    align: 'center',
                    font: {
                        weight: 'bold',
                        size: 14
                    },
                    offset: 10,
                    color: '#333'
                }
            },
            layout: {
                padding: {
                    right: 30 // Jarak antara legend dan grafik
                }
            },
            hover: {
                onHover: function(e) {
                    const chart = pieChart;
                    const point = chart.getElementsAtEventForMode(e, 'nearest', { intersect: true }, true);
                    if (point.length) {
                        const datasetIndex = point[0].datasetIndex;
                        const index = point[0].index;
                        const meta = chart.getDatasetMeta(datasetIndex);
                        const element = meta.data[index];
                        element.$context.hovered = true;
                        chart.update();
                    }
                }
            },
            animation: {
                animateRotate: true,
                animateScale: true
            },
            elements: {
                arc: {
                    borderWidth: 1,
                    borderColor: '#fff',
                    hoverBorderWidth: 2,
                    hoverBorderColor: '#000',
                    hoverShadowColor: '#000',
                    hoverShadowBlur: 15,
                }
            }
        },
        plugins: [ChartDataLabels] // Tambahkan plugin ChartDataLabels
    });
}
        // Load ChartDataLabels plugin
        (function() {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0/dist/chartjs-plugin-datalabels.min.js';
            document.head.appendChild(script);
            script.onload = function() {
                createPieChart(originalData);
            };
        })();

        document.getElementById('exportButton').addEventListener('click', function() {
            html2canvas(document.querySelector('#chartWrapper')).then(canvas => {
                const dataURL = canvas.toDataURL('image/png');
                const a = document.createElement('a');
                a.href = dataURL;
                a.download = 'chart_with_title.png';
                a.click();
            });
        });
    </script>
</body>
</html>
