<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Adjust path to PHPMailer autoload

// Initialize response
$response = ['status' => 'error', 'message' => 'Something went wrong'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $number = filter_var($_POST['number'], FILTER_SANITIZE_STRING);
    $city = filter_var($_POST['city'], FILTER_SANITIZE_STRING);
    $message = filter_var($_POST['message'], FILTER_SANITIZE_STRING);

    // Validate inputs
    if (empty($name) || empty($email) || empty($number) || empty($city) || empty($message)) {
        $response['message'] = 'All fields are required';
        echo json_encode($response);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email address';
        echo json_encode($response);
        exit;
    }

    // Initialize PHPMailer
    $mail = new PHPMailer(true);
    try {
        // SMTP settings for Zoho Mail
        $mail->isSMTP();
        $mail->Host = 'smtp.zoho.in';
        $mail->SMTPAuth = true;
        $mail->Username = 'support@tracewatt.com'; // Your Zoho Mail email
        $mail->Password = 'tQyNXBjMxWza'; // Replace with your Zoho Mail password or app-specific password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Sender and reply-to
        $mail->setFrom('support@tracewatt.com', 'TRACEWATT No-Reply');
        $mail->addReplyTo('support@tracewatt.com', 'TRACEWATT Support');

        // Thank you email to user
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Thank You for Contacting TRACEWATT';
        $mail->Body = '
    <div style="margin:0;padding:0;background-color:#0F172A;">
        <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:640px;margin:auto;padding:50px 20px;font-family:\'Segoe UI\', sans-serif;">
            <tr>
                <td align="center">
                    <div style="
                        background: rgba(255, 255, 255, 0.08);
                        backdrop-filter: blur(18px);
                        border: 1px solid rgba(255,255,255,0.15);
                        border-radius: 20px;
                        padding: 50px 30px;
                        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    ">
                        <img src="https://www.tracewatt.com/assets/img/solar/TRACEWATT-NE.png" alt="TRACEWATT" style="max-width:120px;margin-bottom:30px;" />

                        <h2 style="font-size:28px;color:#FFD700;margin-bottom:12px;">Thank You, '.$name.'!</h2>

                        <p style="font-size:16px;color:#ECECEC;line-height:1.8;margin:0 0 20px;">
                            We appreciate you reaching out to <strong style="color:#A084E8;">TRACEWATT</strong>.<br>
                            Our expert team has received your message and will connect with you soon.
                        </p>

                        <a href="https://tracewatt.com" style="
                            display:inline-block;
                            margin-top:30px;
                            padding:12px 28px;
                            background:linear-gradient(135deg, #6C4AB6, #A084E8);
                            color:#ffffff;
                            border-radius:10px;
                            font-size:15px;
                            text-decoration:none;
                            font-weight:500;
                            box-shadow:0 8px 20px rgba(160,132,232,0.4);
                        ">
                            Explore TRACEWATT
                        </a>

                        <div style="margin-top:40px;font-size:13px;color:#B0B0B0;line-height:1.6;">
                            This is an automated message from a secure no-reply address.<br>
                            For direct communication, email us at 
                            <a href="mailto:support@tracewatt.com" style="color:#FFD700;text-decoration:none;">support@tracewatt.com</a>.
                        </div>

                        <p style="color:#ECECEC;font-size:14px;margin-top:35px;">— The TRACEWATT Team</p>
                    </div>
                </td>
            </tr>
        </table>
    </div>
';

        $mail->send();
        $mail->clearAddresses();

        // Notification email to admin
        $mail->addAddress('support@tracewatt.com', 'TRACEWATT Admin');
        $mail->Subject = 'New Contact Form Submission';
        $mail->Body = "
            <h2>New Contact Form Submission</h2>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Phone:</strong> $number</p>
            <p><strong>City:</strong> $city</p>
            <p><strong>Message:</strong> $message</p>
            <p>Submitted on: " . date('Y-m-d H:i:s') . "</p>
            <p><strong>Note:</strong> This is an automated message from a do-not-reply address. Please direct any replies to <a href='mailto:support@tracewatt.com'>support@tracewatt.com</a>.</p>
        ";
        $mail->send();

        // Send data to Google Apps Script
        $appsScriptUrl = 'https://script.google.com/macros/s/AKfycbwtmRhfH4LhOkU1ueVgVc093UeFzIPfwNM8FHHeuiceFzExAtWbuyySvywBXNRH2kF4GA/exec'; // Replace with your deployed web app URL
        $data = [
            'name' => $name,
            'email' => $email,
            'number' => $number,
            'city' => $city,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data)
            ]
        ];
        $context = stream_context_create($options);
        $result = file_get_contents($appsScriptUrl, false, $context);

        if ($result === false) {
            $response['message'] = 'Error saving to Google Sheet';
        } else {
            $response = ['status' => 'success', 'message' => 'Form submitted successfully'];
        }
    } catch (Exception $e) {
        $response['message'] = 'Error sending email: ' . $mail->ErrorInfo;
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
?>