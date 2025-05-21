<?php
require 'includes/auth.php';
require 'includes/config.php';
require 'includes/functions.php';

$error = '';
$success = '';

// Verify user_id is correctly set
error_log("Current User ID in plants.php: " . $_SESSION['user_id']);

// Handle session messages
if (isset($_SESSION['message'])) {
    $success = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Plant Logic
    if (isset($_POST['add_plant'])) {
        $selectedDate = $_POST['date'];
        $today = date('Y-m-d'); // Current date
        
        // Debug: Log the form submission details
        error_log("Adding plant - User ID: " . $_SESSION['user_id'] . ", Plant Name: " . $_POST['plant_name']);
        
        // Validate that the selected date is not in the past
        if ($selectedDate < $today) {
            $error = "Error: You cannot set a watering schedule for a past date.";
        } else {
            try {
                // Use the current user_id
                $plantId = addPlant(
                    $_SESSION['user_id'],
                    $_POST['plant_name'],
                    $_POST['type'],
                    $_POST['frequency'],
                    $_POST['date'],
                    $_POST['time']
                );
                
                error_log("Plant added successfully with ID: " . $plantId . " for user: " . $_SESSION['user_id']);
                $_SESSION['message'] = "Plant added successfully";
                header("Location: plants.php");
                exit();
            } catch (PDOException $e) {
                $error = "Error adding plant: " . $e->getMessage();
                error_log("Exception when adding plant: " . $e->getMessage());
            }
        }
    }

    // Delete Plant Logic
    if (isset($_POST['delete_plant'])) {
        try {
            // Using transaction for safety
            $pdo->beginTransaction();

            // First delete from WateringSchedule
            $pdo->prepare("DELETE FROM WateringSchedule WHERE plant_id = ?")
                ->execute([$_POST['plant_id']]);

            // Then delete from Plants
            $pdo->prepare("DELETE FROM Plants WHERE plant_id = ? AND user_id = ?")
                ->execute([$_POST['plant_id'], $_SESSION['user_id']]);

            $pdo->commit();

            // Log the action
        logAction($_SESSION['user_id'], "Deleted Plant", "Plant ID: " . $_POST['plant_id']);

            $_SESSION['message'] = "Plant deleted successfully";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error deleting plant: " . $e->getMessage();
        }
        header("Location: plants.php");
        exit();
    }
}

// Retrieve plants for the logged-in user
$plants = getPlantsByUser($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Plants - Plant Watering Reminder</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>My Plants</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="plants.php">My Plants</a>
                <a href="schedule.php">Watering Schedule</a>
                <a href="reports.php">Reports</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <?php if (!empty($success)): ?>
                <div class="success"><?= $success ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>

            <section class="add-plant">
                <h2>Add New Plant</h2>
                <form method="post">
                    <div class="form-group">
                        <label for="plant_name">Plant Name:</label>
                        <input type="text" id="plant_name" name="plant_name" required>
                    </div>
                    <div class="form-group">
                        <label for="type">Plant Type:</label>
                        <input type="text" id="type" name="type">
                    </div>
                    <div class="form-group">
                        <label for="frequency">Water every X days:</label>
                        <input type="number" id="frequency" name="frequency" min="1" value="7" required>
                    </div>
                    <div class="form-group">
                        <label for="date">First Watering Date:</label>
                        <input type="date" id="date" name="date" required>
                    </div>
                    <div class="form-group">
                        <label for="time">First Watering Time:</label>
                        <input type="time" id="time" name="time" required>
                    </div>
                    <button type="submit" name="add_plant">Add Plant</button>
                </form>
            </section>

            <section class="plant-list">
    <h2>My Plant Collection</h2>
    <?php if (empty($plants)): ?>
        <p>You haven't added any plants yet.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($plants as $plant): ?>
                <li>
                    <strong><?= htmlspecialchars($plant['plant_name']) ?></strong>
                    (<?= htmlspecialchars($plant['type']) ?>)
                    - Water every <?= $plant['watering_frequency'] ?> days
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="plant_id" value="<?= $plant['plant_id'] ?>">
                        <button type="submit" name="delete_plant" class="delete-btn" 
                            onclick="return confirm('Are you sure you want to delete this plant?')">
                            Delete
                        </button>
                    </form>
                    <!-- Add Edit Button -->
                    <a href="edit-plant.php?plant_id=<?= $plant['plant_id'] ?>" class="edit-btn">Edit</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
        </main>
    </div>
    <script src="assets/js/script.js"></script>
</body>
</html>