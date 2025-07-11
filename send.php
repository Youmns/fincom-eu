<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Установите кодировку UTF-8 для PHP
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
header('Content-Type: application/json; charset=UTF-8'); // Указываем, что возвращаем JSON

// Use absolute path for PHPMailer
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'] . '/error.log');
error_reporting(E_ALL);

// Initialize variables
$errors = [];
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
$max_file_size = 5 * 1024 * 1024; // 5MB

// Create upload directory if it doesn't exist
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Field labels for the email table
$fields = [
    'radio-301' => 'Previously Lodged Dispute',
    'text-75' => 'Country of Residence',
    'text-76' => 'Your Email',
    'text-77' => 'First Name',
    'text-78' => 'Family Name',
    'text-79' => 'Phone Number',
    'menu-316' => 'Broker Name',
    'text-552' => 'Other Broker',
    'text-81' => 'Reference Number',
    'textarea-573' => 'Nature of Complaint',
    'textarea-574' => 'Proposed Resolution'
];

// Get the referring page URL to redirect back to it
$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] . '#top' : '/#top';

// Validate and process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    if (empty($_POST['text-76'])) {
        $errors[] = 'Email is required';
    }
    if (empty($_POST['text-77'])) {
        $errors[] = 'First Name is required';
    }

    // Process file uploads
    $attachments = [];
    $file_fields = ['file-836', 'file-837', 'file-838'];
    foreach ($file_fields as $file_field) {
        if (!empty($_FILES[$file_field]['name']) && $_FILES[$file_field]['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES[$file_field]['tmp_name'];
            $file_name = basename($_FILES[$file_field]['name']);
            $file_size = $_FILES[$file_field]['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'mp3', 'wav', 'mp4', 'mov'];

            if ($file_size > $max_file_size) {
                $errors[] = "File $file_name exceeds 5MB limit";
            } elseif (!in_array($file_ext, $allowed_exts)) {
                $errors[] = "File $file_name has an invalid extension";
            } else {
                $new_file_path = $upload_dir . uniqid() . '_' . $file_name;
                if (move_uploaded_file($file_tmp, $new_file_path)) {
                    $attachments[] = $new_file_path;
                } else {
                    $errors[] = "Failed to upload $file_name";
                }
            }
        }
    }

    // If no errors, send email
    if (empty($errors)) {
        // Build HTML table
        $email_body = '<h2>Dispute Resolution Form Submission</h2>';
        $email_body .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
        $email_body .= '<tr><th>Field</th><th>Value</th></tr>';

        foreach ($fields as $field_name => $field_label) {
            $value = isset($_POST[$field_name]) ? $_POST[$field_name] : 'Not provided';
            if (mb_detect_encoding($value, 'UTF-8', true) === false) {
                $value = mb_convert_encoding($value, 'UTF-8', 'auto');
            }
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $email_body .= "<tr><td>$field_label</td><td>$value</td></tr>";
        }
        $email_body .= '</table>';

        // Initialize PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Hostinger SMTP settings
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'contact@financialcommission.io';
            $mail->Password = 'Aizv8zhe!';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->SMTPDebug = 0; // Disable debug output

            $mail->CharSet = 'UTF-8';

            // Recipients
            $mail->setFrom('info@financial-commission.eu', 'Dispute Form');
            $mail->addAddress('contact@financial-commission.eu');

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Dispute Form Submission';
            $mail->Body = $email_body;

            // Add attachments
            foreach ($attachments as $attachment) {
                $mail->addAttachment($attachment);
            }

            $mail->send();

            // Return JSON response with redirect URL
            echo json_encode([
                'status' => 'success',
                'message' => 'Form submitted successfully',
                'redirect' => $redirect_url
            ]);
        } catch (Exception $e) {
            $errors[] = "Failed to send email: {$mail->ErrorInfo}";
            error_log("Email sending failed: {$mail->ErrorInfo}", 0);
        }

        // Clean up uploaded files
        foreach ($attachments as $attachment) {
            if (file_exists($attachment)) {
                unlink($attachment);
            }
        }
    }

    // Если есть ошибки, возвращаем их в JSON
    if (!empty($errors)) {
        echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    }
} else {
    // Если запрос не POST, возвращаем ошибку
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

exit; // Завершаем выполнение скрипта
?>