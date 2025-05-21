document.addEventListener('DOMContentLoaded', function () {
    // Alarm sound setup
    const alarmSound = new Audio('assets/sounds/alarm.mp3'); // Ensure this path is correct

    // Track triggered notifications to prevent duplicates
    const triggeredNotifications = new Set();

    // Schedule filtering
    const filterButtons = document.querySelectorAll('.schedule-filters button');
    const scheduleRows = document.querySelectorAll('.schedule-list tbody tr');

    // Function to show browser notifications
    function showNotification(message) {
        if (Notification.permission === 'granted') {
            new Notification('Plant Watering Reminder', { body: message });
        } else if (Notification.permission !== 'denied') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    new Notification('Plant Watering Reminder', { body: message });
                }
            });
        }
    }
    

    // Filter buttons logic
    filterButtons.forEach(button => {
        button.addEventListener('click', function () {
            const filter = this.dataset.filter;

            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            // Filter rows
            scheduleRows.forEach(row => {
                const nextWateringDate = row.querySelector('td:nth-child(3)').innerText.split(',')[0].trim();
                const nextWateringTime = row.querySelector('td:nth-child(3)').innerText.split(',')[1].trim();
                const scheduledDateTime = new Date(`${nextWateringDate}T${nextWateringTime}`);
                const now = new Date();

                let category = '';
                if (scheduledDateTime < now) {
                    category = 'overdue';
                } else if (scheduledDateTime.toDateString() === now.toDateString()) {
                    category = 'today';
                } else {
                    category = 'upcoming';
                }

                if (filter === 'all' || filter === category) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // Function to check watering schedule
    function checkWateringSchedule() {
        const now = new Date();
        console.log("Checking schedule at:", now.toISOString());

        // Select all schedule items from the Today and Overdue sections
        const scheduleItems = document.querySelectorAll('.schedule ul li.today, .schedule ul li.overdue');
        console.log("Found schedule items:", scheduleItems.length);

        scheduleItems.forEach(item => {
            // Get the plant name and scheduled time
            const plantName = item.childNodes[0].textContent.trim();
            const timeSpan = item.querySelector('span');
            if (!timeSpan) {
                console.warn("No time span found for item:", plantName);
                return;
            }

            const scheduledText = timeSpan.textContent.trim(); // e.g., "May 2, 2025 at 2:30 PM"
            console.log("Processing plant:", plantName, "Scheduled:", scheduledText);

            // Parse the scheduled date and time
            try {
                const scheduledDateTime = new Date(scheduledText.replace(' at ', ' '));
                if (isNaN(scheduledDateTime.getTime())) {
                    console.error("Invalid date format for:", scheduledText);
                    return;
                }

                // Round both times to the nearest minute for comparison
                const nowMinutes = new Date(now.getFullYear(), now.getMonth(), now.getDate(), now.getHours(), now.getMinutes());
                const scheduledMinutes = new Date(
                    scheduledDateTime.getFullYear(),
                    scheduledDateTime.getMonth(),
                    scheduledDateTime.getDate(),
                    scheduledDateTime.getHours(),
                    scheduledDateTime.getMinutes()
                );

                // Create a unique key for this notification
                const notificationKey = `${plantName}-${scheduledText}`;

                // Check if the current time matches the scheduled time (within the same minute)
                if (
                    nowMinutes.getTime() === scheduledMinutes.getTime() &&
                    !triggeredNotifications.has(notificationKey)
                ) {
                    console.log(`Triggering alarm for ${plantName} at ${scheduledText}`);

                    // Play the alarm sound
                    alarmSound.play().catch(error => {
                        console.error("Failed to play sound:", error);
                    });

                    // Show browser notification
                    showNotification(`Time to water ${plantName}!`);

                    // Show alert as a fallback
                    alert(`Time to water ${plantName}!`);

                    // Highlight the item
                    item.style.backgroundColor = '#ffcccc'; // Light red background

                    // Mark this notification as triggered
                    triggeredNotifications.add(notificationKey);

                    // Clear the notification key after 60 seconds to allow future triggers
                    setTimeout(() => {
                        triggeredNotifications.delete(notificationKey);
                    }, 60000);
                }
            } catch (error) {
                console.error("Error parsing date for:", plantName, error);
            }
        });
    }

    // Check every second
    setInterval(checkWateringSchedule, 1000);

    // Add confirmation for delete actions
    const deleteForms = document.querySelectorAll('form[action*="delete"]');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function (e) {
            if (!confirm('Are you sure you want to delete this plant?')) {
                e.preventDefault();
            }
        });
    });

    // Early watering warning
    const waterNowForms = document.querySelectorAll('form[action="mark-watered.php"]');
    waterNowForms.forEach(form => {
        form.addEventListener('submit', function (e) {
            const nextWateringDate = form.closest('tr').querySelector('td:nth-child(3)').innerText.split(',')[0].trim();
            const nextWateringTime = form.closest('tr').querySelector('td:nth-child(3)').innerText.split(',')[1].trim();
            const scheduledDateTime = new Date(`${nextWateringDate}T${nextWateringTime}`);
            const now = new Date();

            if (now < scheduledDateTime) {
                if (!confirm('This plant is not due for watering yet. Are you sure you want to water it now?')) {
                    e.preventDefault();
                }
            }
        });
    });
});