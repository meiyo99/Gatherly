<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use SendGrid\Mail\Mail;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email
{
    private static function sendWithSendGrid($to, $subject, $htmlContent)
    {
        try {
            $email = new Mail();
            $email->setFrom($_ENV['SENDGRID_FROM_EMAIL'], $_ENV['SENDGRID_FROM_NAME']);
            $email->setSubject($subject);
            $email->addTo($to);
            $email->addContent("text/html", $htmlContent);

            $sendgrid = new \SendGrid($_ENV['SENDGRID_API_KEY']);
            $response = $sendgrid->send($email);

            return $response->statusCode() >= 200 && $response->statusCode() < 300;
        } catch (Exception $e) {
            error_log("SendGrid Error: " . $e->getMessage());
            return false;
        }
    }

    private static function sendWithPHPMailer($to, $subject, $htmlContent)
    {
        try {
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SMTP_USERNAME'];
            $mail->Password = $_ENV['SMTP_PASSWORD'];
            $mail->SMTPSecure = $_ENV['SMTP_SECURE'];
            $mail->Port = $_ENV['SMTP_PORT'];

            $mail->setFrom($_ENV['SMTP_FROM_EMAIL'], $_ENV['SMTP_FROM_NAME']);
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlContent;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $e->getMessage());
            return false;
        }
    }

    public static function send($to, $subject, $htmlContent)
    {
        if (self::sendWithSendGrid($to, $subject, $htmlContent)) {
            return true;
        }

        return self::sendWithPHPMailer($to, $subject, $htmlContent);
    }

    public static function sendRSVPConfirmation($to, $userName, $eventTitle, $eventDate, $eventTime, $eventLocation, $rsvpStatus)
    {
        $statusText = match($rsvpStatus) {
            'yes' => 'attending',
            'maybe' => 'might attend',
            'no' => 'not attending',
            default => 'responded to'
        };

        $subject = "RSVP Confirmation - $eventTitle";

        $htmlContent = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd;'>
                <h2 style='color: #0d6efd;'>RSVP Confirmation</h2>
                <p>Hi $userName,</p>
                <p>Thank you for your RSVP! You have confirmed that you are <strong>$statusText</strong> the following event:</p>

                <div style='background-color: #f8f9fa; padding: 15px; margin: 20px 0; border-left: 4px solid #0d6efd;'>
                    <h3 style='margin-top: 0;'>$eventTitle</h3>
                    <p><strong>Date:</strong> " . date('F d, Y', strtotime($eventDate)) . "</p>
                    <p><strong>Time:</strong> " . date('g:i A', strtotime($eventTime)) . "</p>
                    <p><strong>Location:</strong> $eventLocation</p>
                </div>

                <p>We look forward to seeing you!</p>

                <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                <p style='font-size: 12px; color: #666;'>
                    This is an automated message from Gatherly. Please do not reply to this email.
                </p>
            </div>
        </body>
        </html>
        ";

        return self::send($to, $subject, $htmlContent);
    }
}
