<?php
include('db.php');
header('Content-Type: application/json');

// Enable error logging but suppress raw errors from breaking our JSON formatting
error_reporting(0);
ini_set('display_errors', 0);

$response = array();

// Query the database for all available facilities
$query = "SELECT name, address, latitude, longitude, accepted_items, operating_hours FROM e_waste_facilities";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $response[] = array(
            'name'  => $row['name'],
            'addr'  => $row['address'],
            'lat'   => (float)$row['latitude'],
            'lng'   => (float)$row['longitude'],
            'items' => $row['accepted_items'],
            'hours' => $row['operating_hours']
        );
    }
}

// Return the collection to Leaflet safely encoded as JSON
echo json_encode($response);
exit();
?>