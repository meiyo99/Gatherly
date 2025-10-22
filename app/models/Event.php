<?php
/**
 * Event Model
 * Handles all event-related database operations
 */

require_once __DIR__ . '/../config/Database.php';

class Event
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a new event
     * @param array $data Event data
     * @return int|false Event ID on success, false on failure
     */
    public function create($data)
    {
        $sql = "INSERT INTO events (host_id, title, description, event_date, event_time,
                location, location_lat, location_lng, max_guests, status, created_at, updated_at)
                VALUES (:host_id, :title, :description, :event_date, :event_time,
                :location, :location_lat, :location_lng, :max_guests, :status, NOW(), NOW())";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':host_id' => $data['host_id'],
                ':title' => $data['title'],
                ':description' => $data['description'],
                ':event_date' => $data['event_date'],
                ':event_time' => $data['event_time'],
                ':location' => $data['location'],
                ':location_lat' => $data['location_lat'] ?? null,
                ':location_lng' => $data['location_lng'] ?? null,
                ':max_guests' => $data['max_guests'],
                ':status' => $data['status'] ?? 'draft'
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Event creation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find event by ID
     * @param int $id Event ID
     * @return array|false Event data or false if not found
     */
    public function findById($id)
    {
        $sql = "SELECT * FROM events WHERE event_id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch();

            return $result ?: false;
        } catch (PDOException $e) {
            error_log("Event fetch failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find all events by host ID
     * @param int $hostId Host user ID
     * @return array|false Array of events or false on failure
     */
    public function findByHostId($hostId)
    {
        $sql = "SELECT * FROM events WHERE host_id = :host_id ORDER BY event_date DESC, event_time DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':host_id' => $hostId]);
            $result = $stmt->fetchAll();

            return $result ?: [];
        } catch (PDOException $e) {
            error_log("Events fetch by host failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update event by ID
     * @param int $id Event ID
     * @param array $data Updated event data
     * @return bool True on success, false on failure
     */
    public function update($id, $data)
    {
        $sql = "UPDATE events SET
                title = :title,
                description = :description,
                event_date = :event_date,
                event_time = :event_time,
                location = :location,
                location_lat = :location_lat,
                location_lng = :location_lng,
                max_guests = :max_guests,
                status = :status,
                updated_at = NOW()
                WHERE event_id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':title' => $data['title'],
                ':description' => $data['description'],
                ':event_date' => $data['event_date'],
                ':event_time' => $data['event_time'],
                ':location' => $data['location'],
                ':location_lat' => $data['location_lat'] ?? null,
                ':location_lng' => $data['location_lng'] ?? null,
                ':max_guests' => $data['max_guests'],
                ':status' => $data['status']
            ]);
        } catch (PDOException $e) {
            error_log("Event update failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Soft delete event (set status to cancelled)
     * @param int $id Event ID
     * @return bool True on success, false on failure
     */
    public function delete($id)
    {
        $sql = "UPDATE events SET status = 'cancelled', updated_at = NOW() WHERE event_id = :id";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Event deletion failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all events
     * @return array|false Array of all events or false on failure
     */
    public function getAll()
    {
        $sql = "SELECT * FROM events ORDER BY event_date DESC, event_time DESC";

        try {
            $stmt = $this->db->query($sql);
            $result = $stmt->fetchAll();

            return $result ?: [];
        } catch (PDOException $e) {
            error_log("Events fetch failed: " . $e->getMessage());
            return false;
        }
    }
}
