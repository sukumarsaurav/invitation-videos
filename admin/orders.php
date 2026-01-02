<?php
/**
 * Admin - Order Management
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Security.php';
require_once __DIR__ . '/auth.php';

// Handle video upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_video'])) {
    $orderId = intval($_POST['order_id']);

    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/videos/' . $orderId . '/';

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = 'invitation_' . time() . '.mp4';
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['video_file']['tmp_name'], $filePath)) {
            $videoUrl = '/uploads/videos/' . $orderId . '/' . $fileName;
            $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

            Database::query(
                "UPDATE orders SET output_video_url = ?, video_uploaded_at = NOW(), video_expires_at = ?, 
                 payment_status = 'paid', order_status = 'completed', completed_at = NOW() WHERE id = ?",
                [$videoUrl, $expiresAt, $orderId]
            );

            header('Location: /admin/orders.php?action=view&id=' . $orderId . '&success=video_uploaded');
            exit;
        }
    }

    header('Location: /admin/orders.php?action=view&id=' . $orderId . '&error=upload_failed');
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = intval($_POST['order_id']);
    $paymentStatus = $_POST['payment_status'] ?? null;
    $orderStatus = $_POST['order_status'] ?? null;

    $updates = [];
    $params = [];

    if ($paymentStatus && in_array($paymentStatus, ['pending', 'paid', 'failed', 'refunded'])) {
        $updates[] = "payment_status = ?";
        $params[] = $paymentStatus;
    }

    if ($orderStatus && in_array($orderStatus, ['awaiting_payment', 'queued', 'processing', 'completed', 'cancelled'])) {
        $updates[] = "order_status = ?";
        $params[] = $orderStatus;

        if ($orderStatus === 'completed') {
            $updates[] = "completed_at = NOW()";
        }
    }

    if (!empty($updates)) {
        $params[] = $orderId;
        Database::query("UPDATE orders SET " . implode(', ', $updates) . " WHERE id = ?", $params);
    }

    header('Location: /admin/orders.php?success=updated');
    exit;
}

// Check if viewing single order
$viewOrder = null;
$orderUploads = [];
if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
    $orderId = intval($_GET['id']);
    $viewOrder = Database::fetchOne(
        "SELECT o.*, t.title as template_title, t.thumbnail_url, u.name as customer_name, u.email as customer_email, u.phone as customer_phone
         FROM orders o
         LEFT JOIN templates t ON o.template_id = t.id
         LEFT JOIN users u ON o.user_id = u.id
         WHERE o.id = ?",
        [$orderId]
    );

    if ($viewOrder) {
        $orderUploads = Database::fetchAll(
            "SELECT * FROM order_uploads WHERE order_id = ?",
            [$orderId]
        );
    }
}

// Filters for list view
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$whereConditions = [];
$params = [];

if ($status) {
    if (in_array($status, ['pending', 'paid', 'failed', 'refunded'])) {
        $whereConditions[] = "o.payment_status = ?";
    } else {
        $whereConditions[] = "o.order_status = ?";
    }
    $params[] = $status;
}

if ($search) {
    $whereConditions[] = "(o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM orders o LEFT JOIN users u ON o.user_id = u.id $whereClause";
$totalOrders = Database::fetchOne($countSql, $params)['total'] ?? 0;
$totalPages = ceil($totalOrders / $perPage);

// Get orders
$sql = "SELECT o.*, t.title as template_title, t.thumbnail_url, u.name as customer_name, u.email as customer_email
        FROM orders o
        LEFT JOIN templates t ON o.template_id = t.id
        LEFT JOIN users u ON o.user_id = u.id
        $whereClause
        ORDER BY o.created_at DESC
        LIMIT $perPage OFFSET $offset";
$orders = Database::fetchAll($sql, $params);

// Get stats
$stats = [
    'new' => Database::fetchOne("SELECT COUNT(*) as c FROM orders WHERE order_status = 'awaiting_payment' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")['c'] ?? 0,
    'queued' => Database::fetchOne("SELECT COUNT(*) as c FROM orders WHERE order_status = 'queued'")['c'] ?? 0,
    'processing' => Database::fetchOne("SELECT COUNT(*) as c FROM orders WHERE order_status = 'processing'")['c'] ?? 0,
    'completed' => Database::fetchOne("SELECT COUNT(*) as c FROM orders WHERE order_status = 'completed'")['c'] ?? 0,
    'revenue_today' => Database::fetchOne("SELECT COALESCE(SUM(amount), 0) as r FROM orders WHERE payment_status = 'paid' AND DATE(created_at) = CURDATE()")['r'] ?? 0,
];

$pendingTickets = 0;
$pageTitle = $viewOrder ? 'Order #' . $viewOrder['order_number'] : 'Orders';
?>

<?php ob_start(); ?>

<?php if ($viewOrder): ?>
    <!-- Order Detail View -->
    <div class="mb-6">
        <a href="/admin/orders.php"
            class="inline-flex items-center gap-2 text-slate-600 hover:text-primary transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
            Back to Orders
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            <?= $_GET['success'] === 'video_uploaded' ? 'Video uploaded successfully!' : 'Order updated!' ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined">error</span>
            Failed to upload video. Please try again.
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Order Header -->
            <div
                class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h2 class="text-2xl font-bold">Order #<?= Security::escape($viewOrder['order_number']) ?></h2>
                        <p class="text-slate-500 mt-1">Placed on
                            <?= date('F j, Y \a\t g:i A', strtotime($viewOrder['created_at'])) ?>
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <?php
                        $paymentColors = ['pending' => 'bg-yellow-100 text-yellow-800', 'paid' => 'bg-green-100 text-green-800', 'failed' => 'bg-red-100 text-red-800', 'refunded' => 'bg-slate-100 text-slate-800'];
                        $orderColors = ['awaiting_payment' => 'bg-yellow-100 text-yellow-800', 'queued' => 'bg-blue-100 text-blue-800', 'processing' => 'bg-purple-100 text-purple-800', 'completed' => 'bg-green-100 text-green-800', 'cancelled' => 'bg-red-100 text-red-800'];
                        ?>
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold <?= $paymentColors[$viewOrder['payment_status'] ?? 'pending'] ?? 'bg-slate-100' ?>">
                            💳 <?= ucfirst($viewOrder['payment_status'] ?? 'pending') ?>
                        </span>
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold <?= $orderColors[$viewOrder['order_status'] ?? 'awaiting_payment'] ?? 'bg-slate-100' ?>">
                            📦 <?= ucwords(str_replace('_', ' ', $viewOrder['order_status'] ?? 'awaiting_payment')) ?>
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <div>
                        <span class="text-xs text-slate-500 uppercase">Amount</span>
                        <p class="font-bold text-lg">
                            <?= $viewOrder['currency'] === 'INR' ? '₹' : '$' ?>
                            <?= number_format($viewOrder['amount'], 2) ?>
                        </p>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 uppercase">Gateway</span>
                        <p class="font-medium capitalize"><?= $viewOrder['payment_gateway'] ?? '—' ?></p>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 uppercase">Payment ID</span>
                        <p class="font-mono text-xs break-all"><?= $viewOrder['payment_id'] ?? '—' ?></p>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 uppercase">Promo Code</span>
                        <p class="font-medium"><?= $viewOrder['promo_code'] ?? '—' ?></p>
                    </div>
                </div>
            </div>

            <!-- Customization Data -->
            <div
                class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-white/5">
                    <h3 class="font-bold flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">edit_note</span>
                        Customization Details
                    </h3>
                </div>
                <div class="p-6">
                    <?php
                    $customData = json_decode($viewOrder['customization_data'] ?? '{}', true);
                    if (!empty($customData)):
                        ?>
                        <div class="space-y-4">
                            <?php foreach ($customData as $key => $value): ?>
                                <?php if (!empty($value) && !is_array($value)): ?>
                                    <div
                                        class="flex flex-col sm:flex-row sm:items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800 last:border-0">
                                        <span
                                            class="text-sm text-slate-500 min-w-[140px] capitalize"><?= str_replace('_', ' ', $key) ?></span>
                                        <span class="font-medium text-slate-900 dark:text-white"><?= Security::escape($value) ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-slate-500 text-center py-4">No customization data available</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Uploaded Images -->
            <?php if (!empty($orderUploads)): ?>
                <div
                    class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-white/5">
                        <h3 class="font-bold flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">image</span>
                            Uploaded Files (<?= count($orderUploads) ?>)
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                            <?php foreach ($orderUploads as $upload): ?>
                                <div class="group relative">
                                    <?php if ($upload['file_type'] === 'image'): ?>
                                        <div class="aspect-square rounded-lg overflow-hidden bg-slate-100 border border-slate-200">
                                            <img src="<?= Security::escape($upload['file_path']) ?>"
                                                alt="<?= Security::escape($upload['field_name']) ?>" class="w-full h-full object-cover">
                                        </div>
                                    <?php else: ?>
                                        <div
                                            class="aspect-square rounded-lg overflow-hidden bg-slate-100 border border-slate-200 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-4xl text-slate-400">audio_file</span>
                                        </div>
                                    <?php endif; ?>
                                    <p class="text-xs text-slate-500 mt-2 truncate capitalize">
                                        <?= str_replace('_', ' ', $upload['field_name']) ?>
                                    </p>
                                    <a href="<?= Security::escape($upload['file_path']) ?>" download
                                        class="absolute top-2 right-2 p-1.5 bg-white/90 rounded-lg shadow opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span class="material-symbols-outlined text-sm">download</span>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Video Upload / Status -->
            <div
                class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-white/5">
                    <h3 class="font-bold flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">video_library</span>
                        Video Delivery
                    </h3>
                </div>
                <div class="p-6">
                    <?php if ($viewOrder['output_video_url']): ?>
                        <!-- Video already uploaded -->
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-green-600 text-3xl">check_circle</span>
                                <div>
                                    <p class="font-bold text-green-800">Video Delivered</p>
                                    <p class="text-sm text-green-700">Uploaded on
                                        <?= date('M j, Y', strtotime($viewOrder['video_uploaded_at'])) ?>
                                    </p>
                                    <?php if ($viewOrder['video_expires_at']): ?>
                                        <p class="text-xs text-green-600 mt-1">
                                            Expires: <?= date('M j, Y', strtotime($viewOrder['video_expires_at'])) ?>
                                            <?php
                                            $daysLeft = ceil((strtotime($viewOrder['video_expires_at']) - time()) / 86400);
                                            if ($daysLeft > 0): ?>
                                                (<?= $daysLeft ?> days left)
                                            <?php else: ?>
                                                <span class="text-red-600 font-bold">(Expired)</span>
                                            <?php endif; ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <a href="<?= Security::escape($viewOrder['output_video_url']) ?>" target="_blank"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-bold text-sm hover:bg-primary/90">
                                <span class="material-symbols-outlined text-lg">play_circle</span>
                                Preview Video
                            </a>
                            <a href="<?= Security::escape($viewOrder['output_video_url']) ?>" download
                                class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 rounded-lg font-medium text-sm hover:bg-slate-50">
                                <span class="material-symbols-outlined text-lg">download</span>
                                Download
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Upload form with progress -->
                        <div id="upload-container">
                            <!-- Upload Form -->
                            <div id="upload-form-section">
                                <div id="upload-dropzone"
                                    class="border-2 border-dashed border-slate-200 rounded-xl p-8 text-center hover:border-primary/50 transition-colors cursor-pointer"
                                    onclick="document.getElementById('video-file-input').click()">
                                    <span class="material-symbols-outlined text-5xl text-slate-300 mb-3">cloud_upload</span>
                                    <p class="font-medium text-slate-700 mb-2">Upload Completed Video</p>
                                    <p class="text-sm text-slate-500 mb-4">MP4 format, max 100MB • Click or drag & drop</p>
                                    <input type="file" id="video-file-input" accept="video/mp4,video/*" class="hidden"
                                        onchange="handleFileSelect(this)">
                                    <button type="button"
                                        onclick="event.stopPropagation(); document.getElementById('video-file-input').click()"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-bold text-sm hover:bg-primary/90">
                                        <span class="material-symbols-outlined text-lg">folder_open</span>
                                        Choose File
                                    </button>
                                </div>

                                <!-- Selected file preview -->
                                <div id="file-preview" class="hidden mt-4 p-4 bg-slate-50 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <span class="material-symbols-outlined text-3xl text-primary">video_file</span>
                                        <div class="flex-1 min-w-0">
                                            <p id="file-name" class="font-medium text-slate-900 truncate"></p>
                                            <p id="file-size" class="text-sm text-slate-500"></p>
                                        </div>
                                        <button type="button" onclick="clearFile()" class="p-2 hover:bg-slate-200 rounded-lg">
                                            <span class="material-symbols-outlined text-slate-500">close</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 text-sm text-slate-500 mt-4">
                                    <span class="material-symbols-outlined text-lg">info</span>
                                    Video will be available for customer download for 7 days after upload.
                                </div>

                                <button type="button" id="upload-btn" onclick="startUpload()" disabled
                                    class="w-full mt-4 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span class="material-symbols-outlined">upload</span>
                                    Upload & Mark Complete
                                </button>
                            </div>

                            <!-- Upload Progress -->
                            <div id="upload-progress-section" class="hidden">
                                <div class="text-center py-8">
                                    <!-- Circular Progress -->
                                    <div class="relative inline-flex items-center justify-center">
                                        <svg class="w-32 h-32 transform -rotate-90">
                                            <circle cx="64" cy="64" r="56" stroke="#e2e8f0" stroke-width="8" fill="none" />
                                            <circle id="progress-circle" cx="64" cy="64" r="56" stroke="#7f13ec"
                                                stroke-width="8" fill="none" stroke-linecap="round" stroke-dasharray="351.86"
                                                stroke-dashoffset="351.86" style="transition: stroke-dashoffset 0.3s ease" />
                                        </svg>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <span id="progress-percent" class="text-3xl font-bold text-slate-900">0%</span>
                                        </div>
                                    </div>

                                    <p id="upload-status" class="text-lg font-medium text-slate-700 mt-4">Uploading...</p>
                                    <p id="upload-detail" class="text-sm text-slate-500 mt-1">Starting upload...</p>
                                </div>
                            </div>

                            <!-- Upload Success -->
                            <div id="upload-success-section" class="hidden">
                                <div class="text-center py-8">
                                    <div
                                        class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
                                        <span class="material-symbols-outlined text-4xl text-green-600">check_circle</span>
                                    </div>
                                    <p class="text-xl font-bold text-green-800">Upload Complete!</p>
                                    <p class="text-sm text-slate-500 mt-2">Video has been uploaded and order marked as complete.
                                    </p>
                                    <button onclick="location.reload()"
                                        class="mt-4 px-6 py-2 bg-primary text-white rounded-lg font-bold">
                                        Refresh Page
                                    </button>
                                </div>
                            </div>

                            <!-- Upload Error -->
                            <div id="upload-error-section" class="hidden">
                                <div class="text-center py-8">
                                    <div
                                        class="w-20 h-20 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                                        <span class="material-symbols-outlined text-4xl text-red-600">error</span>
                                    </div>
                                    <p class="text-xl font-bold text-red-800">Upload Failed</p>
                                    <p id="error-message" class="text-sm text-red-600 mt-2"></p>
                                    <button onclick="resetUpload()"
                                        class="mt-4 px-6 py-2 bg-slate-100 text-slate-700 rounded-lg font-bold hover:bg-slate-200">
                                        Try Again
                                    </button>
                                </div>
                            </div>
                        </div>

                        <script>
                            let selectedFile = null;
                            const orderId = <?= $viewOrder['id'] ?>;

                            function handleFileSelect(input) {
                                const file = input.files[0];
                                if (!file) return;

                                // Validate file type
                                if (!file.type.startsWith('video/')) {
                                    alert('Please select a video file');
                                    return;
                                }

                                // Validate file size (100MB max)
                                const maxSize = 100 * 1024 * 1024;
                                if (file.size > maxSize) {
                                    alert('File size exceeds 100MB limit');
                                    return;
                                }

                                selectedFile = file;

                                // Show preview
                                document.getElementById('file-preview').classList.remove('hidden');
                                document.getElementById('file-name').textContent = file.name;
                                document.getElementById('file-size').textContent = formatFileSize(file.size);
                                document.getElementById('upload-btn').disabled = false;
                            }

                            function clearFile() {
                                selectedFile = null;
                                document.getElementById('video-file-input').value = '';
                                document.getElementById('file-preview').classList.add('hidden');
                                document.getElementById('upload-btn').disabled = true;
                            }

                            function formatFileSize(bytes) {
                                if (bytes < 1024) return bytes + ' B';
                                if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                                return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
                            }

                            function startUpload() {
                                if (!selectedFile) return;

                                // Show progress section
                                document.getElementById('upload-form-section').classList.add('hidden');
                                document.getElementById('upload-progress-section').classList.remove('hidden');

                                const formData = new FormData();
                                formData.append('video_file', selectedFile);
                                formData.append('order_id', orderId);
                                formData.append('upload_video', '1');

                                const xhr = new XMLHttpRequest();

                                // Progress handler
                                xhr.upload.addEventListener('progress', (e) => {
                                    if (e.lengthComputable) {
                                        const percent = Math.round((e.loaded / e.total) * 100);
                                        updateProgress(percent, e.loaded, e.total);
                                    }
                                });

                                // Complete handler
                                xhr.addEventListener('load', () => {
                                    if (xhr.status === 200) {
                                        // Check if redirect (success)
                                        if (xhr.responseURL.includes('success=video_uploaded') || xhr.status === 200) {
                                            showSuccess();
                                        } else {
                                            showError('Upload completed but status update failed. Please refresh.');
                                        }
                                    } else {
                                        showError('Server error: ' + xhr.status);
                                    }
                                });

                                // Error handler
                                xhr.addEventListener('error', () => {
                                    showError('Network error. Please check your connection and try again.');
                                });

                                // Timeout handler
                                xhr.addEventListener('timeout', () => {
                                    showError('Upload timed out. The file may be too large.');
                                });

                                xhr.timeout = 300000; // 5 minutes timeout
                                xhr.open('POST', '/admin/orders.php');
                                xhr.send(formData);
                            }

                            function updateProgress(percent, loaded, total) {
                                // Update percentage text
                                document.getElementById('progress-percent').textContent = percent + '%';

                                // Update circular progress
                                const circle = document.getElementById('progress-circle');
                                const circumference = 2 * Math.PI * 56; // 351.86
                                const offset = circumference - (percent / 100) * circumference;
                                circle.style.strokeDashoffset = offset;

                                // Update status text
                                document.getElementById('upload-status').textContent =
                                    percent < 100 ? 'Uploading...' : 'Processing...';
                                document.getElementById('upload-detail').textContent =
                                    formatFileSize(loaded) + ' / ' + formatFileSize(total);
                            }

                            function showSuccess() {
                                document.getElementById('upload-progress-section').classList.add('hidden');
                                document.getElementById('upload-success-section').classList.remove('hidden');
                            }

                            function showError(message) {
                                document.getElementById('upload-progress-section').classList.add('hidden');
                                document.getElementById('upload-error-section').classList.remove('hidden');
                                document.getElementById('error-message').textContent = message;
                            }

                            function resetUpload() {
                                selectedFile = null;
                                document.getElementById('video-file-input').value = '';
                                document.getElementById('file-preview').classList.add('hidden');
                                document.getElementById('upload-btn').disabled = true;

                                document.getElementById('upload-error-section').classList.add('hidden');
                                document.getElementById('upload-form-section').classList.remove('hidden');

                                // Reset progress
                                document.getElementById('progress-percent').textContent = '0%';
                                document.getElementById('progress-circle').style.strokeDashoffset = 351.86;
                            }

                            // Drag and drop support
                            const dropzone = document.getElementById('upload-dropzone');

                            dropzone.addEventListener('dragover', (e) => {
                                e.preventDefault();
                                dropzone.classList.add('border-primary', 'bg-primary/5');
                            });

                            dropzone.addEventListener('dragleave', (e) => {
                                e.preventDefault();
                                dropzone.classList.remove('border-primary', 'bg-primary/5');
                            });

                            dropzone.addEventListener('drop', (e) => {
                                e.preventDefault();
                                dropzone.classList.remove('border-primary', 'bg-primary/5');

                                const files = e.dataTransfer.files;
                                if (files.length > 0) {
                                    document.getElementById('video-file-input').files = files;
                                    handleFileSelect(document.getElementById('video-file-input'));
                                }
                            });
                        </script>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Customer Info -->
            <div
                class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                <h3 class="font-bold mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">person</span>
                    Customer
                </h3>
                <div class="flex items-center gap-3 mb-4">
                    <div
                        class="w-12 h-12 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold text-lg">
                        <?= strtoupper(substr($viewOrder['customer_name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div>
                        <p class="font-bold"><?= Security::escape($viewOrder['customer_name'] ?? 'Unknown') ?></p>
                        <p class="text-sm text-slate-500"><?= Security::escape($viewOrder['customer_email'] ?? '') ?></p>
                    </div>
                </div>
                <?php if ($viewOrder['customer_phone']): ?>
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <span class="material-symbols-outlined text-lg">phone</span>
                        <?= Security::escape($viewOrder['customer_phone']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Template Info -->
            <div
                class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                <h3 class="font-bold mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">movie</span>
                    Template
                </h3>
                <?php if ($viewOrder['thumbnail_url']): ?>
                    <div class="aspect-video rounded-lg overflow-hidden bg-slate-100 mb-3">
                        <img src="<?= Security::escape($viewOrder['thumbnail_url']) ?>" alt=""
                            class="w-full h-full object-cover">
                    </div>
                <?php endif; ?>
                <p class="font-bold"><?= Security::escape($viewOrder['template_title'] ?? 'Unknown Template') ?></p>
            </div>

            <!-- Update Status -->
            <div
                class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6">
                <h3 class="font-bold mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">sync</span>
                    Update Status
                </h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
                    <input type="hidden" name="update_status" value="1">

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Payment Status</label>
                        <select name="payment_status"
                            class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
                            <option value="pending" <?= ($viewOrder['payment_status'] ?? 'pending') === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="paid" <?= ($viewOrder['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid
                            </option>
                            <option value="failed" <?= ($viewOrder['payment_status'] ?? '') === 'failed' ? 'selected' : '' ?>>
                                Failed</option>
                            <option value="refunded" <?= ($viewOrder['payment_status'] ?? '') === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Order Status</label>
                        <select name="order_status"
                            class="w-full h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
                            <option value="awaiting_payment" <?= ($viewOrder['order_status'] ?? 'awaiting_payment') === 'awaiting_payment' ? 'selected' : '' ?>>Awaiting Payment</option>
                            <option value="queued" <?= ($viewOrder['order_status'] ?? '') === 'queued' ? 'selected' : '' ?>>
                                Queued</option>
                            <option value="processing" <?= ($viewOrder['order_status'] ?? '') === 'processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="completed" <?= ($viewOrder['order_status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= ($viewOrder['order_status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>

                    <button type="submit"
                        class="w-full py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors">
                        Update Status
                    </button>
                </form>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Orders List View -->

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold">Order Management</h2>
            <p class="text-slate-500 mt-1">View and manage customer orders</p>
        </div>

        <div class="flex items-center gap-3">
            <button
                class="flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors text-sm font-medium">
                <span class="material-symbols-outlined text-lg">download</span>
                Export
            </button>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
            <span class="material-symbols-outlined">check_circle</span>
            Order <?= $_GET['success'] ?> successfully!
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white dark:bg-surface-dark p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <p class="text-slate-500 text-xs font-medium uppercase">New (24h)</p>
            <p class="text-2xl font-bold mt-1"><?= $stats['new'] ?></p>
        </div>
        <div class="bg-white dark:bg-surface-dark p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <p class="text-slate-500 text-xs font-medium uppercase">Queued</p>
            <p class="text-2xl font-bold mt-1 text-blue-600"><?= $stats['queued'] ?></p>
        </div>
        <div class="bg-white dark:bg-surface-dark p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <p class="text-slate-500 text-xs font-medium uppercase">Processing</p>
            <p class="text-2xl font-bold mt-1 text-purple-600"><?= $stats['processing'] ?></p>
        </div>
        <div class="bg-white dark:bg-surface-dark p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <p class="text-slate-500 text-xs font-medium uppercase">Completed</p>
            <p class="text-2xl font-bold mt-1 text-green-600"><?= $stats['completed'] ?></p>
        </div>
        <div class="bg-white dark:bg-surface-dark p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <p class="text-slate-500 text-xs font-medium uppercase">Revenue Today</p>
            <p class="text-2xl font-bold mt-1 text-green-600">$<?= number_format($stats['revenue_today'], 2) ?></p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-4 mb-6">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <span
                        class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 material-symbols-outlined text-lg">search</span>
                    <input type="text" name="search" value="<?= Security::escape($search) ?>"
                        class="w-full h-10 pl-10 pr-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm"
                        placeholder="Search by order ID, customer name or email...">
                </div>
            </div>

            <select name="status"
                class="h-10 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm">
                <option value="">All Status</option>
                <optgroup label="Payment">
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>💳 Pending</option>
                    <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>💳 Paid</option>
                    <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>💳 Failed</option>
                </optgroup>
                <optgroup label="Order">
                    <option value="queued" <?= $status === 'queued' ? 'selected' : '' ?>>📦 Queued</option>
                    <option value="processing" <?= $status === 'processing' ? 'selected' : '' ?>>📦 Processing</option>
                    <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>📦 Completed</option>
                </optgroup>
            </select>

            <button type="submit"
                class="h-10 px-6 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors">
                Filter
            </button>

            <?php if ($search || $status): ?>
                <a href="/admin/orders.php" class="text-sm text-slate-500 hover:text-primary">Clear filters</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Orders Table -->
    <div
        class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 dark:bg-white/5 text-slate-500 font-semibold uppercase text-xs">
                    <tr>
                        <th class="px-6 py-4">Order ID</th>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4">Template</th>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">Amount</th>
                        <th class="px-6 py-4">Payment</th>
                        <th class="px-6 py-4">Order</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php foreach ($orders as $order):
                        $paymentColors = ['pending' => 'bg-yellow-100 text-yellow-800', 'paid' => 'bg-green-100 text-green-800', 'failed' => 'bg-red-100 text-red-800', 'refunded' => 'bg-slate-100 text-slate-800'];
                        $orderColors = ['awaiting_payment' => 'bg-yellow-100 text-yellow-800', 'queued' => 'bg-blue-100 text-blue-800', 'processing' => 'bg-purple-100 text-purple-800', 'completed' => 'bg-green-100 text-green-800', 'cancelled' => 'bg-red-100 text-red-800'];
                        $paymentColor = $paymentColors[$order['payment_status'] ?? 'pending'] ?? 'bg-slate-100 text-slate-800';
                        $orderColor = $orderColors[$order['order_status'] ?? 'awaiting_payment'] ?? 'bg-slate-100 text-slate-800';
                        ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4">
                                <span
                                    class="font-bold text-slate-900 dark:text-white">#<?= Security::escape($order['order_number']) ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="size-8 rounded-full bg-primary/20 flex items-center justify-center text-primary text-xs font-bold shrink-0">
                                        <?= strtoupper(substr($order['customer_name'] ?? 'U', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900 dark:text-white">
                                            <?= Security::escape($order['customer_name'] ?? 'Unknown') ?>
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            <?= Security::escape($order['customer_email'] ?? '') ?>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <?php if ($order['thumbnail_url']): ?>
                                        <div class="size-8 rounded bg-slate-100 bg-cover bg-center shrink-0"
                                            style="background-image: url('<?= Security::escape($order['thumbnail_url']) ?>');">
                                        </div>
                                    <?php endif; ?>
                                    <span
                                        class="truncate max-w-[120px]"><?= Security::escape($order['template_title'] ?? '-') ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-500">
                                <?= date('M j, Y', strtotime($order['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold text-slate-900 dark:text-white">
                                    <?= $order['currency'] === 'INR' ? '₹' : '$' ?>         <?= number_format($order['amount'], 2) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $paymentColor ?>">
                                    <?= ucfirst($order['payment_status'] ?? 'pending') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $orderColor ?>">
                                    <?= ucwords(str_replace('_', ' ', $order['order_status'] ?? 'awaiting')) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="/admin/orders.php?action=view&id=<?= $order['id'] ?>"
                                        class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-500 hover:text-primary transition-colors"
                                        title="View Details">
                                        <span class="material-symbols-outlined text-lg">visibility</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                <span class="material-symbols-outlined text-5xl text-slate-300 mb-2">shopping_bag</span>
                                <p class="text-lg font-medium">No orders found</p>
                                <p class="text-sm">Orders will appear here once customers make purchases</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <p class="text-sm text-slate-500">
                    Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalOrders) ?> of <?= $totalOrders ?> orders
                </p>

                <div class="flex items-center gap-1">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>"
                            class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-500">
                            <span class="material-symbols-outlined">chevron_left</span>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?= $i ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>"
                            class="w-10 h-10 flex items-center justify-center rounded-lg <?= $i === $page ? 'bg-primary text-white' : 'hover:bg-slate-100 dark:hover:bg-white/10 text-slate-600' ?> font-medium text-sm">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>"
                            class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 text-slate-500">
                            <span class="material-symbols-outlined">chevron_right</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/admin.php';
?>