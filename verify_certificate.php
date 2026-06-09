<?php
session_start();
require_once 'forms/db.php';

$code = isset($_GET['code']) ? trim($_GET['code']) : '';
$cert = null;
$verified = false;
$searched = false;

if (!empty($code)) {
    $searched = true;
    // Query certificate
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
    if ($cert) {
        $verified = true;
    }
}

$pageTitle = "Certificate Verification Portal";
include "includes/header.php";
?>

<div style="height: 100px;"></div>

<main class="container py-5">
    <div class="row justify-content-center" data-aos="fade-up">
        <div class="col-lg-6 col-md-8">
            <div class="card border-0 shadow-lg bg-white p-4 rounded-4">
                <div class="text-center mb-4">
                    <i class="bi bi-patch-check-fill text-primary fs-1"></i>
                    <h3 class="fw-bold mt-2 text-dark">Certificate Verification</h3>
                    <p class="text-muted small">Verify KAcademyX student completion certificates instantly.</p>
                </div>

                <form method="GET" action="verify_certificate.php" class="mb-4">
                    <div class="input-group">
                        <input type="text" name="code" class="form-control form-control-lg border-2" placeholder="Enter Certificate Code (e.g. CERT-B3D2...)" value="<?php echo htmlspecialchars($code); ?>" required>
                        <button type="submit" class="btn btn-primary btn-lg px-4"><i class="bi bi-search me-1"></i>Verify</button>
                    </div>
                </form>

                <?php if ($searched): ?>
                    <hr class="my-4 opacity-25">
                    
                    <?php if ($verified): ?>
                        <div class="alert alert-success p-4 border-0 rounded-3 text-center">
                            <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-3"></i>
                            <h4 class="alert-heading fw-bold">Verification Successful</h4>
                            <p class="mb-0 text-muted small">This document is verified as an official certificate issued by KAcademyX.</p>
                        </div>
                        
                        <div class="card bg-light border-0 rounded-3">
                            <div class="card-body p-4">
                                <h6 class="fw-bold mb-3 border-bottom pb-2 text-dark"><i class="bi bi-file-earmark-check me-2"></i>Official Records</h6>
                                <ul class="list-unstyled mb-0 small text-muted">
                                    <li class="mb-2 d-flex justify-content-between">
                                        <span>Recipient Student:</span>
                                        <strong class="text-dark"><?php echo htmlspecialchars($cert['student_name']); ?></strong>
                                    </li>
                                    <li class="mb-2 d-flex justify-content-between">
                                        <span>Examination Name:</span>
                                        <strong class="text-dark"><?php echo htmlspecialchars($cert['test_title']); ?></strong>
                                    </li>
                                    <li class="mb-2 d-flex justify-content-between">
                                        <span>Final Grade Score:</span>
                                        <strong class="text-success fw-bold"><?php echo $cert['percentage']; ?>%</strong>
                                    </li>
                                    <li class="mb-2 d-flex justify-content-between">
                                        <span>Issue Timestamp:</span>
                                        <strong class="text-dark"><?php echo date('d F Y', strtotime($cert['generated_at'])); ?></strong>
                                    </li>
                                    <li class="d-flex justify-content-between">
                                        <span>Verification Status:</span>
                                        <span class="badge bg-success"><i class="bi bi-shield-check me-1"></i>Authentic</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="mt-4 text-center">
                            <a href="certificate.php?code=<?php echo $cert['verification_code']; ?>" class="btn btn-outline-primary px-4 fw-bold">
                                View Full Certificate
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger p-4 border-0 rounded-3 text-center">
                            <i class="bi bi-exclamation-triangle-fill text-danger fs-1 d-block mb-3"></i>
                            <h4 class="alert-heading fw-bold">Verification Failed</h4>
                            <p class="mb-0 small text-muted">No record matches the certificate verification code <strong><?php echo htmlspecialchars($code); ?></strong>. It may be invalid or incorrectly typed.</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php
include "includes/footer.php";
?>
