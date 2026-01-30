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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400;1,600&display=swap');
    </style>
</head>

<body
    style="margin: 0; padding: 0; background-color: #ffffff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <!-- Wrapper Table -->
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
        style="background-color: #ffffff;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <!-- Email Container -->
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"
                    style="max-width: 600px; width: 100%;">

                    <!-- Header with Logo -->
                    <tr>
                        <td align="center" style="padding: 24px 0;">
                            <a href="<?= $appUrl ?? '#' ?>" style="text-decoration: none;">
                                <img src="<?= ($appUrl ?? '') ?>/assets/images/logo.png"
                                    alt="<?= $appName ?? 'Invitation Videos' ?>" width="48" height="48"
                                    style="border: 0; display: block;">
                            </a>
                        </td>
                    </tr>

                    <!-- Brand Name -->
                    <tr>
                        <td align="center" style="padding: 0 0 8px;">
                            <span
                                style="font-size: 14px; font-weight: 600; letter-spacing: 3px; color: #970747; text-transform: uppercase;">
                                INVITATION VIDEOS
                            </span>
                        </td>
                    </tr>

                    <!-- Divider Line -->
                    <tr>
                        <td align="center" style="padding: 0 0 40px;">
                            <div style="width: 40px; height: 3px; background-color: #970747;"></div>
                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td style="padding: 0 20px;">
                            <?= $content ?? '' ?>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 48px 20px 32px;">
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

                            <!-- Divider -->
                            <div style="width: 100%; height: 1px; background-color: #e5e7eb; margin: 24px 0;"></div>

                            <p style="margin: 0 0 8px; font-size: 13px; color: #9ca3af;">
                                © <?= date('Y') ?> <?= $appName ?? 'Invitation Videos' ?>. All rights reserved.
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #d1d5db;">
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