<?php
// counsellor_dashboard.php
session_start();
require 'db_connect.php';

// Security Check: Kick them out if they aren't a counsellor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'counsellor') {
    header("Location: index.php");
    exit();
}

$counsellor_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$message = "";

// Handle the Form Submission (Logging an Intervention)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['log_intervention'])) {
    $alert_id = $_POST['alert_id'];
    $resolution_status = $conn->real_escape_string($_POST['resolution_status']);
    $meeting_date = date("Y-m-d");

    // Insert into Intervention table
    $sql_insert = "INSERT INTO intervention (alert_id, meeting_date, resolution_status) 
                   VALUES ($alert_id, '$meeting_date', '$resolution_status')";
    
    if ($conn->query($sql_insert) === TRUE) {
        $message = "<p style='color: green;'>Intervention logged successfully.</p>";
    } else {
        $message = "<p style='color: red;'>Error logging intervention: " . $conn->error . "</p>";
    }
}

// FEATURE 3: THE CRISIS AUDIT SCANNER
// Find severe alerts (Risk Level >= 3) for students assigned to THIS counsellor
// where no "Resolved" intervention exists yet.
$crisis_sql = "
    SELECT ba.alert_id, u.username AS student_name, ba.risk_level, ba.alert_date 
    FROM burnout_alert ba
    JOIN user u ON ba.user_id = u.user_id
    LEFT JOIN intervention i ON ba.alert_id = i.alert_id
    WHERE u.counsellor_id = $counsellor_id 
      AND ba.risk_level >= 3 
      AND (i.intervention_id IS NULL OR i.resolution_status != 'Resolved')
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
        .header { display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .alert-high { color: #e74c3c; font-weight: bold; }
        input, select { padding: 6px; border: 1px solid #ccc; border-radius: 4px; }
        button { background-color: #3498db; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #2980b9; }
        .logout { background-color: #e74c3c; padding: 8px 12px; text-decoration: none; color: white; border-radius: 4px; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Counsellor Dashboard: <?php echo htmlspecialchars($username); ?></h2>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="card">
        <h3>🚨 Crisis Audit Scanner (Action Required)</h3>
        <p>The following students on your caseload have triggered a severe burnout alert.</p>
        <?php echo $message; ?>

        <table>
            <tr>
                <th>Alert ID</th>
                <th>Student Name</th>
                <th>Risk Level</th>
                <th>Date Triggered</th>
                <th>Action</th>
            </tr>
            <?php 
            if ($crisis_result->num_rows > 0) {
                while($row = $crisis_result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['alert_id'] . "</td>";
                    echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                    echo "<td class='alert-high'>Level " . $row['risk_level'] . "</td>";
                    echo "<td>" . $row['alert_date'] . "</td>";
                    echo "<td>
                            <form method='POST' action='' style='margin:0;'>
                                <input type='hidden' name='alert_id' value='" . $row['alert_id'] . "'>
                                <select name='resolution_status' required>
                                    <option value=''>Update Status...</option>
                                    <option value='Meeting Scheduled'>Meeting Scheduled</option>
                                    <option value='Resolved'>Mark Resolved</option>
                                </select>
                                <button type='submit' name='log_intervention'>Save</button>
                            </form>
                          </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5' style='text-align:center;'>No critical alerts at this time. Great job!</td></tr>";
            }
            ?>
        </table>
    </div>

</body>
</html>