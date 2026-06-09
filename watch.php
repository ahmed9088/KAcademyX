<?php
session_start();
require_once 'forms/db.php';

// Auth Guard
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header('Location: forms/login.php');
    exit();
}

$user_id = $_SESSION["id"];

// Fetch student ID
$st_res = mysqli_query($conn, "SELECT id, name FROM students WHERE user_id = $user_id");
$student = mysqli_fetch_assoc($st_res);
if (!$student) {
    header('Location: lectures.php');
    exit();
}
$student_id = $student['id'];

// Handle AJAX / POST requests for watch progress & notes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_progress'])) {
        header('Content-Type: application/json');
        $video_id = $_POST['video_id'] ?? '';
        $position = intval($_POST['position'] ?? 0);
        $duration = intval($_POST['duration'] ?? 0);
        
        if ($video_id && $student_id > 0) {
            $stmt = $conn->prepare("INSERT INTO watch_history (student_id, video_id, last_position, duration) 
                                    VALUES (?, ?, ?, ?) 
                                    ON DUPLICATE KEY UPDATE last_position = VALUES(last_position), duration = VALUES(duration)");
            $stmt->bind_param("isii", $student_id, $video_id, $position, $duration);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
        }
        exit();
    }
    
    if (isset($_POST['add_note'])) {
        header('Content-Type: application/json');
        $video_id = $_POST['video_id'] ?? '';
        $note_text = trim($_POST['note_text'] ?? '');
        $timestamp = intval($_POST['timestamp'] ?? 0);
        
        if ($video_id && !empty($note_text) && $student_id > 0) {
            $stmt = $conn->prepare("INSERT INTO student_notes (student_id, video_id, note_text, timestamp_seconds) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $student_id, $video_id, $note_text, $timestamp);
            if ($stmt->execute()) {
                $note_id = $stmt->insert_id;
                $stmt->close();
                
                $ts_display = sprintf('%02d:%02d', floor($timestamp / 60), $timestamp % 60);
                echo json_encode([
                    'status' => 'success',
                    'note' => [
                        'id' => $note_id,
                        'text' => htmlspecialchars($note_text),
                        'timestamp' => $timestamp,
                        'timestamp_display' => $ts_display,
                        'created_at' => date('d M Y, h:i A')
                    ]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database error']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
        }
        exit();
    }
    
    if (isset($_POST['delete_note'])) {
        header('Content-Type: application/json');
        $note_id = intval($_POST['note_id'] ?? 0);
        
        if ($note_id > 0 && $student_id > 0) {
            $stmt = $conn->prepare("DELETE FROM student_notes WHERE id = ? AND student_id = ?");
            $stmt->bind_param("ii", $note_id, $student_id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database error']);
            }
            $stmt->close();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
        }
        exit();
    }
}

// Fetch video details
$video_id = $_GET['video_id'] ?? '';
if (empty($video_id)) {
    header('Location: lectures.php');
    exit();
}

$vid_stmt = $conn->prepare("SELECT * FROM youtube_videos WHERE video_id = ?");
$vid_stmt->bind_param("s", $video_id);
$vid_stmt->execute();
$video = $vid_stmt->get_result()->fetch_assoc();
$vid_stmt->close();

if (!$video) {
    header('Location: lectures.php');
    exit();
}

// Fetch saved watch position
$pos_stmt = $conn->prepare("SELECT last_position FROM watch_history WHERE student_id = ? AND video_id = ?");
$pos_stmt->bind_param("is", $student_id, $video_id);
$pos_stmt->execute();
$pos_row = $pos_stmt->get_result()->fetch_assoc();
$pos_stmt->close();
$saved_position = $pos_row ? intval($pos_row['last_position']) : 0;
if (isset($_GET['t'])) {
    $saved_position = intval($_GET['t']);
}

// Fetch existing notes
$notes_stmt = $conn->prepare("SELECT * FROM student_notes WHERE student_id = ? AND video_id = ? ORDER BY timestamp_seconds ASC, created_at DESC");
$notes_stmt->bind_param("is", $student_id, $video_id);
$notes_stmt->execute();
$notes_res = $notes_stmt->get_result();
$notes = [];
while ($row = $notes_res->fetch_assoc()) {
    $notes[] = $row;
}
$notes_stmt->close();

$pageTitle = htmlspecialchars($video['title']) . " — KAcademyX";
$activePage = "lectures";
include "includes/header.php";
?>

<div style="height: 100px;"></div>

<main class="container py-4">
  <div class="row g-4" data-aos="fade-up">
    
    <!-- Left Column: Video Player & Tabs -->
    <div class="col-lg-8 col-md-12">
      
      <!-- Video Player Wrapper -->
      <div class="video-player-card card border-0 shadow-sm rounded-4 overflow-hidden bg-black mb-4">
        <div class="ratio ratio-16x9">
          <div id="yt-player"></div>
        </div>
      </div>
      
      <!-- Video Info & Tabs -->
      <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
        
        <!-- Category & Title -->
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill w-auto align-self-start mb-2 px-3 py-2 fw-semibold" style="font-size: 0.8rem;">
          <?php echo htmlspecialchars($video['category']); ?>
        </span>
        <h3 class="fw-bold text-dark mb-1 mt-1"><?php echo htmlspecialchars($video['title']); ?></h3>
        <p class="text-muted small mb-4"><i class="bi bi-calendar3 me-1"></i> Published on <?php echo date('d M Y', strtotime($video['published_at'])); ?></p>
        
        <!-- Interactive Tabs Header -->
        <ul class="nav nav-tabs border-bottom border-light mb-3 gap-2" id="watchTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold border-0 px-3 py-2 text-dark" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab" aria-controls="overview" aria-selected="true">
              Lecture Overview
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold border-0 px-3 py-2 text-dark" id="take-notes-tab" data-bs-toggle="tab" data-bs-target="#take-notes" type="button" role="tab" aria-controls="take-notes" aria-selected="false">
              Take Note
            </button>
          </li>
        </ul>
        
        <!-- Tabs Content -->
        <div class="tab-content" id="watchTabsContent">
          <!-- Overview Tab -->
          <div class="tab-pane fade show active py-2" id="overview" role="tabpanel" aria-labelledby="overview-tab">
            <p class="text-secondary mb-0" style="line-height: 1.6; white-space: pre-wrap; font-size: 0.95rem;">
              <?php echo htmlspecialchars($video['description'] ?: 'No description available for this lecture.'); ?>
            </p>
          </div>
          
          <!-- Take Notes Tab -->
          <div class="tab-pane fade py-2" id="take-notes" role="tabpanel" aria-labelledby="take-notes-tab">
            <div id="note-success" class="alert alert-success border-0 shadow-sm d-none mb-3">
              <i class="bi bi-check-circle-fill me-2"></i>Note saved successfully!
            </div>
            <form id="noteForm">
              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <label for="noteText" class="form-label fw-bold text-dark small mb-0">Compose Note</label>
                  <button type="button" id="timeTagBtn" class="btn btn-sm btn-outline-secondary fw-semibold" style="font-size: 0.75rem;">
                    <i class="bi bi-stopwatch-fill me-1 text-primary"></i> Tag current time: <span id="currentTimeDisplay">00:00</span>
                  </button>
                </div>
                <textarea class="form-control border-light-subtle rounded-3" id="noteText" rows="3" placeholder="Write down important details, formulas, or concepts from the lecture..." required></textarea>
              </div>
              <input type="hidden" id="noteTimestamp" value="0">
              <button type="submit" class="btn btn-primary fw-semibold btn-sm px-4">
                <i class="bi bi-plus-lg me-1"></i> Save Note
              </button>
            </form>
          </div>
        </div>
        
      </div>
    </div>
    
    <!-- Right Column: Notes Sidebar -->
    <div class="col-lg-4 col-md-12">
      <div class="card border-0 shadow-sm rounded-4 bg-white h-100 p-4">
        
        <div class="d-flex justify-content-between align-items-center border-bottom border-light pb-3 mb-3">
          <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-text me-2 text-primary"></i>My Notes</h5>
          <span class="badge bg-secondary-subtle text-secondary px-3 py-1 rounded-pill small fw-semibold" id="notesCount"><?php echo count($notes); ?> notes</span>
        </div>
        
        <!-- Notes List Container -->
        <div class="notes-list-scroll overflow-y-auto pe-1" style="max-height: 480px;" id="notesContainer">
          <?php if (empty($notes)): ?>
            <div class="text-center py-5 text-muted" id="noNotesPlaceholder">
              <i class="bi bi-journal-x fs-1 text-secondary mb-3 d-block"></i>
              <p class="small mb-0">No notes taken yet.<br>Click the "Take Note" tab to add one!</p>
            </div>
          <?php else: ?>
            <?php foreach ($notes as $n): 
              $ts = intval($n['timestamp_seconds']);
              $ts_formatted = sprintf('%02d:%02d', floor($ts / 60), $ts % 60);
            ?>
              <div class="note-card p-3 rounded-3 border mb-3" id="note-<?php echo $n['id']; ?>">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                  <button onclick="seekToTime(<?php echo $ts; ?>)" class="btn btn-sm btn-outline-primary fw-bold px-2 py-1" style="font-size: 0.75rem;" title="Jump to timestamp">
                    <i class="bi bi-play-circle-fill me-1"></i><?php echo $ts_formatted; ?>
                  </button>
                  <button onclick="deleteNote(<?php echo $n['id']; ?>)" class="btn btn-sm btn-link text-danger p-0" title="Delete Note">
                    <i class="bi bi-trash3"></i>
                  </button>
                </div>
                <p class="text-secondary small mb-1 note-content-text"><?php echo htmlspecialchars($n['note_text']); ?></p>
                <small class="text-muted" style="font-size: 0.7rem;"><?php echo date('d M Y, h:i A', strtotime($n['created_at'])); ?></small>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        
      </div>
    </div>
    
  </div>
</main>

<!-- YouTube Iframe API -->
<script src="https://www.youtube.com/iframe_api"></script>
<script>
  let player;
  const initialPosition = <?php echo $saved_position; ?>;
  const videoId = <?php echo json_encode($video['video_id']); ?>;

  function onYouTubeIframeAPIReady() {
    player = new YT.Player('yt-player', {
      height: '100%',
      width: '100%',
      videoId: videoId,
      playerVars: {
        'playsinline': 1,
        'rel': 0,
        'modestbranding': 1
      },
      events: {
        'onReady': onPlayerReady,
        'onStateChange': onPlayerStateChange
      }
    });
  }

  function onPlayerReady(event) {
    if (initialPosition > 0) {
      event.target.seekTo(initialPosition);
    }
    
    // Auto-update playback progress every 4 seconds
    setInterval(() => {
      if (player && typeof player.getPlayerState === 'function' && player.getPlayerState() === YT.PlayerState.PLAYING) {
        const currentTime = Math.floor(player.getCurrentTime());
        const duration = Math.round(player.getDuration());
        if (currentTime > 0 && duration > 0) {
          updateProgressOnServer(currentTime, duration);
        }
      }
    }, 4000);
    
    // Live update the tag timer in Note composing
    setInterval(() => {
      if (player && typeof player.getCurrentTime === 'function') {
        const time = Math.floor(player.getCurrentTime());
        const m = Math.floor(time / 60);
        const s = time % 60;
        document.getElementById('currentTimeDisplay').textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
      }
    }, 500);
  }

  function onPlayerStateChange(event) {
    if (event.data === YT.PlayerState.PAUSED || event.data === YT.PlayerState.ENDED) {
      const currentTime = Math.floor(player.getCurrentTime());
      const duration = Math.round(player.getDuration());
      if (currentTime > 0) {
        updateProgressOnServer(currentTime, duration);
      }
    }
  }

  function updateProgressOnServer(position, duration) {
    const data = new FormData();
    data.append('update_progress', '1');
    data.append('video_id', videoId);
    data.append('position', position);
    data.append('duration', duration);

    fetch(window.location.href, {
      method: 'POST',
      body: data
    })
    .catch(() => {});
  }

  function seekToTime(seconds) {
    if (player && typeof player.seekTo === 'function') {
      player.seekTo(seconds, true);
      player.playVideo();
    }
  }

  // Handle Note tagging
  document.getElementById('timeTagBtn').addEventListener('click', function() {
    if (player && typeof player.getCurrentTime === 'function') {
      const currentTime = Math.floor(player.getCurrentTime());
      document.getElementById('noteTimestamp').value = currentTime;
      const m = Math.floor(currentTime / 60);
      const s = currentTime % 60;
      this.innerHTML = `<i class="bi bi-stopwatch-fill me-1 text-primary"></i> Tagged at ${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    }
  });

  // Handle Note Submission
  document.getElementById('noteForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const noteText = document.getElementById('noteText').value.trim();
    let timestamp = parseInt(document.getElementById('noteTimestamp').value);
    
    // If they haven't explicitly clicked Tag Time, tag the current video position automatically
    if (timestamp === 0 && player && typeof player.getCurrentTime === 'function') {
      timestamp = Math.floor(player.getCurrentTime());
    }

    const data = new FormData();
    data.append('add_note', '1');
    data.append('video_id', videoId);
    data.append('note_text', noteText);
    data.append('timestamp', timestamp);

    fetch(window.location.href, {
      method: 'POST',
      body: data
    })
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        // Clear input form
        document.getElementById('noteText').value = '';
        document.getElementById('noteTimestamp').value = '0';
        document.getElementById('timeTagBtn').innerHTML = `<i class="bi bi-stopwatch-fill me-1 text-primary"></i> Tag current time: <span id="currentTimeDisplay">00:00</span>`;
        
        // Hide placeholder
        const placeholder = document.getElementById('noNotesPlaceholder');
        if (placeholder) placeholder.remove();
        
        // Prepend note to notes listing
        const container = document.getElementById('notesContainer');
        const card = document.createElement('div');
        card.className = 'note-card p-3 rounded-3 border mb-3';
        card.id = `note-${res.note.id}`;
        card.innerHTML = `
          <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <button onclick="seekToTime(${res.note.timestamp})" class="btn btn-sm btn-outline-primary fw-bold px-2 py-1" style="font-size: 0.75rem;" title="Jump to timestamp">
              <i class="bi bi-play-circle-fill me-1"></i>${res.note.timestamp_display}
            </button>
            <button onclick="deleteNote(${res.note.id})" class="btn btn-sm btn-link text-danger p-0" title="Delete Note">
              <i class="bi bi-trash3"></i>
            </button>
          </div>
          <p class="text-secondary small mb-1 note-content-text">${res.note.text}</p>
          <small class="text-muted" style="font-size: 0.7rem;">${res.note.created_at}</small>
        `;
        container.insertBefore(card, container.firstChild);
        
        // Update count badge
        const countBadge = document.getElementById('notesCount');
        const currentCount = parseInt(countBadge.textContent);
        countBadge.textContent = `${currentCount + 1} notes`;
        
        // Show success alert
        const alertBox = document.getElementById('note-success');
        alertBox.classList.remove('d-none');
        setTimeout(() => alertBox.classList.add('d-none'), 3000);
      }
    })
    .catch(() => {});
  });

  // Handle Note Deletion
  function deleteNote(id) {
    if (!confirm('Are you sure you want to delete this note?')) return;
    
    const data = new FormData();
    data.append('delete_note', '1');
    data.append('note_id', id);

    fetch(window.location.href, {
      method: 'POST',
      body: data
    })
    .then(r => r.json())
    .then(res => {
      if (res.status === 'success') {
        const card = document.getElementById(`note-${id}`);
        if (card) {
          card.style.opacity = '0';
          card.style.transition = 'opacity 0.3s ease';
          setTimeout(() => {
            card.remove();
            // Show placeholder if empty
            const container = document.getElementById('notesContainer');
            if (container.querySelectorAll('.note-card').length === 0) {
              container.innerHTML = `
                <div class="text-center py-5 text-muted" id="noNotesPlaceholder">
                  <i class="bi bi-journal-x fs-1 text-secondary mb-3 d-block"></i>
                  <p class="small mb-0">No notes taken yet.<br>Click the "Take Note" tab to add one!</p>
                </div>
              `;
            }
          }, 300);
        }
        
        // Update count badge
        const countBadge = document.getElementById('notesCount');
        const currentCount = parseInt(countBadge.textContent);
        countBadge.textContent = `${Math.max(0, currentCount - 1)} notes`;
      }
    })
    .catch(() => {});
  }
</script>

<style>
.video-player-card {
  box-shadow: 0 16px 40px rgba(0,0,0,0.15) !important;
}
.nav-tabs .nav-link {
  color: #64748b !important;
  background-color: transparent !important;
  border-bottom: 2px solid transparent !important;
  transition: all 0.2s ease;
}
.nav-tabs .nav-link:hover {
  color: #4f46e5 !important;
  border-bottom-color: rgba(79,70,229,0.2) !important;
}
.nav-tabs .nav-link.active {
  color: #4f46e5 !important;
  border-bottom: 2px solid #4f46e5 !important;
}
.note-card {
  background-color: #fafafa;
  border-color: #eaeaea !important;
  transition: all 0.2s ease;
}
.note-card:hover {
  border-color: #d1d5db !important;
  background-color: #f9fafb;
}
.note-content-text {
  line-height: 1.5;
  white-space: pre-wrap;
  word-break: break-word;
}
.notes-list-scroll::-webkit-scrollbar {
  width: 5px;
}
.notes-list-scroll::-webkit-scrollbar-track {
  background: transparent;
}
.notes-list-scroll::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}
.notes-list-scroll::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>

<?php include "includes/footer.php"; ?>
