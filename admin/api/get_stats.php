<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$db = getDB();
$stats = [];

// Unread messages
$result = $db->query("SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0 AND is_archived = 0");
$stats['messages'] = $result->fetch_assoc()['count'];

echo json_encode($stats);

