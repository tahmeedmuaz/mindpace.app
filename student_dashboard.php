<?php
// student_dashboard.php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$message = "";

// ---------------------------------------------------------
// FORM HANDLING: LOGGING DATA
// ---------------------------------------------------------

// 1. Log Study Session
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['log_study'])) {
    $subj_id = $_POST['subj_id'];
    $focus = $_POST['focus'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $log_date = date("Y-m-d", strtotime($start_time));

    $sql_parent = "INSERT INTO activity_log (user_id, log_date, log_type) VALUES ($user_id, '$log_date', 'study')";
    if ($conn->query($sql_parent) === TRUE) {
        $log_id = $conn->insert_id; 
        $sql_child = "INSERT INTO study_session (log_id, subj_id, start_time, end_time, focus_rating) 
                      VALUES ($log_id, $subj_id, '$start_time', '$end_time', $focus)";
        $conn->query($sql_child);
        $message = "<p style='color: green;'>Study session logged successfully!</p>";
    }
}

// 2. Log Wellness
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['log_wellness'])) {
    $sleep = $_POST['sleep'];
    $stress = $_POST['stress'];
    $mood = 5; // Defaulting to 5 for simplicity
    $log_date = date("Y-m-d"); // Log for today

    $sql_parent = "INSERT INTO activity_log (user_id, log_date, log_type) VALUES ($user_id, '$log_date', 'wellness')";
    if ($conn->query($sql_parent) === TRUE) {
        $log_id = $conn->insert_id; 
        $sql_child = "INSERT INTO wellness_log (log_id, sleep_hours, stress_level, mood_score) 
                      VALUES ($log_id, $sleep, $stress, $mood)";
        $conn->query($sql_child);
        $message = "<p style='color: green;'>Wellness data logged successfully!</p>";
    }
}

// ---------------------------------------------------------
// DATA QUERIES FOR THE DASHBOARD FEATURES
// ---------------------------------------------------------
// 3. Create a Study Group (Organic Generation)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_group'])) {
    $group_name = $conn->real_escape_string($_POST['group_name']);
    $joined_date = date("Y-m-d");

    // First: Create the group in the parent table
    $sql_create_group = "INSERT INTO study_group (group_name) VALUES ('$group_name')";
    
    if ($conn->query($sql_create_group) === TRUE) {
        $new_grp_id = $conn->insert_id; // Grab the newly created group ID

        // Second: Automatically add the creator as a group member
        $sql_join_group = "INSERT INTO group_member (user_id, grp_id, joined_date) 
                           VALUES ($user_id, $new_grp_id, '$joined_date')";
        
        if ($conn->query($sql_join_group) === TRUE) {
            $message = "<p style='color: green;'>Study group '$group_name' created successfully!</p>";
        } else {
            $message = "<p style='color: red;'>Group created, but failed to join: " . $conn->error . "</p>";
        }
    } else {
        $message = "<p style='color: red;'>Error creating group: " . $conn->error . "</p>";
    }
}

// Fetch Subjects for dropdown
$subjects_result = $conn->query("SELECT subj_id, subj_name FROM subject");

// FEATURE 6: MILESTONE APPRAISER (Total Hours)
$hours_sql = "SELECT SUM(TIMESTAMPDIFF(MINUTE, ss.start_time, ss.end_time) / 60.0) AS total_hours 
              FROM study_session ss JOIN activity_log al ON ss.log_id = al.log_id 
              WHERE al.user_id = $user_id";
$hours_result = $conn->query($hours_sql)->fetch_assoc();
$total_hours = round($hours_result['total_hours'] ?? 0, 1);
$badge_status = ($total_hours >= 20) ? "🏆 Healthy Scholar Badge Earned!" : "Keep studying to unlock badges!";

// FEATURE 1: SMART PEER TUTOR MATCHING
// Finds subjects where YOU had low focus (<=5), and matches you with a student who had high focus (>=8) in that subject.
$tutor_sql = "
    SELECT DISTINCT u.username AS tutor_name, s.subj_name 
    FROM study_session ss1
    JOIN activity_log al1 ON ss1.log_id = al1.log_id
    JOIN subject s ON ss1.subj_id = s.subj_id
    JOIN study_session ss2 ON ss1.subj_id = ss2.subj_id
    JOIN activity_log al2 ON ss2.log_id = al2.log_id
    JOIN user u ON al2.user_id = u.user_id
    WHERE al1.user_id = $user_id AND ss1.focus_rating <= 5
      AND al2.user_id != $user_id AND ss2.focus_rating >= 8
";
$tutor_result = $conn->query($tutor_sql);

// FEATURE 2: HABIT IMPACT ANALYZER
// Joins your wellness logs and study logs on the same date to see correlations
// FEATURE 2: HABIT IMPACT ANALYZER (Bug Fix Applied)
$habit_sql = "
    SELECT 
        al.log_date, 
        wl.sleep_hours, 
        wl.stress_level, 
        (SELECT AVG(ss2.focus_rating) 
         FROM activity_log al_sub
         JOIN study_session ss2 ON al_sub.log_id = ss2.log_id
         WHERE al_sub.user_id = $user_id 
           AND al_sub.log_date = al.log_date 
           AND al_sub.log_type = 'study'
        ) AS avg_focus
    FROM activity_log al
    JOIN wellness_log wl ON al.log_id = wl.log_id
    WHERE al.user_id = $user_id AND al.log_type = 'wellness'
    ORDER BY al.log_date DESC, al.log_id DESC 
    LIMIT 5
";
$habit_result = $conn->query($habit_sql);
// FEATURE 5: CLAIM MVP CHALLENGER
// Finds the study group the current user belongs to, then ranks all members by total study hours
$mvp_sql = "
    SELECT u.username, SUM(TIMESTAMPDIFF(MINUTE, ss.start_time, ss.end_time) / 60.0) AS group_hours
    FROM user u
    JOIN group_member gm ON u.user_id = gm.user_id
    JOIN activity_log al ON u.user_id = al.user_id
    JOIN study_session ss ON al.log_id = ss.log_id
    WHERE gm.grp_id IN (SELECT grp_id FROM group_member WHERE user_id = $user_id)
      AND al.log_type = 'study'
    GROUP BY u.username
    ORDER BY group_hours DESC
    LIMIT 3
";
$mvp_result = $conn->query($mvp_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard - MindPace</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #faf8f5; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .grid-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        input, select { width: 100%; padding: 8px; margin: 8px 0 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #27ae60; color: white; padding: 10px; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-weight: bold;}
        button:hover { background-color: #2ecc71; }
        .btn-blue { background-color: #3498db; }
        .btn-blue:hover { background-color: #2980b9; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        .logout { background-color: #e74c3c; padding: 8px 12px; text-decoration: none; color: white; border-radius: 4px; }
        .milestone-box { background: #f1c40f; padding: 10px; border-radius: 5px; text-align: center; font-weight: bold; margin-bottom: 15px; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <?php echo $message; ?>

    <div class="grid-container">
        <div class="card">
            <h3>📝 Log Your Day</h3>
            
            <form method="POST" action="" style="border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
                <h4 style="margin: 0 0 10px 0;">1. Study Session</h4>
                <select name="subj_id" required>
                    <option value="">-- Choose Course --</option>
                    <?php 
                    mysqli_data_seek($subjects_result, 0);
                    while($row = $subjects_result->fetch_assoc()) echo "<option value='" . $row['subj_id'] . "'>" . htmlspecialchars($row['subj_name']) . "</option>"; 
                    ?>
                </select>
                <input type="datetime-local" name="start_time" required>
                <input type="datetime-local" name="end_time" required>
                <input type="number" name="focus" min="1" max="10" placeholder="Focus Rating (1-10)" required>
                <button type="submit" name="log_study">Save Session</button>
            </form>

            <form method="POST" action="">
                <h4 style="margin: 0 0 10px 0;">2. Wellness Check-in</h4>
                <input type="number" name="sleep" step="0.5" min="0" max="24" placeholder="Hours of Sleep" required>
                <input type="number" name="stress" min="1" max="10" placeholder="Stress Level (1-10)" required>
                <button type="submit" name="log_wellness" class="btn-blue">Save Wellness</button>
            </form>
			
			<form method="POST" action="" style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
                <h4 style="margin: 0 0 10px 0;">3. Create a Study Group</h4>
                
                <input type="text" name="group_name" placeholder="Enter Group Name (e.g., Midnight Coders)" required>
                
                <label style="font-size: 0.9em; color: #555;">Invite Members (Hold Ctrl/Cmd to select multiple):</label>
                <select name="members[]" multiple style="height: 80px; margin-top: 5px;">
                    <?php 
                    if ($students_result && $students_result->num_rows > 0) {
                        while($student = $students_result->fetch_assoc()) {
                            echo "<option value='" . $student['user_id'] . "'>" . htmlspecialchars($student['username']) . "</option>";
                        }
                    } else {
                        echo "<option value=''>No other students found</option>";
                    }
                    ?>
                </select>

                <button type="submit" name="create_group" class="btn-blue" style="background-color: #8e44ad; margin-top: 10px;">Create & Invite</button>
            </form>
        </div>

        <div>
            <div class="milestone-box">
                Total Study Time: <?php echo $total_hours; ?> Hours<br>
                <span style="font-size: 0.9em; font-weight: normal;"><?php echo $badge_status; ?></span>
            </div>

            <div class="card" style="margin-bottom: 20px;">
                <h3>📊 Habit Impact Analyzer</h3>
                <p style="font-size: 0.9em; color: #555;">See how your sleep impacts your focus rating.</p>
                <table>
                    <tr><th>Date</th><th>Sleep (Hrs)</th><th>Stress</th><th>Avg Focus</th></tr>
                    <?php 
                    if ($habit_result && $habit_result->num_rows > 0) {
                        while($row = $habit_result->fetch_assoc()) {
                            $focus = $row['avg_focus'] ? round($row['avg_focus'], 1) : "No study logged";
                            echo "<tr><td>{$row['log_date']}</td><td>{$row['sleep_hours']}</td><td>{$row['stress_level']}</td><td>{$focus}</td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4'>Log some wellness data to see your habits!</td></tr>";
                    }
                    ?>
                </table>
            </div>

            <div class="card">
                <h3>🤝 Recommended Peer Tutors</h3>
                <p style="font-size: 0.9em; color: #555;">Struggling to focus? These students excel in your tough courses.</p>
                <table>
                    <tr><th>Tutor Name</th><th>Subject</th></tr>
                    <?php 
                    if ($tutor_result && $tutor_result->num_rows > 0) {
                        while($row = $tutor_result->fetch_assoc()) {
                            echo "<tr><td><strong>" . htmlspecialchars($row['tutor_name']) . "</strong></td><td>" . htmlspecialchars($row['subj_name']) . "</td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='2'>No matches right now. Keep logging your sessions!</td></tr>";
                    }
                    ?>
                </table>
            </div>
			<div class="card" style="margin-top: 20px;">
                <h3>👑 Study Group MVP</h3>
                <p style="font-size: 0.9em; color: #555;">Leaderboard for your active study groups.</p>
                <table>
                    <tr><th>Rank</th><th>Student</th><th>Hours Contributed</th></tr>
                    <?php 
                    if ($mvp_result && $mvp_result->num_rows > 0) {
                        $rank = 1;
                        while($row = $mvp_result->fetch_assoc()) {
                            $medal = ($rank == 1) ? "🥇 " : (($rank == 2) ? "🥈 " : "🥉 ");
                            $hours = round($row['group_hours'], 1);
                            echo "<tr><td>{$medal}</td><td><strong>" . htmlspecialchars($row['username']) . "</strong></td><td>{$hours} hrs</td></tr>";
                            $rank++;
                        }
                    } else {
                        echo "<tr><td colspan='3'>Join a group and log sessions to see the leaderboard!</td></tr>";
                    }
                    ?>
                </table>
            </div>
        </div>
    </div>

</body>
</html>