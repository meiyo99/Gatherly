<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Faker\Factory;

// load env vars
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Database configuration from .env
$host = getenv('DB_HOST') ?: $_ENV['DB_HOST'] ?? 'localhost';
$dbname = getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?? 'gatherly';
$username = getenv('DB_USER') ?: $_ENV['DB_USER'] ?? 'root';
$password = getenv('DB_PASS') ?: $_ENV['DB_PASS'] ?? '';

try {
    // For XAMPP on macOS, use unix_socket
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    if ($host === 'localhost' && file_exists('/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock')) {
        $dsn = "mysql:unix_socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock;dbname=$dbname;charset=utf8mb4";
    }

    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to database successfully.\n";

    // Initialize Faker
    $faker = Factory::create();

    // Event types for varied generation
    $eventTypes = [
        ['title' => 'Birthday Party', 'description' => 'A celebration of another year around the sun!'],
        ['title' => 'Anniversary Celebration', 'description' => 'Celebrating years of love and commitment.'],
        ['title' => 'Graduation Party', 'description' => 'Celebrating academic achievements and new beginnings.'],
        ['title' => 'Baby Shower', 'description' => 'Welcoming a new little one into the world.'],
        ['title' => 'Wedding Reception', 'description' => 'Celebrating the union of two hearts.'],
        ['title' => 'Engagement Party', 'description' => 'Celebrating a couple\'s engagement.'],
        ['title' => 'Housewarming Party', 'description' => 'Celebrating a new home.'],
        ['title' => 'Holiday Gathering', 'description' => 'Seasonal celebration with friends and family.'],
        ['title' => 'Retirement Party', 'description' => 'Honoring years of hard work and dedication.'],
        ['title' => 'Reunion', 'description' => 'Reconnecting with old friends and family.'],
    ];

    // Create sample users (hosts and guests)
    echo "Creating sample users...\n";
    $userIds = [];

    for ($i = 0; $i < 20; $i++) {
        $firstName = $faker->firstName;
        $lastName = $faker->lastName;
        $email = strtolower($firstName . '.' . $lastName . $i . '@example.com');
        $passwordHash = password_hash('Password123!', PASSWORD_DEFAULT);
        $role = $i < 10 ? 'host' : 'guest';

        $stmt = $pdo->prepare("
            INSERT INTO users (email, password_hash, first_name, last_name, role, email_verified)
            VALUES (?, ?, ?, ?, ?, 1)
        ");

        $stmt->execute([$email, $passwordHash, $firstName, $lastName, $role]);
        $userIds[$role][] = $pdo->lastInsertId();

        echo "Created user: $firstName $lastName ($email)\n";
    }

    // Create 50+ sample events
    echo "\nCreating sample events...\n";
    $eventIds = [];

    for ($i = 0; $i < 55; $i++) {
        $eventType = $faker->randomElement($eventTypes);
        $hostId = $faker->randomElement($userIds['host']);

        // Generate event date (mix of past, current, and future events)
        if ($i < 15) {
            // Past events
            $eventDate = $faker->dateTimeBetween('-6 months', '-1 day');
            $status = 'completed';
        } elseif ($i < 40) {
            // Future events
            $eventDate = $faker->dateTimeBetween('+1 day', '+6 months');
            $status = $faker->randomElement(['published', 'published', 'published', 'draft']);
        } else {
            // Far future events
            $eventDate = $faker->dateTimeBetween('+7 months', '+12 months');
            $status = 'draft';
        }

        $eventTime = $faker->time('H:i:s');
        $location = $faker->streetAddress . ', ' . $faker->city . ', ' . $faker->stateAbbr;
        $locationLat = $faker->latitude(25, 49);
        $locationLng = $faker->longitude(-125, -65);
        $maxGuests = $faker->numberBetween(10, 100);
        $rsvpDeadline = (clone $eventDate)->modify('-7 days')->format('Y-m-d');

        // Create unique title with event type
        $title = $faker->firstName . "'s " . $eventType['title'];
        $descriptions = [
            'Join us for an unforgettable celebration filled with joy and laughter.',
            'We would love to have you celebrate this special occasion with us.',
            'Come and share in our happiness at this wonderful gathering.',
            'Your presence would make this event even more special.',
            'Let\'s create beautiful memories together at this celebration.',
        ];
        $description = $eventType['description'] . ' ' . $faker->randomElement($descriptions);

        $stmt = $pdo->prepare("
            INSERT INTO events (host_id, title, description, event_date, event_time, location,
                              location_lat, location_lng, max_guests, status, rsvp_deadline)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $hostId,
            $title,
            $description,
            $eventDate->format('Y-m-d'),
            $eventTime,
            $location,
            $locationLat,
            $locationLng,
            $maxGuests,
            $status,
            $rsvpDeadline
        ]);

        $eventIds[] = $pdo->lastInsertId();

        echo "Created event: $title (Status: $status, Date: " . $eventDate->format('Y-m-d') . ")\n";
    }

    // Create RSVPs for events
    echo "\nCreating sample RSVPs...\n";
    $rsvpCount = 0;

    foreach ($eventIds as $eventId) {
        // Each event gets between 5 and 20 RSVPs
        $numRsvps = $faker->numberBetween(5, 20);
        $usedEmails = [];

        for ($j = 0; $j < $numRsvps; $j++) {
            // Use both registered users and guest emails
            $useRegisteredUser = $faker->boolean(60); // 60% chance of registered user

            if ($useRegisteredUser && !empty($userIds['guest'])) {
                $guestId = $faker->randomElement($userIds['guest']);
                $guestStmt = $pdo->prepare("SELECT email, first_name, last_name FROM users WHERE user_id = ?");
                $guestStmt->execute([$guestId]);
                $guest = $guestStmt->fetch(PDO::FETCH_ASSOC);
                $guestEmail = $guest['email'];
                $guestName = $guest['first_name'] . ' ' . $guest['last_name'];
            } else {
                $guestId = null;
                $guestEmail = $faker->unique()->email;
                $guestName = $faker->name;
            }

            // Skip if email already used for this event
            if (in_array($guestEmail, $usedEmails)) {
                continue;
            }
            $usedEmails[] = $guestEmail;

            $status = $faker->randomElement(['yes', 'yes', 'yes', 'no', 'maybe', 'pending']);
            $plusOnes = $status === 'yes' ? $faker->numberBetween(0, 3) : 0;
            $dietaryRestrictions = $faker->optional(0.3)->randomElement([
                'Vegetarian',
                'Vegan',
                'Gluten-free',
                'Nut allergy',
                'Dairy-free',
                'No restrictions'
            ]);
            $rsvpToken = bin2hex(random_bytes(16));
            $responseDate = $status !== 'pending' ? $faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d H:i:s') : null;

            $stmt = $pdo->prepare("
                INSERT INTO rsvps (event_id, guest_id, guest_email, guest_name, status,
                                 response_date, plus_ones, dietary_restrictions, rsvp_token)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            try {
                $stmt->execute([
                    $eventId,
                    $guestId,
                    $guestEmail,
                    $guestName,
                    $status,
                    $responseDate,
                    $plusOnes,
                    $dietaryRestrictions,
                    $rsvpToken
                ]);
                $rsvpCount++;
            } catch (PDOException $e) {
                // Skip duplicates
                continue;
            }
        }
    }

    echo "Created $rsvpCount RSVPs.\n";

    // Create sample notifications
    echo "\nCreating sample notifications...\n";
    $notificationCount = 0;

    foreach ($eventIds as $index => $eventId) {
        // Get event details
        $stmt = $pdo->prepare("SELECT host_id, title FROM events WHERE event_id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        // Create notifications for the host
        $notificationTypes = [
            ['type' => 'rsvp_update', 'title' => 'New RSVP Received', 'message' => 'A guest has responded to your event: ' . $event['title']],
            ['type' => 'reminder', 'title' => 'Event Reminder', 'message' => 'Your event "' . $event['title'] . '" is coming up soon!'],
        ];

        // Add 1-3 notifications per event
        $numNotifications = $faker->numberBetween(1, 3);
        for ($k = 0; $k < $numNotifications; $k++) {
            $notification = $faker->randomElement($notificationTypes);
            $isRead = $faker->boolean(40) ? 1 : 0; // 40% chance of being read
            $emailSent = $faker->boolean(80) ? 1 : 0; // 80% chance of email being sent

            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, event_id, type, title, message, is_read, email_sent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $event['host_id'],
                $eventId,
                $notification['type'],
                $notification['title'],
                $notification['message'],
                $isRead,
                $emailSent
            ]);
            $notificationCount++;
        }
    }

    echo "Created $notificationCount notifications.\n";

    echo "\n========================================\n";
    echo "Database seeding completed successfully!\n";
    echo "========================================\n";
    echo "Summary:\n";
    echo "- Users created: " . count($userIds['host']) + count($userIds['guest']) . "\n";
    echo "- Events created: " . count($eventIds) . "\n";
    echo "- RSVPs created: $rsvpCount\n";
    echo "- Notifications created: $notificationCount\n";
    echo "\nSample credentials:\n";
    echo "Admin: admin@gatherly.com / Admin123!\n";
    echo "Users: Any created user email / Password123!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
