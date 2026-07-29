<?php
// admin/barang/export_barang.php
// Export Excel & PDF (compatible with project using PDO getConnection())

// --- load DB connection helper (try a few likely paths) ---
if (file_exists(__DIR__ . '/../../config/database.php')) {
    require_once __DIR__ . '/../../config/database.php';
} elseif (file_exists(__DIR__ . '/../../config/functions.php')) {
    require_once __DIR__ . '/../../config/functions.php';
} else {
    die("Config database tidak ditemukan. Pastikan file config/database.php atau config/functions.php ada.");
}

if (!function_exists('getConnection')) {
    // If your functions.php provides a different function, adapt here.
    // Otherwise try to create a local PDO connection fallback (adjust credentials if needed)
    function getConnection() {
        try {
            $dsn = "mysql:host=localhost;dbname=inventaris_sekolah;charset=utf8mb4";
            $pdo = new PDO($dsn, "root", "", [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            die("Koneksi DB gagal: " . $e->getMessage());
        }
    }
}

$pdo = getConnection();

// Accept either ?export=excel or ?type=excel (compatibility with existing JS)
$exportType = strtolower(trim($_GET['export'] ?? $_GET['type'] ?? ''));

// Accept filters
$search   = trim($_GET['search'] ?? '');
$kategori = trim($_GET['kategori'] ?? '');
$lokasi   = trim($_GET['lokasi'] ?? '');

// Build base query with parameters (PDO)
$sql = "SELECT b.kode_barang, b.nama_barang, k.nama_kategori, l.nama_lokasi,
               b.jumlah_total, b.jumlah_tersedia,
               (b.jumlah_total - b.jumlah_tersedia) AS jumlah_dipinjam,
               b.kondisi, b.tahun_pengadaan, b.harga_perolehan, b.created_at
        FROM barang b
        LEFT JOIN kategori k ON b.kategori_id = k.id
        LEFT JOIN lokasi l ON b.lokasi_id = l.id
        WHERE 1=1";

$params = [];

if ($search !== '') {
    $sql .= " AND (b.nama_barang LIKE :search OR b.kode_barang LIKE :search OR b.deskripsi LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($kategori !== '') {
    $sql .= " AND b.kategori_id = :kategori";
    $params[':kategori'] = $kategori;
}

if ($lokasi !== '') {
    $sql .= " AND b.lokasi_id = :lokasi";
    $params[':lokasi'] = $lokasi;
}

$sql .= " ORDER BY b.nama_barang ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// --- EXPORT: EXCEL (HTML table) ---
if ($exportType === 'excel' || $exportType === 'xls') {
    $filename = "Laporan_Barang_" . date('Ymd_His') . ".xls";
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    // BOM supaya Excel membaca UTF-8 dengan baik
    echo "\xEF\xBB\xBF";

    echo "<table border='1'>";
    echo "<tr>
            <th>No</th>
            <th>Kode Barang</th>
            <th>Nama Barang</th>
            <th>Kategori</th>
            <th>Lokasi</th>
            <th>Jumlah Total</th>
            <th>Jumlah Tersedia</th>
            <th>Jumlah Dipinjam</th>
            <th>Kondisi</th>
            <th>Tahun</th>
            <th>Harga Perolehan</th>
            <th>Tanggal Input</th>
          </tr>";

    $no = 1;
    foreach ($rows as $r) {
        $harga = is_null($r['harga_perolehan']) ? '' : $r['harga_perolehan'];
        $tahun  = is_null($r['tahun_pengadaan']) ? '' : $r['tahun_pengadaan'];
        $created = isset($r['created_at']) ? $r['created_at'] : '';
        echo "<tr>";
        echo "<td>{$no}</td>";
        echo "<td>" . htmlspecialchars($r['kode_barang']) . "</td>";
        echo "<td>" . htmlspecialchars($r['nama_barang']) . "</td>";
        echo "<td>" . htmlspecialchars($r['nama_kategori']) . "</td>";
        echo "<td>" . htmlspecialchars($r['nama_lokasi']) . "</td>";
        echo "<td>" . htmlspecialchars($r['jumlah_total']) . "</td>";
        echo "<td>" . htmlspecialchars($r['jumlah_tersedia']) . "</td>";
        echo "<td>" . htmlspecialchars($r['jumlah_dipinjam']) . "</td>";
        echo "<td>" . htmlspecialchars($r['kondisi']) . "</td>";
        echo "<td>" . htmlspecialchars($tahun) . "</td>";
        echo "<td>" . htmlspecialchars($harga) . "</td>";
        echo "<td>" . htmlspecialchars($created) . "</td>";
        echo "</tr>";
        $no++;
    }

    if (empty($rows)) {
        echo "<tr><td colspan='12' style='text-align:center;'>Tidak ada data</td></tr>";
    }

    echo "</table>";
    exit;
}

// --- EXPORT: PDF (try FPDF first, then Dompdf) ---
if ($exportType === 'pdf') {
    // 1) Try FPDF (local file)
    $fpdfPath = __DIR__ . '/../../fpdf/fpdf.php'; // expected location
    if (file_exists($fpdfPath)) {
        require_once $fpdfPath;

        class PDF extends FPDF {
            // header
            function Header() {
                $this->SetFont('Arial', 'B', 14);
                $this->Cell(0, 8, 'LAPORAN DATA BARANG', 0, 1, 'C');
                $this->SetFont('Arial', '', 9);
                $this->Ln(3);
                // table header
                $this->SetFont('Arial', 'B', 9);
                $this->Cell(8, 7, 'No', 1, 0, 'C');
                $this->Cell(28, 7, 'Kode', 1, 0, 'C');
                $this->Cell(60, 7, 'Nama Barang', 1, 0, 'C');
                $this->Cell(36, 7, 'Kategori', 1, 0, 'C');
                $this->Cell(36, 7, 'Lokasi', 1, 0, 'C');
                $this->Cell(18, 7, 'Total', 1, 0, 'C');
                $this->Cell(18, 7, 'Tersedia', 1, 0, 'C');
                $this->Cell(18, 7, 'Dipinjam', 1, 0, 'C');
                $this->Cell(20, 7, 'Kondisi', 1, 0, 'C');
                $this->Ln();
            }
            // footer
            function Footer() {
                $this->SetY(-15);
                $this->SetFont('Arial','I',8);
                $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
            }
        }

        $pdf = new PDF('L', 'mm', 'A4');
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetFont('Arial','',9);

        $n = 1;
        if ($rows) {
            foreach ($rows as $r) {
                $pdf->Cell(8, 6, $n, 1, 0, 'C');
                $pdf->Cell(28, 6, $r['kode_barang'], 1);
                // MultiCell requires manual position handling for consistent rows — use Cell with truncation
                $pdf->Cell(60, 6, (strlen($r['nama_barang'])>45?substr($r['nama_barang'],0,42).'...':$r['nama_barang']), 1);
                $pdf->Cell(36, 6, (strlen($r['nama_kategori'])>20?substr($r['nama_kategori'],0,17).'...':$r['nama_kategori']), 1);
                $pdf->Cell(36, 6, (strlen($r['nama_lokasi'])>20?substr($r['nama_lokasi'],0,17).'...':$r['nama_lokasi']), 1);
                $pdf->Cell(18, 6, $r['jumlah_total'], 1, 0, 'C');
                $pdf->Cell(18, 6, $r['jumlah_tersedia'], 1, 0, 'C');
                $pdf->Cell(18, 6, $r['jumlah_dipinjam'], 1, 0, 'C');
                $pdf->Cell(20, 6, (strlen($r['kondisi'])>12?substr($r['kondisi'],0,9).'...':$r['kondisi']), 1, 0, 'C');
                $pdf->Ln();
                $n++;
            }
        } else {
            $pdf->Cell(0, 6, 'Tidak ada data', 1, 1, 'C');
        }

        $filename = "Laporan_Barang_" . date('Ymd_His') . ".pdf";
        $pdf->Output('D', $filename);
        exit;
    }

    // 2) Try Dompdf (composer) fallback
    if (class_exists('Dompdf\\Dompdf') || file_exists(__DIR__ . '/../../vendor/autoload.php')) {
        if (!class_exists('Dompdf\\Dompdf')) {
            require_once __DIR__ . '/../../vendor/autoload.php';
        }
        if (!class_exists('Dompdf\\Dompdf')) {
            die("Dompdf tidak ditemukan walau vendor/autoload ada. Jalankan: composer require dompdf/dompdf");
        }

        use Dompdf\Dompdf;
        use Dompdf\Options;

        $html = '<!doctype html><html><head><meta charset="utf-8"><style>
            body{font-family: DejaVu Sans, sans-serif; font-size:12px}
            table{width:100%;border-collapse:collapse}th,td{border:1px solid #000;padding:6px}
            th{background:#eee}
            </style></head><body>';
        $html .= '<h3 style="text-align:center">LAPORAN DATA BARANG</h3>';
        $html .= '<table><thead><tr>
                    <th>No</th><th>Kode</th><th>Nama Barang</th><th>Kategori</th><th>Lokasi</th>
                    <th>Total</th><th>Tersedia</th><th>Dipinjam</th><th>Kondisi</th>
                  </tr></thead><tbody>';
        $n = 1;
        foreach ($rows as $r) {
            $html .= '<tr>';
            $html .= "<td>{$n}</td>";
            $html .= "<td>" . htmlspecialchars($r['kode_barang']) . "</td>";
            $html .= "<td>" . htmlspecialchars($r['nama_barang']) . "</td>";
            $html .= "<td>" . htmlspecialchars($r['nama_kategori']) . "</td>";
            $html .= "<td>" . htmlspecialchars($r['nama_lokasi']) . "</td>";
            $html .= "<td>" . htmlspecialchars($r['jumlah_total']) . "</td>";
            $html .= "<td>" . htmlspecialchars($r['jumlah_tersedia']) . "</td>";
            $html .= "<td>" . htmlspecialchars($r['jumlah_dipinjam']) . "</td>";
            $html .= "<td>" . htmlspecialchars($r['kondisi']) . "</td>";
            $html .= '</tr>';
            $n++;
        }
        $html .= '</tbody></table></body></html>';

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $filename = "Laporan_Barang_" . date('Ymd_His') . ".pdf";
        $dompdf->stream($filename, ["Attachment" => true]);
        exit;
    }

    // 3) Neither library found => show guidance
    echo "<h3>Library PDF tidak ditemukan</h3>";
    echo "<p>Agar fitur PDF bekerja, silakan salah satu:</p>";
    echo "<ol>";
    echo "<li>Pasang FPDF: download dari <a href='http://www.fpdf.org/' target='_blank'>fpdf.org</a> dan letakkan file <code>fpdf.php</code> + folder di <code>/fpdf/</code> pada root project. (Skript sudah coba cari di <code>/fpdf/fpdf.php</code>.)</li>";
    echo "<li>Atau pasang Dompdf via Composer: jalankan di folder project: <code>composer require dompdf/dompdf</code></li>";
    echo "</ol>";
    exit;
}

// If none matched => show minimal info
echo "Parameter export tidak ditemukan. Gunakan ?type=excel (atau ?export=excel) atau ?type=pdf (atau ?export=pdf).";
exit;
