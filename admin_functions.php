<?php
function logAdminAction($admin_id, $action_type, $action_details, $target_id = null, $target_type = null) {
    global $mysqli;
    
    $sql = "INSERT INTO admin_logs (admin_id, action_type, action_details, target_id, target_type) 
            VALUES (?, ?, ?, ?, ?)";
            
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("issis", $admin_id, $action_type, $action_details, $target_id, $target_type);
        $stmt->execute();
        $stmt->close();
    }
}
?>