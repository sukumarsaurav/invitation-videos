<?php
/**
 * Email Test Page
 * 
 * Tests the email configuration and PHPMailer setup.
 * Access at: /api/test-email.php
 * 
 * DELETE THIS FILE AFTER TESTING!
 */

// Security: Only allow admin access or localhost
$allowedIPs = ['127.0.0.1', '::1'];
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'], $allowedIPs);

// You can also add a secret key check
$secretKey = $_GET['key'] ?? '';
$validKey = 'test_email_2026'; // Change this!

if (!$isLocalhost && $secretKey !== $validKey) {
    http_response_code(403);
    die('Access denied. Add ?key=test_email_2026 to the URL');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Test</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .card { background: white; border-radius: 12px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 0; }
        .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; }
        .warning { background: #fff3cd; color: #856404; padding: 12px; border-radius: 8px; }
        .info { background: #d1ecf1; color: #0c5460; padding: 12px; border-radius: 8px; }
        pre { background: #1e1e1e; color: #d4d4d4; padding: 16px; border-radius: 8px; overflow-x: auto; font-size: 14px; }
        code { background: #e9ecef; padding: 2px 6px; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 8px 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; }
        form { margin-top: 20px; }
        input[type="email"] { width: 300px; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        button { background: #7f13ec; color: white; padding: 10px 24px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        button:hover { background: #6610b3; }
    </style>
</head>
<body>
    <h1>📧 Email Configuration Test</h1>
    
    <!-- Step 1: Check Configuration -->
    <div class="card">
        <h2>1. Configuration Check</h2>
        <table>
            <tr><th>Setting</th><th>Value</th><th>Status</th></tr>
            <tr>
                <td>MAIL_HOST</td>
                <td><code><?= defined('MAIL_HOST') ? MAIL_HOST : 'NOT DEFINED' ?></code></td>
                <td><?= defined('MAIL_HOST') && !empty(MAIL_HOST) ? '✅' : '❌' ?></td>
            </tr>
            <tr>
                <td>MAIL_PORT</td>
                <td><code><?= defined('MAIL_PORT') ? MAIL_PORT : 'NOT DEFINED' ?></code></td>
                <td><?= defined('MAIL_PORT') && MAIL_PORT > 0 ? '✅' : '❌' ?></td>
            </tr>
            <tr>
                <td>MAIL_USERNAME</td>
                <td><code><?= defined('MAIL_USERNAME') ? (MAIL_USERNAME ? substr(MAIL_USERNAME, 0, 5) . '***' : 'EMPTY') : 'NOT DEFINED' ?></code></td>
                <td><?= defined('MAIL_USERNAME') && !empty(MAIL_USERNAME) ? '✅' : '❌' ?></td>
            </tr>
            <tr>
                <td>MAIL_PASSWORD</td>
                <td><code><?= defined('MAIL_PASSWORD') ? (MAIL_PASSWORD ? '****** (set)' : 'EMPTY') : 'NOT DEFINED' ?></code></td>
                <td><?= defined('MAIL_PASSWORD') && !empty(MAIL_PASSWORD) ? '✅' : '❌' ?></td>
            </tr>
            <tr>
                <td>MAIL_FROM_ADDRESS</td>
                <td><code><?= defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'NOT DEFINED' ?></code></td>
                <td><?= defined('MAIL_FROM_ADDRESS') && !empty(MAIL_FROM_ADDRESS) ? '✅' : '❌' ?></td>
            </tr>
            <tr>
                <td>MAIL_FROM_NAME</td>
                <td><code><?= defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'NOT DEFINED' ?></code></td>
                <td><?= defined('MAIL_FROM_NAME') ? '✅' : '⚠️' ?></td>
            </tr>
        </table>
        
        <?php
        $configOk = defined('MAIL_HOST') && defined('MAIL_PORT') && defined('MAIL_USERNAME') && defined('MAIL_PASSWORD');
        if (!$configOk): ?>
            <div class="error" style="margin-top: 16px;">
                ❌ <strong>Configuration incomplete!</strong> Check your <code>.env</code> file.
            </div>
        <?php else: ?>
            <div class="success" style="margin-top: 16px;">
                ✅ Configuration looks complete!
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Step 2: Check PHPMailer -->
    <div class="card">
        <h2>2. PHPMailer Check</h2>
        <?php
        $vendorAutoload = __DIR__ . '/../vendor/autoload.php';
        $phpmailerExists = file_exists($vendorAutoload);
        
        if (!$phpmailerExists): ?>
            <div class="error">
                ❌ <strong>Composer autoload not found!</strong><br>
                Run: <code>composer install</code> in your project root.
            </div>
        <?php else:
            require_once $vendorAutoload;
            
            if (!class_exists('PHPMailer\PHPMailer\PHPMailer')): ?>
                <div class="error">
                    ❌ <strong>PHPMailer class not found!</strong><br>
                    Run: <code>composer require phpmailer/phpmailer</code>
                </div>
            <?php else: ?>
                <div class="success">
                    ✅ PHPMailer is installed and available!
                </div>
            <?php endif;
        endif; ?>
    </div>
    
    <!-- Step 3: Send Test Email -->
    <div class="card">
        <h2>3. Send Test Email</h2>
        
        <?php
        $testEmail = $_POST['test_email'] ?? '';
        $sendResult = null;
        $debugOutput = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($testEmail)):
            // Try to send email with PHPMailer
            if ($phpmailerExists && class_exists('PHPMailer\PHPMailer\PHPMailer')):
                use PHPMailer\PHPMailer\PHPMailer;
                use PHPMailer\PHPMailer\SMTP;
                use PHPMailer\PHPMailer\Exception;
                
                ob_start(); // Capture debug output
                
                try {
                    $mail = new PHPMailer(true);
                    
                    // Enable verbose debug output
                    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                    $mail->Debugoutput = function($str, $level) {
                        echo htmlspecialchars($str) . "\n";
                    };
                    
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = MAIL_HOST;
                    $mail->Port = MAIL_PORT;
                    $mail->SMTPAuth = true;
                    $mail->Username = MAIL_USERNAME;
                    $mail->Password = MAIL_PASSWORD;
                    
                    // Set encryption based on port
                    if (MAIL_PORT == 465) {
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    } else {
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    }
                    
                    // Timeout settings
                    $mail->Timeout = 30;
                    $mail->SMTPKeepAlive = false;
                    
                    // Recipients
                    $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME ?? 'InvitationVideos');
                    $mail->addAddress($testEmail);
                    
                    // Content
                    $mail->isHTML(true);
                    $mail->CharSet = 'UTF-8';
                    $mail->Subject = '🎉 Test Email from InvitationVideos';
                    $mail->Body = '
                        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                            <h1 style="color: #7f13ec;">✅ Email is Working!</h1>
                            <p>Congratulations! Your email configuration is correct.</p>
                            <p><strong>Server:</strong> ' . MAIL_HOST . '</p>
                            <p><strong>Port:</strong> ' . MAIL_PORT . '</p>
                            <p><strong>Time:</strong> ' . date('Y-m-d H:i:s') . '</p>
                            <hr>
                            <p style="color: #666; font-size: 12px;">This is a test email from InvitationVideos.com</p>
                        </div>
                    ';
                    $mail->AltBody = 'Email is working! Server: ' . MAIL_HOST . ' Port: ' . MAIL_PORT;
                    
                    $mail->send();
                    $sendResult = true;
                    
                } catch (Exception $e) {
                    $sendResult = false;
                    echo "\n\n❌ EXCEPTION: " . $e->getMessage();
                }
                
                $debugOutput = ob_get_clean();
            endif;
        endif;
        ?>
        
        <?php if ($sendResult === true): ?>
            <div class="success">
                ✅ <strong>Email sent successfully!</strong><br>
                Check <code><?= htmlspecialchars($testEmail) ?></code> inbox (and spam folder).
            </div>
        <?php elseif ($sendResult === false): ?>
            <div class="error">
                ❌ <strong>Email failed to send!</strong><br>
                Check the debug output below for details.
            </div>
        <?php endif; ?>
        
        <?php if (!empty($debugOutput)): ?>
            <h3>SMTP Debug Output:</h3>
            <pre><?= $debugOutput ?></pre>
        <?php endif; ?>
        
        <form method="POST">
            <label for="test_email"><strong>Send test email to:</strong></label><br><br>
            <input type="email" name="test_email" id="test_email" placeholder="your@email.com" required value="<?= htmlspecialchars($testEmail) ?>">
            <button type="submit">Send Test Email</button>
        </form>
    </div>
    
    <!-- Step 4: Common Issues -->
    <div class="card">
        <h2>4. Common Issues & Fixes</h2>
        <table>
            <tr><th>Issue</th><th>Solution</th></tr>
            <tr>
                <td>Connection timed out</td>
                <td>Check if port <?= MAIL_PORT ?? '465' ?> is open. Some hosts block outgoing SMTP.</td>
            </tr>
            <tr>
                <td>Authentication failed</td>
                <td>Double-check username and password in <code>.env</code>. For Gmail, use App Password.</td>
            </tr>
            <tr>
                <td>SSL/TLS error</td>
                <td>Port 465 needs SMTPS, Port 587 needs STARTTLS. Check your port setting.</td>
            </tr>
            <tr>
                <td>"From" address rejected</td>
                <td>MAIL_FROM_ADDRESS must be a valid email on your SMTP server.</td>
            </tr>
            <tr>
                <td>Composer not installed</td>
                <td>Run: <code>cd ~/domains/invitationvideos.com/public_html && composer install</code></td>
            </tr>
        </table>
    </div>
    
    <p style="color: #999; text-align: center; margin-top: 30px;">
        ⚠️ <strong>Delete this file after testing!</strong> It exposes sensitive configuration.
    </p>
</body>
</html>
