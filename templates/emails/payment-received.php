<?php
/**
 * Payment Received / Invoice Email Template
 * Sent after successful payment
 */
ob_start();
?>
<!-- Invoice Header -->
<div style="text-align: center; margin-bottom: 32px;">
    <div
        style="width: 80px; height: 80px; background: linear-gradient(135deg, #10b981 0%, #34d399 100%); border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
        <span style="font-size: 40px;">✅</span>
    </div>
    <h1 style="margin: 0 0 8px; font-size: 28px; font-weight: 700; color: #0f172a;">
        Payment Confirmed!
    </h1>
    <p style="margin: 0; font-size: 16px; color: #64748b;">
        Thank you for your purchase.
    </p>
</div>

<!-- Greeting -->
<p style="margin: 0 0 20px; font-size: 16px; color: #334155; line-height: 1.6;">
    Hi <strong>
        <?= htmlspecialchars($name) ?>
    </strong>,
</p>

<p style="margin: 0 0 24px; font-size: 16px; color: #334155; line-height: 1.6;">
    We've received your payment and your order is now being processed. Here are your order details:
</p>

<!-- Invoice Card -->
<div style="background-color: #f8fafc; border-radius: 12px; padding: 24px; margin: 24px 0; border: 1px solid #e2e8f0;">
    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; margin-bottom: 16px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
            <tr>
                <td>
                    <span
                        style="font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Order
                        Number</span><br>
                    <span style="font-size: 18px; font-weight: 700; color: #0f172a;">#
                        <?= htmlspecialchars($orderNumber) ?>
                    </span>
                </td>
                <td align="right">
                    <span
                        style="font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Date</span><br>
                    <span style="font-size: 14px; color: #334155;">
                        <?= date('M j, Y', strtotime($paidAt)) ?>
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Order Details -->
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="padding: 12px 0; border-bottom: 1px solid #e2e8f0;">
                <span style="font-size: 15px; color: #334155;">
                    <?= htmlspecialchars($templateTitle) ?>
                </span>
            </td>
            <td align="right" style="padding: 12px 0; border-bottom: 1px solid #e2e8f0;">
                <span style="font-size: 15px; font-weight: 600; color: #0f172a;">
                    <?= $currency === 'INR' ? '₹' : '$' ?>
                    <?= number_format($amount, 2) ?>
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding: 16px 0 0;">
                <span style="font-size: 16px; font-weight: 700; color: #0f172a;">Total Paid</span>
            </td>
            <td align="right" style="padding: 16px 0 0;">
                <span style="font-size: 20px; font-weight: 700; color: #10b981;">
                    <?= $currency === 'INR' ? '₹' : '$' ?>
                    <?= number_format($amount, 2) ?>
                    <?= $currency ?>
                </span>
            </td>
        </tr>
    </table>
</div>

<!-- Payment Info -->
<div style="background-color: #f0fdf4; border-radius: 8px; padding: 16px; margin: 24px 0;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="padding: 4px 0;">
                <span style="font-size: 13px; color: #64748b;">Payment Method:</span>
                <span style="font-size: 13px; color: #334155; font-weight: 500; margin-left: 8px;">
                    <?= ucfirst($paymentGateway) ?>
                </span>
            </td>
        </tr>
        <?php if ($paymentId): ?>
            <tr>
                <td style="padding: 4px 0;">
                    <span style="font-size: 13px; color: #64748b;">Transaction ID:</span>
                    <span style="font-size: 13px; color: #334155; font-family: monospace; margin-left: 8px;">
                        <?= htmlspecialchars($paymentId) ?>
                    </span>
                </td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<!-- What's Next -->
<div style="margin: 24px 0;">
    <h2 style="margin: 0 0 12px; font-size: 18px; font-weight: 600; color: #0f172a;">What's Next?</h2>
    <p style="margin: 0; font-size: 15px; color: #475569; line-height: 1.6;">
        Our team will now process your video with the details you provided. You'll receive another email once your video
        is ready for download (usually within 24-48 hours).
    </p>
</div>

<!-- CTA Button -->
<div style="text-align: center; margin: 32px 0;">
    <a href="<?= $appUrl ?>/my-orders" class="button"
        style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #7f13ec 0%, #a855f7 100%); color: #ffffff; font-size: 16px; font-weight: 600; text-decoration: none; border-radius: 12px; box-shadow: 0 4px 14px rgba(127, 19, 236, 0.4);">
        View My Orders
    </a>
</div>

<p style="margin: 24px 0 0; font-size: 16px; color: #334155;">
    Thank you for choosing
    <?= $appName ?>!<br>
    <strong>The
        <?= $appName ?> Team
    </strong>
</p>
<?php
$content = ob_get_clean();
