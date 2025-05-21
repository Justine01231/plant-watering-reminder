<?php
require 'config.php';

function addPlant($userId, $plantName, $plantType, $frequency, $date, $time) {
    global $pdo;

    try {
        // Start transaction for atomicity
        $pdo->beginTransaction();
        
        // Debug: Log the user ID that's being used
        error_log("Adding plant for user ID: " . $userId);
        
        // Insert plant details - plant_id should auto-increment
        $stmt = $pdo->prepare("INSERT INTO Plants (user_id, plant_name, type, watering_frequency, last_watered) 
                              VALUES (?, ?, ?, ?, CURDATE())");
        $stmt->execute([$userId, $plantName, $plantType, $frequency]);
        
        // Get the auto-incremented plant_id
        $plantId = $pdo->lastInsertId();
        error_log("New plant created with ID: " . $plantId);
        
        // Use the user-provided date and time
        $nextWateringDate = $date;
        $nextWateringTime = $time;

        // Insert into WateringSchedule
        $stmt = $pdo->prepare("INSERT INTO WateringSchedule (plant_id, next_watering_date, next_watering_time) 
                              VALUES (?, ?, ?)");
        $stmt->execute([$plantId, $nextWateringDate, $nextWateringTime]);
        
        // Commit transaction
        $pdo->commit();

        // Log the action
        logAction($userId, "Added Plant", "Plant Name: $plantName, Type: $plantType");
        
        return $plantId;
    } catch (PDOException $e) {
        // Rollback on error
        $pdo->rollBack();
        error_log("Error adding plant: " . $e->getMessage());
        throw $e;
    }
}
function getPlantsByUser($userId) {
    global $pdo;

    // Log the user ID we're searching for
    error_log("Retrieving plants for user ID: " . $userId);

    // Retrieve plants and their watering schedule
    $stmt = $pdo->prepare("SELECT p.*, ws.next_watering_date, ws.next_watering_time, ws.status 
                          FROM Plants p
                          JOIN WateringSchedule ws ON p.plant_id = ws.plant_id
                          WHERE p.user_id = ?
                          ORDER BY p.plant_id ASC");
    $stmt->execute([$userId]);
    $plants = $stmt->fetchAll();
    
    // Log the number of plants found
    error_log("Found " . count($plants) . " plants for user ID: " . $userId);
    
    return $plants;
}

function markAsWatered($plantId) {
    global $pdo;

    // Get plant's details
    $stmt = $pdo->prepare("SELECT p.watering_frequency, ws.next_watering_date, ws.next_watering_time 
                          FROM Plants p
                          JOIN WateringSchedule ws ON p.plant_id = ws.plant_id
                          WHERE p.plant_id = ?");
    $stmt->execute([$plantId]);
    $plant = $stmt->fetch();

    if ($plant) {
        // Get the current time without seconds
        $now = new DateTime();
        $now->setTime($now->format('H'), $now->format('i'), 0); // Set seconds to 0

        // Combine the scheduled date and time
        $scheduledDateTime = new DateTime($plant['next_watering_date'] . ' ' . $plant['next_watering_time']);
        $scheduledDateTime->setTime($scheduledDateTime->format('H'), $scheduledDateTime->format('i'), 0); // Set seconds to 0

        // Check if watering is too early
        if ($now < $scheduledDateTime) {
            $_SESSION['warning'] = "You are watering this plant earlier.";
        } elseif ($now > $scheduledDateTime) {
            $_SESSION['warning'] = "You are watering this plant later than scheduled.";
        }

        // Update last watered date
        $pdo->prepare("UPDATE Plants SET last_watered = CURDATE() WHERE plant_id = ?")->execute([$plantId]);

        // Calculate next watering date and time
        $nextWateringDate = clone $now;
        $nextWateringDate->modify("+" . $plant['watering_frequency'] . " days");
        $nextWateringTime = $scheduledDateTime->format('H:i:s'); // Keep the same time

        // Update schedule
        $pdo->prepare("UPDATE WateringSchedule 
                       SET next_watering_date = ?, next_watering_time = ?, status = 'pending'
                       WHERE plant_id = ?")
            ->execute([$nextWateringDate->format('Y-m-d'), $nextWateringTime, $plantId]);

        // Log the action
        $details = "Plant ID: $plantId, Next Watering Date: " . $nextWateringDate->format('Y-m-d') .
                   ", Next Watering Time: " . $nextWateringTime;
        logAction($_SESSION['user_id'], "Marked Plant as Watered", $details);
    }
}
function logAction($userId, $action, $details = '') {
    global $pdo;

    try {
        $stmt = $pdo->prepare("INSERT INTO Logs (user_id, action, details) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $action, $details]);
    } catch (PDOException $e) {
        error_log("Error logging action: " . $e->getMessage());
    }
}
?>