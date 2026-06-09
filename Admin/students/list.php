<?php
// Admin/students/list.php
include "../db.php";
include "../includes/header.php";
$pageTitle = "Manage Students";
include "../includes/sidebar.php";
include "../includes/footer.php";

// Handle delete action
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Retrieve user_id from students to also delete from users table
    $student_res = $conn->query("SELECT user_id FROM students WHERE id = $id");
    if ($student_res && $student_res->num_rows > 0) {
        $student = $student_res->fetch_assoc();
        $user_id = $student['user_id'];
        
        // Delete student record
        $conn->query("DELETE FROM students WHERE id = $id");
        
        // Delete associated user account if it exists
        if (!empty($user_id)) {
            $conn->query("DELETE FROM users WHERE id = $user_id");
        }
        
        $success_msg = "Student deleted successfully.";
    } else {
        $error_msg = "Student not found.";
    }
}

// Handle Search query
$search = "";
$where_clause = "";
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = $conn->real_escape_string(trim($_GET['search']));
    $where_clause = " WHERE name LIKE '%$search%' OR username LIKE '%$search%' OR email LIKE '%$search%'";
}

// Fetch students count
$total_students_res = $conn->query("SELECT COUNT(*) as total FROM students" . $where_clause);
$total_students = $total_students_res->fetch_assoc()['total'];

// Fetch students
$students_query = "SELECT * FROM students" . $where_clause . " ORDER BY created_at DESC";
$result = $conn->query($students_query);
?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Registered Students (<?php echo $total_students; ?>)</h6>
        <form method="GET" action="" class="d-flex gap-2 align-items-center">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search students..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
            <?php if (!empty($search)): ?>
                <a href="list.php" class="btn btn-secondary btn-sm"><i class="bi bi-x-circle"></i></a>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body">
        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Avatar</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Joined Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <?php 
                                $avatar_url = !empty($row['avatar']) ? getImagePath($row['avatar'], 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?ixlib=rb-4.0.3&auto=format&fit=crop&w=150&q=80') : 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?ixlib=rb-4.0.3&auto=format&fit=crop&w=150&q=80';
                            ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="avatar" class="rounded-circle" width="40" height="40" style="object-fit: cover; border: 2px solid #ddd;">
                                </td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo date('M j, Y (h:i A)', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <a href="list.php?delete=<?php echo $row['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Are you sure you want to delete this student and their login user account?')">
                                        <i class="bi bi-trash-fill"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No students found.</td>
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
