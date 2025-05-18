<?php
header('Content-Type: application/json');

// In-memory storage for payment statuses
$paymentStatuses = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $callbackData = json_decode(file_get_contents('php://input'), true);
    error_log('Received callback raw: ' . print_r($callbackData, true));

    if ($callbackData && isset($callbackData['ExternalReference']) && isset($callbackData['Status'])) {
        $externalReference = $callbackData['ExternalReference'];
        $status = strtoupper($callbackData['Status']);
        $paymentStatuses[$externalReference] = ['status' => $status, 'details' => $callbackData];
        error_log("Updated status for reference $externalReference to $status");
        http_response_code(200);
        echo json_encode(['success' => true]);
    } else {
        error_log('Invalid callback payload: ' . print_r($callbackData, true));
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid callback payload']);
    }
}

// Expose status for polling
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['reference'])) {
    $reference = $_GET['reference'];
    error_log("Checking status for reference: $reference");
    if (isset($paymentStatuses[$reference])) {
        echo json_encode(['success' => true, 'status' => $paymentStatuses[$reference]['status'], 'details' => $paymentStatuses[$reference]['details']]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Status not found']);
    }
}
?>
