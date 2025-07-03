<?php

if (!function_exists('get_unread_notifications_count')) {
    /**
     * Retrieves the count of unread notifications for the current logged-in user.
     *
     * @return int The count of unread notifications.
     */
    function get_unread_notifications_count(): int
    {
        $currentUser = auth()->user();
        if (!$currentUser) {
            return 0;
        }

        $notificationModel = new \App\Models\NotificationModel();
        return $notificationModel->countUnreadNotifications($currentUser->id);
    }
}

if (!function_exists('get_unread_notifications')) {
    /**
     * Retrieves unread notifications for the current logged-in user.
     *
     * @param int $limit The maximum number of notifications to retrieve.
     * @return array An array of unread notifications.
     */
    function get_unread_notifications(int $limit = 5): array
    {
        $currentUser = auth()->user();
        if (!$currentUser) {
            return [];
        }

        $notificationModel = new \App\Models\NotificationModel();
        return $notificationModel->getUnreadNotifications($currentUser->id, $limit);
    }
}

if (!function_exists('time_ago')) {
    /**
     * Formats a timestamp into a human-readable "time ago" string.
     * E.g., "5 minutes ago", "2 hours ago", "3 days ago".
     *
     * @param string $timestamp A MySQL DATETIME or TIMESTAMP string.
     * @return string The "time ago" string.
     */
    function time_ago(string $timestamp): string
    {
        $currentTime = time();
        $time = strtotime($timestamp);
        $timeDifference = $currentTime - $time;
        $seconds = $timeDifference;
        $minutes = round($seconds / 60);
        $hours = round($seconds / 3600);
        $days = round($seconds / 86400);
        $weeks = round($seconds / 604800);
        $months = round($seconds / 2629440);
        $years = round($seconds / 31553280);

        if ($seconds <= 60) {
            return "Baru saja";
        } elseif ($minutes <= 60) {
            return ($minutes === 1) ? "1 menit lalu" : "$minutes menit lalu";
        } elseif ($hours <= 24) {
            return ($hours === 1) ? "1 jam lalu" : "$hours jam lalu";
        } elseif ($days <= 7) {
            return ($days === 1) ? "Kemarin" : "$days hari lalu";
        } elseif ($weeks <= 4.3) { // 4.3 weeks per month
            return ($weeks === 1) ? "1 minggu lalu" : "$weeks minggu lalu";
        } elseif ($months <= 12) {
            return ($months === 1) ? "1 bulan lalu" : "$months bulan lalu";
        } else {
            return ($years === 1) ? "1 tahun lalu" : "$years tahun lalu";
        }
    }
}
