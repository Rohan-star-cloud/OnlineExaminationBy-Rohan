<?php
header("Content-Type: application/json");

// Your OpenRouteService API key
$apiKey = "eyJvcmciOiI1YjNjZTM1OTc4NTExMTAwMDFjZjYyNDgiLCJpZCI6IjMzNzhkMDc4ZTA5NzRhMDY4YmFmMDBiMmIxY2UxYzQ5IiwiaCI6Im11cm11cjY0In0=";

// Kathmandu exam center (you can modify coordinates)
$examCenter = [85.3240, 27.7172];

// Read user coordinates from AJAX POST
$userLat = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
$userLng = isset($_POST['lng']) ? floatval($_POST['lng']) : null;

if (!$userLat || !$userLng) {
    echo json_encode(["status" => "ERROR", "message" => "Missing user coordinates."]);
    exit;
}

// Function to call OpenRouteService API
function getORSData($mode, $start, $end, $apiKey) {
    $url = "https://api.openrouteservice.org/v2/directions/$mode";
    $body = [
        "coordinates" => [
            [$start[0], $start[1]],
            [$end[0], $end[1]]
        ]
    ];

    $options = [
        "http" => [
            "method" => "POST",
            "header" => "Content-Type: application/json\r\n" .
                        "Authorization: $apiKey\r\n",
            "content" => json_encode($body),
            "ignore_errors" => true
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    return json_decode($response, true);
}

// Get results for driving
$drivingData = getORSData("driving-car", [$userLng, $userLat], $examCenter, $apiKey);
$walkingData = getORSData("foot-walking", [$userLng, $userLat], $examCenter, $apiKey);

if (!isset($drivingData['routes'][0]['summary']) || !isset($walkingData['routes'][0]['summary'])) {
    echo json_encode(["status" => "ERROR", "message" => "Could not fetch distance data."]);
    exit;
}

// Extract data
$drivingSummary = $drivingData['routes'][0]['summary'];
$walkingSummary = $walkingData['routes'][0]['summary'];

function formatDistance($meters) {
    return round($meters / 1000, 2) . " km";
}

function formatDuration($seconds) {
    $minutes = round($seconds / 60);
    return $minutes . " min";
}

$response = [
    "status" => "OK",
    "results" => [
        "driving" => [
            "distance" => formatDistance($drivingSummary['distance']),
            "duration" => formatDuration($drivingSummary['duration'])
        ],
        "walking" => [
            "distance" => formatDistance($walkingSummary['distance']),
            "duration" => formatDuration($walkingSummary['duration'])
        ]
    ]
];

echo json_encode($response);
exit;
?>
