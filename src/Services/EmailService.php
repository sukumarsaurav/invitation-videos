<?php
/**
 * Email Service
 * 
 * Handles sending transactional emails using PHPMailer.
 */

namespace VideoInvites\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class EmailService
{
    private static ?PHPMailer $mailer = null;

    /**
     * Get configured PHPMailer instance
     */
    private static function getMailer(): PHPMailer
    {
        if (self::$mailer === null) {
            self::$mailer = new PHPMailer(true);

            // Server settings
            self::$mailer->isSMTP();
            self::$mailer->Host = MAIL_HOST;
            self::$mailer->Port = MAIL_PORT;
            self::$mailer->SMTPAuth = true;
            self::$mailer->Username = MAIL_USERNAME;
            self::$mailer->Password = MAIL_PASSWORD;
            self::$mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

            // Default sender
            self::$mailer->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);

            // Content settings
            self::$mailer->isHTML(true);
            self::$mailer->CharSet = 'UTF-8';
        }

        return self::$mailer;
    }

    /**
     * Send an email
     */
    public static function send(string $to, string $subject, string $htmlBody, ?string $toName = null): bool
    {
        try {
            $mailer = self::getMailer();

            // Clear previous recipients
            $mailer->clearAddresses();
            $mailer->clearAttachments();

            // Add recipient
            $mailer->addAddress($to, $toName ?? '');
            $mailer->Subject = $subject;
            $mailer->Body = $htmlBody;

            // Plain text fallback
            $mailer->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

            $mailer->send();

            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Render an email template
     */
    private static function render(string $templateName, array $data = []): string
    {
        extract($data);

        ob_start();
        include __DIR__ . '/../../templates/emails/' . $templateName . '.php';
        $content = ob_get_clean();

        // Wrap in base layout
        ob_start();
        include __DIR__ . '/../../templates/emails/base.php';
        return ob_get_clean();
    }

    // =========================================================================
    // TRANSACTIONAL EMAIL METHODS
    // =========================================================================

    /**
     * Send welcome email on registration
     */
    public static function sendWelcomeEmail(string $email, string $name): bool
    {
        $data = [
            'name' => $name,
            'email' => $email,
            'appName' => APP_NAME,
            'appUrl' => APP_URL,
        ];

        $html = self::render('welcome', $data);
        $subject = "Welcome to " . APP_NAME . "! 🎉";

        return self::send($email, $subject, $html, $name);
    }

    /**
     * Send payment received / invoice email
     */
    public static function sendPaymentReceivedEmail(array $order, array $user): bool
    {
        $data = [
            'name' => $user['name'],
            'email' => $user['email'],
            'orderNumber' => $order['order_number'],
            'templateTitle' => $order['template_title'] ?? 'Video Template',
            'amount' => $order['amount'],
            'currency' => $order['currency'],
            'paymentGateway' => $order['payment_gateway'] ?? 'Online Payment',
            'paymentId' => $order['payment_id'] ?? '',
            'paidAt' => $order['paid_at'] ?? date('Y-m-d H:i:s'),
            'appName' => APP_NAME,
            'appUrl' => APP_URL,
        ];

        $html = self::render('payment-received', $data);
        $subject = "Payment Confirmed - Order #" . $order['order_number'];

        return self::send($user['email'], $subject, $html, $user['name']);
    }

    /**
     * Send order completed / download ready email
     */
    public static function sendOrderCompletedEmail(array $order, array $user): bool
    {
        $data = [
            'name' => $user['name'],
            'email' => $user['email'],
            'orderNumber' => $order['order_number'],
            'templateTitle' => $order['template_title'] ?? 'Your Video',
            'videoUrl' => APP_URL . $order['output_video_url'],
            'expiresAt' => $order['video_expires_at'] ?? date('Y-m-d H:i:s', strtotime('+7 days')),
            'appName' => APP_NAME,
            'appUrl' => APP_URL,
        ];

        $html = self::render('order-completed', $data);
        $subject = "Your Video is Ready! 🎬 Order #" . $order['order_number'];

        return self::send($user['email'], $subject, $html, $user['name']);
    }
}
