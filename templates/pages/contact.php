<?php
/**
 * Contact Us Page
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/Core/Security.php';

$pageTitle = 'Contact Us';
$metaDescription = 'Get in touch with VideoInvites support. We respond within 24 hours to help with orders, customizations, and any questions about video invitations.';

// Handle form submission
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!Security::validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $name = Security::sanitizeString($_POST['name'] ?? '');
        $email = Security::sanitizeEmail($_POST['email'] ?? '');
        $subject = Security::sanitizeString($_POST['subject'] ?? '');
        $message = Security::sanitizeString($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Create support ticket
            $today = date('Ymd');
            $lastTicket = Database::fetchOne(
                "SELECT ticket_number FROM support_tickets WHERE ticket_number LIKE ? ORDER BY id DESC LIMIT 1",
                ["CT-{$today}-%"]
            );

            if ($lastTicket) {
                $lastNumber = intval(substr($lastTicket['ticket_number'], -4));
                $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '0001';
            }
            $ticketNumber = "CT-{$today}-{$newNumber}";

            // Find or create user
            $user = Database::fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
            if (!$user) {
                Database::query(
                    "INSERT INTO users (email, name, role, status) VALUES (?, ?, 'customer', 'active')",
                    [$email, $name]
                );
                $userId = Database::lastInsertId();
            } else {
                $userId = $user['id'];
            }

            // Insert ticket
            Database::query(
                "INSERT INTO support_tickets (ticket_number, user_id, subject, message, priority, status) 
                 VALUES (?, ?, ?, ?, 'medium', 'open')",
                [$ticketNumber, $userId, $subject, $message]
            );

            $success = "Thank you for contacting us! Your ticket #{$ticketNumber} has been created. We'll respond within 24 hours.";
        }
    }
}
?>

<?php ob_start(); ?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm mb-8">
        <a class="text-slate-500 hover:text-primary transition-colors" href="/">Home</a>
        <span class="text-slate-400">/</span>
        <span class="font-medium text-slate-900 dark:text-white">Contact Us</span>
    </nav>

    <!-- Header -->
    <div class="text-center mb-12">
        <h1 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
            Get in Touch
        </h1>
        <p class="text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
            Have a question or need help? We'd love to hear from you. Our team typically responds within 24 hours.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contact Info Cards -->
        <div class="space-y-6">
            <!-- Email -->
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-800 p-6">
                <div class="flex items-center gap-4">
                    <div class="size-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">mail</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white">Email Us</h3>
                        <a href="mailto:support@invitationvideos.com" class="text-primary hover:underline">
                            support@invitationvideos.com
                        </a>
                    </div>
                </div>
            </div>

            <!-- Response Time -->
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-800 p-6">
                <div class="flex items-center gap-4">
                    <div
                        class="size-12 rounded-xl bg-green-100 dark:bg-green-900/30 text-green-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">schedule</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white">Response Time</h3>
                        <p class="text-slate-600 dark:text-slate-400">Within 24 hours</p>
                    </div>
                </div>
            </div>

            <!-- Support Hours -->
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-800 p-6">
                <div class="flex items-center gap-4">
                    <div
                        class="size-12 rounded-xl bg-amber-100 dark:bg-amber-900/30 text-amber-600 flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">support_agent</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white">Support Hours</h3>
                        <p class="text-slate-600 dark:text-slate-400">Mon-Sat, 9AM-9PM IST</p>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-6">
                <h3 class="font-bold text-slate-900 dark:text-white mb-4">Quick Links</h3>
                <ul class="space-y-3">
                    <li>
                        <a href="/faq"
                            class="flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-primary">
                            <span class="material-symbols-outlined text-lg">help</span>
                            FAQ
                        </a>
                    </li>
                    <li>
                        <a href="/support"
                            class="flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-primary">
                            <span class="material-symbols-outlined text-lg">confirmation_number</span>
                            Submit Support Ticket
                        </a>
                    </li>
                    <li>
                        <a href="/refund"
                            class="flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-primary">
                            <span class="material-symbols-outlined text-lg">payments</span>
                            Refund Policy
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="lg:col-span-2">
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-800 p-8">
                <?php if ($success): ?>
                    <div
                        class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-700 flex items-center gap-3">
                        <span class="material-symbols-outlined">check_circle</span>
                        <?= Security::escape($success) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700 flex items-center gap-3">
                        <span class="material-symbols-outlined">error</span>
                        <?= Security::escape($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <?= Security::csrfField() ?>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Your Name *
                            </label>
                            <input type="text" id="name" name="name" required
                                class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                                placeholder="John Doe">
                        </div>
                        <div>
                            <label for="email"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Email Address *
                            </label>
                            <input type="email" id="email" name="email" required
                                class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                                placeholder="you@example.com">
                        </div>
                    </div>

                    <div>
                        <label for="subject" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Subject *
                        </label>
                        <select id="subject" name="subject" required
                            class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                            <option value="">Select a topic</option>
                            <option value="General Inquiry">General Inquiry</option>
                            <option value="Order Question">Order Question</option>
                            <option value="Technical Support">Technical Support</option>
                            <option value="Partnership">Partnership Opportunity</option>
                            <option value="Feedback">Feedback / Suggestion</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Message *
                        </label>
                        <textarea id="message" name="message" rows="5" required
                            class="w-full px-4 py-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-primary focus:border-transparent transition-all resize-none"
                            placeholder="How can we help you?"></textarea>
                    </div>

                    <button type="submit"
                        class="w-full py-3 px-4 bg-primary hover:bg-primary/90 text-white font-bold rounded-lg shadow-lg shadow-primary/30 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">send</span>
                        Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>