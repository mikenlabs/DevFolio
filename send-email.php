<?php
header('Content-Type: application/json');

function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($key, $val) = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        if (!empty($key) && !getenv($key)) {
            putenv($key . '=' . $val);
        }
    }
}

loadEnv(__DIR__ . '/.env');

$apiKey = getenv('BREVO_API_KEY');
$fromEmail = getenv('BREVO_FROM_EMAIL') ?: 'noreply@example.com';
$toEmail = getenv('BREVO_TO_EMAIL') ?: 'you@example.com';

$response = ['success' => false, 'message' => ''];

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($name) || empty($email) || empty($message)) {
    $response['message'] = 'Please fill in all required fields.';
    echo json_encode($response);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Please provide a valid email address.';
    echo json_encode($response);
    exit;
}

$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
$subject = htmlspecialchars($subject ?: 'Portfolio Contact', ENT_QUOTES, 'UTF-8');
$message = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

if (empty($apiKey)) {
    $response['message'] = 'Server configuration error. Please try again later.';
    echo json_encode($response);
    exit;
}

$htmlContent = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <style>
        body { font-family: 'Montserrat', sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 30px; }
        .header { background: #096727; color: #fff; padding: 25px; text-align: center; }
        .header h2 { margin: 0; font-size: 22px; }
        .content { padding: 25px; background: #f9f9f9; }
        .field { margin-bottom: 15px; }
        .label { font-weight: 600; color: #096727; display: block; margin-bottom: 5px; font-size: 14px; }
        .value { font-size: 16px; }
        .message-box { background: #fff; padding: 15px; border-left: 4px solid #11ce4d; border-radius: 4px; margin-top: 10px; }
        .footer { text-align: center; padding: 15px; color: #797979; font-size: 13px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>New Message from Portfolio</h2>
        </div>
        <div class='content'>
            <div class='field'>
                <span class='label'>Name</span>
                <span class='value'>{$name}</span>
            </div>
            <div class='field'>
                <span class='label'>Email</span>
                <span class='value'>{$email}</span>
            </div>
            <div class='field'>
                <span class='label'>Subject</span>
                <span class='value'>{$subject}</span>
            </div>
            <div class='field'>
                <span class='label'>Message</span>
                <div class='message-box'>{$message}</div>
            </div>
        </div>
        <div class='footer'>
            Sent from Uche Michael's portfolio contact form
        </div>
    </div>
</body>
</html>";

$payload = json_encode([
    'sender' => ['email' => $fromEmail, 'name' => 'Portfolio Contact Form'],
    'to' => [['email' => $toEmail, 'name' => $name]],
    'replyTo' => ['email' => $email, 'name' => $name],
    'subject' => '[Portfolio] ' . $subject,
    'htmlContent' => $htmlContent,
]);

$ch = curl_init('https://api.brevo.com/v3/smtpEmail');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
    'api-key: ' . $apiKey,
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    $response['message'] = 'Connection error. Please try again later.';
    echo json_encode($response);
    exit;
}

$decoded = json_decode($result, true);

if ($httpCode >= 200 && $httpCode < 300) {
    $response['success'] = true;
    $response['message'] = 'Message sent successfully! I will get back to you soon.';
} else {
    $errorMsg = $decoded['message'] ?? 'Failed to send message. Please try again later.';
    $response['message'] = 'Error: ' . $errorMsg;
}

echo json_encode($response);
?>
