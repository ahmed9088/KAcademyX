<?php
session_start();
require_once 'forms/db.php';

// Auth guard
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Location: forms/login.php');
    exit();
}

$user_id = $_SESSION["id"];
$st_res = mysqli_query($conn, "SELECT id FROM students WHERE user_id = $user_id");
$student = mysqli_fetch_assoc($st_res);
if (!$student) { header('Location: forms/login.php'); exit(); }
$student_id = $student['id'];

$attempt_id = isset($_GET['attempt_id']) ? intval($_GET['attempt_id']) : 0;
if ($attempt_id <= 0) {
    header("Location: test.php");
    exit();
}

// Fetch attempt details
$att_stmt = $conn->prepare("SELECT * FROM student_attempts WHERE id = ? AND student_id = ? AND status = 'In Progress'");
$att_stmt->bind_param("ii", $attempt_id, $student_id);
$att_stmt->execute();
$attempt = $att_stmt->get_result()->fetch_assoc();
$att_stmt->close();

if (!$attempt) {
    // Attempt not found or already completed
    header("Location: test_result.php?attempt_id=$attempt_id");
    exit();
}

$test_id = $attempt['test_id'];

// Fetch test details
$test_stmt = $conn->prepare("SELECT * FROM tests WHERE id = ?");
$test_stmt->bind_param("i", $test_id);
$test_stmt->execute();
$test = $test_stmt->get_result()->fetch_assoc();
$test_stmt->close();

if (!$test) {
    header("Location: test.php");
    exit();
}

// Security: Recalculate remaining seconds to block cheats
$now = new DateTime("now", new DateTimeZone("Asia/Karachi"));
$started_at = new DateTime($attempt['started_at'], new DateTimeZone("Asia/Karachi"));

if ($test['timer_mode'] == 'Fixed') {
    $end_time = new DateTime($test['end_datetime'], new DateTimeZone("Asia/Karachi"));
    $rem_sec = $end_time->getTimestamp() - $now->getTimestamp();
} else {
    $rem_sec = ($test['duration_minutes'] * 60) - ($now->getTimestamp() - $started_at->getTimestamp());
}

if ($rem_sec <= 0) {
    // Auto submit immediately
    $now_db = $now->format('Y-m-d H:i:s');
    mysqli_query($conn, "UPDATE student_attempts SET status = 'Auto Submitted', completed_at = '$now_db', remaining_seconds = 0 WHERE id = $attempt_id");
    header("Location: complete_attempt.php?attempt_id=$attempt_id&mode=timeout");
    exit();
}

// Update remaining seconds database checkpoint
mysqli_query($conn, "UPDATE student_attempts SET remaining_seconds = $rem_sec WHERE id = $attempt_id");

// Fetch questions for this test
$questions_query = "SELECT q.* FROM test_questions tq 
                    JOIN questions q ON tq.question_id = q.id 
                    WHERE tq.test_id = $test_id 
                    ORDER BY tq.sort_order ASC, tq.id ASC";
$questions_res = mysqli_query($conn, $questions_query);
$questions = [];
while ($row = mysqli_fetch_assoc($questions_res)) {
    $q_id = $row['id'];
    
    // Fetch options
    $opts_res = mysqli_query($conn, "SELECT id, option_text FROM question_options WHERE question_id = $q_id");
    $opts = [];
    while ($o = mysqli_fetch_assoc($opts_res)) {
        $opts[] = $o;
    }
    $row['options'] = $opts;
    
    // Fetch if student already has saved answer
    $ans_res = mysqli_query($conn, "SELECT selected_option_ids, text_answer FROM student_answers WHERE attempt_id = $attempt_id AND question_id = $q_id");
    if ($ans_row = mysqli_fetch_assoc($ans_res)) {
        $row['saved_options'] = $ans_row['selected_option_ids'];
        $row['saved_text'] = $ans_row['text_answer'];
    } else {
        $row['saved_options'] = '';
        $row['saved_text'] = '';
    }
    $questions[] = $row;
}

if (empty($questions)) {
    die("<h3>Error: No questions found in this assessment. Contact administrator.</h3>");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam: <?php echo htmlspecialchars($test['title']); ?> - KAcademyX</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6fa;
            user-select: none; /* Disable text selection */
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
        .exam-header {
            background-color: #ffffff;
            border-bottom: 2px solid #eef1f6;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .timer-badge {
            background-color: #fff0f0;
            color: #d93838;
            font-weight: 700;
            border: 1px solid #ffcccc;
            border-radius: 8px;
            font-size: 1.15rem;
            padding: 8px 16px;
        }
        .progress-bar-container {
            height: 6px;
            background-color: #e9ecef;
            width: 100%;
        }
        .progress-bar-fill {
            height: 100%;
            background-color: #475bb2;
            transition: width 0.3s ease;
        }
        .q-palette-btn {
            width: 42px;
            height: 42px;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content-center;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            background-color: #ffffff;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .q-palette-btn.active {
            border-color: #475bb2;
            color: #475bb2;
            font-weight: 700;
            box-shadow: 0 0 0 3px rgba(71, 91, 178, 0.15);
        }
        .q-palette-btn.answered {
            background-color: #d1fae5;
            border-color: #10b981;
            color: #065f46;
        }
        .option-card {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            background-color: #ffffff;
            display: flex;
            align-items: center;
        }
        .option-card:hover {
            border-color: #cbd5e1;
            background-color: #f8fafc;
        }
        .option-card.selected {
            border-color: #475bb2;
            background-color: #f0f3ff;
            color: #1e293b;
            font-weight: 600;
        }
        .option-card input {
            cursor: pointer;
        }
        .exam-layout {
            min-height: calc(100vh - 100px);
        }
        .q-card {
            border-radius: 12px;
            border: 0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
        }
        .palette-card {
            border-radius: 12px;
            border: 0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
        }
        .pulse-icon {
            animation: blinker 1.5s linear infinite;
        }
        @keyframes blinker {
            50% { opacity: 0; }
        }
    </style>
</head>
<body>

    <!-- Exam Top Bar -->
    <header class="exam-header py-3 shadow-sm">
        <div class="container-fluid px-4">
            <div class="row align-items-center">
                <div class="col-md-6 col-sm-8">
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 fw-bold mb-1">KAcademyX Proctorship</span>
                    <h4 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($test['title']); ?></h4>
                </div>
                <div class="col-md-6 col-sm-4 d-flex justify-content-end align-items-center gap-3">
                    <div class="timer-badge d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history"></i>
                        <span id="timerDisplay">00:00</span>
                    </div>
                    <button class="btn btn-danger btn-lg fw-bold px-4 rounded-3" id="submitExamBtn">Submit Exam</button>
                </div>
            </div>
        </div>
        
        <!-- Live Progress bar -->
        <div class="progress-bar-container mt-3">
            <div class="progress-bar-fill" id="progressBar" style="width: 0%"></div>
        </div>
    </header>

    <!-- Exam Layout -->
    <div class="container-fluid px-4 py-4">
        <div class="row exam-layout">
            <!-- Left Side: Question area -->
            <div class="col-lg-9 col-md-12 mb-4">
                <div class="card q-card p-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="text-primary fw-bold mb-0" id="questionNumberHeader">Question 1 of <?php echo count($questions); ?></h5>
                        <span class="badge bg-light text-muted border px-3 py-2 fw-semibold" id="questionPoints">1 Point</span>
                    </div>
                    
                    <!-- Question Text -->
                    <div class="fs-5 fw-semibold text-dark mb-4" id="questionText">Loading question text...</div>
                    
                    <!-- Answers inputs container -->
                    <div id="optionsContainer">
                        <!-- Rendered dynamically via JS -->
                    </div>
                    
                    <!-- Navigation buttons -->
                    <div class="d-flex justify-content-between border-top pt-4 mt-4">
                        <button class="btn btn-outline-secondary btn-lg rounded-3 px-4" id="prevBtn"><i class="bi bi-chevron-left me-1"></i>Previous</button>
                        <button class="btn btn-primary btn-lg rounded-3 px-4" id="nextBtn">Next<i class="bi bi-chevron-right ms-1"></i></button>
                    </div>
                </div>
            </div>
            
            <!-- Right Side: Question Palette & Meta info -->
            <div class="col-lg-3 col-md-12">
                <div class="card palette-card p-4 bg-white mb-4">
                    <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i>Question Navigator</h6>
                    
                    <!-- Grid of palette items -->
                    <div class="d-flex flex-wrap gap-2 mb-4" id="paletteGrid">
                        <!-- Populated dynamically via JS -->
                    </div>
                    
                    <div class="small border-top pt-3">
                        <div class="d-flex align-items-center mb-2">
                            <div class="q-palette-btn answered me-2" style="width: 20px; height: 20px;"></div>
                            <span class="text-muted">Answered / Saved</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="q-palette-btn me-2" style="width: 20px; height: 20px;"></div>
                            <span class="text-muted">Unanswered / Skipped</span>
                        </div>
                    </div>
                </div>

                <!-- Live Competition Tracker Card -->
                <div class="card palette-card p-4 bg-white mb-4 shadow-sm" id="liveStatsCard">
                    <h6 class="fw-bold mb-3 text-danger"><i class="bi bi-activity me-2 pulse-icon"></i>Live Competition</h6>
                    
                    <div class="row text-center mb-2 g-2">
                        <div class="col-4">
                            <div class="p-2 bg-light rounded-3">
                                <span class="small text-muted d-block text-uppercase fw-semibold" style="font-size:0.6rem;">Online</span>
                                <h5 class="fw-bold mb-0 text-primary" id="onlineCount">1</h5>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-light rounded-3">
                                <span class="small text-muted d-block text-uppercase fw-semibold" style="font-size:0.6rem;">Submitted</span>
                                <h5 class="fw-bold mb-0 text-success" id="submittedCount">0</h5>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-light rounded-3">
                                <span class="small text-muted d-block text-uppercase fw-semibold" style="font-size:0.6rem;">Remaining</span>
                                <h5 class="fw-bold mb-0 text-secondary" id="remainingCount">0</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Switching Warning Modal -->
    <div class="modal fade" id="cheatWarningModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Security Alert: Focus Lost</h5>
                </div>
                <div class="modal-body text-center p-4">
                    <h5 class="fw-bold text-danger mb-2">Tab Switch Warning</h5>
                    <p class="text-muted">You switched tabs or left the KAcademyX exam environment. This incident has been logged. Repeated tab switching may invalidate your attempt automatically.</p>
                    <button class="btn btn-danger px-4" data-bs-dismiss="modal">Return to Exam</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Core variables
        const questions = <?php echo json_encode($questions); ?>;
        const attemptId = <?php echo $attempt_id; ?>;
        let currentIndex = 0;
        let remainingSeconds = <?php echo $rem_sec; ?>;
        let focusWarnings = 0;
        const totalQuestions = questions.length;
        
        const timerDisplay = document.getElementById('timerDisplay');
        const questionText = document.getElementById('questionText');
        const questionPoints = document.getElementById('questionPoints');
        const questionNumberHeader = document.getElementById('questionNumberHeader');
        const optionsContainer = document.getElementById('optionsContainer');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const progressBar = document.getElementById('progressBar');
        const paletteGrid = document.getElementById('paletteGrid');
        const submitExamBtn = document.getElementById('submitExamBtn');
        const cheatWarningModal = new bootstrap.Modal(document.getElementById('cheatWarningModal'));

        // Initialize exam page
        function init() {
            renderQuestion(currentIndex);
            buildPalette();
            updateProgress();
            startTimer();
            setupAntiCheat();
            startLiveStatsPolling();
        }

        // Timer control
        function startTimer() {
            updateTimerUI();
            const timerInterval = setInterval(() => {
                remainingSeconds--;
                updateTimerUI();
                
                if (remainingSeconds <= 0) {
                    clearInterval(timerInterval);
                    autoSubmit();
                }
                
                // Sync timer state with server every 10 seconds
                if (remainingSeconds % 10 === 0) {
                    syncTimer();
                }
            }, 1000);
        }

        function updateTimerUI() {
            const h = Math.floor(remainingSeconds / 3600);
            const m = Math.floor((remainingSeconds % 3600) / 60);
            const s = remainingSeconds % 60;
            
            let timeStr = "";
            if (h > 0) {
                timeStr += String(h).padStart(2, '0') + ":";
            }
            timeStr += String(m).padStart(2, '0') + ":" + String(s).padStart(2, '0');
            timerDisplay.textContent = timeStr;
            
            // Add red pulsing if remaining time is low
            if (remainingSeconds < 60) {
                timerDisplay.parentElement.style.animation = "pulse 1s infinite";
            }
        }

        // Render current question
        function renderQuestion(index) {
            currentIndex = index;
            const q = questions[index];
            
            // Set header
            questionNumberHeader.textContent = `Question ${index + 1} of ${totalQuestions}`;
            questionPoints.textContent = `${q.points} Point` + (q.points > 1 ? 's' : '');
            questionText.textContent = q.question_text;
            optionsContainer.innerHTML = '';

            // Handle navigation buttons availability
            prevBtn.disabled = (index === 0);
            if (index === totalQuestions - 1) {
                nextBtn.innerHTML = 'Finish <i class="bi bi-check-lg ms-1"></i>';
                nextBtn.classList.replace('btn-primary', 'btn-success');
            } else {
                nextBtn.innerHTML = 'Next <i class="bi bi-chevron-right ms-1"></i>';
                nextBtn.classList.replace('btn-success', 'btn-primary');
            }

            // Highlight active palette button
            document.querySelectorAll('.q-palette-btn').forEach((btn, idx) => {
                if (idx === index) btn.classList.add('active');
                else btn.classList.remove('active');
            });

            // Render option fields by question type
            if (q.question_type === 'MCQ_SINGLE') {
                q.options.forEach(opt => {
                    const isSelected = q.saved_options == opt.id;
                    const card = document.createElement('div');
                    card.className = `option-card ${isSelected ? 'selected' : ''}`;
                    card.innerHTML = `
                        <input type="radio" name="mcq_single" value="${opt.id}" class="form-check-input me-3" ${isSelected ? 'checked' : ''}>
                        <span>${escapeHtml(opt.option_text)}</span>
                    `;
                    card.addEventListener('click', () => selectSingleOption(card, opt.id));
                    optionsContainer.appendChild(card);
                });
            } else if (q.question_type === 'MCQ_MULTIPLE') {
                const savedArr = q.saved_options ? q.saved_options.split(',') : [];
                q.options.forEach(opt => {
                    const isSelected = savedArr.includes(String(opt.id));
                    const card = document.createElement('div');
                    card.className = `option-card ${isSelected ? 'selected' : ''}`;
                    card.innerHTML = `
                        <input type="checkbox" name="mcq_multiple[]" value="${opt.id}" class="form-check-input me-3" ${isSelected ? 'checked' : ''}>
                        <span>${escapeHtml(opt.option_text)}</span>
                    `;
                    card.addEventListener('click', (e) => {
                        // If click is on checkbox itself, don't duplicate toggle
                        if(e.target.tagName !== 'INPUT') {
                            const checkbox = card.querySelector('input');
                            checkbox.checked = !checkbox.checked;
                        }
                        selectMultipleOption(card);
                    });
                    optionsContainer.appendChild(card);
                });
            } else if (q.question_type === 'TRUE_FALSE') {
                q.options.forEach(opt => {
                    const isSelected = q.saved_options == opt.id;
                    const card = document.createElement('div');
                    card.className = `option-card ${isSelected ? 'selected' : ''}`;
                    card.innerHTML = `
                        <input type="radio" name="tf_answer" value="${opt.id}" class="form-check-input me-3" ${isSelected ? 'checked' : ''}>
                        <span>${escapeHtml(opt.option_text)}</span>
                    `;
                    card.addEventListener('click', () => selectSingleOption(card, opt.id));
                    optionsContainer.appendChild(card);
                });
            } else if (q.question_type === 'FILL_BLANK') {
                optionsContainer.innerHTML = `
                    <div class="mb-3">
                        <label for="fb_answer" class="form-label text-muted small fw-semibold">Enter your response:</label>
                        <input type="text" class="form-control form-control-lg border-2" id="fb_answer" value="${escapeHtml(q.saved_text)}" placeholder="Type answer here...">
                    </div>
                `;
                const input = document.getElementById('fb_answer');
                // Debounce input updates
                let timeout;
                input.addEventListener('input', () => {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        q.saved_text = input.value;
                        saveAnswer(q.id, '', q.saved_text);
                    }, 500);
                });
            } else {
                // SHORT_ANSWER, LONG_ANSWER
                const rows = q.question_type === 'LONG_ANSWER' ? 8 : 4;
                optionsContainer.innerHTML = `
                    <div class="mb-3">
                        <label for="text_answer" class="form-label text-muted small fw-semibold">Type your response below:</label>
                        <textarea class="form-control border-2" id="text_answer" rows="${rows}" placeholder="Type complete explanation...">${escapeHtml(q.saved_text)}</textarea>
                    </div>
                `;
                const textarea = document.getElementById('text_answer');
                let timeout;
                textarea.addEventListener('input', () => {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        q.saved_text = textarea.value;
                        saveAnswer(q.id, '', q.saved_text);
                    }, 500);
                });
            }
        }

        // Single Choice handlers
        function selectSingleOption(card, optId) {
            // Unselect sibling cards
            optionsContainer.querySelectorAll('.option-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            card.querySelector('input').checked = true;

            const q = questions[currentIndex];
            q.saved_options = optId;
            saveAnswer(q.id, optId, '');
        }

        // Multiple Selection handlers
        function selectMultipleOption(card) {
            if (card.querySelector('input').checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }

            const checkedOpts = [];
            optionsContainer.querySelectorAll('input[name="mcq_multiple[]"]:checked').forEach(chk => {
                checkedOpts.push(chk.value);
            });

            const q = questions[currentIndex];
            q.saved_options = checkedOpts.join(',');
            saveAnswer(q.id, q.saved_options, '');
        }

        // Build Question Palette
        function buildPalette() {
            paletteGrid.innerHTML = '';
            questions.forEach((q, idx) => {
                const btn = document.createElement('button');
                btn.className = 'q-palette-btn';
                btn.textContent = idx + 1;
                
                const isAnswered = q.saved_options !== '' || q.saved_text !== '';
                if (isAnswered) {
                    btn.classList.add('answered');
                }
                
                if (idx === currentIndex) {
                    btn.classList.add('active');
                }
                
                btn.addEventListener('click', () => {
                    renderQuestion(idx);
                });
                paletteGrid.appendChild(btn);
            });
        }

        // Update Palette item state on save
        function updatePaletteItem(idx, answered) {
            const btn = paletteGrid.children[idx];
            if (btn) {
                if (answered) btn.classList.add('answered');
                else btn.classList.remove('answered');
            }
        }

        // Update Progress bar
        function updateProgress() {
            let answeredCount = 0;
            questions.forEach(q => {
                if (q.saved_options !== '' || q.saved_text !== '') {
                    answeredCount++;
                }
            });
            const pct = (answeredCount / totalQuestions) * 100;
            progressBar.style.width = `${pct}%`;
        }

        // Auto Save to Server via AJAX
        function saveAnswer(questionId, selectedOptions, textAnswer) {
            const formData = new FormData();
            formData.append('attempt_id', attemptId);
            formData.append('question_id', questionId);
            formData.append('selected_options', selectedOptions);
            formData.append('text_answer', textAnswer);
            formData.append('remaining_seconds', remainingSeconds);

            fetch('save_answer.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update palette and progress
                    const hasData = (selectedOptions !== '' || textAnswer !== '');
                    updatePaletteItem(currentIndex, hasData);
                    updateProgress();
                } else {
                    console.error("Auto-save failed: ", data.message);
                }
            });
        }

        // Sync remaining time with server
        function syncTimer() {
            const formData = new FormData();
            formData.append('attempt_id', attemptId);
            formData.append('question_id', 0); // No question update
            formData.append('remaining_seconds', remainingSeconds);
            fetch('save_answer.php', {
                method: 'POST',
                body: formData
            });
        }

        // Auto Submit on Timeout
        function autoSubmit() {
            alert("Time's up! Your assessment will now be submitted automatically.");
            window.location.href = `complete_attempt.php?attempt_id=${attemptId}&mode=timeout`;
        }

        // Event Listeners for Next / Previous buttons
        prevBtn.addEventListener('click', () => {
            if (currentIndex > 0) {
                renderQuestion(currentIndex - 1);
            }
        });

        nextBtn.addEventListener('click', () => {
            if (currentIndex < totalQuestions - 1) {
                renderQuestion(currentIndex + 1);
            } else {
                // Submit trigger on the final question
                triggerSubmit();
            }
        });

        submitExamBtn.addEventListener('click', triggerSubmit);

        function triggerSubmit() {
            const unanswered = questions.filter(q => q.saved_options === '' && q.saved_text === '').length;
            let warningText = "Are you sure you want to finalize and submit this exam?";
            if (unanswered > 0) {
                warningText = `You have ${unanswered} unanswered question(s). ` + warningText;
            }
            if (confirm(warningText)) {
                window.location.href = `complete_attempt.php?attempt_id=${attemptId}&mode=submit`;
            }
        }

        // Security / Anti Cheat Methods
        function setupAntiCheat() {
            // Disable right click
            document.addEventListener('contextmenu', e => e.preventDefault());
            
            // Disable copy-paste shortcuts
            document.addEventListener('keydown', e => {
                if (e.ctrlKey && (e.key === 'c' || e.key === 'v' || e.key === 'x' || e.key === 'a' || e.key === 's')) {
                    e.preventDefault();
                }
            });

            // Warn on window focus loss (tab switching)
            window.addEventListener('blur', () => {
                focusWarnings++;
                cheatWarningModal.show();
            });
        }

        // 4. Live Stats & Toast Notifications
        let displayedSubmissions = new Set();
        
        function startLiveStatsPolling() {
            pollLiveStats();
            setInterval(pollLiveStats, 10000);
        }
        
        function pollLiveStats() {
            const testId = <?php echo $test_id; ?>;
            fetch(`test_live_stats.php?test_id=${testId}&attempt_id=${attemptId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) return;
                    
                    document.getElementById('onlineCount').textContent = data.online_count;
                    document.getElementById('submittedCount').textContent = data.submitted_count;
                    document.getElementById('remainingCount').textContent = data.remaining_count;
                    
                    // Toast notifications for recent submissions
                    if (data.recent_submissions && data.recent_submissions.length > 0) {
                        data.recent_submissions.forEach(sub => {
                            if (!displayedSubmissions.has(sub.attempt_id)) {
                                displayedSubmissions.add(sub.attempt_id);
                                showSubmissionToast(sub.name);
                            }
                        });
                    }
                })
                .catch(err => console.error("Error polling live stats:", err));
        }
        
        function showSubmissionToast(name) {
            const toastEl = document.getElementById('submissionToast');
            const toastText = document.getElementById('submissionToastText');
            toastText.innerHTML = `<i class="bi text-success me-2 bi-check-circle-fill"></i><strong>${escapeHtml(name)}</strong> has submitted the exam!`;
            
            const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
            toast.show();
        }

        // HTML escaping utility
        function escapeHtml(text) {
            if (!text) return '';
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Run initializer on load
        window.onload = init;
    </script>

    <!-- Submission Toast Container -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
        <div id="submissionToast" class="toast align-items-center text-white bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <span id="submissionToastText">Someone has submitted!</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
</body>
</html>