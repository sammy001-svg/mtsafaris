<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }

$user = currentUser();
if (!$user) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Please log in to save to your wishlist.']); exit; }

$data      = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$packageId = (int)($data['package_id'] ?? 0);

if (!$packageId) { echo json_encode(['success'=>false,'message'=>'Invalid package.']); exit; }

$existing = DB::row("SELECT id FROM wishlists WHERE user_id=? AND package_id=?", [$user['id'], $packageId]);
if ($existing) {
    DB::delete('wishlists', ['user_id'=>$user['id'],'package_id'=>$packageId]);
    echo json_encode(['success'=>true,'in_wishlist'=>false,'message'=>'Removed from wishlist.']);
} else {
    DB::insert('wishlists', ['user_id'=>$user['id'],'package_id'=>$packageId]);
    echo json_encode(['success'=>true,'in_wishlist'=>true,'message'=>'Added to wishlist.']);
}
