<?php
// Admin/resources/add.php
include "../db.php";
include "../includes/header.php";
$pageTitle = "Add New Resource";
include "../includes/sidebar.php";
include "../includes/footer.php";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $type = trim($_POST['type']);
    $size = trim($_POST['size']);
    $image = trim($_POST['image']);
    $download_url = trim($_POST['download_url']);
    $downloads = intval($_POST['downloads']);

    if (empty($image)) {
        $image = 'https://images.unsplash.com/photo-1635070041078-e363dbe005cb?ixlib=rb-4.0.3&auto=format&fit=crop&w=2069&q=80';
    }

    $stmt = $conn->prepare("INSERT INTO resources (title, description, category, type, size, downloads, image, download_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssiss", $title, $description, $category, $type, $size, $downloads, $image, $download_url);

    if ($stmt->execute()) {
        header("Location: list.php?success=1");
        exit();
    } else {
        $error = "Error adding resource: " . $conn->error;
    }
}
?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Add New Resource</h6>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="title" class="form-label">Resource Title</label>
                    <input type="text" class="form-control" id="title" name="title" required placeholder="e.g. Physics Fundamentals Handbook">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="Physics">Physics</option>
                        <option value="Computer Science">Computer Science</option>
                        <option value="Biology">Biology</option>
                        <option value="Mathematics">Mathematics</option>
                        <option value="Motivation">Motivation</option>
                        <option value="Career Guidance">Career Guidance</option>
                        <option value="Scholarships">Scholarships</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="type" class="form-label">Resource Type</label>
                    <input type="text" class="form-control" id="type" name="type" required placeholder="e.g. PDF, Interactive, ZIP">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="size" class="form-label">File Size</label>
                    <input type="text" class="form-control" id="size" name="size" required placeholder="e.g. 15.2 MB, 1.2 GB">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="downloads" class="form-label">Initial Downloads Count</label>
                    <input type="number" class="form-control" id="downloads" name="downloads" value="0">
                </div>
            </div>

            <div class="mb-3">
                <label for="image" class="form-label">Cover Image URL</label>
                <input type="url" class="form-control" id="image" name="image" placeholder="Leave empty for default image">
            </div>

            <div class="mb-3">
                <label for="download_url" class="form-label">Download URL / File Link</label>
                <input type="text" class="form-control" id="download_url" name="download_url" required placeholder="e.g. # or absolute download file path">
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4" required placeholder="Detailed information about the resource..."></textarea>
            </div>

            <div class="mb-3 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Save Resource
                </button>
                <a href="list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

</div> <!-- col-md-10 content -->
</div> <!-- row -->
</div> <!-- container-fluid -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
