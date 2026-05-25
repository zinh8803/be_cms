<?php

namespace app\services;

use app\models\AdminLog;

class AuditLogService
{
    /**
     * Log an admin action to the database.
     * @param int $userId
     * @param string $action
     * @param mixed $details
     * @return bool
     */
    public function log($userId, $action, $details = null)
    {
        $log = new AdminLog();
        $log->user_id = $userId;
        $log->action = $action;
        $log->details = is_array($details) || is_object($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : (string)$details;
        
        return $log->save(false);
    }
}
