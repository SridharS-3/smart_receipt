<?php
// api/bulk_import_contacts.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['contacts'])) {
    echo json_encode(['success' => false, 'message' => 'No contacts provided for import']);
    exit;
}

$contacts = $input['contacts'];
$imported = 0;
$skipped = 0;

try {
    $pdo->beginTransaction();

    $stmtCheck = $pdo->prepare("SELECT id FROM contacts WHERE account_number = ? LIMIT 1");
    $stmtInsert = $pdo->prepare("INSERT INTO contacts (name, phone, email, account_number, ifsc_code, bank_name, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmtUpdate = $pdo->prepare("UPDATE contacts SET name = ?, phone = COALESCE(NULLIF(?, ''), phone), email = COALESCE(NULLIF(?, ''), email), ifsc_code = COALESCE(NULLIF(?, ''), ifsc_code), bank_name = COALESCE(NULLIF(?, ''), bank_name), notes = ? WHERE id = ?");

    foreach ($contacts as $c) {
        $name = sanitize($c['name'] ?? '');
        $phone = sanitize($c['phone'] ?? '');
        $email = sanitize($c['email'] ?? '');
        $account = sanitize($c['account_number'] ?? '');
        $ifsc = sanitize($c['ifsc_code'] ?? '');
        $bank = sanitize($c['bank_name'] ?? '');
        $notes = sanitize($c['notes'] ?? 'Imported via Bulk Upload');

        if (empty($name) || empty($account)) {
            $skipped++;
            continue;
        }

        $stmtCheck->execute([$account]);
        $existing = $stmtCheck->fetch();

        if ($existing) {
            $stmtUpdate->execute([$name, $phone, $email, $ifsc, $bank, $notes, $existing['id']]);
            $imported++;
        } else {
            $stmtInsert->execute([$name, $phone, $email, $account, $ifsc, $bank, $notes]);
            $imported++;
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'imported' => $imported, 'skipped' => $skipped]);
} catch (\PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

