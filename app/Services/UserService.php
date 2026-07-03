<?php

class UserService {
    //admin
    public static function listUsers() {
        $db = getDB();
        $stmt = $db->query("SELECT id, name, email, role, status, created_at 
        FROM users 
        WHERE role != 'admin' 
        ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }
    //doctors
    public static function listDoctors() {
        $db = getDB();
        $stmt = $db->query("SELECT id, name, email, role, status, created_at 
        FROM users 
        WHERE role = 'doctor' 
        ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    //patients
    public static function listRegisterPatients() {
        $db = getDB();
        $stmt = $db->query("SELECT id, name, role
        FROM users 
        WHERE role = 'patient' 
        ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }
    public static function listPatients() {
        $db = getDB();
        
        $sql = "SELECT 
                    p.id AS patient_id, 
                    u.name, 
                    u.role 
                FROM users u
                INNER JOIN patients p ON u.id = p.user_id
                WHERE u.role = 'patient' AND p.deleted_at IS NULL
                ORDER BY p.created_at DESC";
                
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
}
    

    

    public static function updateStatus($userId, $status) {
        $db = getDB();

        $allowed = ['active', 'inactive'];
        if (!in_array($status, $allowed)) {
            Response::error('Invalid status value', 400);
        }

        $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            Response::error('User not found', 404);
        }

        $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$status, $userId]);

        return ['user_id' => (int) $userId, 'status' => $status];
    }
}