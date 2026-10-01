<?php
/**
 * AcademicHub Nepal - Notification System Helper Functions
 * Handles creation and management of notifications for users and admins
 */

if (!function_exists('create_notification')) {
    /**
     * Create a notification for a user
     */
    function create_notification($pdo, $user_id, $title, $message, $type = 'info', $link_url = null) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, type, link_url, is_read, created_at)
                VALUES (:user_id, :title, :message, :type, :link_url, 0, NOW())
            ");
            $stmt->execute([
                ':user_id' => $user_id,
                ':title' => $title,
                ':message' => $message,
                ':type' => $type,
                ':link_url' => $link_url
            ]);
            return $pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("Failed to create notification: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('create_admin_notification')) {
    /**
     * Create a notification for all admins
     */
    function create_admin_notification($pdo, $title, $message, $type = 'info', $link_url = null) {
        try {
            // Get all admin users
            $stmt = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'superadmin')");
            $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $created = 0;
            foreach ($admins as $admin_id) {
                if (create_notification($pdo, $admin_id, $title, $message, $type, $link_url)) {
                    $created++;
                }
            }
            return $created;
        } catch (Exception $e) {
            error_log("Failed to create admin notifications: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('get_user_notifications')) {
    /**
     * Get unread notifications for a user
     */
    function get_user_notifications($pdo, $user_id, $limit = 10) {
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM notifications 
                WHERE user_id = :user_id 
                ORDER BY is_read ASC, created_at DESC 
                LIMIT :limit
            ");
            $stmt->execute([
                ':user_id' => $user_id,
                ':limit' => $limit
            ]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Failed to get user notifications: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('get_unread_notification_count')) {
    /**
     * Get count of unread notifications for a user
     */
    function get_unread_notification_count($pdo, $user_id) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0");
            $stmt->execute([':user_id' => $user_id]);
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Failed to get unread notification count: " . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('mark_notification_read')) {
    /**
     * Mark a notification as read
     */
    function mark_notification_read($pdo, $notification_id, $user_id) {
        try {
            $stmt = $pdo->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE id = :id AND user_id = :user_id
            ");
            return $stmt->execute([
                ':id' => $notification_id,
                ':user_id' => $user_id
            ]);
        } catch (Exception $e) {
            error_log("Failed to mark notification as read: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('mark_all_notifications_read')) {
    /**
     * Mark all notifications as read for a user
     */
    function mark_all_notifications_read($pdo, $user_id) {
        try {
            $stmt = $pdo->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE user_id = :user_id AND is_read = 0
            ");
            return $stmt->execute([':user_id' => $user_id]);
        } catch (Exception $e) {
            error_log("Failed to mark all notifications as read: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('notify_purchase')) {
    /**
     * Send notification when a student purchases a resource
     */
    function notify_purchase($pdo, $user_id, $resource_id, $resource_title, $amount) {
        // Notify student
        create_notification(
            $pdo,
            $user_id,
            'Purchase Successful',
            "You have successfully purchased '{$resource_title}' for " . format_npr($amount) . ". Your download is now available.",
            'purchase',
            BASE_URL . '/resource-details.php?id=' . $resource_id
        );
        
        // Notify admins
        create_admin_notification(
            $pdo,
            'New Purchase Alert',
            "A student has purchased '{$resource_title}' for " . format_npr($amount),
            'purchase',
            BASE_URL . '/admin/manage-orders.php'
        );
    }
}

if (!function_exists('notify_payment_approval')) {
    /**
     * Send notification when admin approves a payment
     */
    function notify_payment_approval($pdo, $user_id, $resource_id, $resource_title) {
        create_notification(
            $pdo,
            $user_id,
            'Payment Approved',
            "Your payment for '{$resource_title}' has been approved. You can now download the file.",
            'success',
            BASE_URL . '/resource-details.php?id=' . $resource_id
        );
    }
}

if (!function_exists('notify_download_activated')) {
    /**
     * Send notification when download access is activated
     */
    function notify_download_activated($pdo, $user_id, $resource_id, $resource_title) {
        create_notification(
            $pdo,
            $user_id,
            'Download Activated',
            "Your download access for '{$resource_title}' has been activated.",
            'download',
            BASE_URL . '/download.php?resource_id=' . $resource_id
        );
    }
}

if (!function_exists('notify_new_resource')) {
    /**
     * Send notification to students when new resource is uploaded
     */
    function notify_new_resource($pdo, $resource_title, $campus, $program) {
        // Get students matching campus/program
        $stmt = $pdo->prepare("
            SELECT id FROM users 
            WHERE role = 'student' 
            AND (campus = :campus OR campus IS NULL)
        ");
        $stmt->execute([':campus' => $campus]);
        $students = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $notified = 0;
        foreach ($students as $student_id) {
            if (create_notification(
                $pdo,
                $student_id,
                'New Resource Available',
                "A new resource '{$resource_title}' for {$program} at {$campus} is now available.",
                'announcement',
                BASE_URL . '/resources.php?program=' . urlencode($program)
            )) {
                $notified++;
            }
        }
        return $notified;
    }
}
