<?php
// admin_dashboard.php
session_start();
require 'db_connect.php';

// Security Check: Kick them out if they aren't logged in OR aren't an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];

// FEATURE 4: OUTLIER COURSE DETECTOR
// This query calculates the average study duration (in hours) for every course 
// by finding the time difference between start_time and end_time.
$outlier_sql = "
    SELECT 
        d.dept_name, 
        s.subj_name, 
        COUNT(ss.log_id) AS total_sessions_logged,
        AVG(TIMESTAMPDIFF(MINUTE, ss.start_time, ss.end_time) / 60.0) AS avg_hours_per_session
    FROM department d
    JOIN subject s ON d.dept_id = s.dept_id
    JOIN study_session ss ON s.subj_id = ss.subj_id
    GROUP BY d.dept_name, s.subj_name
    ORDER BY avg_hours_per_session DESC
";
$outlier_result = $conn->query($outlier_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - MindPace</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #faf8f5; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .warning-text { color: #d35400; font-weight: bold; }
        .logout { background-color: #e74c3c; padding: 8px 12px; text-decoration: none; color: white; border-radius: 4px; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Admin Oversight: <?php echo htmlspecialchars($username); ?></h2>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="card">
        <h3>📊 Outlier Course Detector</h3>
        <p>This tool monitors academic workload across departments. Courses with an average session duration of over 3 hours are flagged for review.</p>

        <table>
            <tr>
                <th>Department</th>
                <th>Subject Name</th>
                <th>Total Sessions Logged</th>
                <th>Average Session Duration</th>
                <th>Status</th>
            </tr>
            <?php 
            if ($outlier_result && $outlier_result->num_rows > 0) {
                while($row = $outlier_result->fetch_assoc()) {
                    $avg_hours = round($row['avg_hours_per_session'], 2);
                    
                    // Flag courses that take too long
                    $status = ($avg_hours >= 3.0) ? "<span class='warning-text'>Review Required</span>" : "<span style='color: green;'>Healthy</span>";

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['dept_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['subj_name']) . "</td>";
                    echo "<td>" . $row['total_sessions_logged'] . "</td>";
                    echo "<td>" . $avg_hours . " hours</td>";
                    echo "<td>" . $status . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5' style='text-align:center;'>No study sessions have been logged yet.</td></tr>";
            }
            ?>
        </table>
    </div>

</body>
</html>