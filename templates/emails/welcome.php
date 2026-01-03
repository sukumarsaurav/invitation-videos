<?php
/**
 * Welcome Email Template
 * Sent when a user registers
 */
ob_start();
?>
<!-- Welcome Header -->
<div style="text-align: center; margin-bottom: 32px;">
    <div
        style="width: 80px; height: 80px; background: linear-gradient(135deg, #7f13ec 0%, #a855f7 100%); border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
        <span style="font-size: 40px;">🎉</span>
    </div>
    <h1 style="margin: 0 0 8px; font-size: 28px; font-weight: 700; color: #0f172a;">
        Welcome to
        <?= $appName ?>!
    </h1>
    <p style="margin: 0; font-size: 16px; color: #64748b;">
        We're thrilled to have you on board.
    </p>
</div>

<!-- Greeting -->
<p style="margin: 0 0 20px; font-size: 16px; color: #334155; line-height: 1.6;">
    Hi <strong>
        <?= htmlspecialchars($name) ?>
    </strong>,
</p>

<p style="margin: 0 0 20px; font-size: 16px; color: #334155; line-height: 1.6;">
    Thank you for creating an account with <strong>
        <?= $appName ?>
    </strong>! You're now ready to create stunning video invitations for your special occasions.
</p>

<!-- What You Can Do -->
<div style="background-color: #f8fafc; border-radius: 12px; padding: 24px; margin: 24px 0;">
    <h2 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #0f172a;">
        Here's what you can do:
    </h2>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="padding: 8px 0;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="padding-right: 12px; vertical-align: top;">✨</td>
                        <td style="font-size: 15px; color: #475569;">Browse 100+ beautiful templates for weddings,
                            birthdays, and more</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="padding-right: 12px; vertical-align: top;">🎨</td>
                        <td style="font-size: 15px; color: #475569;">Customize with your photos, names, and event
                            details</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="padding-right: 12px; vertical-align: top;">📱</td>
                        <td style="font-size: 15px; color: #475569;">Share your video on WhatsApp, Instagram, and more
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

<!-- CTA Button -->
<div style="text-align: center; margin: 32px 0;">
    <a href="<?= $appUrl ?>/templates" class="button"
        style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #7f13ec 0%, #a855f7 100%); color: #ffffff; font-size: 16px; font-weight: 600; text-decoration: none; border-radius: 12px; box-shadow: 0 4px 14px rgba(127, 19, 236, 0.4);">
        Browse Templates
    </a>
</div>

<p style="margin: 0; font-size: 16px; color: #334155; line-height: 1.6;">
    If you have any questions, just reply to this email. We're always happy to help!
</p>

<p style="margin: 24px 0 0; font-size: 16px; color: #334155;">
    Cheers,<br>
    <strong>The
        <?= $appName ?> Team
    </strong>
</p>
<?php
$content = ob_get_clean();
