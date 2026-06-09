<?php
// Admin/resources/edit.php
include "../db.php";
include "../includes/header.php";
$pageTitle = "Edit Resource";
include "../includes/sidebar.php";
include "../includes/footer.php";

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$resource_res = $conn->query("SELECT * FROM resources WHERE id = $id");

if (!$resource_res || $resource_res->num_rows == 0) {
    echo "<div class='alert alert-danger'>Resource not found.</div>";
    exit();
}

$resource = $resource_res->fetch_assoc();

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

    $stmt = $conn->prepare("UPDATE resources SET title = ?, description = ?, category = ?, type = ?, size = ?, downloads = ?, image = ?, download_url = ? WHERE id = ?");
    $stmt->bind_param("sssssissi", $title, $description, $category, $type, $size, $downloads, $image, $download_url, $id);

    if ($stmt->execute()) {
        header("Location: list.php?updated=1");
        exit();
    } else {
        $error = "Error updating resource: " . $conn->error;
    }
}
?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Edit Resource</h6>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="title" class="form-label">Resource Title</label>
                    <input type="text" class="form-control" id="title" name="title" required value="<?php echo htmlspecialchars($resource['title']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="Physics" <?php echo $resource['category'] == 'Physics' ? 'selected' : ''; ?>>Physics</option>
                        <option value="Computer Science" <?php echo $resource['category'] == 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                        <option value="Biology" <?php echo $resource['category'] == 'Biology' ? 'selected' : ''; ?>>Biology</option>
                        <option value="Mathematics" <?php echo $resource['category'] == 'Mathematics' ? 'selected' : ''; ?>>Mathematics</option>
                        <option value="Motivation" <?php echo $resource['category'] == 'Motivation' ? 'selected' : ''; ?>>Motivation</option>
                        <option value="Career Guidance" <?php echo $resource['category'] == 'Career Guidance' ? 'selected' : ''; ?>>Career Guidance</option>
                        <option value="Scholarships" <?php echo $resource['category'] == 'Scholarships' ? 'selected' : ''; ?>>Scholarships</option>
                        <option value="Other" <?php echo $resource['category'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="type" class="form-label">Resource Type</label>
                    <input type="text" class="form-control" id="type" name="type" required value="<?php echo htmlspecialchars($resource['type']); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="size" class="form-label">File Size</label>
                    <input type="text" class="form-control" id="size" name="size" required value="<?php echo htmlspecialchars($resource['size']); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="downloads" class="form-label">Downloads Count</label>
                    <input type="number" class="form-control" id="downloads" name="downloads" value="<?php echo $resource['downloads']; ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="image" class="form-label">Cover Image URL</label>
                <input type="url" class="form-control" id="image" name="image" value="<?php echo htmlspecialchars($resource['image']); ?>">
            </div>

            <div class="mb-3">
                <label for="download_url" class="form-label">Download URL / File Link</label>
                <input type="text" class="form-control" id="download_url" name="download_url" required value="<?php echo htmlspecialchars($resource['download_url']); ?>">
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($resource['description']); ?></textarea>
            </div>

            <div class="mb-3 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Update Resource
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
