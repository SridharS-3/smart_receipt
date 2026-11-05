<?php
// api/contact_actions.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM contacts WHERE id = ?");
    $stmt->execute([$id]);
    $contact = $stmt->fetch();
    if ($contact) {
        echo json_encode(['success' => true, 'contact' => $contact]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Contact not found']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $account_number = sanitize($_POST['account_number'] ?? '');
    $ifsc_code = sanitize($_POST['ifsc_code'] ?? '');
    $bank_name = sanitize($_POST['bank_name'] ?? '');
    $payment_type = sanitize($_POST['payment_type'] ?? 'IMPS');
    if (!in_array($payment_type, ['IMPS', 'NEFT', 'RTGS', 'UPI'])) {
        $payment_type = 'IMPS';
    }
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid Contact ID']);
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Contact deleted successfully']);
        exit;
    }

    if (empty($name) || empty($account_number)) {
        echo json_encode(['success' => false, 'message' => 'Name and Account Number are required']);
        exit;
    }

    if ($action === 'add') {
        // Check duplicate account number
        $chk = $pdo->prepare("SELECT id FROM contacts WHERE account_number = ? LIMIT 1");
        $chk->execute([$account_number]);
        if ($chk->fetch()) {
            echo json_encode(['success' => false, 'message' => 'A contact with this account number already exists.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO contacts (name, account_number, ifsc_code, bank_name, payment_type, email, phone, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $account_number, $ifsc_code, $bank_name, $payment_type, $email, $phone, $notes]);
        echo json_encode(['success' => true, 'message' => 'Contact added successfully!', 'id' => $pdo->lastInsertId()]);
        exit;
    }

    if ($action === 'edit') {
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid Contact ID']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE contacts SET name = ?, account_number = ?, ifsc_code = ?, bank_name = ?, payment_type = ?, email = ?, phone = ?, notes = ? WHERE id = ?");
        $stmt->execute([$name, $account_number, $ifsc_code, $bank_name, $payment_type, $email, $phone, $notes, $id]);
        echo json_encode(['success' => true, 'message' => 'Contact updated successfully!']);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);

