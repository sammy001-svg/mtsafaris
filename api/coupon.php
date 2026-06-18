<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }

$data   = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$code   = strtoupper(trim($data['code'] ?? ''));
$amount = (float)($data['amount'] ?? 0);

if (!$code) { echo json_encode(['valid'=>false,'message'=>'Please enter a coupon code.']); exit; }

$result = validateCoupon($code, $amount);
echo json_encode($result);
