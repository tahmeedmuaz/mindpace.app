<?php

session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];
$audit_message = "";

// curriculum policy 
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enforce_policy'])) {
    $subj_id = (int)$_POST['subj_id'];
    $course_name = $conn->real_escape_string($_POST['course_name']);
    $reduction_pct = (float)$_POST['reduction_pct']; 

    
    $impact_sql = "
       select count(log_id) as total_sessions, avg(abs(timestampdiff(minute, start_time, end_time)) / 60.0) as current_avg from study_session 
       where subj_id = $subj_id
    ";
    $impact_result = $conn->query($impact_sql)->fetch_assoc();
    
    if ($impact_result['total_sessions'] > 0) {
        $current_avg = $impact_result['current_avg'];
        $total_sessions = $impact_result['total_sessions'];

       
        $new_projected_avg = $current_avg * (1 - ($reduction_pct / 100));
        $hours_saved_per_session = $current_avg - $new_projected_avg;
        $total_system_hours_saved = $hours_saved_per_session * $total_sessions;

        
        $audit_message = "
        <div style='background: #fdf2e9; padding: 15px; border-left: 5px solid #d35400; margin-bottom: 20px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
            <h4 style='color: #d35400; margin: 0 0 10px 0;'>🏛️ Dean's Directive Issued to {$course_name} Faculty</h4>
            <p style='margin: 0 0 10px 0; color: #333;'>An official mandate has been sent to the department requiring a <strong>{$reduction_pct}% reduction</strong> in syllabus workload.</p>
            <div style='background: white; padding: 10px; border: 1px solid #eccbcbb; border-radius: 4px; font-family: monospace; color: #555;'>
                <strong>Systemic Impact Projection:</strong><br>
                • Current Avg Workload: " . round($current_avg, 2) . " hrs<br>
                • Mandated New Target: " . round($new_projected_avg, 2) . " hrs<br>
                • Total Time Given Back to Student Body: <span style='color: #27ae60; font-weight: bold; font-size: 1.1em;'>" . round($total_system_hours_saved, 1) . " Hours</span>
            </div>
        </div>";
    }
}


$outlier_sql = "
   select s.subj_id, d.dept_name, s.subj_name, count(ss.log_id) as total_sessions_logged, avg(abs(timestampdiff(minute, ss.start_time, ss.end_time)) / 60.0) as avg_hours from department d
join subject s on d.dept_id = s.dept_id
join study_session ss on s.subj_id = ss.subj_id
group by s.subj_id, d.dept_name, s.subj_name order by avg_hours desc
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
        .logout { background-color: #e74c3c; padding: 8px 12px; text-decoration: none; color: white; border-radius: 4px; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Admin Oversight: <?php echo htmlspecialchars($username); ?></h2>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="card">
        <h3>📊 Curriculum Workload Monitor</h3>
        <p>This tool monitors academic workload. Courses with an average session duration over 3.0 hours are flagged for administrative intervention.</p>

        <?php echo $audit_message; ?>

        <table>
            <tr>
                <th>Department</th>
                <th>Course</th>
                <th>Sessions Logged</th>
                <th>Avg Duration</th>
                <th>System Status</th>
                <th>Admin Policy Control</th>
            </tr>
            <?php 
            if ($outlier_result && $outlier_result->num_rows > 0) {
                while($row = $outlier_result->fetch_assoc()) {
                    $avg_hours = round($row['avg_hours'], 2);
                    
                    if ($avg_hours >= 3.0) {
                        $status = "<span style='color: #c0392b; font-weight: bold;'>⚠️ Outlier Detected</span>";
                        
                        $action_ui = "
                            <form method='POST' action='' style='margin: 0; display: flex; align-items: center; gap: 8px;'>
                                <input type='hidden' name='subj_id' value='{$row['subj_id']}'>
                                <input type='hidden' name='course_name' value='{$row['subj_name']}'>
                                
                                <label style='font-size: 0.8em; color: #555;'>Mandate Cut:</label>
                                <input type='number' name='reduction_pct' min='5' max='50' value='20' style='width: 60px; padding: 4px; border: 1px solid #ccc; border-radius: 4px;'>
                                <span style='font-size: 0.9em;'>%</span>
                                
                                <button type='submit' name='enforce_policy' style='background-color: #d35400; color: white; padding: 6px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9em;'>Enforce</button>
                            </form>
                        ";
                    } else {
                        $status = "<span style='color: #27ae60;'>✅ Syllabus Optimized</span>";
                        $action_ui = "<span style='color: #95a5a6; font-style: italic;'>No Intervention Required</span>";
                    }

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['dept_name']) . "</td>";
                    echo "<td><strong>" . htmlspecialchars($row['subj_name']) . "</strong></td>";
                    echo "<td>" . $row['total_sessions_logged'] . "</td>";
                    echo "<td>" . $avg_hours . " hours</td>";
                    echo "<td>" . $status . "</td>";
                    echo "<td>" . $action_ui . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6' style='text-align:center;'>No study sessions have been logged yet.</td></tr>";
            }
            ?>
        </table>
    </div>

</body>
</html>
