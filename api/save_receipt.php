<?php
// api/save_receipt.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$template_type = sanitize($input['template_type'] ?? 'template1');
$status = sanitize($input['status'] ?? 'Successful');
$amount = (float)($input['amount'] ?? 0);
$transaction_type = sanitize($input['transaction_type'] ?? 'IMPS');
$transaction_id = sanitize($input['transaction_id'] ?? '');
$banking_name = sanitize($input['banking_name'] ?? '');
$account_number = sanitize($input['account_number'] ?? '');
$ifsc_code = sanitize($input['ifsc_code'] ?? '');
$generated_type = sanitize($input['generated_type'] ?? 'single');
$batch_id = sanitize($input['batch_id'] ?? null);

$raw_date = sanitize($input['payment_date'] ?? '');
if (!empty($raw_date) && ($parsed_ts = strtotime($raw_date)) !== false) {
    $payment_date = date('Y-m-d H:i:s', $parsed_ts);
} else {
    $payment_date = date('Y-m-d H:i:s');
}

if (empty($transaction_id) || empty($banking_name)) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID and Beneficiary Name are required.']);
    exit;
}

$receipt_no = 'REC-' . strtoupper(substr(uniqid(), -6)) . '-' . rand(100, 999);

try {
    // Check if receipt already exists by transaction_id
    $check = $pdo->prepare("SELECT id FROM receipts WHERE transaction_id = ? LIMIT 1");
    $check->execute([$transaction_id]);
    $existing = $check->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE receipts SET 
            template_type = ?, status = ?, amount = ?, transaction_type = ?, 
            banking_name = ?, account_number = ?, ifsc_code = ?, payment_date = ?
            WHERE id = ?");
        $stmt->execute([
            $template_type, $status, $amount, $transaction_type,
            $banking_name, $account_number, $ifsc_code, $payment_date, $existing['id']
        ]);
        echo json_encode(['success' => true, 'message' => 'Receipt record saved successfully!', 'receipt_id' => $existing['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO receipts 
            (receipt_no, template_type, status, amount, transaction_type, transaction_id, banking_name, account_number, ifsc_code, generated_type, batch_id, payment_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $receipt_no, $template_type, $status, $amount, $transaction_type,
            $transaction_id, $banking_name, $account_number, $ifsc_code, $generated_type, $batch_id, $payment_date
        ]);
        echo json_encode(['success' => true, 'message' => 'Receipt record saved successfully!', 'receipt_id' => $pdo->lastInsertId()]);
    }
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

