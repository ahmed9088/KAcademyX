<?php
// test_lobby.php - Real-time waiting room for competitive scheduled exams
session_start();
require_once 'forms/db.php';

// Auth Guard
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: forms/login.php");
    exit();
}

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
if (empty($token)) {
    header("Location: test.php");
    exit();
}

// Fetch test details
$test_stmt = $conn->prepare("SELECT t.*, s.name as subject_name, tc.name as category_name
                             FROM tests t
                             JOIN subjects s ON t.subject_id = s.id
                             JOIN test_categories tc ON t.category_id = tc.id
                             WHERE t.share_token = ?");
$test_stmt->bind_param("s", $token);
$test_stmt->execute();
$test = $test_stmt->get_result()->fetch_assoc();
$test_stmt->close();

if (!$test || $test['timer_mode'] !== 'Fixed') {
    header("Location: test.php");
    exit();
}

// Fetch student details
$user_id = $_SESSION["id"];
$st_res = mysqli_query($conn, "SELECT id, name FROM students WHERE user_id = $user_id");
$student = mysqli_fetch_assoc($st_res);
if (!$student) {
    header("Location: forms/login.php");
    exit();
}
$student_id = $student['id'];

$now = new DateTime("now", new DateTimeZone("Asia/Karachi"));
$start_time = new DateTime($test['start_datetime'], new DateTimeZone("Asia/Karachi"));

// Redirect if already started
if ($now >= $start_time) {
    header("Location: test_details.php?token=$token&auto_start=1");
    exit();
}

$remaining_seconds = $start_time->getTimestamp() - $now->getTimestamp();

$pageTitle = "Competition Lobby: " . htmlspecialchars($test['title']);
include "includes/header.php";
?>

<div style="height: 100px;"></div>

<main class="container py-4">
    <div class="row g-4" data-aos="fade-up">
        <!-- Left Panel: Test details and countdown -->
        <div class="col-lg-6 col-md-12">
            <div class="card lobby-card border-0 shadow-lg text-white p-4 h-100 position-relative overflow-hidden" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); border-radius: 20px;">
                <!-- Decorative background elements -->
                <div class="position-absolute opacity-10 bg-indigo rounded-circle" style="width: 300px; height: 300px; top: -100px; right: -100px;"></div>
                
                <div class="position-relative z-index-1">
                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 px-3 py-2 fw-bold text-uppercase mb-3">
                        <i class="bi bi-broadcast me-1 pulse-icon"></i> Live Waiting Lobby
                    </span>
                    
                    <h1 class="fw-bold mb-2 Montserrat text-white" style="font-size: 2.2rem;"><?php echo htmlspecialchars($test['title']); ?></h1>
                    <p class="text-indigo-200 fs-5 mb-4 opacity-75">Subject: <?php echo htmlspecialchars($test['subject_name']); ?> | Category: <?php echo htmlspecialchars($test['category_name']); ?></p>
                    
                    <!-- Digital countdown block -->
                    <div class="text-center my-4 py-4 bg-white bg-opacity-5 rounded-4 border border-white border-opacity-10">
                        <span class="small text-indigo-300 text-uppercase fw-semibold tracking-wider d-block mb-2">Starts In</span>
                        <div class="display-3 fw-bold font-monospace text-indigo-100" id="lobbyTimer">00:00:00</div>
                        <div class="mt-2 text-indigo-200 small opacity-75">Scheduled for <?php echo date('d M Y h:i A', strtotime($test['start_datetime'])); ?></div>
                    </div>

                    <!-- Instruction Box -->
                    <div class="p-3 rounded-3 bg-white bg-opacity-5 border border-white border-opacity-10 mt-4">
                        <h6 class="fw-bold text-indigo-200 mb-2"><i class="bi bi-info-circle me-2"></i>Lobby Instructions</h6>
                        <ul class="mb-0 small text-indigo-100 opacity-75 ps-3" style="line-height: 1.6;">
                            <li>The exam will **start automatically** for all students when the timer hits zero.</li>
                            <li>Do **not close or reload** this page. It keeps you connected to the server.</li>
                            <li>See other active participants in the live list on the right.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel: Participants List -->
        <div class="col-lg-6 col-md-12">
            <div class="card border-0 shadow-lg bg-white h-100" style="border-radius: 20px;">
                <div class="card-header bg-light border-0 py-3 px-4 d-flex justify-content-between align-items-center" style="border-radius: 20px 20px 0 0;">
                    <div>
                        <h4 class="fw-bold mb-0 text-dark font-sans"><i class="bi bi-people-fill text-primary me-2"></i>Participants</h4>
                        <small class="text-muted">Currently waiting in this lobby</small>
                    </div>
                    <span class="badge bg-primary fs-6 py-2 px-3 rounded-pill" id="totalCountBadge">0 Joined</span>
                </div>
                
                <div class="card-body px-4 py-3 d-flex flex-column h-100">
                    <!-- Search & Sort Controls -->
                    <div class="row g-2 mb-3">
                        <div class="col-sm-8 col-12">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" class="form-control bg-light border-0" id="searchParticipants" placeholder="Search students...">
                            </div>
                        </div>
                        <div class="col-sm-4 col-12">
                            <button class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2 border-0 bg-light" id="sortToggleBtn" data-sort="asc">
                                <i class="bi bi-sort-alpha-down"></i> Sort A-Z
                            </button>
                        </div>
                    </div>

                    <!-- Scrollable Participant List -->
                    <div class="flex-grow-1 overflow-y-auto mb-2" style="max-height: 350px;" id="participantList">
                        <!-- Filled dynamically via JS -->
                        <div class="text-center py-5 text-muted">
                            <div class="spinner-border spinner-border-sm text-primary mb-2"></div>
                            <div>Connecting to waiting lobby...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.text-indigo-100 { color: #e0e7ff; }
.text-indigo-200 { color: #c7d2fe; }
.text-indigo-300 { color: #a5b4fc; }
.pulse-icon {
    animation: blinker 1.5s linear infinite;
}
@keyframes blinker {
    50% { opacity: 0; }
}
.lobby-card {
    min-height: 480px;
}
.participant-item {
    border-radius: 12px;
    padding: 10px 16px;
    margin-bottom: 8px;
    transition: all 0.2s ease;
    border: 1px solid #f1f5f9;
}
.participant-item:hover {
    background-color: #f8fafc;
    border-color: #cbd5e1;
}
.participant-avatar {
    width: 32px;
    height: 32px;
    background-color: #e0e7ff;
    color: #4f46e5;
    font-weight: 700;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    justify-content-center;
    border-radius: 50%;
}
.participant-you {
    background-color: #f0f3ff;
    border-color: #c7d2fe !important;
}
.participant-you .participant-avatar {
    background-color: #4f46e5;
    color: #ffffff;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const token = <?php echo json_encode($token); ?>;
    let remainingSeconds = <?php echo $remaining_seconds; ?>;
    let participantData = [];
    
    const lobbyTimer = document.getElementById("lobbyTimer");
    const participantList = document.getElementById("participantList");
    const totalCountBadge = document.getElementById("totalCountBadge");
    const searchInput = document.getElementById("searchParticipants");
    const sortToggle = document.getElementById("sortToggleBtn");
    
    // 1. JS Client-side Countdown timer
    function formatTime(seconds) {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return [
            String(h).padStart(2, '0'),
            String(m).padStart(2, '0'),
            String(s).padStart(2, '0')
        ].join(':');
    }
    
    const interval = setInterval(() => {
        remainingSeconds--;
        if (remainingSeconds <= 0) {
            clearInterval(interval);
            lobbyTimer.textContent = "00:00:00";
            // Redirect to start
            window.location.href = "test_details.php?token=" + token + "&auto_start=1";
            return;
        }
        lobbyTimer.textContent = formatTime(remainingSeconds);
    }, 1000);
    
    // 2. Search & Sort Logic
    function renderParticipants() {
        const query = searchInput.value.trim().toLowerCase();
        const sortDirection = sortToggle.getAttribute("data-sort");
        
        let filtered = participantData.filter(p => p.name.toLowerCase().includes(query));
        
        filtered.sort((a, b) => {
            if (sortDirection === "asc") {
                return a.name.localeCompare(b.name);
            } else {
                return b.name.localeCompare(a.name);
            }
        });
        
        if (filtered.length === 0) {
            participantList.innerHTML = `<div class="text-center py-5 text-muted"><i class="bi bi-people fs-2 d-block mb-1"></i>No matching participants found.</div>`;
            return;
        }
        
        let html = "";
        filtered.forEach(p => {
            const initials = p.name.substring(0, 2).toUpperCase();
            html += `
                <div class="participant-item d-flex align-items-center gap-3 ${p.is_you ? 'participant-you' : ''}">
                    <div class="participant-avatar">${initials}</div>
                    <div class="flex-grow-1 fw-semibold text-dark">${p.name} ${p.is_you ? '<span class="badge bg-indigo ms-1">You</span>' : ''}</div>
                    <div class="small text-success"><i class="bi bi-circle-fill" style="font-size:0.6rem;"></i> Connected</div>
                </div>
            `;
        });
        participantList.innerHTML = html;
    }
    
    searchInput.addEventListener("input", renderParticipants);
    
    sortToggle.addEventListener("click", function() {
        const dir = this.getAttribute("data-sort");
        if (dir === "asc") {
            this.setAttribute("data-sort", "desc");
            this.innerHTML = `<i class="bi bi-sort-alpha-up"></i> Sort Z-A`;
        } else {
            this.setAttribute("data-sort", "asc");
            this.innerHTML = `<i class="bi bi-sort-alpha-down"></i> Sort A-Z`;
        }
        renderParticipants();
    });

    // 3. Heartbeat Polling
    function pollLobby() {
        fetch(`lobby_ping.php?token=${token}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                    return;
                }
                
                if (data.started) {
                    clearInterval(interval);
                    window.location.href = "test_details.php?token=" + token + "&auto_start=1";
                    return;
                }
                
                // Sync remaining seconds if drift > 5 seconds
                if (Math.abs(remainingSeconds - data.remaining_seconds) > 5) {
                    remainingSeconds = data.remaining_seconds;
                }
                
                participantData = data.participants;
                totalCountBadge.textContent = `${data.total_count} Joined`;
                renderParticipants();
            })
            .catch(err => console.error("Lobby connection heartbeat failed:", err));
    }
    
    pollLobby();
    // Ping every 4 seconds to ensure we refresh before user removal timeout
    setInterval(pollLobby, 4000);
});
</script>

<?php
include "includes/footer.php";
?>
