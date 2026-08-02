<?php
require_once __DIR__ . '/../../app/helpers.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['mark_all'])) {
        $_SESSION['read_notifs'] = $_SESSION['read_notifs'] ?? [];
        foreach ($data['ids'] ?? [] as $id) {
            $_SESSION['read_notifs'][$id] = true;
        }
        echo json_encode(['success' => true]);
        exit;
    }
    
    if (isset($data['mark_id'])) {
        $_SESSION['read_notifs'] = $_SESSION['read_notifs'] ?? [];
        $_SESSION['read_notifs'][$data['mark_id']] = true;
        echo json_encode(['success' => true]);
        exit;
    }
}
