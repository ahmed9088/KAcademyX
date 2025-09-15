<?php
include "../db.php";
include "../includes/header.php";
$pageTitle = "Add New Course";
include "../includes/sidebar.php";
include "../includes/footer.php";

// Get instructors for dropdown
$instructors = $conn->query("SELECT * FROM instructors ORDER BY name");

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $instructor_id = $_POST['instructor_id'];
    
    $sql = "INSERT INTO courses (title, description, instructor_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $title, $description, $instructor_id);
    
    if ($stmt->execute()) {
        header("Location: list.php?success=1");
        exit();
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Add New Course</h6>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="title" class="form-label">Course Title</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>
            <div class="mb-3">
                <label for="instructor_id" class="form-label">Instructor</label>
                <select class="form-select" id="instructor_id" name="instructor_id" required>
                    <option value="">Select Instructor</option>
                    <?php while($row = $instructors->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Course Description</label>
                <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Save Course
            </button>
            <a href="list.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>