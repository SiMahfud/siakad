<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table            = 'notifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false; // Depending on whether you want to soft delete notifications
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'student_id',
        'type',
        'message',
        'link',
        'is_read'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    // protected $deletedField  = 'deleted_at'; // Uncomment if useSoftDeletes is true

    // Validation
    protected $validationRules      = [
        'user_id'    => 'required|integer|is_not_unique[users.id]',
        'student_id' => 'permit_empty|integer|is_not_unique[students.id]',
        'type'       => 'required|max_length[100]',
        'message'    => 'required|string',
        'link'       => 'permit_empty|max_length[255]',
        'is_read'    => 'required|in_list[0,1]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['setDefaultIsRead'];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    protected function setDefaultIsRead(array $data)
    {
        if (!isset($data['data']['is_read'])) {
            $data['data']['is_read'] = 0; // Default to false (unread)
        }
        return $data;
    }

    /**
     * Get unread notifications for a user.
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getUnreadNotifications(int $userId, int $limit = 5): array
    {
        return $this->where('user_id', $userId)
                    ->where('is_read', false)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Count unread notifications for a user.
     * @param int $userId
     * @return int
     */
    public function countUnreadNotifications(int $userId): int
    {
        return $this->where('user_id', $userId)
                    ->where('is_read', false)
                    ->countAllResults();
    }

    /**
     * Mark notifications as read.
     * @param array|int $notificationIds
     * @param int $userId To ensure user can only mark their own notifications as read
     * @return bool
     */
    public function markAsRead($notificationIds, int $userId): bool
    {
        if (!is_array($notificationIds)) {
            $notificationIds = [$notificationIds];
        }

        if (empty($notificationIds)) {
            return true;
        }

        return $this->where('user_id', $userId)
                    ->whereIn('id', $notificationIds)
                    ->set(['is_read' => true, 'updated_at' => date('Y-m-d H:i:s')])
                    ->update();
    }

    /**
     * Mark all unread notifications for a user as read.
     * @param int $userId
     * @return bool
     */
    public function markAllAsRead(int $userId): bool
    {
        return $this->where('user_id', $userId)
                    ->where('is_read', false)
                    ->set(['is_read' => true, 'updated_at' => date('Y-m-d H:i:s')])
                    ->update();
    }
}
