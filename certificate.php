<?php
session_start();
require_once 'forms/db.php';

$code = isset($_GET['code']) ? trim($_GET['code']) : '';
if (empty($code)) {
    die("<h3>Error: Certificate verification code is required.</h3>");
}

// Fetch certificate details
$query = "SELECT c.*, r.score, r.percentage, st.name as student_name, t.title as test_title
          FROM certificates c
          JOIN results r ON c.result_id = r.id
          JOIN students st ON c.student_id = st.id
          JOIN tests t ON c.test_id = t.id
          WHERE c.verification_code = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $code);
$stmt->execute();
$cert = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cert) {
    die("<h3>Error: Invalid certificate code. This certificate could not be verified.</h3>");
}

$pageTitle = "Certificate - " . htmlspecialchars($cert['student_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;800&family=Great+Vibes&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Poppins', sans-serif;
            color: #1e293b;
        }
        .cert-container {
            max-width: 900px;
            margin: 50px auto;
            position: relative;
        }
        .cert-paper {
            background-color: #ffffff;
            border: 20px solid #1e293b;
            padding: 50px 80px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            background-image: radial-gradient(#fafaf9 1px, transparent 0);
            background-size: 24px 24px;
        }
        .cert-paper::before {
            content: '';
            position: absolute;
            top: 5px;
            bottom: 5px;
            left: 5px;
            right: 5px;
            border: 2px solid #d4af37; /* Gold inner border */
            pointer-events: none;
        }
        .cert-header {
            font-family: 'Cinzel', serif;
            color: #1e293b;
            letter-spacing: 2px;
        }
        .cert-title {
            font-family: 'Cinzel', serif;
            font-size: 2.8rem;
            font-weight: 800;
            color: #1e293b;
            letter-spacing: 3px;
            margin-bottom: 20px;
        }
        .cert-recipient-name {
            font-family: 'Great Vibes', cursive;
            font-size: 4rem;
            color: #b8860b;
            margin: 20px 0;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
            display: inline-block;
            min-width: 400px;
        }
        .cert-badge {
            width: 120px;
            height: 120px;
            background-color: #d4af37;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-family: 'Cinzel', serif;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(212, 175, 55, 0.4);
            border: 4px double #ffffff;
            margin: 0 auto;
        }
        .cert-verification {
            font-size: 0.75rem;
            color: #64748b;
        }
        @media print {
            body {
                background-color: #ffffff;
                margin: 0;
            }
            .cert-container {
                margin: 0;
                max-width: 100%;
                width: 100%;
            }
            .cert-paper {
                box-shadow: none;
                border: 15px solid #1e293b;
                page-break-inside: avoid;
            }
            .btn-actions {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="container text-center btn-actions mt-4">
        <button onclick="window.print()" class="btn btn-primary btn-lg px-4 me-2"><i class="bi bi-printer me-2"></i>Print / Save as PDF</button>
        <a href="verify_certificate.php?code=<?php echo $cert['verification_code']; ?>" class="btn btn-outline-secondary btn-lg px-4"><i class="bi bi-patch-check me-2"></i>Verify Authenticity</a>
    </div>

    <div class="cert-container">
        <div class="cert-paper text-center">
            <div class="cert-header mb-2 text-uppercase">KAcademyX Academy</div>
            <div class="cert-title">Certificate of Completion</div>
            
            <div class="text-muted fs-5 mb-1">This is proudly presented to</div>
            <div class="cert-recipient-name"><?php echo htmlspecialchars($cert['student_name']); ?></div>
            
            <div class="text-muted fs-5 mb-2">for successfully completing the examination</div>
            <h3 class="fw-bold mb-3 text-dark"><?php echo htmlspecialchars($cert['test_title']); ?></h3>
            
            <div class="text-muted mb-4">
                with a final assessment grade of <strong class="text-indigo"><?php echo $cert['percentage']; ?>%</strong>
                on <strong><?php echo date('F d, Y', strtotime($cert['generated_at'])); ?></strong>
            </div>

            <!-- Signature and Gold Badge row -->
            <div class="row align-items-center mt-5 mb-4">
                <div class="col-4">
                    <div style="border-bottom: 1px solid #cbd5e1; width: 80%; margin: 0 auto 5px;"></div>
                    <small class="text-muted">Academic Board</small>
                </div>
                <div class="col-4">
                    <div class="cert-badge">
                        <div class="text-center" style="font-size: 0.8rem;">
                            OFFICIAL<br>SEAL
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div style="border-bottom: 1px solid #cbd5e1; width: 80%; margin: 0 auto 5px;"></div>
                    <small class="text-muted">Exam Proctor</small>
                </div>
            </div>

            <div class="cert-verification mt-4 pt-3 border-top">
                Verification ID: <strong><?php echo $cert['verification_code']; ?></strong> 
                &bull; Verify this document at: <strong><?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/KAcademyX/verify_certificate.php?code=' . $cert['verification_code']; ?></strong>
            </div>
        </div>
    </div>

</body>
</html>
