<?php
/**
 * Payment Received / Invoice Email Template
 * Sent after successful payment
 */
?>
<!-- Elegant Header -->
<div style="text-align: center; margin-bottom: 48px;">
    <h1
        style="margin: 0 0 12px; font-size: 36px; font-weight: 400; color: #1f2937; font-family: 'Playfair Display', Georgia, serif;">
        Payment Confirmed
    </h1>
    <p style="margin: 0; font-size: 16px; color: #6b7280;">
        Thank you for your purchase
    </p>
</div>

<!-- Greeting -->
<p style="margin: 0 0 24px; font-size: 16px; color: #374151; line-height: 1.7;">
    Hi <strong><?= htmlspecialchars($name) ?></strong>,
</p>

<p style="margin: 0 0 32px; font-size: 16px; color: #374151; line-height: 1.7;">
    We've received your payment and your order is now being processed. Here are your order details:
</p>

<!-- Order Details -->
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin: 0 0 32px;">
    <tr>
        <td style="padding: 16px 0; border-top: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td>
                        <span
                            style="font-size: 12px; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px;">Order
                            Number</span><br>
                        <span style="font-size: 18px; font-weight: 600; color: #1f2937;">#
                            <?= htmlspecialchars($orderNumber) ?></span>
                    </td>
                    <td align="right">
                        <span
                            style="font-size: 12px; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px;">Date</span><br>
                        <span style="font-size: 14px; color: #4b5563;"><?= date('M j, Y', strtotime($paidAt)) ?></span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding: 16px 0; border-bottom: 1px solid #e5e7eb;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td>
                        <span style="font-size: 15px; color: #374151;"><?= htmlspecialchars($templateTitle) ?></span>
                    </td>
                    <td align="right">
                        <span style="font-size: 15px; font-weight: 600; color: #1f2937;">
                            <?= $currency === 'INR' ? '₹' : '$' ?><?= number_format($amount, 2) ?>
                        </span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding: 20px 0 0;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td>
                        <span style="font-size: 16px; font-weight: 600; color: #1f2937;">Total Paid</span>
                    </td>
                    <td align="right">
                        <span style="font-size: 22px; font-weight: 700; color: #970747;">
                            <?= $currency === 'INR' ? '₹' : '$' ?><?= number_format($amount, 2) ?> <?= $currency ?>
                        </span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Payment Info -->
<div style="background-color: #f9fafb; border-radius: 8px; padding: 16px; margin: 0 0 32px;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="padding: 4px 0;">
                <span style="font-size: 13px; color: #6b7280;">Payment Method:</span>
                <span style="font-size: 13px; color: #374151; font-weight: 500; margin-left: 8px;">
                    <?= ucfirst($paymentGateway) ?>
                </span>
            </td>
        </tr>
        <?php if ($paymentId): ?>
            <tr>
                <td style="padding: 4px 0;">
                    <span style="font-size: 13px; color: #6b7280;">Transaction ID:</span>
                    <span style="font-size: 13px; color: #374151; font-family: monospace; margin-left: 8px;">
                        <?= htmlspecialchars($paymentId) ?>
                    </span>
                </td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<!-- What's Next -->
<div style="margin: 0 0 32px;">
    <h2 style="margin: 0 0 12px; font-size: 18px; font-weight: 600; color: #1f2937;">What's Next?</h2>
    <p style="margin: 0; font-size: 15px; color: #4b5563; line-height: 1.7;">
        Your video is now in the rendering queue. You'll receive another email once your video is ready for download
        (usually within a few minutes).
    </p>
</div>

<!-- CTA Button -->
<div style="text-align: center; margin: 40px 0;">
    <a href="<?= $appUrl ?>/my-orders"
        style="display: inline-block; padding: 16px 48px; background-color: #970747; color: #ffffff; font-size: 14px; font-weight: 600; text-decoration: none; border-radius: 4px; letter-spacing: 0.5px;">
        VIEW MY ORDERS
    </a>
</div>

<!-- Signature -->
<p style="margin: 32px 0 0; font-size: 15px; color: #374151; line-height: 1.7;">
    Thank you for choosing <?= $appName ?>!
</p>
<p style="margin: 8px 0 0; font-size: 15px; color: #374151;">
    <strong>The <?= $appName ?> Team</strong>
</p>
<?php
// Content is captured by EmailService::render()
