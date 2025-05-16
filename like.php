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

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['post_id']) || !isset($data['action'])) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$result = $api->handleLike($_SESSION['user_id'], (int)$data['post_id'], $data['action']);
echo json_encode($result);