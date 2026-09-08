<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env_loader.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendRentedHandoverMail($toEmail, $pdf) {
    $mail = new PHPMailer(true);

    try {
        // SMTP Config
        $mail->isSMTP();
        $mail->Host       = env('SMTP_HOST');
        $mail->SMTPAuth   = true;
        $mail->Username   = env('SMTP_USERNAME');
        $mail->Password   = env('SMTP_PASSWORD');
        $mail->Port       = env('SMTP_PORT');
        $mail->SMTPSecure = env('SMTP_SECURE');

        // Email Metadata
        $mail->setFrom(env('MAIL_FROM'), env('COMPANY_NAME'));
        $mail->addAddress($toEmail);

        // CC emails (Logic from Code 2)
        $ccEnv = env('MAIL_CC');
        if (!empty($ccEnv)) {
            $ccList = explode(",", $ccEnv);
            foreach ($ccList as $cc) {
                $mail->addCC(trim($cc));
            }
        }

        // Embed Logo for the Signature (Logic from Code 2)
        $logoPath = $_SERVER['DOCUMENT_ROOT'] . "/itms.arukustech.com/public/assets/logo/mail_logo.png";
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'company_logo');
        }

        // Content (Styled like Code 2 but keeping "Rented Equipment" logic)
        $mail->isHTML(true);
        $mail->Subject = "Rented Asset Handover - Arukus Technologies";
        $mail->Body    = "
            <div style='font-family:Arial, sans-serif; color:#333;'>
                <p>Dear <b>Employee</b>,</p>
                <p>Please find the attached handover document for the <b>Rented Equipment</b> assigned to you.</p>
                <p>Kindly keep this for your records.</p>
                <br>
                <p>Regards,<br>
                <b>Arukus Technologies.</b><br>
                <img src='cid:company_logo' style='width:150px; margin-top:10px;'></p>
            </div>";

        // Attach Files
        $mail->addAttachment($pdf);

        $mail->send();
        return true;

    } catch (Exception $e) {
        return false;
    }
}
