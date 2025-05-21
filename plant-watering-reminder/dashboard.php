<?php
require 'includes/auth.php';
require 'includes/functions.php';

$plants = getPlantsByUser($_SESSION['user_id']);
$today = date('Y-m-d');

// Check for plants needing water today
$needsWater = array_filter($plants, function($plant) use ($today) {
    return $plant['next_watering_date'] <= $today && $plant['status'] == 'pending';
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plant Watering Reminder</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Plant Watering Reminder</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="plants.php">My Plants</a>
                <a href="schedule.php">Watering Schedule</a>
                <a href="reports.php">Reports</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <?php if (!empty($needsWater)): ?>
                <div class="alert">
                    <h3>Plants Needing Water Today</h3>
                    <ul>
                        <?php foreach ($needsWater as $plant): ?>
                            <li>
                                <?= htmlspecialchars($plant['plant_name']) ?> 
                                (<?= htmlspecialchars($plant['type']) ?>)
                                <form method="post" action="mark-watered.php" style="display: inline;">
                                    <input type="hidden" name="plant_id" value="<?= $plant['plant_id'] ?>">
                                    <button type="submit">Mark as Watered</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <section class="stats">
                <div class="stat-card">
                    <h3>Total Plants</h3>
                    <p><?= count($plants) ?></p>
                </div>
                <div class="stat-card">
                    <h3>Needing Water Today</h3>
                    <p><?= count($needsWater) ?></p>
                </div>
                <div class="stat-card">
                    <h3>Upcoming in Next 3 Days</h3>
                    <p><?= count(array_filter($plants, function($plant) use ($today) {
                        $threeDaysLater = date('Y-m-d', strtotime('+3 days'));
                        return $plant['next_watering_date'] > $today && 
                               $plant['next_watering_date'] <= $threeDaysLater;
                    })) ?></p>
                </div>
            </section>
        </main>
    </div>
</body>
</html>