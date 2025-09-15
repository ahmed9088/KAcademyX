<?php
session_start();
require_once 'forms/db.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Location: forms/login.php');
    exit();
}

// Check if test result ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid request. Test result ID is required.";
    header('Location: my_tests.php');
    exit();
}

$test_result_id = (int)$_GET['id'];

// Fetch test result data
$query = "SELECT tr.*, u.name as user_name 
          FROM test_results tr 
          LEFT JOIN users u ON tr.user_id = u.id 
          WHERE tr.id = ? AND tr.user_id = ? AND tr.is_passed = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $test_result_id, $_SESSION["id"]);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Test result not found or you haven't passed this test.";
    header('Location: my_tests.php');
    exit();
}

$test_data = $result->fetch_assoc();
$stmt->close();

// Check if certificate already exists
$cert_query = "SELECT * FROM certificates WHERE test_result_id = ?";
$cert_stmt = $conn->prepare($cert_query);
$cert_stmt->bind_param("i", $test_result_id);
$cert_stmt->execute();
$cert_result = $cert_stmt->get_result();

if ($cert_result->num_rows > 0) {
    $cert_data = $cert_result->fetch_assoc();
    $certificate_file = $cert_data['certificate_url'];
    
    // Check if file exists
    if (file_exists($certificate_file)) {
        // Serve the existing file
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($certificate_file) . '"');
        header('Content-Length: ' . filesize($certificate_file));
        readfile($certificate_file);
        exit();
    }
}
$cert_stmt->close();

// If we get here, we need to generate the certificate

// TCPDF PATH FIX
$tcpdfPath = __DIR__ . '/tcpdf/tcpdf.php';
if (!file_exists($tcpdfPath)) {
    die("TCPDF not found at: " . $tcpdfPath . "<br>Please install TCPDF in the tcpdf directory.");
}
require_once $tcpdfPath;

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('KAcademyX');
$pdf->SetAuthor('KAcademyX');
$pdf->SetTitle('Certificate of Completion');
$pdf->SetSubject('Test Certificate');
$pdf->SetKeywords('KAcademyX, Certificate, Test, Completion');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(20, 20, 20);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Add a page
$pdf->AddPage();

// Set background - use absolute path for background image
$bgImagePath = __DIR__ . '/assets/img/certificate-bg.jpg';
if (file_exists($bgImagePath)) {
    $pdf->Image($bgImagePath, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
}

// Set font
$pdf->SetFont('helvetica', '', 16);

// Certificate content
$html = '
<style>
    .certificate-title {
        font-size: 36px;
        font-weight: bold;
        color: #2c3e50;
        text-align: center;
        margin-bottom: 30px;
    }
    .certificate-subtitle {
        font-size: 24px;
        color: #34495e;
        text-align: center;
        margin-bottom: 50px;
    }
    .certificate-text {
        font-size: 18px;
        color: #2c3e50;
        text-align: center;
        margin-bottom: 20px;
    }
    .student-name {
        font-size: 28px;
        font-weight: bold;
        color: #3498db;
        text-align: center;
        margin-bottom: 30px;
        text-decoration: underline;
    }
    .test-details {
        font-size: 18px;
        color: #2c3e50;
        text-align: center;
        margin-bottom: 15px;
    }
    .certificate-footer {
        font-size: 16px;
        color: #7f8c8d;
        text-align: center;
        margin-top: 80px;
    }
    .certificate-date {
        font-size: 16px;
        color: #2c3e50;
        text-align: center;
        margin-top: 20px;
    }
    .certificate-id {
        font-size: 14px;
        color: #95a5a6;
        text-align: center;
        margin-top: 10px;
    }
</style>
<div class="certificate-title">CERTIFICATE OF COMPLETION</div>
<div class="certificate-subtitle">This is to certify that</div>
<div class="student-name">' . htmlspecialchars($test_data['user_name']) . '</div>
<div class="certificate-text">has successfully completed the test</div>
<div class="test-details"><strong>' . htmlspecialchars($test_data['test_name']) . '</strong></div>
<div class="test-details">with a score of <strong>' . round($test_data['score'], 1) . '%</strong></div>
<div class="certificate-text">on <strong>' . date('F d, Y', strtotime($test_data['test_date'])) . '</strong></div>
<div class="certificate-footer">
    <div style="margin-bottom: 20px;">_________________________</div>
    <div>KAcademyX Administration</div>
</div>
<div class="certificate-date">Issued on ' . date('F d, Y') . '</div>
<div class="certificate-id">Certificate ID: CERT-' . str_pad($test_result_id, 6, '0', STR_PAD_LEFT) . '</div>
';

// Write HTML content
$pdf->writeHTML($html, true, false, true, false, '');

// Add a watermark for security
$pdf->SetAlpha(0.1);
$pdf->SetFont('helvetica', 'B', 120);
$pdf->SetTextColor(245, 245, 245);
$pdf->Text(30, 150, 'KACADEMYX', 0, false, false, 0, 0, 'L', false, '', 0, false, 'T', 'M');
$pdf->SetAlpha(1);

// FIXED: Use absolute path for certificate directory
$certificate_dir = __DIR__ . '/certificates/';
if (!file_exists($certificate_dir)) {
    mkdir($certificate_dir, 0777, true);
}
$certificate_file = $certificate_dir . 'certificate_' . $test_result_id . '.pdf';

// Save the PDF to file
$pdf->Output($certificate_file, 'F');

// Update database with certificate file path (store absolute path)
$update_query = "UPDATE certificates SET certificate_url = ? WHERE test_result_id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("si", $certificate_file, $test_result_id);
$update_stmt->execute();
$update_stmt->close();

// Also update test_results to mark certificate as generated
$update_query = "UPDATE test_results SET certificate_generated = 1 WHERE id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("i", $test_result_id);
$update_stmt->execute();
$update_stmt->close();

// Close database connection
$conn->close();

// Serve the generated PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="certificate_' . $test_result_id . '.pdf"');
header('Content-Length: ' . filesize($certificate_file));
readfile($certificate_file);
exit();
?>