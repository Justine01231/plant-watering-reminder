<?php
require 'includes/auth.php';
require 'includes/functions.php';

$error = '';
$success = '';

// Get plant ID from query string
if (!isset($_GET['plant_id'])) {
    header("Location: plants.php");
    exit();
}

$plantId = $_GET['plant_id'];
$plant = null;

try {
    // Fetch plant details
    $stmt = $pdo->prepare("
        SELECT p.*, ws.next_watering_date, ws.next_watering_time
        FROM Plants p
        JOIN WateringSchedule ws ON p.plant_id = ws.plant_id
        WHERE p.plant_id = ? AND p.user_id = ?
    ");
    $stmt->execute([$plantId, $_SESSION['user_id']]);
    $plant = $stmt->fetch();

    if (!$plant) {
        header("Location: plants.php");
        exit();
    }
} catch (PDOException $e) {
    $error = "Error fetching plant details.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plantName = $_POST['plant_name'];
    $plantType = $_POST['type'];
    $frequency = $_POST['frequency'];
    $nextWateringDate = $_POST['date'];
    $nextWateringTime = $_POST['time'];

    // Validate that the selected date is not in the past
    $today = date('Y-m-d'); // Current date
    if ($nextWateringDate < $today) {
        $error = "Error: You cannot set a watering schedule for a past date.";
    } else {
        try {
            // Update plant details
            $pdo->beginTransaction();

            // Update Plants table
            $stmt = $pdo->prepare("UPDATE Plants SET plant_name = ?, type = ?, watering_frequency = ? WHERE plant_id = ?");
            $stmt->execute([$plantName, $plantType, $frequency, $plantId]);

            // Update WateringSchedule table
            $stmt = $pdo->prepare("UPDATE WateringSchedule SET next_watering_date = ?, next_watering_time = ? WHERE plant_id = ?");
            $stmt->execute([$nextWateringDate, $nextWateringTime, $plantId]);

            $pdo->commit();

            // Log the action
            logAction($_SESSION['user_id'], "Edited Plant", "Plant Name: $plantName, Type: $plantType");

            $_SESSION['message'] = "Plant updated successfully";
            header("Location: plants.php");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error updating plant: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Plant - Plant Watering Reminder</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Edit Plant</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="plants.php">My Plants</a>
                <a href="schedule.php">Watering Schedule</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <?php if (!empty($error)): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>

            <section class="edit-plant">
                <h2>Edit Plant Details</h2>
                <form method="post">
                    <div class="form-group">
                        <label for="plant_name">Plant Name:</label>
                        <input type="text" id="plant_name" name="plant_name" value="<?= htmlspecialchars($plant['plant_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="type">Plant Type:</label>
                        <input type="text" id="type" name="type" value="<?= htmlspecialchars($plant['type']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="frequency">Water every X days:</label>
                        <input type="number" id="frequency" name="frequency" min="1" value="<?= $plant['watering_frequency'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="date">Next Watering Date:</label>
                        <input type="date" id="date" name="date" value="<?= htmlspecialchars($plant['next_watering_date'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="time">Next Watering Time:</label>
                        <input type="time" id="time" name="time" value="<?= htmlspecialchars($plant['next_watering_time'] ?? '') ?>" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit">Update Plant</button>
                        <!-- Cancel Button -->
                        <a href="plants.php" class="cancel-btn">Cancel</a>
                    </div>
                </form>
            </section>
        </main>
    </div>
</body>
</html>