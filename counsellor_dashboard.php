<?php
// counsellor_dashboard.php
session_start();
require 'db_connect.php';

// Security Check: Kick them out if they aren't logged in OR aren't a counsellor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'counsellor') {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];
$message = "";

// ---------------------------------------------------------
// FORM HANDLING: RESOLVE CRISIS (Updated to match your DB schema)
// ---------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['resolve_crisis'])) {
    $student_id = (int)$_POST['student_id'];
    $log_date = $conn->real_escape_string($_POST['log_date']);
    $stress_level = (int)$_POST['stress_level'];
    $action_taken = $conn->real_escape_string($_POST['action_taken']);
    $today = date("Y-m-d");
    
    // Step 1: Create a record in the burnout_alert table first
    $sql_alert = "INSERT INTO burnout_alert (user_id, risk_level, alert_date) 
                  VALUES ($student_id, $stress_level, '$log_date')";
    
    if ($conn->query($sql_alert) === TRUE) {
        // Grab the ID of the alert we just created
        $alert_id = $conn->insert_id;
        
        // Step 2: Create the intervention linked to that alert_id
        $sql_intervention = "INSERT INTO intervention (alert_id, meeting_date, resolution_status) 
                             VALUES ($alert_id, '$today', '$action_taken')";
        $conn->query($sql_intervention);
        
        $message = "<p style='color: green; background: #e8f8f5; padding: 10px; border-radius: 5px;'>Crisis successfully logged as a Burnout Alert and marked as resolved!</p>";
    } else {
        $message = "<p style='color: red;'>Error resolving crisis: " . $conn->error . "</p>";
    }
}

// ---------------------------------------------------------
// FEATURE 3: CRISIS AUDIT SCANNER (Updated to match your DB schema)
// Scans for high stress, but excludes logs that already have a burnout_alert on that date
// ---------------------------------------------------------
$crisis_sql = "
    SELECT u.user_id, u.username, wl.stress_level, wl.sleep_hours, al.log_date 
    FROM wellness_log wl 
    JOIN activity_log al ON wl.log_id = al.log_id 
    JOIN user u ON al.user_id = u.user_id 
    LEFT JOIN burnout_alert ba ON ba.user_id = u.user_id AND ba.alert_date = al.log_date
    WHERE wl.stress_level >= 8 AND ba.alert_id IS NULL
    ORDER BY wl.stress_level DESC, al.log_date ASC
";
$crisis_result = $conn->query($crisis_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Counsellor Dashboard - MindPace</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #faf8f5; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;}
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 5px solid #e74c3c; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f9f9f9; }
        .logout { background-color: #34495e; padding: 8px 12px; text-decoration: none; color: white; border-radius: 4px; }
        select, button { padding: 6px; border-radius: 4px; border: 1px solid #ccc; }
        button { background-color: #27ae60; color: white; cursor: pointer; border: none; font-weight: bold; }
        button:hover { background-color: #2ecc71; }
        .danger-text { color: #e74c3c; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <h2>⚕️ Wellbeing Triage | Dr. <?php echo htmlspecialchars($username); ?></h2>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <?php echo $message; ?>

    <div class="card">
        <h3>🚨 Crisis Audit Scanner</h3>
        <p style="color: #555;">Students showing severe burnout (Stress Level 8+). Review and mark as resolved once contacted.</p>
        
        <table>
            <tr>
                <th>Date</th>
                <th>Student</th>
                <th>Stress Level</th>
                <th>Sleep Logged</th>
                <th>Action</th>
            </tr>
            <?php 
            if ($crisis_result && $crisis_result->num_rows > 0) {
                while($row = $crisis_result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['log_date']}</td>
                            <td><strong>" . htmlspecialchars($row['username']) . "</strong></td>
                            <td class='danger-text'>{$row['stress_level']} / 10</td>
                            <td>{$row['sleep_hours']} hrs</td>
                            <td>
                                <form method='POST' action='' style='display:flex; gap:10px;'>
                                    <input type='hidden' name='student_id' value='{$row['user_id']}'>
                                    <input type='hidden' name='log_date' value='{$row['log_date']}'>
                                    <input type='hidden' name='stress_level' value='{$row['stress_level']}'>
                                    
                                    <select name='action_taken' required>
                                        <option value=''>-- Select Resolution --</option>
                                        <option value='Emailed Student'>Emailed Student</option>
                                        <option value='In-person Meeting'>In-person Meeting</option>
                                        <option value='Referred to Doctor'>Referred to Doctor</option>
                                    </select>
                                    <button type='submit' name='resolve_crisis'>Resolve</button>
                                </form>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='5' style='text-align: center; padding: 20px; color: green;'>✅ All clear! No active student crises found.</td></tr>";
            }
            ?>
        </table>
    </div>

</body>
</html>
