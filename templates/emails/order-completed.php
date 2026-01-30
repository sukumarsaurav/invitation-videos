<?php
/**
 * Order Completed / Download Ready Email Template
 * Sent when video rendering is complete
 */

$daysLeft = ceil((strtotime($expiresAt) - time()) / 86400);
?>
<!-- Elegant Header -->
<div style="text-align: center; margin-bottom: 48px;">
    <h1
        style="margin: 0 0 12px; font-size: 36px; font-weight: 400; color: #1f2937; font-family: 'Playfair Display', Georgia, serif;">
        Your Video is
        <span style="font-style: italic; color: #970747;">Ready!</span>
    </h1>
    <p style="margin: 0; font-size: 16px; color: #6b7280;">
        Order #<?= htmlspecialchars($orderNumber) ?>
    </p>
</div>

<!-- Greeting -->
<p style="margin: 0 0 24px; font-size: 16px; color: #374151; line-height: 1.7;">
    Hi <strong><?= htmlspecialchars($name) ?></strong>,
</p>

<p style="margin: 0 0 32px; font-size: 16px; color: #374151; line-height: 1.7;">
    Great news! Your <strong><?= htmlspecialchars($templateTitle) ?></strong> video invitation is complete and ready for
    download.
</p>

<!-- Download Button -->
<div style="text-align: center; margin: 40px 0;">
    <a href="<?= $videoUrl ?>"
        style="display: inline-block; padding: 18px 56px; background-color: #970747; color: #ffffff; font-size: 14px; font-weight: 600; text-decoration: none; border-radius: 4px; letter-spacing: 0.5px;">
        ⬇ DOWNLOAD VIDEO
    </a>
</div>

<!-- Expiration Notice -->
<div style="text-align: center; background-color: #fef3c7; border-radius: 8px; padding: 16px; margin: 32px 0;">
    <span style="font-size: 14px; color: #92400e;">
        ⚠️ Download link expires in <strong><?= $daysLeft ?> days</strong>
        <br>
        <span style="font-size: 13px;">Please download before <?= date('F j, Y', strtotime($expiresAt)) ?></span>
    </span>
</div>

<!-- Sharing Tips -->
<div style="margin: 40px 0 32px;">
    <h2 style="margin: 0 0 20px; font-size: 18px; font-weight: 600; color: #1f2937; text-align: center;">
        Ready to Share?
    </h2>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="padding: 12px 0; border-bottom: 1px solid #f3f4f6;">
                <span style="font-size: 15px; color: #374151;">
                    📱 Share directly on <strong>WhatsApp</strong> to your contacts
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding: 12px 0; border-bottom: 1px solid #f3f4f6;">
                <span style="font-size: 15px; color: #374151;">
                    📸 Post as a <strong>Reel</strong> or <strong>Story</strong> on Instagram
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding: 12px 0;">
                <span style="font-size: 15px; color: #374151;">
                    💌 Attach to emails or embed on your <strong>wedding website</strong>
                </span>
            </td>
        </tr>
    </table>
</div>

<!-- Secondary CTA -->
<div style="text-align: center; margin: 32px 0;">
    <a href="<?= $appUrl ?>/my-orders"
        style="display: inline-block; padding: 14px 32px; background-color: #f3f4f6; color: #374151; font-size: 14px; font-weight: 500; text-decoration: none; border-radius: 4px;">
        View All My Orders
    </a>
</div>

<!-- Signature -->
<p style="margin: 32px 0 0; font-size: 15px; color: #374151; line-height: 1.7;">
    We hope you love your video! If you need any changes, please reach out to our support team.
</p>
<p style="margin: 16px 0 0; font-size: 15px; color: #374151;">
    Warm regards,<br>
    <strong>The <?= $appName ?> Team</strong>
</p>
<?php
// Content is captured by EmailService::render()
