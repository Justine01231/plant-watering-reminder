<?php
require 'includes/auth.php';
require 'includes/functions.php';

$plants = getPlantsByUser($_SESSION['user_id']);
$today = new DateTime(); // Current date and time in the correct timezone

// Set the timezone explicitly
$timezone = new DateTimeZone('Asia/Manila'); // Replace with your desired timezone
$today->setTimezone($timezone);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watering Schedule - Plant Watering Reminder</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Watering Schedule</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="plants.php">My Plants</a>
                <a href="schedule.php">Watering Schedule</a>
                <a href="reports.php">Reports</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <?php if (isset($_SESSION['warning'])): ?>
                <div class="warning"><?= $_SESSION['warning'] ?></div>
                <?php unset($_SESSION['warning']); ?>
            <?php endif; ?>

            <section class="schedule">
                <h2>Today</h2>
                <ul>
                    <?php foreach ($plants as $plant): ?>
                        <?php
                        // Create a DateTime object for the scheduled time
                        $scheduledDateTime = new DateTime($plant['next_watering_date'] . ' ' . $plant['next_watering_time'], $timezone);
                        $status = '';
                        if ($scheduledDateTime < $today) {
                            $status = 'overdue';
                        } elseif ($scheduledDateTime->format('Y-m-d') === $today->format('Y-m-d')) {
                            $status = 'today';
                        } else {
                            continue; // Skip upcoming dates for this section
                        }
                        ?>
                        <li class="<?= $status ?>">
                            <?= htmlspecialchars($plant['plant_name']) ?> 
                            (<?= htmlspecialchars($plant['type']) ?>)
                            <span><?= $scheduledDateTime->format('M j, Y \a\t h:i A') ?></span>
                            <form method="post" action="mark-watered.php" style="display: inline;">
                                <input type="hidden" name="plant_id" value="<?= $plant['plant_id'] ?>">
                                <button type="submit">Water Now</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <h2>Upcoming</h2>
                <ul>
                    <?php foreach ($plants as $plant): ?>
                        <?php
                        // Create a DateTime object for the scheduled time
                        $scheduledDateTime = new DateTime($plant['next_watering_date'] . ' ' . $plant['next_watering_time'], $timezone);
                        if ($scheduledDateTime->format('Y-m-d') > $today->format('Y-m-d')) {
                            $status = 'upcoming';
                        } else {
                            continue; // Skip overdue/today dates for this section
                        }
                        ?>
                        <li class="<?= $status ?>">
                            <?= htmlspecialchars($plant['plant_name']) ?> 
                            (<?= htmlspecialchars($plant['type']) ?>)
                            <span><?= $scheduledDateTime->format('M j, Y \a\t h:i A') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <h2>Overdue</h2>
                <ul>
                    <?php foreach ($plants as $plant): ?>
                        <?php
                        // Create a DateTime object for the scheduled time
                        $scheduledDateTime = new DateTime($plant['next_watering_date'] . ' ' . $plant['next_watering_time'], $timezone);
                        if ($scheduledDateTime < $today) {
                            $status = 'overdue';
                        } else {
                            continue; // Skip upcoming/today dates for this section
                        }
                        ?>
                        <li class="<?= $status ?>">
                            <?= htmlspecialchars($plant['plant_name']) ?> 
                            (<?= htmlspecialchars($plant['type']) ?>)
                            <span><?= $scheduledDateTime->format('M j, Y \a\t h:i A') ?></span>
                            <form method="post" action="mark-watered.php" style="display: inline;">
                                <input type="hidden" name="plant_id" value="<?= $plant['plant_id'] ?>">
                                <button type="submit">Water Now</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </main>
    </div>
    <script src="assets/js/script.js"></script>
</body>
</html>