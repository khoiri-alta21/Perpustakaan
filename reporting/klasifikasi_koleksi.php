<?php
// key to authentication
define('INDEX_AUTH', '1');

// main system configuration
require '../../../sysconfig.inc.php';

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
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

<style>
    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 15px;
        padding: 0 20px;
    }

    .total-collection, .export-btn {
        font-size: 18px;
        font-weight: bold;
        color: #333;
        padding: 10px 15px;
        background-color: #f0f0f0;
        border-radius: 5px;
        box-shadow: 2px 2px 5px rgba(0,0,0,0.1);
    }

    .total-collection-value {
        color: #333;
        font-size: 20px;
    }

    .export-btn {
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
    }

    .export-btn:hover {
        color: #007bff;
        box-shadow: 2px 2px 5px rgba(0, 123, 255, 0.5);
    }
</style>

<div class="menuBox">
    <div class="menuBoxInner statisticIcon">
        <div class="per_title">
            <h2><?php echo __('Klasifikasi Koleksi'); ?></h2>
        </div>
    </div>
</div>

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
?>

<div class="header-container">
    <div class="total-collection">
        Total Koleksi: <span class="total-collection-value"><?php echo number_format($total_keseluruhan, 0, ',', '.'); ?></span>
    </div>

    <button id="downloadPDF" class="export-btn">
        Unduh Laporan
    </button>
</div>

<div id="chartContainer" style="width: 100%; max-width: 1000px; height: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; background-color: #fff; text-align: center;">
    <canvas id="klasifikasiChart"></canvas>
    <div id="chartStatus" style="margin-top: 10px; color: #666;"></div>
</div>

<script>
const loadChartJS = (callback) => {
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js';
    script.onload = callback;
    document.head.appendChild(script);
};

const createChart = () => {
    const ctx = document.getElementById('klasifikasiChart');

    const labels = <?php echo json_encode($labels); ?>;
    const data = <?php echo json_encode($data); ?>;
    const totalKeseluruhan = <?php echo $total_keseluruhan; ?>;

    // Definisikan posisi kustom
    Chart.Tooltip.positioners.myCustomPositioner = function(elements, eventPosition) {
        const tooltip = this;
        const chart = tooltip._chart;
        const { x, y } = eventPosition;

        // Logika untuk menentukan posisi tooltip lebih dekat ke grafik
        return {
            x: x - 50, // Sesuaikan nilai x untuk mengatur posisi horizontal
            y: y - 50  // Sesuaikan nilai y untuk mengatur posisi vertikal
        };
    };

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
                        position: 'myCustomPositioner', // Menggunakan posisi kustom
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

                                ctx.fillText(percent, model.x + x, model.y + y - 8);
                                ctx.fillText(value, model.x + x, model.y + y + 8);

                                ctx.restore();
                            }
                        });
                    });
                }
            }]
        });

    } catch (error) {
        if (chartStatus) chartStatus.innerHTML = '' + error.message;
    }
};

loadChartJS(() => {
    createChart();
});

// Fungsi untuk mengunduh PDF
function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'mm', 'a4');
    const chartContainer = document.getElementById('chartContainer');
    const table = document.querySelector('.s-table');
    const totalCollection = document.querySelector('.total-collection').textContent;

    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();

    doc.setFontSize(16);
    doc.text('Laporan Klasifikasi Koleksi', pageWidth / 2, 15, { align: 'center' });

    // Menambahkan Total Koleksi ke PDF
    doc.setFontSize(14);
    doc.text(totalCollection, pageWidth / 2, 25, { align: 'center' });

    const scale = 2; // Meningkatkan skala untuk kualitas yang lebih baik

    html2canvas(chartContainer, { scale: scale }).then((canvas) => {
        const imgData = canvas.toDataURL('image/png');
        const imgProps = doc.getImageProperties(imgData);
        const pdfWidth = pageWidth - 40; // Mengurangi lebar untuk memberikan margin
        const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
        
        // Menempatkan grafik di tengah
        const x = (pageWidth - pdfWidth) / 2;
        const y = 35;
        
        doc.addImage(imgData, 'PNG', x, y, pdfWidth, pdfHeight);

        html2canvas(table, { scale: scale }).then((canvas) => {
            const imgData = canvas.toDataURL('image/png');
            const imgProps = doc.getImageProperties(imgData);
            const tableWidth = pageWidth - 20;
            const tableHeight = (imgProps.height * tableWidth) / imgProps.width;
            
            // Posisikan tabel di bawah grafik
            const tableY = y + pdfHeight + 10;
            
            // Jika tabel melebihi halaman pertama, tambahkan halaman baru
            if (tableY + tableHeight > pageHeight) {
                doc.addPage();
                doc.addImage(imgData, 'PNG', 10, 10, tableWidth, tableHeight);
            } else {
                doc.addImage(imgData, 'PNG', 10, tableY, tableWidth, tableHeight);
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
echo '<h3 style="text-align: center; margin-top: 30px; font-size: 18px;">'.__('Tabel Klasifikasi Koleksi').'</h3>';
echo '<table class="s-table table" style="width: 80%; margin: 0 auto; border-collapse: collapse; font-size: 14px;">';
echo '<tr style="background-color: #f2f2f2;"><th style="border: 1px solid #ddd; padding: 8px;">No.</th><th style="border: 1px solid #ddd; padding: 8px;">'.__('Klasifikasi').'</th><th style="border: 1px solid #ddd; padding: 8px;">'.__('Total').'</th></tr>';
$no = 1;
foreach ($klasifikasi_data as $group => $data) {
    echo '<tr>';
    echo '<td style="border: 1px solid #ddd; padding: 8px;">'.$no.'</td>';
    echo '<td style="border: 1px solid #ddd; padding: 8px;">'.$data['name'].'</td>';
    echo '<td style="border: 1px solid #ddd; padding: 8px;">'.$data['total'].'</td>';
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
