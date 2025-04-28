<?php
require_once('../db.php');
require_once('check_admin.php');

try {
    if (empty($_GET['id'])) {
        throw new Exception('Maintenance ID is required');
    }

    // Get maintenance task details
    $query = "
        SELECT 
            m.*,
            v.name as vehicle_name,
            v.type as vehicle_type,
            v.model as vehicle_model,
            v.status as vehicle_status,
            CONCAT(u.full_name, ' (', CASE WHEN u.is_admin = 1 THEN 'Admin' ELSE 'Tech' END, ')') as assigned_to_name
        FROM maintenance m
        LEFT JOIN vehicles v ON m.vehicle_id = v.id
        LEFT JOIN users u ON m.assigned_to = u.id
        WHERE m.id = ?
    ";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $_GET['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($task = mysqli_fetch_assoc($result)) {
        // Get maintenance history
        $history_query = "
            SELECT *
            FROM maintenance_history
            WHERE maintenance_id = ?
            ORDER BY created_at DESC
        ";
        
        $stmt = mysqli_prepare($conn, $history_query);
        mysqli_stmt_bind_param($stmt, "i", $_GET['id']);
        mysqli_stmt_execute($stmt);
        $history_result = mysqli_stmt_get_result($stmt);
        
        $history = [];
        while ($row = mysqli_fetch_assoc($history_result)) {
            $history[] = $row;
        }
        
        // Create timeline
        $timeline = [];
        
        // Add task creation
        $timeline[] = [
            'date' => $task['created_at'],
            'title' => 'Task Created',
            'description' => 'Maintenance task was scheduled'
        ];
        
        // Add history events
        foreach ($history as $event) {
            $timeline[] = [
                'date' => $event['created_at'],
                'title' => 'Status Updated to ' . ucfirst($event['status']),
                'description' => $event['notes'] ?? 'Status was updated'
            ];
        }
        
        // Sort timeline by date
        usort($timeline, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        // Add history and timeline to response
        $task['history'] = $history;
        $task['timeline'] = $timeline;
        
        echo json_encode($task);
    } else {
        throw new Exception('Maintenance task not found');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
