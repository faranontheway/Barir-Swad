<?php
// notification_system.php - Core notification functions (Updated for existing schema)

function createNotification($conn, $user_id, $title, $message, $related_id = null) {
    // Using complaint_support table structure for notifications
    // Status: 'Open' = Unread, 'In Progress' = Read, 'Closed' = Dismissed
    $sql = "INSERT INTO complaint_support (User_ID, Complaint_ID, Description, Status, Submitted_Date, Messages) 
            VALUES (?, ?, ?, 'Open', CURDATE(), ?)";
    $stmt = $conn->prepare($sql);
    
    // Use a unique complaint_id for notifications (starting from 9000 to avoid conflicts)
    $notification_id = 9000 + rand(1, 9999);
    $description = "NOTIFICATION: " . $title;
    $full_message = json_encode(['title' => $title, 'message' => $message, 'related_id' => $related_id, 'type' => 'notification']);
    
    $stmt->bind_param("iiss", $user_id, $notification_id, $description, $full_message);
    return $stmt->execute();
}

function notifyAllCooks($conn, $title, $message, $related_id = null) {
    $cooks_sql = "SELECT U_ID FROM user WHERE Type = 'Cook'";
    $cooks_result = $conn->query($cooks_sql);
    
    while($cook = $cooks_result->fetch_assoc()) {
        createNotification($conn, $cook['U_ID'], $title, $message, $related_id);
    }
}

function notifyAdmins($conn, $title, $message, $related_id = null) {
    $admins_sql = "SELECT U_ID FROM user WHERE Type = 'Admin'";
    $admins_result = $conn->query($admins_sql);
    
    while($admin = $admins_result->fetch_assoc()) {
        createNotification($conn, $admin['U_ID'], $title, $message, $related_id);
    }
}

function getNotifications($conn, $user_id, $limit = 10) {
    $sql = "SELECT * FROM complaint_support 
            WHERE User_ID = ? AND Description LIKE 'NOTIFICATION:%' 
            ORDER BY Submitted_Date DESC, Complaint_ID DESC 
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while($row = $result->fetch_assoc()) {
        $message_data = json_decode($row['Messages'], true);
        $notifications[] = [
            'id' => $row['Complaint_ID'],
            'title' => $message_data['title'] ?? 'Notification',
            'message' => $message_data['message'] ?? '',
            'related_id' => $message_data['related_id'] ?? null,
            'status' => $row['Status'],
            'date' => $row['Submitted_Date']
        ];
    }
    
    return $notifications;
}

function markNotificationAsRead($conn, $user_id, $notification_id) {
    $sql = "UPDATE complaint_support SET Status = 'In Progress' 
            WHERE User_ID = ? AND Complaint_ID = ? AND Description LIKE 'NOTIFICATION:%'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $notification_id);
    return $stmt->execute();
}

function getUnreadNotificationCount($conn, $user_id) {
    $sql = "SELECT COUNT(*) as count FROM complaint_support 
            WHERE User_ID = ? AND Status = 'Open' AND Description LIKE 'NOTIFICATION:%'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['count'];
}

// Since we can't modify the orders table, we'll use a different approach for order assignment
// We'll create a temporary tracking system using the complaint_support table

function assignOrderToCook($conn, $order_id, $cook_id) {
    // Check if order is already assigned
    $check_sql = "SELECT * FROM complaint_support 
                  WHERE Description = 'ORDER_ASSIGNMENT' 
                  AND Messages LIKE '%\"order_id\":$order_id%'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        return false; // Already assigned
    }
    
    // Create assignment record
    $assignment_id = 8000 + rand(1, 9999);
    $assignment_data = json_encode([
        'order_id' => $order_id,
        'cook_id' => $cook_id,
        'assigned_date' => date('Y-m-d H:i:s')
    ]);
    
    $assign_sql = "INSERT INTO complaint_support (User_ID, Complaint_ID, Description, Status, Submitted_Date, Messages) 
                   VALUES (?, ?, 'ORDER_ASSIGNMENT', 'In Progress', CURDATE(), ?)";
    $assign_stmt = $conn->prepare($assign_sql);
    $assign_stmt->bind_param("iis", $cook_id, $assignment_id, $assignment_data);
    
    return $assign_stmt->execute();
}

function getAssignedOrders($conn, $cook_id) {
    // Get orders assigned to this cook
    $assignment_sql = "SELECT Messages FROM complaint_support 
                       WHERE User_ID = ? AND Description = 'ORDER_ASSIGNMENT' AND Status = 'In Progress'";
    $assignment_stmt = $conn->prepare($assignment_sql);
    $assignment_stmt->bind_param("i", $cook_id);
    $assignment_stmt->execute();
    $assignment_result = $assignment_stmt->get_result();
    
    $assigned_order_ids = [];
    while($assignment = $assignment_result->fetch_assoc()) {
        $data = json_decode($assignment['Messages'], true);
        if (isset($data['order_id'])) {
            $assigned_order_ids[] = $data['order_id'];
        }
    }
    
    if (empty($assigned_order_ids)) {
        // Return empty result
        return $conn->query("SELECT * FROM orders WHERE 1=0");
    }
    
    // Get order details
    $order_ids_str = implode(',', $assigned_order_ids);
    $orders_sql = "SELECT o.*, u.Name as Customer_Name, u.Email as Customer_Email,
                          GROUP_CONCAT(m.Name SEPARATOR ', ') as Meals
                   FROM orders o
                   JOIN user u ON o.Customer_ID = u.U_ID
                   LEFT JOIN orders_have_meal ohm ON o.OrderID = ohm.OrderID
                   LEFT JOIN meal m ON ohm.M_ID = m.Meal_ID
                   WHERE o.OrderID IN ($order_ids_str)
                   GROUP BY o.OrderID
                   ORDER BY o.Date DESC";
    
    return $conn->query($orders_sql);
}

function isOrderAssigned($conn, $order_id) {
    $check_sql = "SELECT * FROM complaint_support 
                  WHERE Description = 'ORDER_ASSIGNMENT' 
                  AND Messages LIKE '%\"order_id\":$order_id%'
                  AND Status = 'In Progress'";
    $check_result = $conn->query($check_sql);
    return $check_result->num_rows > 0;
}

function getAssignedCook($conn, $order_id) {
    $check_sql = "SELECT User_ID, Messages FROM complaint_support 
                  WHERE Description = 'ORDER_ASSIGNMENT' 
                  AND Messages LIKE '%\"order_id\":$order_id%'
                  AND Status = 'In Progress'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        $assignment = $check_result->fetch_assoc();
        $data = json_decode($assignment['Messages'], true);
        return [
            'cook_id' => $assignment['User_ID'],
            'assigned_date' => $data['assigned_date'] ?? null
        ];
    }
    
    return null;
}

function completeOrderAssignment($conn, $order_id, $cook_id) {
    // Mark the assignment as completed
    $complete_sql = "UPDATE complaint_support 
                     SET Status = 'Closed' 
                     WHERE User_ID = ? 
                     AND Description = 'ORDER_ASSIGNMENT' 
                     AND Messages LIKE '%\"order_id\":$order_id%'";
    $complete_stmt = $conn->prepare($complete_sql);
    $complete_stmt->bind_param("i", $cook_id);
    return $complete_stmt->execute();
}
?>