<?php
// Include database connection
include "../db.php";

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: list.php?error=Invalid instructor ID");
    exit();
}

$instructor_id = $_GET['id'];

// Verify instructor exists
$check_query = "SELECT profile_image FROM instructors WHERE id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: list.php?error=Instructor not found");
    exit();
}

$instructor = $result->fetch_assoc();
$profile_image = $instructor['profile_image'];

// Delete the instructor
$delete_query = "DELETE FROM instructors WHERE id = ?";
$stmt = $conn->prepare($delete_query);
$stmt->bind_param("i", $instructor_id);

if ($stmt->execute()) {
    // Delete the profile image if it exists
    if (!empty($profile_image)) {
        // Extract filename from path
        $image_path = "../" . $profile_image;
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    header("Location: list.php?success=2");
    exit();
} else {
    header("Location: list.php?error=Failed to delete instructor");
    exit();
}
?>