<?php
// includes/badge_helper.php - Awards student badges and updates ranks

function insert_student_badge($conn, $student_id, $badge_name, $badge_type, $description, $test_id) {
    // Check if they already have this badge for this test
    $check = $conn->query("SELECT id FROM student_badges 
                           WHERE student_id = $student_id 
                           AND badge_type = '" . mysqli_real_escape_string($conn, $badge_type) . "' 
                           AND test_id = $test_id");
    if ($check && $check->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO student_badges (student_id, badge_name, badge_type, description, test_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $student_id, $badge_name, $badge_type, $description, $test_id);
        $stmt->execute();
        $stmt->close();
        
        // Push a notification to the student
        $notif_title = "🏆 New Badge Unlocked!";
        $notif_msg = "Congratulations! You earned the '" . $badge_name . "' badge for your performance in the exam.";
        $notif_stmt = $conn->prepare("INSERT INTO notifications (student_id, title, message, link) VALUES (?, ?, ?, 'profile.php')");
        $notif_stmt->bind_param("iss", $student_id, $notif_title, $notif_msg);
        $notif_stmt->execute();
        $notif_stmt->close();
    }
}

function award_badges($conn, $student_id, $test_id, $attempt_id) {
    // 1. Fetch student's result metrics
    $res_stmt = $conn->prepare("SELECT score, percentage, time_taken_seconds, is_passed FROM results WHERE attempt_id = ?");
    $res_stmt->bind_param("i", $attempt_id);
    $res_stmt->execute();
    $result = $res_stmt->get_result()->fetch_assoc();
    $res_stmt->close();
    
    if (!$result) {
        return; // Result not published or graded yet
    }
    
    $student_score = $result['score'];
    $is_passed = intval($result['is_passed']);
    $time_taken = intval($result['time_taken_seconds']);
    
    // 2. Fetch test and subject details
    $test_stmt = $conn->prepare("SELECT t.title, s.name as subject_name, s.id as subject_id 
                                 FROM tests t 
                                 JOIN subjects s ON t.subject_id = s.id 
                                 WHERE t.id = ?");
    $test_stmt->bind_param("i", $test_id);
    $test_stmt->execute();
    $test = $test_stmt->get_result()->fetch_assoc();
    $test_stmt->close();
    
    if (!$test) {
        return;
    }
    
    $test_title = $test['title'];
    $subject_name = $test['subject_name'];
    $subject_id = $test['subject_id'];
    
    // 3. Recalculate rank position for all students in this test (using tie-breaker: score DESC, time ASC)
    $scores_res = $conn->query("SELECT student_id, MAX(score) as best_score, MIN(time_taken_seconds) as best_time 
                                FROM results 
                                WHERE test_id = $test_id 
                                GROUP BY student_id 
                                ORDER BY best_score DESC, best_time ASC");
    
    $rank = 1;
    while ($sc = $scores_res->fetch_assoc()) {
        if ($sc['student_id'] == $student_id) {
            break;
        }
        $rank++;
    }
    
    // 4. Update the Leaderboard entry with correct rank position
    $conn->query("DELETE FROM leaderboards WHERE test_id = $test_id AND student_id = $student_id");
    $lead_stmt = $conn->prepare("INSERT INTO leaderboards (test_id, student_id, score, rank_position) 
                                 VALUES (?, ?, ?, ?)");
    $lead_stmt->bind_param("iidi", $test_id, $student_id, $student_score, $rank);
    $lead_stmt->execute();
    $lead_stmt->close();
    
    // 5. Fetch Max possible points for this test
    $max_res = $conn->query("SELECT SUM(q.points) FROM test_questions tq 
                             JOIN questions q ON tq.question_id = q.id 
                             WHERE tq.test_id = $test_id");
    $max_points = $max_res ? ($max_res->fetch_row()[0] ?: 1) : 1;
    
    // 6. Award badges based on performance criteria
    // Top 10 Finisher
    if ($rank <= 10) {
        insert_student_badge($conn, $student_id, "Top 10 Finisher", "top_10", "Ranked in the top 10 on '" . $test_title . "'.", $test_id);
    }
    
    // Top 50 Finisher
    if ($rank <= 50) {
        insert_student_badge($conn, $student_id, "Top 50 Finisher", "top_50", "Ranked in the top 50 on '" . $test_title . "'.", $test_id);
    }
    
    // Top 100 Finisher
    if ($rank <= 100) {
        insert_student_badge($conn, $student_id, "Top 100 Finisher", "top_100", "Ranked in the top 100 on '" . $test_title . "'.", $test_id);
    }
    
    // Perfect Score
    if ($student_score == $max_points) {
        insert_student_badge($conn, $student_id, "Perfect Score", "perfect_score", "Achieved a 100% perfect score on '" . $test_title . "'.", $test_id);
    }
    
    // Fastest Finisher (must pass and have the lowest time among all passing scores on this test)
    if ($is_passed) {
        $faster_res = $conn->query("SELECT COUNT(*) FROM results 
                                    WHERE test_id = $test_id 
                                    AND time_taken_seconds < $time_taken 
                                    AND is_passed = 1");
        if ($faster_res && $faster_res->fetch_row()[0] == 0) {
            insert_student_badge($conn, $student_id, "Fastest Finisher", "fastest_finisher", "Completed '" . $test_title . "' in the fastest time among all passing students.", $test_id);
        }
    }
    
    // Highest Subject-wise Score (Physics / Mathematics)
    if ($is_passed) {
        $better_subj_res = $conn->query("SELECT COUNT(*) FROM results r 
                                         JOIN tests t ON r.test_id = t.id 
                                         WHERE t.subject_id = $subject_id 
                                         AND r.score > $student_score");
        if ($better_subj_res && $better_subj_res->fetch_row()[0] == 0) {
            if ($subject_name == 'Physics') {
                insert_student_badge($conn, $student_id, "Highest Physics Score", "highest_subject_physics", "Achieved the highest score on a Physics test in KAcademyX.", $test_id);
            } elseif ($subject_name == 'Mathematics') {
                insert_student_badge($conn, $student_id, "Highest Mathematics Score", "highest_subject_math", "Achieved the highest score on a Mathematics test in KAcademyX.", $test_id);
            }
        }
    }
}
?>
