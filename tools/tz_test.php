<?php
// Small test harness to simulate schedule check logic.
// Usage (web): tools/tz_test.php?exam_dt=2025-10-30+09:00:00&tz_name=America/New_York
// Usage (CLI): php tools/tz_test.php "2025-10-30 09:00:00" America/Los_Angeles

// Read inputs
$exam_dt = isset($_GET['exam_dt']) ? $_GET['exam_dt'] : (isset($argv[1]) ? $argv[1] : null);
$tz_name = isset($_GET['tz_name']) ? $_GET['tz_name'] : (isset($argv[2]) ? $argv[2] : null);
$tz_offset = isset($_GET['tz_offset']) ? intval($_GET['tz_offset']) : (isset($argv[3]) ? intval($argv[3]) : null);

if (!$exam_dt) {
    echo json_encode(['error' => 'missing exam_dt (e.g. 2025-10-30 09:00:00)']);
    exit(1);
}

$result = [
    'exam_dt' => $exam_dt,
    'tz_name' => $tz_name,
    'tz_offset' => $tz_offset,
];

// Try tz_name first
if (!empty($tz_name)) {
    try {
        $serverTz = new DateTimeZone(date_default_timezone_get());
        $dt = new DateTime($exam_dt, $serverTz);
        $userTz = new DateTimeZone($tz_name);
        $dt->setTimezone($userTz);
        $scheduled_local_date = $dt->format('Y-m-d');
        $now = new DateTime('now', $userTz);
        $current_date = $now->format('Y-m-d');

        $result['scheduled_local_date'] = $scheduled_local_date;
        $result['current_date'] = $current_date;
        $result['allowed'] = ($scheduled_local_date === $current_date);
        echo json_encode($result, JSON_PRETTY_PRINT);
        exit(0);
    } catch (Exception $e) {
        $result['error'] = 'invalid tz_name: ' . $e->getMessage();
    }
}

// Fallback to tz_offset
if ($tz_offset !== null) {
    $user_now_ts = time() - ($tz_offset * 60);
    $current_date = date('Y-m-d', $user_now_ts);
    $scheduled_local_date = date('Y-m-d', strtotime($exam_dt) - ($tz_offset * 60));
    $result['scheduled_local_date'] = $scheduled_local_date;
    $result['current_date'] = $current_date;
    $result['allowed'] = ($scheduled_local_date === $current_date);
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit(0);
}

// Last resort: server-local comparison
$result['scheduled_local_date'] = date('Y-m-d', strtotime($exam_dt));
$result['current_date'] = date('Y-m-d');
$result['allowed'] = ($result['scheduled_local_date'] === $result['current_date']);

echo json_encode($result, JSON_PRETTY_PRINT);
return 0;
