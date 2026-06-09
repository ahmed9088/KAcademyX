<?php
// Admin/videos/import.php
include "../db.php";
include "../includes/header.php";
$pageTitle = "Import YouTube Videos";
include "../includes/sidebar.php";
include "../includes/footer.php";

$success_msg = "";
$error_msg = "";

// Helper to extract YouTube video ID from URL
function getYouTubeId($url) {
    $video_id = "";
    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {
        $video_id = $match[1];
    }
    return $video_id;
}

// 1. Channel API Import
if (isset($_POST['action']) && $_POST['action'] == 'api_import') {
    $api_key = trim($_POST['api_key']);
    $channel_id = trim($_POST['channel_id']);
    $category = trim($_POST['category']);
    $max_results = intval($_POST['max_results']);
    
    if (empty($api_key) || empty($channel_id)) {
        $error_msg = "API Key and Channel ID are required.";
    } else {
        $url = "https://www.googleapis.com/youtube/v3/search?key=" . urlencode($api_key) . 
               "&channelId=" . urlencode($channel_id) . 
               "&part=snippet,id&order=date&maxResults=" . $max_results . "&type=video";
               
        // Use cURL for better error handling and TLS compliance
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local environments
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error_msg = "cURL Error: " . curl_error($ch);
        } else {
            $data = json_decode($response, true);
            if (isset($data['error'])) {
                $error_msg = "YouTube API Error: " . $data['error']['message'];
            } elseif (isset($data['items']) && count($data['items']) > 0) {
                $imported = 0;
                $stmt = $conn->prepare("INSERT INTO youtube_videos (video_id, title, description, thumbnail_url, category, published_at) 
                                       VALUES (?, ?, ?, ?, ?, ?) 
                                       ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), 
                                       thumbnail_url=VALUES(thumbnail_url), category=VALUES(category), published_at=VALUES(published_at)");
                
                foreach ($data['items'] as $item) {
                    $video_id = $item['id']['videoId'];
                    $title = $item['snippet']['title'];
                    $description = $item['snippet']['description'];
                    $thumbnail = $item['snippet']['thumbnails']['high']['url'] ?? $item['snippet']['thumbnails']['default']['url'];
                    $published_at = date('Y-m-d H:i:s', strtotime($item['snippet']['publishedAt']));
                    
                    $stmt->bind_param("ssssss", $video_id, $title, $description, $thumbnail, $category, $published_at);
                    if ($stmt->execute()) {
                        $imported++;
                    }
                }
                $success_msg = "Successfully imported $imported videos from the channel!";
            } else {
                $error_msg = "No videos found in this channel.";
            }
        }
        curl_close($ch);
    }
}

// 2. URL oEmbed Import
if (isset($_POST['action']) && $_POST['action'] == 'url_import') {
    $video_url = trim($_POST['video_url']);
    $category = trim($_POST['category']);
    
    $video_id = getYouTubeId($video_url);
    
    if (empty($video_id)) {
        $error_msg = "Invalid YouTube URL. Please make sure the link is correct.";
    } else {
        // Fetch metadata via YouTube's open oEmbed endpoint (No API key needed!)
        $oembed_url = "https://www.youtube.com/oembed?url=" . urlencode("https://www.youtube.com/watch?v=" . $video_id) . "&format=json";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $oembed_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        
        $title = "YouTube Video (" . $video_id . ")";
        $description = "Manual import of YouTube video lecture.";
        $thumbnail = "https://img.youtube.com/vi/" . $video_id . "/hqdefault.jpg";
        
        if (!curl_errno($ch)) {
            $meta = json_decode($response, true);
            if ($meta) {
                $title = $meta['title'] ?? $title;
                $thumbnail = $meta['thumbnail_url'] ?? $thumbnail;
            }
        }
        curl_close($ch);
        
        $published_at = date('Y-m-d H:i:s'); // Default to current time for manual import
        
        $stmt = $conn->prepare("INSERT INTO youtube_videos (video_id, title, description, thumbnail_url, category, published_at) 
                               VALUES (?, ?, ?, ?, ?, ?) 
                               ON DUPLICATE KEY UPDATE title=VALUES(title), category=VALUES(category)");
        $stmt->bind_param("ssssss", $video_id, $title, $description, $thumbnail, $category, $published_at);
        
        if ($stmt->execute()) {
            $success_msg = "Successfully imported video: \"" . htmlspecialchars($title) . "\"";
        } else {
            $error_msg = "Database Error: " . $conn->error;
        }
    }
}

// 3. Mock Import (Fallback/Quick Seeding)
if (isset($_POST['action']) && $_POST['action'] == 'mock_import') {
    $mock_videos = [
        [
            'video_id' => 'USz7tY6aG7E',
            'title' => 'Quantum Physics for 7 Year Olds | Dominic Walliman | TEDxEastVan',
            'description' => 'Dr. Dominic Walliman describes quantum physics in a language simple enough for a child to understand, showing how the rules change entirely at the atomic scale.',
            'thumbnail_url' => 'https://img.youtube.com/vi/USz7tY6aG7E/maxresdefault.jpg',
            'category' => 'Physics',
            'published_at' => '2023-01-15 10:00:00'
        ],
        [
            'video_id' => 'zojy0W13C1Q',
            'title' => 'How Computer Memory Works - RAM, ROM, and SSDs',
            'description' => 'An easy-to-understand breakdown of hardware memory types, explaining how binary states are stored and retrieved inside solid state devices.',
            'thumbnail_url' => 'https://img.youtube.com/vi/zojy0W13C1Q/maxresdefault.jpg',
            'category' => 'Computer Science',
            'published_at' => '2023-03-22 14:30:00'
        ],
        [
            'video_id' => '8hly31yKz28',
            'title' => 'Python for Beginners - Full Programming Course',
            'description' => 'A complete core programming walkthrough of the Python syntax, loops, lists, objects, and standard library functions for beginners.',
            'thumbnail_url' => 'https://img.youtube.com/vi/8hly31yKz28/maxresdefault.jpg',
            'category' => 'Computer Science',
            'published_at' => '2023-05-10 09:00:00'
        ],
        [
            'video_id' => 'URUJD5NEXC8',
            'title' => 'Introduction to Cells: The Grand Cell Tour',
            'description' => 'Take a deep look inside the cell membrane. Learn about organelles, prokaryotes vs eukaryotes, and cellular respiration mechanisms.',
            'thumbnail_url' => 'https://img.youtube.com/vi/URUJD5NEXC8/maxresdefault.jpg',
            'category' => 'Biology',
            'published_at' => '2023-07-04 11:15:00'
        ],
        [
            'video_id' => 'HEfHFsfGXjs',
            'title' => 'The Map of Mathematics - Visualizing all Fields',
            'description' => 'Explore the vast map of mathematical concepts. This lecture links pure math, applied mathematics, numbers, algebra, and geometry.',
            'thumbnail_url' => 'https://img.youtube.com/vi/HEfHFsfGXjs/maxresdefault.jpg',
            'category' => 'Mathematics',
            'published_at' => '2023-08-18 16:45:00'
        ],
        [
            'video_id' => 'hUP3mB1X_W0',
            'title' => 'How to Choose Your Career Path Wisely',
            'description' => 'A detailed guidance session for students on how to identify their skills, map out career goals, and choose target degree options.',
            'thumbnail_url' => 'https://img.youtube.com/vi/hUP3mB1X_W0/maxresdefault.jpg',
            'category' => 'Career Guidance',
            'published_at' => '2023-10-05 08:30:00'
        ],
        [
            'video_id' => '_o_77M-fV6Y',
            'title' => 'How to Secure Fully-Funded Foreign Scholarships',
            'description' => 'Step-by-step breakdown on writing personal statements, sourcing recommendation letters, and applying to global scholarship schemes.',
            'thumbnail_url' => 'https://img.youtube.com/vi/_o_77M-fV6Y/maxresdefault.jpg',
            'category' => 'Scholarships',
            'published_at' => '2023-11-28 12:00:00'
        ]
    ];
    
    $imported = 0;
    $stmt = $conn->prepare("INSERT INTO youtube_videos (video_id, title, description, thumbnail_url, category, published_at) 
                           VALUES (?, ?, ?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), 
                           thumbnail_url=VALUES(thumbnail_url), category=VALUES(category)");
                           
    foreach ($mock_videos as $v) {
        $stmt->bind_param("ssssss", $v['video_id'], $v['title'], $v['description'], $v['thumbnail_url'], $v['category'], $v['published_at']);
        if ($stmt->execute()) {
            $imported++;
        }
    }
    $success_msg = "Seeded $imported mock educational videos into the hub successfully!";
}
?>

<div class="row">
    <div class="col-12 mb-3">
        <a href="list.php" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Video List</a>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">YouTube Video Importer</h6>
    </div>
    <div class="card-body">
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Nav Tabs -->
        <ul class="nav nav-tabs mb-4" id="importTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="api-tab" data-bs-toggle="tab" data-bs-target="#api" type="button" role="tab" aria-controls="api" aria-selected="true">
                    <i class="bi bi-youtube me-1"></i> Import from YouTube Channel (API Key)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="url-tab" data-bs-toggle="tab" data-bs-target="#url" type="button" role="tab" aria-controls="url" aria-selected="false">
                    <i class="bi bi-link-45deg me-1"></i> Single Video URL Import
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="mock-tab" data-bs-toggle="tab" data-bs-target="#mock" type="button" role="tab" aria-controls="mock" aria-selected="false">
                    <i class="bi bi-database-fill-add me-1"></i> Quick Test (Mock Seed)
                </button>
            </li>
        </ul>

        <!-- Tab Contents -->
        <div class="tab-content" id="importTabsContent">
            <!-- 1. Channel API Form -->
            <div class="tab-pane fade show active" id="api" role="tabpanel" aria-labelledby="api-tab">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="api_import">
                    <div class="mb-3">
                        <label for="api_key" class="form-label">YouTube Data API Key</label>
                        <input type="text" class="form-control" id="api_key" name="api_key" placeholder="Enter your YouTube v3 API Key" required>
                        <div class="form-text text-muted">Generate a key on your Google Cloud Console.</div>
                    </div>
                    <div class="mb-3">
                        <label for="channel_id" class="form-label">YouTube Channel ID</label>
                        <input type="text" class="form-control" id="channel_id" name="channel_id" placeholder="e.g. UC_x5XG1OV2P6uZZ5FSM9Ttw" required>
                        <div class="form-text text-muted">You can locate your Channel ID in your channel's About page on YouTube or Account Settings.</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label">Assign Category</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="Physics">Physics</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Biology">Biology</option>
                                <option value="Mathematics">Mathematics</option>
                                <option value="Motivation">Motivation</option>
                                <option value="Career Guidance">Career Guidance</option>
                                <option value="Scholarships">Scholarships</option>
                                <option value="General">General / Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="max_results" class="form-label">Max Videos to Fetch</label>
                            <select class="form-select" id="max_results" name="max_results">
                                <option value="5">5 Videos</option>
                                <option value="10" selected>10 Videos</option>
                                <option value="25">25 Videos</option>
                                <option value="50">50 Videos</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">
                        <i class="bi bi-cloud-download me-1"></i> Start Importing channel
                    </button>
                </form>
            </div>

            <!-- 2. Single URL Form -->
            <div class="tab-pane fade" id="url" role="tabpanel" aria-labelledby="url-tab">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="url_import">
                    <div class="mb-3">
                        <label for="video_url" class="form-label">YouTube Video Link</label>
                        <input type="url" class="form-control" id="video_url" name="video_url" placeholder="e.g. https://www.youtube.com/watch?v=dQw4w9WgXcQ" required>
                        <div class="form-text text-muted">Supports full links, mobile links (youtu.be), or embed links. Title and thumbnail are resolved automatically using oEmbed.</div>
                    </div>
                    <div class="mb-3">
                        <label for="category_url" class="form-label">Assign Category</label>
                        <select class="form-select" id="category_url" name="category" required>
                            <option value="Physics">Physics</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Biology">Biology</option>
                            <option value="Mathematics">Mathematics</option>
                            <option value="Motivation">Motivation</option>
                            <option value="Career Guidance">Career Guidance</option>
                            <option value="Scholarships">Scholarships</option>
                            <option value="General">General / Other</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">
                        <i class="bi bi-plus-circle me-1"></i> Add Video
                    </button>
                </form>
            </div>

            <!-- 3. Mock Import Form -->
            <div class="tab-pane fade" id="mock" role="tabpanel" aria-labelledby="mock-tab">
                <div class="p-3 bg-light border rounded">
                    <h5>Instant Seed Tool</h5>
                    <p class="text-muted">If you do not have a YouTube API key generated or want to see how the video dashboard functions immediately, click the button below. This will seed 7 real educational lectures covering Physics, Computer Science, Biology, and Math directly into the database.</p>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="mock_import">
                        <button type="submit" class="btn btn-success mt-2">
                            <i class="bi bi-database-fill-gear me-1"></i> Seed Mock Video Hub
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</div> <!-- col-md-10 content -->
</div> <!-- row -->
</div> <!-- container-fluid -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
