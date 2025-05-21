<?php
require 'includes/auth.php';
require 'includes/config.php';

// Fetch logs for the current user
$stmt = $pdo->prepare("SELECT * FROM Logs WHERE user_id = ? ORDER BY timestamp DESC");
$stmt->execute([$_SESSION['user_id']]);
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Plant Watering Reminder</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Reports</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="plants.php">My Plants</a>
                <a href="schedule.php">Watering Schedule</a>
                <a href="reports.php">Reports</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <section class="reports">
                <h2>Action Logs</h2>

                <!-- Clear All Logs Button -->
                <form method="post" style="margin-bottom: 20px;">
                    <button type="submit" name="clear_logs" class="delete-btn"
                        onclick="return confirm('Are you sure you want to clear all logs?')">
                        Clear All Logs
                    </button>
                </form>

                <?php if (empty($logs)): ?>
                    <p>No logs available.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Details</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['action']) ?></td>
                                    <td><?= htmlspecialchars($log['details']) ?></td>
                                    <td><?= htmlspecialchars($log['timestamp']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>

<?php
// Handle Clear All Logs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM Logs WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $_SESSION['message'] = "All logs cleared successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error clearing logs: " . $e->getMessage();
    }
    header("Location: reports.php");
    exit();
}
?>