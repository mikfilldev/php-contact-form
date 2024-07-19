<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

$env_file_path = realpath(__DIR__ . "/.env");

if (!is_file($env_file_path)) {
    throw new ErrorException("Environment File is Missing.");
}
if (!is_readable($env_file_path)) {
    throw new ErrorException("Permission Denied for reading the " . ($env_file_path) . ".");
}
if (!is_writable($env_file_path)) {
    throw new ErrorException("Permission Denied for writing on the " . ($env_file_path) . ".");
}

$var_arrs = array();
$fopen = fopen($env_file_path, 'r');
if ($fopen) {
    while (($line = fgets($fopen)) !== false) {
        $line_is_comment = (substr(trim($line), 0, 1) == '#') ? true : false;
        // If line is a comment or empty, then skip
        if ($line_is_comment || empty(trim($line)))
            continue;

        // Split the line variable and succeeding comment on line if exists
        $line_no_comment = explode("#", $line, 2)[0];
        // Split the variable name and value
        $env_ex = preg_split('/(\s?)\=(\s?)/', $line_no_comment);
        $env_name = trim($env_ex[0]);
        $env_value = isset($env_ex[1]) ? trim($env_ex[1]) : "";
        $var_arrs[$env_name] = $env_value;
    }
    fclose($fopen);
}

foreach ($var_arrs as $name => $value) {
    putenv("{$name}={$value}");
}

if (!empty($_POST['fullname']) && !empty($_POST['email']) && !empty($_POST['phone']) && !empty($_POST['message'])) {
    $fullname = 'Full name: ' . $_POST['fullname'] . PHP_EOL;
    $email = 'Email: ' . $_POST['email'] . PHP_EOL;
    $phone = 'Phone: ' . $_POST['phone'] . PHP_EOL;
    $message = 'Message: ' . $_POST['message'] . PHP_EOL;
}

var_dump($fullname, $phone, $email, $message);

echo 'SMTP_SERVER_ADDRESS -> ' . getenv('SMTP_SERVER_ADDRESS') . PHP_EOL;
echo 'SMTP_USERNAME -> ' . getenv('SMTP_USERNAME') . PHP_EOL;
echo 'SMTP_PASSWORD -> ' . getenv('SMTP_PASSWORD') . PHP_EOL;

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = SMTP::DEBUG_OFF; #DEBUG_SERVER                      
    $mail->isSMTP();
    $mail->Host       = getenv('SMTP_SERVER_ADDRESS');
    $mail->SMTPAuth   = true;
    $mail->Username   = getenv('SMTP_USERNAME');
    $mail->Password   = getenv('SMTP_PASSWORD');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    $mail->setFrom(getenv('SMTP_USERNAME'), 'Mailer');
    $mail->addAddress($_POST['email'], 'User');

    $mail->isHTML(true);
    $mail->Subject = sprintf('New message from %s on %s', $_POST['email'], date('Y-m-d H:i:s'));

    $htmlTemplate = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 0;
                background-color: #f4f4f4;
            }
            .email-container {
                max-width: 600px;
                margin: auto;
                background-color: #ffffff;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }
            .email-header {
                background-color: #4CAF50;
                color: white;
                padding: 10px;
                text-align: center;
                border-radius: 10px 10px 0 0;
            }
            .email-body {
                padding: 20px;
            }
            .email-footer {
                background-color: #f1f1f1;
                padding: 10px;
                text-align: center;
                border-radius: 0 0 10px 10px;
                font-size: 12px;
                color: #777;
            }
            h1 {
                font-size: 24px;
                margin-bottom: 20px;
            }
            p {
                font-size: 16px;
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h1>New Message Notification</h1>
            </div>
            <div class="email-body">
                <p><strong>Message:</strong> <br> %s</p>
                <p><strong>Phone:</strong> <br> %s</p>
            </div>
            <div class="email-footer">
                <p>This is an automated message. Please do not reply.</p>
            </div>
        </div>
    </body>
    </html>
    ';

    $mail->Body = sprintf($htmlTemplate, $_POST['message'], $_POST['phone']);
    $mail->AltBody = sprintf('This is the body in plain text. Message: %s, Phone: %s', $_POST['message'], $_POST['phone']);

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
