<?php
// Admin/resources/list.php
include "../db.php";
include "../includes/header.php";
$pageTitle = "Manage Resources";
include "../includes/sidebar.php";
include "../includes/footer.php";

// Handle delete action
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM resources WHERE id = $id");
    $success_msg = "Resource deleted successfully.";
}
?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Resources List</h6>
        <a href="add.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i> Add New Resource
        </a>
    </div>
    <div class="card-body">
        <?php if (isset($success_msg)): ?>
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
                        <th>Preview</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Size</th>
                        <th>Downloads</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM resources ORDER BY id DESC");
                    if ($result && $result->num_rows > 0):
                        while($row = $result->fetch_assoc()):
                            $image_url = !empty($row['image']) ? $row['image'] : 'https://images.unsplash.com/photo-1635070041078-e363dbe005cb?ixlib=rb-4.0.3&auto=format&fit=crop&w=2069&q=80';
                    ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <img src="<?php echo htmlspecialchars($image_url); ?>" alt="Preview" style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                                    <div class="small text-muted"><?php echo htmlspecialchars(substr($row['description'], 0, 80)); ?>...</div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($row['category']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($row['type']); ?></td>
                                <td><?php echo htmlspecialchars($row['size']); ?></td>
                                <td><?php echo $row['downloads']; ?></td>
                                <td>
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <a href="list.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this resource?')">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No resources found.</td>
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
