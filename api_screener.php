<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$action = $_REQUEST['action'] ?? '';
$user_id = $_SESSION['user_id'];

if ($action === 'init_db') {
    $sql = "CREATE TABLE IF NOT EXISTS saved_screens (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        user_id INT, 
        name VARCHAR(255), 
        description TEXT, 
        rules JSON, 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    if ($conn->query($sql)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
    exit();
}

if ($action === 'save') {
    $name = $_POST['screen_name'] ?? 'Unnamed Screener';
    $desc = $_POST['screen_desc'] ?? '';
    
    // rules as an array of objects: [{field: 'pe_ratio', op: '<', val: 15}]
    $rules = $_POST['rules'] ?? '[]';
    
    $stmt = $conn->prepare("INSERT INTO saved_screens (user_id, name, description, rules) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $name, $desc, $rules);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }
    $stmt->close();
    exit();
}

if ($action === 'load') {
    $stmt = $conn->prepare("SELECT id, name, description, rules FROM saved_screens WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $screens = [];
    while ($row = $result->fetch_assoc()) {
        $row['rules'] = json_decode($row['rules'], true);
        $screens[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $screens]);
    $stmt->close();
    exit();
}

if ($action === 'delete') {
    $screen_id = intval($_POST['screen_id'] ?? 0);
    if ($screen_id > 0) {
        $stmt = $conn->prepare("DELETE FROM saved_screens WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $screen_id, $user_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid screen ID']);
    }
    exit();
}

if ($action === 'screen') {
    try {
        $whereClauses = [];
        $params = [];
        $types = "";

        // rules in format: [ {field: 'price', op: '>', val: '1000'}, ... ]
        $rules = json_decode($_POST['rules'] ?? '[]', true);

        $allowedFields = ['price', 'pe_ratio', 'pbv', 'roe', 'market_cap', 'volume'];
        $allowedOps = ['>', '<', '=', '>=', '<='];

        if (is_array($rules)) {
            foreach ($rules as $rule) {
                $field = $rule['field'] ?? '';
                $op = $rule['op'] ?? '';
                $val = $rule['val'] ?? '';

                if (in_array($field, $allowedFields) && in_array($op, $allowedOps) && is_numeric($val)) {
                    $whereClauses[] = "$field $op ?";
                    $params[] = (float)$val;
                    $types .= "d";
                }
            }
        }

        $sql = "SELECT ticker, company_name, sector, price, pe_ratio, pbv, roe, market_cap, volume, trend FROM stocks";
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        } else {
            $sql .= " LIMIT 50"; 
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $stocks = [];
        while ($row = $result->fetch_assoc()) {
            $stocks[] = $row;
        }
        $stmt->close();
        echo json_encode(['status' => 'success', 'data' => $stocks]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);


