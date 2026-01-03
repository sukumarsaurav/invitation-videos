<?php
/**
 * Order Completed / Download Ready Email Template
 * Sent when admin uploads the final video
 */
ob_start();

$daysLeft = ceil((strtotime($expiresAt) - time()) / 86400);
?>
<!-- Success Header -->
<div style="text-align: center; margin-bottom: 32px;">
    <div
        style="width: 80px; height: 80px; background: linear-gradient(135deg, #7f13ec 0%, #a855f7 100%); border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
        <span style="font-size: 40px;">🎬</span>
    </div>
    <h1 style="margin: 0 0 8px; font-size: 28px; font-weight: 700; color: #0f172a;">
        Your Video is Ready!
    </h1>
    <p style="margin: 0; font-size: 16px; color: #64748b;">
        Order #
        <?= htmlspecialchars($orderNumber) ?>
    </p>
</div>

<!-- Greeting -->
<p style="margin: 0 0 20px; font-size: 16px; color: #334155; line-height: 1.6;">
    Hi <strong>
        <?= htmlspecialchars($name) ?>
    </strong>,
</p>

<p style="margin: 0 0 24px; font-size: 16px; color: #334155; line-height: 1.6;">
    Great news! Your <strong>
        <?= htmlspecialchars($templateTitle) ?>
    </strong> video invitation is complete and ready for download.
</p>

<!-- Download Card -->
<div
    style="background: linear-gradient(135deg, #7f13ec 0%, #a855f7 100%); border-radius: 16px; padding: 32px; margin: 24px 0; text-align: center;">
    <p style="margin: 0 0 20px; font-size: 18px; color: #ffffff; font-weight: 500;">
        Click below to download your video
    </p>
    <a href="<?= $videoUrl ?>" class="button"
        style="display: inline-block; padding: 18px 48px; background: #ffffff; color: #7f13ec; font-size: 18px; font-weight: 700; text-decoration: none; border-radius: 12px; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);">
        ⬇️ Download Video
    </a>
</div>

<!-- Expiration Warning -->
<div
    style="background-color: #fef3c7; border-radius: 8px; padding: 16px; margin: 24px 0; border-left: 4px solid #f59e0b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="padding-right: 12px; vertical-align: top; width: 24px;">
                <span style="font-size: 20px;">⚠️</span>
            </td>
            <td>
                <span style="font-size: 14px; color: #92400e; font-weight: 600;">Download link expires in
                    <?= $daysLeft ?> days
                </span><br>
                <span style="font-size: 13px; color: #a16207;">Please download your video before
                    <?= date('F j, Y', strtotime($expiresAt)) ?>.
                </span>
            </td>
        </tr>
    </table>
</div>

<!-- Sharing Tips -->
<div style="background-color: #f8fafc; border-radius: 12px; padding: 24px; margin: 24px 0;">
    <h2 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #0f172a;">
        📤 Ready to Share?
    </h2>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="padding: 8px 0;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="padding-right: 12px; vertical-align: top;">📱</td>
                        <td style="font-size: 15px; color: #475569;">Share directly on <strong>WhatsApp</strong> to your
                            contacts</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="padding-right: 12px; vertical-align: top;">📸</td>
                        <td style="font-size: 15px; color: #475569;">Post as a <strong>Reel</strong> or
                            <strong>Story</strong> on Instagram</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="padding-right: 12px; vertical-align: top;">💌</td>
                        <td style="font-size: 15px; color: #475569;">Attach to emails or embed on your <strong>wedding
                                website</strong></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

<!-- CTA Button -->
<div style="text-align: center; margin: 32px 0;">
    <a href="<?= $appUrl ?>/my-orders" class="button"
        style="display: inline-block; padding: 14px 32px; background: #f1f5f9; color: #334155; font-size: 15px; font-weight: 600; text-decoration: none; border-radius: 10px;">
        View All My Orders
    </a>
</div>

<p style="margin: 0 0 16px; font-size: 16px; color: #334155; line-height: 1.6;">
    We hope you love your video! If you need any changes, please reach out to our support team.
</p>

<p style="margin: 24px 0 0; font-size: 16px; color: #334155;">
    Warm regards,<br>
    <strong>The
        <?= $appName ?> Team
    </strong>
</p>
<?php
$content = ob_get_clean();
