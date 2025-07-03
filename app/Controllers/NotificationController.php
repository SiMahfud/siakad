<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\NotificationModel;

class NotificationController extends BaseController
{
    protected $notificationModel;
    protected $helpers = ['auth', 'notification', 'text', 'url']; // Added url helper

    public function __construct()
    {
        $this->notificationModel = new NotificationModel();
    }

    public function index()
    {
        $currentUser = auth()->user();
        if (!$currentUser) {
            return redirect()->to('/login');
        }

        $data = [
            'title' => 'Notifikasi Saya',
            'notifications' => $this->notificationModel
                                ->where('user_id', $currentUser->id)
                                ->orderBy('created_at', 'DESC')
                                ->paginate(10), // Paginate per 10 notifications
            'pager' => $this->notificationModel->pager,
        ];

        // Mark all as read when viewing the full list - or do this selectively
        // For simplicity now, let's not mark all as read here automatically.
        // User can click a button or individual notification to mark as read.

        return view('notifications/index', $data);
    }

    public function markAsRead($notificationId = null)
    {
        $currentUser = auth()->user();
        if (!$currentUser) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        if ($notificationId === 'all') {
            if ($this->notificationModel->markAllAsRead($currentUser->id)) {
                return $this->response->setJSON(['success' => 'Semua notifikasi ditandai sudah dibaca.']);
            }
        } elseif ($notificationId) {
            if ($this->notificationModel->markAsRead($notificationId, $currentUser->id)) {
                // Optionally, redirect to the notification's link if available
                $notification = $this->notificationModel->find($notificationId);
                if ($notification && !empty($notification['link']) && $notification['user_id'] == $currentUser->id) {
                    // Check if link is full URL or relative
                    if (filter_var($notification['link'], FILTER_VALIDATE_URL)) {
                        return redirect()->to($notification['link']);
                    }
                    return redirect()->to(site_url($notification['link']));
                }
                return $this->response->setJSON(['success' => 'Notifikasi ditandai sudah dibaca.']);
            }
        }
        return $this->response->setStatusCode(400)->setJSON(['error' => 'Gagal menandai notifikasi.']);
    }

    // AJAX endpoint for marking a single notification as read, useful if clicking dropdown item
    public function ajaxMarkAsRead($notificationId)
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized or invalid request.']);
        }

        if ($this->notificationModel->markAsRead($notificationId, $currentUser->id)) {
            return $this->response->setJSON(['success' => true, 'unread_count' => get_unread_notifications_count()]);
        }

        return $this->response->setStatusCode(400)->setJSON(['error' => 'Failed to mark notification as read.']);
    }
}
