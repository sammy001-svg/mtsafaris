<?php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

$data  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$email = strtolower(trim($data['email'] ?? ''));
$name  = trim($data['name'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success'=>false,'message'=>'Please enter a valid email address.']); exit;
}

$existing = DB::row("SELECT id, is_active FROM newsletter_subscribers WHERE email=?", [$email]);
if ($existing) {
    if ($existing['is_active']) {
        echo json_encode(['success'=>false,'message'=>'This email is already subscribed. Thank you!']); exit;
    }
    DB::update('newsletter_subscribers', ['is_active'=>1,'name'=>$name], ['email'=>$email]);
} else {
    DB::insert('newsletter_subscribers', ['email'=>$email,'name'=>$name,'is_active'=>1,'token'=>generateToken(32)]);
}

echo json_encode(['success'=>true,'message'=>'Thank you for subscribing! You\'ll receive travel inspiration in your inbox.']);
