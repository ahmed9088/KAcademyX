<?php
// PHP CLI script to import scraped MCQs from scraped_mcqs.json into the database
require_once 'forms/db.php';

// Check if JSON file exists
$json_file = 'scraped_mcqs.json';
if (!file_exists($json_file)) {
    die("Error: scraped_mcqs.json not found. Run the python scraper first.\n");
}

$json_data = file_get_contents($json_file);
$mcqs = json_decode($json_data, true);

if ($mcqs === null) {
    die("Error: Failed to parse scraped_mcqs.json (Invalid JSON).\n");
}

$total_count = count($mcqs);
echo "Found $total_count MCQs to import...\n";

$success_count = 0;
$error_count = 0;

// Default fields
$test_type = 'Practice';
$difficulty_level = 'Medium';
$time_limit = 60; // 60 seconds per question

foreach ($mcqs as $m) {
    // Check if question already exists in the database
    $check_stmt = $conn->prepare("SELECT id FROM mcq_questions WHERE question = ?");
    $check_stmt->bind_param("s", $m['question']);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        // Question already exists, skip
        $check_stmt->close();
        continue;
    }
    $check_stmt->close();
    
    // Insert question (omitting instructor_id to avoid constraint violations)
    $insert_query = "INSERT INTO mcq_questions (
        question, 
        option_a, 
        option_b, 
        option_c, 
        option_d, 
        correct_answer, 
        test_type, 
        difficulty_level, 
        category, 
        time_limit, 
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($insert_query);
    if ($stmt) {
        $stmt->bind_param(
            "sssssssssi",
            $m['question'],
            $m['option_a'],
            $m['option_b'],
            $m['option_c'],
            $m['option_d'],
            $m['correct_answer'],
            $test_type,
            $difficulty_level,
            $m['category'],
            $time_limit
        );
        
        if ($stmt->execute()) {
            $success_count++;
        } else {
            $error_count++;
            echo "Error inserting question: " . $stmt->error . "\n";
        }
        $stmt->close();
    } else {
        $error_count++;
        echo "Failed to prepare insert query: " . $conn->error . "\n";
    }
}

echo "\nImport finished!\n";
echo "Successfully imported: $success_count MCQs.\n";
echo "Skipped/Duplicates: " . ($total_count - $success_count - $error_count) . " MCQs.\n";
echo "Errors: $error_count MCQs.\n";

// Remove the json file to clean up
@unlink($json_file);
?>
