<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>
        <?= $appName ?? 'Invitation Videos' ?>
    </title>
    <!--[if mso]>
    <style type="text/css">
        table {border-collapse: collapse;}
        .button {padding: 14px 32px !important;}
    </style>
    <![endif]-->
</head>

<body
    style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <!-- Wrapper Table -->
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
        style="background-color: #f1f5f9;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <!-- Email Container -->
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"
                    style="max-width: 600px; width: 100%;">

                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding: 24px 0;">
                            <a href="<?= $appUrl ?? '#' ?>" style="text-decoration: none;">
                                <img src="<?= ($appUrl ?? '') ?>/assets/images/logo.png"
                                    alt="<?= $appName ?? 'Invitation Videos' ?>" width="48" height="48"
                                    style="border: 0; display: block;">
                            </a>
                        </td>
                    </tr>

                    <!-- Main Content Card -->
                    <tr>
                        <td>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="background-color: #ffffff; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                                <tr>
                                    <td style="padding: 40px 32px;">
                                        <?= $content ?? '' ?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 32px 20px;">
                            <!-- Social Links -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <?php if (defined('SOCIAL_FACEBOOK') && SOCIAL_FACEBOOK !== '#'): ?>
                                        <td style="padding: 0 8px;">
                                            <a href="<?= SOCIAL_FACEBOOK ?>" style="text-decoration: none;">
                                                <img src="https://cdn-icons-png.flaticon.com/24/733/733547.png"
                                                    alt="Facebook" width="24" height="24" style="border: 0;">
                                            </a>
                                        </td>
                                    <?php endif; ?>
                                    <?php if (defined('SOCIAL_INSTAGRAM') && SOCIAL_INSTAGRAM !== '#'): ?>
                                        <td style="padding: 0 8px;">
                                            <a href="<?= SOCIAL_INSTAGRAM ?>" style="text-decoration: none;">
                                                <img src="https://cdn-icons-png.flaticon.com/24/2111/2111463.png"
                                                    alt="Instagram" width="24" height="24" style="border: 0;">
                                            </a>
                                        </td>
                                    <?php endif; ?>
                                    <?php if (defined('SOCIAL_YOUTUBE') && SOCIAL_YOUTUBE !== '#'): ?>
                                        <td style="padding: 0 8px;">
                                            <a href="<?= SOCIAL_YOUTUBE ?>" style="text-decoration: none;">
                                                <img src="https://cdn-icons-png.flaticon.com/24/1384/1384060.png"
                                                    alt="YouTube" width="24" height="24" style="border: 0;">
                                            </a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            </table>

                            <p style="margin: 24px 0 8px; font-size: 14px; color: #64748b;">
                                ©
                                <?= date('Y') ?>
                                <?= $appName ?? 'Invitation Videos' ?>. All rights reserved.
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #94a3b8;">
                                You received this email because you have an account with us.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>