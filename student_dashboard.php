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

// --------------------------------------------------------

// ---------------------------------------------------------

// 1. Log Study Session
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['log_study'])) {
    $subj_id = (int)$_POST['subj_id'];
    $focus = (int)$_POST['focus'];
    $start_time = $conn->real_escape_string($_POST['start_time']);
    $end_time = $conn->real_escape_string($_POST['end_time']);
    $log_date = date("Y-m-d", strtotime($start_time));

    
    if (strtotime($start_time) >= strtotime($end_time)) {
        $message = "<div style='background: #fadbd8; padding: 10px; border-left: 5px solid #e74c3c; border-radius: 4px;'><p style='color: #c0392b; margin: 0;'><strong>⚠️ Validation Error:</strong> Your study session cannot end before it starts! Please check your dates.</p></div>";
    } else {
        $sql_parent = "INSERT INTO activity_log (user_id, log_date, log_type) VALUES ($user_id, '$log_date', 'study')";
        if ($conn->query($sql_parent) === TRUE) {
            $log_id = $conn->insert_id;

            $sql_child = "INSERT INTO study_session (log_id, subj_id, start_time, end_time, focus_rating) 
                          VALUES ($log_id, $subj_id, '$start_time', '$end_time', $focus)";
            $conn->query($sql_child);

            $message = "<p style='color: green;'>Study session logged successfully!</p>";
        }
    }
}

// 2. Log Wellness
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['log_wellness'])) {
    $sleep = (float)$_POST['sleep'];
    $stress = (int)$_POST['stress'];
    $log_date = $conn->real_escape_string($_POST['log_date']);
    $mood = 5;

    // Check if wellness log already exists for this student and date
    $check_sql = "SELECT log_id FROM activity_log 
                  WHERE user_id = $user_id 
                  AND log_date = '$log_date' 
                  AND log_type = 'wellness'";

    $check_result = $conn->query($check_sql);

    if ($check_result && $check_result->num_rows > 0) {
        // If log already exists, update it
        $row = $check_result->fetch_assoc();
        $existing_log_id = $row['log_id'];

        $update_sql = "UPDATE wellness_log 
                       SET sleep_hours = $sleep, stress_level = $stress 
                       WHERE log_id = $existing_log_id";

        $conn->query($update_sql);
    } else {
        // If no log exists, insert into parent table first
        $sql_parent = "INSERT INTO activity_log (user_id, log_date, log_type) 
                       VALUES ($user_id, '$log_date', 'wellness')";

        if ($conn->query($sql_parent) === TRUE) {
            $new_log_id = $conn->insert_id;

            // Then insert into child wellness table
            $sql_child = "INSERT INTO wellness_log (log_id, sleep_hours, stress_level, mood_score) 
                          VALUES ($new_log_id, $sleep, $stress, $mood)";

            $conn->query($sql_child);
        }
    }

    $sql_calc = "
        SELECT GREATEST(0, 100 - (GREATEST(0, 8 - wl.sleep_hours) * 8) - (GREATEST(0, wl.stress_level - 1) * 5)) AS immediate_score
        FROM activity_log al
        JOIN wellness_log wl ON al.log_id = wl.log_id
        WHERE al.user_id = $user_id AND al.log_date = '$log_date'
    ";
    $calc_result = $conn->query($sql_calc)->fetch_assoc();
    $readiness_score = $calc_result['immediate_score'];

    $status_color = ($readiness_score >= 75) ? "#27ae60" : (($readiness_score >= 50) ? "#f39c12" : "#e74c3c");
    $status_text = ($readiness_score >= 75) ? "Optimal" : (($readiness_score >= 50) ? "Fatigued" : "High Risk of Burnout");

    $message = "
    <div style='background: white; padding: 15px; border-left: 5px solid {$status_color}; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-radius: 4px;'>
        <h4 style='margin: 0 0 5px 0; color: {$status_color};'>✅ Wellness Logged | Readiness Score: {$readiness_score}% ({$status_text})</h4>
        <p style='margin: 0; font-size: 0.9em; color: #555;'><strong>300-Level Logic:</strong> Score calculated natively via SQL Database Engine using GREATEST() mathematical functions.</p>
    </div>";
}

// 3. Create a Study Group
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_group'])) {
    $group_name = $conn->real_escape_string($_POST['group_name']);
    $joined_date = date("Y-m-d");

    $sql_create_group = "INSERT INTO study_group (group_name) VALUES ('$group_name')";

    if ($conn->query($sql_create_group) === TRUE) {
        $new_grp_id = $conn->insert_id;

        $conn->query("INSERT INTO group_member (user_id, grp_id, joined_date) 
                      VALUES ($user_id, $new_grp_id, '$joined_date')");

        if (isset($_POST['members']) && !empty($_POST['members'])) {
            foreach ($_POST['members'] as $invited_id) {
                $invited_id = (int)$invited_id;

                $conn->query("INSERT INTO group_member (user_id, grp_id, joined_date) 
                              VALUES ($invited_id, $new_grp_id, '$joined_date')");
            }
        }

        $safe_group_name = htmlspecialchars($group_name);
        $message = "<p style='color: green;'>Study group '{$safe_group_name}' created and members invited!</p>";
    } else {
        $message = "<p style='color: red;'>Error creating group: " . $conn->error . "</p>";
    }
}



$students_sql = "SELECT user_id, username 
                 FROM user 
                 WHERE user_type = 'student' 
                 AND user_id != $user_id";

$students_result = $conn->query($students_sql);

$subjects_result = $conn->query("SELECT subj_id, subj_name FROM subject");

// Milestone Appraiser
$hours_sql = "SELECT SUM(ABS(TIMESTAMPDIFF(MINUTE, ss.start_time, ss.end_time)) / 60.0) AS total_hours 
              FROM study_session ss 
              JOIN activity_log al ON ss.log_id = al.log_id 
              WHERE al.user_id = $user_id";

$hours_result = $conn->query($hours_sql)->fetch_assoc();
$total_hours = round($hours_result['total_hours'] ?? 0, 1);

if ($total_hours >= 50) {
    $badge_status = "🌟 Grandmaster Scholar Badge Earned!";
} elseif ($total_hours >= 20) {
    $badge_status = "🏆 Healthy Scholar Badge Earned!";
} elseif ($total_hours >= 10) {
    $badge_status = "🥈 Focused Learner Badge Earned!";
} elseif ($total_hours >= 5) {
    $badge_status = "🥉 Rising Star Badge Earned!";
} else {
    $badge_status = "Keep studying to unlock your first badge (5 hrs)!";
}

// Smart Peer Tutor Matching
$tutor_sql = "
    SELECT DISTINCT u.username AS tutor_name, s.subj_name 
    FROM study_session ss1
    JOIN activity_log al1 ON ss1.log_id = al1.log_id
    JOIN subject s ON ss1.subj_id = s.subj_id
    JOIN study_session ss2 ON ss1.subj_id = ss2.subj_id
    JOIN activity_log al2 ON ss2.log_id = al2.log_id
    JOIN user u ON al2.user_id = u.user_id
    WHERE al1.user_id = $user_id 
    AND ss1.focus_rating <= 5
    AND al2.user_id != $user_id 
    AND ss2.focus_rating >= 8
";

$tutor_result = $conn->query($tutor_sql);

// Habit Trend Table
$habit_sql = "
    SELECT 
        al.log_date, 
        wl.sleep_hours, 
        wl.stress_level,
        GREATEST(0, 100 - 
            (GREATEST(0, 8 - wl.sleep_hours) * 8) - 
            (GREATEST(0, wl.stress_level - 1) * 5)
        ) AS sql_readiness_score
    FROM activity_log al
    JOIN wellness_log wl ON al.log_id = wl.log_id
    WHERE al.user_id = $user_id 
    AND al.log_type = 'wellness'
    ORDER BY al.log_date DESC, al.log_id DESC 
    LIMIT 7
";

$habit_result = $conn->query($habit_sql);

// Study Group MVP
$mvp_sql = "
    SELECT 
        sg.group_name,
        u.username, 
        SUM(ABS(TIMESTAMPDIFF(MINUTE, ss.start_time, ss.end_time)) / 60.0) AS group_hours
    FROM user u
    JOIN group_member gm ON u.user_id = gm.user_id
    JOIN study_group sg ON gm.grp_id = sg.grp_id
    JOIN activity_log al ON u.user_id = al.user_id
    JOIN study_session ss ON al.log_id = ss.log_id
    WHERE gm.grp_id IN (
        SELECT grp_id 
        FROM group_member 
        WHERE user_id = $user_id
    )
    AND al.log_type = 'study'
    GROUP BY sg.group_name, u.username
    ORDER BY sg.group_name, group_hours DESC
    LIMIT 10
";

$mvp_result = $conn->query($mvp_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard - MindPace</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #faf8f5;
            margin: 0;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .grid-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        input, select {
            width: 100%;
            padding: 8px;
            margin: 8px 0 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            background-color: #27ae60;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-weight: bold;
        }

        button:hover {
            background-color: #2ecc71;
        }

        .btn-blue {
            background-color: #3498db;
        }

        .btn-blue:hover {
            background-color: #2980b9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
        }

        .logout {
            background-color: #e74c3c;
            padding: 8px 12px;
            text-decoration: none;
            color: white;
            border-radius: 4px;
        }

        .milestone-box {
            background: #f1c40f;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .status-low {
            color: #27ae60;
            font-weight: bold;
        }

        .status-medium {
            color: #f39c12;
            font-weight: bold;
        }

        .status-high {
            color: #e74c3c;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="header">
        <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <?php echo $message; ?>

    <div class="grid-container">

        <div>
            <div class="card">
                <h3>📝 Log Your Day</h3>

                <form method="POST" action="" style="border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
                    <h4 style="margin: 0 0 10px 0;">1. Study Session</h4>

                    <select name="subj_id" required>
                        <option value="">-- Choose Course --</option>
                        <?php
                        if ($subjects_result && $subjects_result->num_rows > 0) {
                            mysqli_data_seek($subjects_result, 0);

                            while($row = $subjects_result->fetch_assoc()) {
                                echo "<option value='" . $row['subj_id'] . "'>" . htmlspecialchars($row['subj_name']) . "</option>";
                            }
                        }
                        ?>
                    </select>

                    <input type="datetime-local" name="start_time" required>
                    <input type="datetime-local" name="end_time" required>
                    <input type="number" name="focus" min="1" max="10" placeholder="Focus Rating (1-10)" required>

                    <button type="submit" name="log_study">Save Session</button>
                </form>

                <form method="POST" action="">
                    <h4 style="margin: 0 0 10px 0;">2. Wellness Check-in</h4>

                    <label style="font-size: 0.9em; color: #555;">Select Date:</label>
                    <input type="date" name="log_date" required value="<?php echo date('Y-m-d'); ?>">

                    <input type="number" name="sleep" step="0.5" min="0" max="24" placeholder="Hours of Sleep" required>
                    <input type="number" name="stress" min="1" max="10" placeholder="Stress Level (1-10)" required>

                    <button type="submit" name="log_wellness" class="btn-blue">Save Wellness</button>
                </form>

                <form method="POST" action="" style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
                    <h4 style="margin: 0 0 10px 0;">3. Create a Study Group</h4>

                    <input type="text" name="group_name" placeholder="Enter Group Name" required>

                    <label style="font-size: 0.9em; color: #555;">Invite Members Hold Ctrl/Cmd:</label>

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

                    <button type="submit" name="create_group" class="btn-blue" style="background-color: #8e44ad; margin-top: 10px;">
                        Create & Invite
                    </button>
                </form>
            </div>
        </div>

        <div>
            <div class="milestone-box">
                Total Study Time: <?php echo $total_hours; ?> Hours<br>
                <span style="font-size: 0.9em; font-weight: normal;">
                    <?php echo $badge_status; ?>
                </span>
            </div>

            <div class="card">
                <h3>🧮 Historical Readiness Log</h3>
                <p style="font-size: 0.9em; color: #555;">Calculated natively via SQL Database Engine.</p>
                <table>
                    <tr>
                        <th>Date</th>
                        <th>Sleep (Hrs)</th>
                        <th>Stress</th>
                        <th>Calculated Readiness</th>
                    </tr>
                    <?php 
                    if ($habit_result && $habit_result->num_rows > 0) {
                        while($row = $habit_result->fetch_assoc()) {
                            $score = $row['sql_readiness_score'];
                            $color = ($score >= 75) ? "green" : (($score >= 50) ? "orange" : "red");
                            
                            echo "<tr>
                                    <td>{$row['log_date']}</td>
                                    <td>{$row['sleep_hours']}</td>
                                    <td>{$row['stress_level']}</td>
                                    <td><strong style='color: {$color};'>{$score}%</strong></td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4'>Log some wellness data to see your history!</td></tr>";
                    }
                    ?>
                </table>
            </div>
            <div class="card">
                <h3>🤝 Recommended Peer Tutors</h3>
                <p style="font-size: 0.9em; color: #555;">
                    Struggling to focus? These students excel in your tough courses.
                </p>

                <table>
                    <tr>
                        <th>Tutor Name</th>
                        <th>Subject</th>
                    </tr>

                    <?php
                    if ($tutor_result && $tutor_result->num_rows > 0) {
                        while($row = $tutor_result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td><strong>" . htmlspecialchars($row['tutor_name']) . "</strong></td>";
                            echo "<td>" . htmlspecialchars($row['subj_name']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='2'>No matches right now. Keep logging your sessions!</td></tr>";
                    }
                    ?>
                </table>
            </div>

            <div class="card">
                <h3>👑 Study Group MVP</h3>
                <p style="font-size: 0.9em; color: #555;">
                    Leaderboards for your active study groups.
                </p>

                <table>
                    <tr>
                        <th>Group</th>
                        <th>Student</th>
                        <th>Hours Contributed</th>
                    </tr>

                    <?php
                    if ($mvp_result && $mvp_result->num_rows > 0) {
                        $current_group = "";

                        while($row = $mvp_result->fetch_assoc()) {
                            $hours = round($row['group_hours'], 1);

                            if ($current_group != $row['group_name']) {
                                $current_group = $row['group_name'];

                                echo "<tr>";
                                echo "<td colspan='3' style='background: #f9f9f9; font-size: 0.85em;'>";
                                echo "<strong>Group: " . htmlspecialchars($current_group) . "</strong>";
                                echo "</td>";
                                echo "</tr>";
                            }

                            echo "<tr>";
                            echo "<td></td>";
                            echo "<td><strong>" . htmlspecialchars($row['username']) . "</strong></td>";
                            echo "<td>{$hours} hrs</td>";
                            echo "</tr>";
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
