<?php
require_once '../core.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Получение комментариев
    if (!isset($_GET['post_id'])) {
        echo json_encode(['success' => false, 'error' => 'Missing post_id']);
        exit;
    }
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $result = $api->getPostComments((int)$_GET['post_id'], $page);
    echo json_encode($result);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Добавление комментария
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not authorized']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['post_id']) || !isset($data['content'])) {
        echo json_encode(['success' => false, 'error' => 'Missing parameters']);
        exit;
    }
    
    $result = $api->addComment($_SESSION['user_id'], (int)$data['post_id'], $data['content']);
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}