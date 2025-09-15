<?php
include "../db.php";
include "../includes/header.php";
$pageTitle = "Manage Courses";
include "../includes/sidebar.php";
include "../includes/footer.php";

// Handle delete action
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM courses WHERE id = $id");
    header("Location: list.php");
    exit();
}
?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Courses List</h6>
        <a href="add.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i> Add New Course
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Instructor</th>
                        <th>Description</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT courses.*, instructors.name as instructor_name 
                                          FROM courses 
                                          LEFT JOIN instructors ON courses.instructor_id = instructors.id 
                                          ORDER BY courses.created_at DESC");
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['id']}</td>
                                <td>{$row['title']}</td>
                                <td>{$row['instructor_name']}</td>
                                <td>".substr($row['description'], 0, 100)."...</td>
                                <td>".date('M j, Y', strtotime($row['created_at']))."</td>
                                <td>
                                    <a href='edit.php?id={$row['id']}' class='btn btn-info btn-sm'>
                                        <i class='bi bi-pencil'></i>
                                    </a>
                                    <a href='list.php?delete={$row['id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure?\")'>
                                        <i class='bi bi-trash'></i>
                                    </a>
                                </td>
                              </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>