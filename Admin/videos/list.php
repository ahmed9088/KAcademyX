<?php
// Admin/videos/list.php
include "../db.php";
include "../includes/header.php";
$pageTitle = "Manage YouTube Videos";
include "../includes/sidebar.php";
include "../includes/footer.php";

$success_msg = "";

// Handle delete action
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM youtube_videos WHERE id = $id");
    $success_msg = "Video deleted successfully from the portal.";
}

// Fetch all videos
$videos_result = $conn->query("SELECT * FROM youtube_videos ORDER BY published_at DESC");
$total_videos = $videos_result ? $videos_result->num_rows : 0;
?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Imported YouTube Videos (<?php echo $total_videos; ?>)</h6>
        <a href="import.php" class="btn btn-primary btn-sm">
            <i class="bi bi-cloud-download me-1"></i> Import/Add Videos
        </a>
    </div>
    <div class="card-body">
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Thumbnail</th>
                        <th>Video ID</th>
                        <th>Title / Description</th>
                        <th>Category</th>
                        <th>Published Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total_videos > 0): ?>
                        <?php while($row = $videos_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <div style="position: relative; width: 100px;">
                                        <img src="<?php echo htmlspecialchars($row['thumbnail_url']); ?>" alt="thumbnail" style="width: 100px; height: 60px; object-fit: cover; border-radius: 4px;">
                                        <a href="https://www.youtube.com/watch?v=<?php echo $row['video_id']; ?>" target="_blank" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: red; font-size: 1.2rem; filter: drop-shadow(0px 1px 2px black);">
                                            <i class="bi bi-play-btn-fill"></i>
                                        </a>
                                    </div>
                                </td>
                                <td><code><?php echo htmlspecialchars($row['video_id']); ?></code></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                                    <div class="small text-muted text-truncate" style="max-width: 300px;"><?php echo htmlspecialchars($row['description']); ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($row['category']); ?></span>
                                </td>
                                <td><?php echo date('M j, Y (h:i A)', strtotime($row['published_at'])); ?></td>
                                <td>
                                    <a href="list.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to remove this video from KAcademyX?')">
                                        <i class="bi bi-trash-fill"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No YouTube videos imported yet. Click "Import/Add Videos" to start.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div> <!-- col-md-10 content -->
</div> <!-- row -->
</div> <!-- container-fluid -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
