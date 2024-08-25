<?php
/**
 * Copyright (C) 2007,2008  Arie Nugraha (dicarve@yahoo.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

/* Laporan Klasifikasi Koleksi */

// key to authentication
define('INDEX_AUTH', '1');

// main system configuration

require '../../../sysconfig.inc.php';

// start the session
require SB.'admin/default/session.inc.php';
require SB.'admin/default/session_check.inc.php';
require SIMBIO.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO.'simbio_GUI/form_maker/simbio_form_table_AJAX.inc.php';
require SIMBIO.'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO.'simbio_DB/datagrid/simbio_dbgrid.inc.php';
require SIMBIO.'simbio_DB/simbio_dbop.inc.php';

// privileges checking
$can_read = utility::havePrivilege('reporting', 'r');
$can_write = utility::havePrivilege('reporting', 'w');

/* Klasifikasi koleksi statistic */
$page_title = __('Laporan Klasifikasi Koleksi');

// Definisi klasifikasi
$classification_names = [
    '0' => '000-099 Umum',
    '1' => '100-199 Filsafat dan psikologi',
    '2' => '200-299 Agama',
    '3' => '300-399 Ilmu sosial',
    '4' => '400-499 Bahasa',
    '5' => '500-599 Sains dan matematika',
    '6' => '600-699 Teknologi',
    '7' => '700-799 Kesenian dan rekreasi',
    '8' => '800-899 Sastra',
    '9' => '900-999 Sejarah dan geografi'
];

ob_start();
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

<style>
    .export-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        padding: 10px 20px;
        background-color: #4CAF50;
        color: white;
        border: none;
        border-radius: 25px;
        cursor: pointer;
        font-size: 16px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .export-btn:hover {
        background-color: #45a049;
        transform: translateY(-2px);
        box-shadow: 0 6px 8px rgba(0,0,0,0.15);
    }
    .export-btn:active {
        transform: translateY(0);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
</style>



<div class="menuBox">
    <div class="menuBoxInner statisticIcon">
        <div class="per_title">
            <h2><?php echo __('Klasifikasi Koleksi'); ?></h2>
        </div>
    </div>
</div>

<button id="downloadPDF" class="export-btn">
    Export
</button>

<?php
// Query untuk mengambil data klasifikasi dari database
$sql = "SELECT LEFT(classification, 1) AS class_group, COUNT(*) AS total
        FROM biblio
        WHERE classification IS NOT NULL AND classification != ''
        GROUP BY class_group
        ORDER BY class_group";
$query = $dbs->query($sql);

$klasifikasi_data = [];
while ($data = $query->fetch_assoc()) {
    $class_group = $data['class_group'];
    if (isset($classification_names[$class_group])) {
        $klasifikasi_data[$class_group] = [
            'name' => $classification_names[$class_group],
            'total' => $data['total']
        ];
    }
}

// Persiapkan data untuk grafik
$labels = array_column($klasifikasi_data, 'name');
$data = array_column($klasifikasi_data, 'total');

// Hitung total keseluruhan
$total_keseluruhan = array_sum($data);

// Tampilkan grafik
?>
<div id="chartContainer" style="width: 100%; max-width: 1000px; height: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; background-color: #fff; text-align: center;">
    <canvas id="klasifikasiChart"></canvas>
    <div id="chartStatus" style="margin-top: 10px; color: #666;"></div>
</div>

<script>
const loadChartJS = (callback) => {
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js';
    script.integrity = 'sha512-ElRFoEQdI5Ht6kZvyzXhYG9NqjtkmlkfYk0wr6wHxU9JEHakS7UJZNeml5ALk+8IKlU6jDgMabC3vkumRokgJA==';
    script.crossOrigin = 'anonymous';
    script.referrerPolicy = 'no-referrer';
    script.onload = callback;
    script.onerror = () => {
        document.getElementById('chartStatus').innerHTML = 'Gagal memuat Chart.js';
    };
    document.head.appendChild(script);
};

const createChart = () => {
    const ctx = document.getElementById('klasifikasiChart');
    if (!ctx) {
        if (chartStatus) chartStatus.innerHTML = 'Error: Elemen canvas tidak ditemukan';
        return;
    }

    const labels = <?php echo json_encode($labels); ?>;
    const data = <?php echo json_encode($data); ?>;
    const totalKeseluruhan = <?php echo $total_keseluruhan; ?>;

    try {
        const chart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                        'rgba(199, 199, 199, 0.8)',
                        'rgba(83, 102, 255, 0.8)',
                        'rgba(40, 159, 64, 0.8)',
                        'rgba(210, 199, 199, 0.8)'
                    ],
                    borderColor: 'rgba(255, 255, 255, 1)',
                    borderWidth: 2,
                    hoverOffset: 10,
                    shadowOffsetX: 3,
                    shadowOffsetY: 3,
                    shadowBlur: 10,
                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: {
                                size: 14,
                                weight: 'bold'
                            },
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'rectRounded'
                        }
                    },
                    title: {
                        display: true,
                        text: <?php echo json_encode(__('Grafik Klasifikasi Koleksi')); ?>,
                        font: {
                            size: 20,
                            weight: 'bold'
                        },
                        padding: {
                            top: 10,
                            bottom: 30
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const simplifiedLabel = label.split(' ').slice(1).join(' ');
                                return simplifiedLabel + ': ' + value;
                            }
                        },
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 14
                        },
                        padding: 12,
                        cornerRadius: 5
                    }
                },
                layout: {
                    padding: 20
                },
                elements: {
                    arc: {
                        borderWidth: 2,
                        borderColor: 'rgba(255, 255, 255, 1)'
                    }
                }
            },
            plugins: [{
                beforeDraw: (chart) => {
                    const ctx = chart.ctx;
                    ctx.save();
                    ctx.shadowColor = 'rgba(0, 0, 0, 0.5)';
                    ctx.shadowBlur = 10;
                    ctx.shadowOffsetX = 5;
                    ctx.shadowOffsetY = 5;
                },
                afterDraw: (chart) => {
                    const ctx = chart.ctx;
                    ctx.restore();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.font = 'bold 12px Arial';
                    ctx.fillStyle = '#fff';

                    chart.data.datasets.forEach((dataset, datasetIndex) => {
                        chart.getDatasetMeta(datasetIndex).data.forEach((segment, index) => {
                            const model = segment;
                            const mid_radius = (segment.innerRadius + segment.outerRadius) / 2;
                            const start_angle = segment.startAngle;
                            const end_angle = segment.endAngle;
                            const mid_angle = (start_angle + end_angle) / 2;

                            const x = mid_radius * Math.cos(mid_angle);
                            const y = mid_radius * Math.sin(mid_angle);

                            const value = dataset.data[index];
                            const percent = ((value / totalKeseluruhan) * 100).toFixed(2) + '%';

                            if (value > 0) {
                                ctx.save();
                                ctx.shadowColor = 'rgba(0, 0, 0, 0.5)';
                                ctx.shadowBlur = 2;
                                ctx.shadowOffsetX = 1;
                                ctx.shadowOffsetY = 1;

                                ctx.fillText(percent, model.x + x, model.y + y);

                                ctx.restore();
                            }
                        });
                    });
                }
            }]
        });

    } catch (error) {
        if (chartStatus) chartStatus.innerHTML = 'Error membuat grafik: ' + error.message;
    }
};

loadChartJS(() => {
    createChart();
});

// Fungsi untuk mengunduh PDF
function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'mm', 'a4'); // Orientasi potrait
    const chartContainer = document.getElementById('chartContainer');
    const table = document.querySelector('.s-table');

    // Tambahkan judul
    doc.setFontSize(16);
    doc.text('Laporan Klasifikasi Koleksi', 105, 15, null, null, 'center');

    // Tambahkan grafik
    html2canvas(chartContainer).then((canvas) => {
        const imgData = canvas.toDataURL('image/png');
        const imgProps = doc.getImageProperties(imgData);
        const pdfWidth = doc.internal.pageSize.getWidth() - 20; // Lebar penuh dikurangi margin
        const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
        doc.addImage(imgData, 'PNG', 10, 25, pdfWidth, pdfHeight);

        // Tambahkan tabel
        html2canvas(table).then((canvas) => {
            const imgData = canvas.toDataURL('image/png');
            const imgProps = doc.getImageProperties(imgData);
            const pdfWidth = doc.internal.pageSize.getWidth() - 20; // Lebar penuh dikurangi margin
            const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
            
            // Cek apakah perlu halaman baru
            if (doc.internal.getCurrentPageInfo().pageNumber === 1 && 25 + pdfHeight + pdfHeight > doc.internal.pageSize.getHeight()) {
                doc.addPage();
                doc.addImage(imgData, 'PNG', 10, 10, pdfWidth, pdfHeight);
            } else {
                doc.addImage(imgData, 'PNG', 10, 25 + pdfHeight + 10, pdfWidth, pdfHeight);
            }

            // Simpan PDF
            doc.save('laporan_klasifikasi_koleksi.pdf');
        });
    });
}

document.getElementById("downloadPDF").addEventListener("click", downloadPDF);
</script>

<?php
// Tampilkan data dalam bentuk tabel
echo '<h3 style="text-align: center; margin-top: 30px;">'.__('Tabel Klasifikasi Koleksi').'</h3>';
echo '<table class="s-table table" style="width: 80%; margin: 0 auto;">';
echo '<tr><th>No.</th><th>'.__('Klasifikasi').'</th><th>'.__('Total').'</th></tr>';
$no = 1;
foreach ($klasifikasi_data as $group => $data) {
    echo '<tr>';
    echo '<td>'.$no.'</td>';
    echo '<td>'.$data['name'].'</td>';
    echo '<td>'.$data['total'].'</td>';
    echo '</tr>';
    $no++;
}
echo '</table>';

$report_result = ob_get_clean();

// Tampilkan hasil sebelum memasukkannya ke dalam template
echo $report_result;

// include the page template
require SB.'/admin/'.$sysconf['admin_template']['dir'].'/printed_page_tpl.php';
?>