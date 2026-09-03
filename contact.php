<?php
header('Content-Type: application/json');

$allowed_origins = ['https://webdev.adfeeder.com', 'https://adfeeder.com', 'https://www.adfeeder.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

// Honeypot -- real visitors never fill this in.
if (!empty($data['botcheck'])) {
    echo json_encode(['success' => true]);
    exit;
}

$name = trim((string)($data['name'] ?? ''));
$email = trim((string)($data['email'] ?? ''));
$dealership = trim((string)($data['dealership'] ?? ''));
$message = trim((string)($data['message'] ?? ''));

if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please fill in your name, a valid email, and a message.']);
    exit;
}

$to = 'support@adfeeder.com';
$subject = 'New AdFeeder website enquiry from ' . $name;

$bodyLines = [
    'Name: ' . $name,
    'Email: ' . $email,
];
if ($dealership !== '') {
    $bodyLines[] = 'Dealership: ' . $dealership;
}
$bodyLines[] = '';
$bodyLines[] = $message;
$body = implode("\n", $bodyLines);

$headers = [
    'From: AdFeeder Website <support@adfeeder.com>',
    'Reply-To: ' . $name . ' <' . $email . '>',
    'Content-Type: text/plain; charset=UTF-8',
];

$sent = mail($to, $subject, $body, implode("\r\n", $headers));

if ($sent) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not send message.']);
}
