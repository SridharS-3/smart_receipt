<?php
// api/bulk_save_receipts.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['rows'])) {
    echo json_encode(['success' => false, 'message' => 'No rows provided']);
    exit;
}

$template_type = sanitize($input['template_type'] ?? 'template1');
$batch_id = sanitize($input['batch_id'] ?? ('BATCH-' . time()));
$rows = $input['rows'];

$savedCount = 0;

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO receipts 
        (receipt_no, template_type, status, amount, transaction_type, transaction_id, banking_name, account_number, ifsc_code, generated_type, batch_id, payment_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'bulk', ?, ?)
        ON DUPLICATE KEY UPDATE 
        amount = VALUES(amount), status = VALUES(status), banking_name = VALUES(banking_name), account_number = VALUES(account_number), ifsc_code = VALUES(ifsc_code)");

    foreach ($rows as $r) {
        $name = sanitize($r['name'] ?? '');
        $account_no = sanitize($r['account_no'] ?? '');
        $amount = (float)($r['amount'] ?? 0);
        $utr = sanitize($r['utr'] ?? '');
        $ifsc = sanitize($r['ifsc'] ?? '');
        $type = sanitize($r['type'] ?? 'IMPS');
        $status = sanitize($r['status'] ?? 'Successful');
        $date = !empty($r['date']) ? date('Y-m-d H:i:s', strtotime($r['date'])) : date('Y-m-d H:i:s');

        if (empty($name) || empty($utr)) continue;

        $receipt_no = 'REC-' . strtoupper(substr(uniqid(), -6)) . '-' . rand(100, 999);

        $stmt->execute([
            $receipt_no, $template_type, $status, $amount, $type,
            $utr, $name, $account_no, $ifsc, $batch_id, $date
        ]);
        $savedCount++;
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'count' => $savedCount, 'batch_id' => $batch_id]);
} catch (\PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

