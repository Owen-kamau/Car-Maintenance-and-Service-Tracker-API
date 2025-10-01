<?php
// Database connection
$servername = "localhost";
$username   = "root";
$password   = "6350";   // your MySQL password
$dbname     = "car_service_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// =======================
// OOP CLASS
// =======================
class CarServiceTracker {
    private $db;

    public function __construct($db_connection) {
        $this->db = $db_connection;
    }

    // Heading
    public function heading() {
        echo "<h1>🚗 Car Maintenance & Service Tracker</h1>";
    }

    // Show Owners and Cars
    public function showOwnersAndCars() {
        $sql = "SELECT owners.full_name, owners.phone, cars.make, cars.model, cars.year, cars.license_plate
                FROM cars
                JOIN owners ON cars.owner_id = owners.owner_id";
        $result = $this->db->query($sql);

        if ($result->num_rows > 0) {
            echo "<h2>Registered Cars</h2>";
            echo "<table border='1' cellpadding='8'>
                    <tr><th>Owner</th><th>Phone</th><th>Car</th><th>Year</th><th>License Plate</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['full_name']}</td>
                        <td>{$row['phone']}</td>
                        <td>{$row['make']} {$row['model']}</td>
                        <td>{$row['year']}</td>
                        <td>{$row['license_plate']}</td>
                      </tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No cars registered yet.</p>";
        }
    }

    // Show Service History
    public function showServiceHistory() {
        $sql = "SELECT cars.license_plate, services.service_name, mechanics.full_name AS mechanic, 
                       service_records.service_date, service_records.cost, service_records.notes
                FROM service_records
                JOIN cars ON service_records.car_id = cars.car_id
                JOIN services ON service_records.service_id = services.service_id
                JOIN mechanics ON service_records.mechanic_id = mechanics.mechanic_id
                ORDER BY service_records.service_date DESC";
        $result = $this->db->query($sql);

        if ($result->num_rows > 0) {
            echo "<h2>Service History</h2>";
            echo "<table border='1' cellpadding='8'>
                    <tr><th>Car</th><th>Service</th><th>Mechanic</th><th>Date</th><th>Cost</th><th>Notes</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['license_plate']}</td>
                        <td>{$row['service_name']}</td>
                        <td>{$row['mechanic']}</td>
                        <td>{$row['service_date']}</td>
                        <td>KES {$row['cost']}</td>
                        <td>{$row['notes']}</td>
                      </tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No service history yet.</p>";
        }
    }

    // Footer
    public function footer() {
        echo "<footer><p>📧 Contact us at <a href='mailto:info@cartracker.com'>info@cartracker.com</a></p></footer>";
    }
}
