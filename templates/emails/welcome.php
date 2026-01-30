<?php
/**
 * Welcome Email Template
 * Sent after user registration
 */
?>
<!-- Elegant Header -->
<div style="text-align: center; margin-bottom: 48px;">
    <h1
        style="margin: 0 0 12px; font-size: 36px; font-weight: 400; color: #1f2937; font-family: 'Playfair Display', Georgia, serif;">
        Welcome to
        <span style="font-style: italic; color: #970747;"><?= $appName ?></span>
    </h1>
    <p style="margin: 0; font-size: 16px; color: #6b7280;">
        Make your moments unforgettable
    </p>
</div>

<!-- Greeting -->
<p style="margin: 0 0 24px; font-size: 16px; color: #374151; line-height: 1.7;">
    Hi <strong><?= htmlspecialchars($name) ?></strong>,
</p>

<p style="margin: 0 0 24px; font-size: 16px; color: #374151; line-height: 1.7;">
    Thank you for creating an account with us! We're excited to help you create beautiful video invitations for your
    special occasions.
</p>

<!-- What You Can Do -->
<div style="margin: 32px 0;">
    <h2 style="margin: 0 0 20px; font-size: 18px; font-weight: 600; color: #1f2937;">
        Here's what you can do:
    </h2>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="padding: 12px 0; border-bottom: 1px solid #f3f4f6;">
                <span style="font-size: 15px; color: #374151;">
                    🎬 Browse our collection of <strong>stunning video templates</strong>
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding: 12px 0; border-bottom: 1px solid #f3f4f6;">
                <span style="font-size: 15px; color: #374151;">
                    ✨ Customize with your <strong>photos, names, and details</strong>
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding: 12px 0;">
                <span style="font-size: 15px; color: #374151;">
                    📤 Share instantly on <strong>WhatsApp, Instagram, and more</strong>
                </span>
            </td>
        </tr>
    </table>
</div>

<!-- CTA Button -->
<div style="text-align: center; margin: 40px 0;">
    <a href="<?= $appUrl ?>/templates"
        style="display: inline-block; padding: 16px 48px; background-color: #970747; color: #ffffff; font-size: 14px; font-weight: 600; text-decoration: none; border-radius: 4px; letter-spacing: 0.5px;">
        EXPLORE TEMPLATES
    </a>
</div>

<!-- Signature -->
<p style="margin: 32px 0 0; font-size: 15px; color: #374151; line-height: 1.7;">
    If you have any questions, feel free to reach out to our support team. We're here to help!
</p>
<p style="margin: 16px 0 0; font-size: 15px; color: #374151;">
    Welcome aboard,<br>
    <strong>The <?= $appName ?> Team</strong>
</p>
<?php
// Content is captured by EmailService::render()
