<?php
require_once '../core.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit;
}

if (!isset($_FILES['media'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

$result = $api->uploadMedia($_SESSION['user_id'], $_FILES['media']);
echo json_encode($result);