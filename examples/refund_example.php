<?php

/**
 * Refund Example for PhonePgV2
 * This file demonstrates how to initiate refunds and check their status
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/PhonePgV2.php';

use Fayyaztech\PhonePgV2\PhonePgV2;
use PhonePe\Env;

// Configuration
$clientId = "YOUR_CLIENT_ID"; // Replace with your actual Client ID
$clientVersion = 1; // Replace with your actual Client Version
$clientSecret = "YOUR_CLIENT_SECRET"; // Replace with your actual Client Secret
$env = Env::UAT; // Use UAT for testing, PRODUCTION for live

// Refund details
$originalMerchantOrderId = "ORDER_123456789"; // Replace with actual order ID to refund
$refundAmount = 500; // Amount to refund in paisa (500 = ₹5.00)
$merchantRefundId = "REFUND_" . time(); // Unique refund ID

try {
    // Initialize PhonePgV2
    $phonePg = new PhonePgV2($clientId, $clientVersion, $clientSecret, $env);

    echo "=== Initiating Refund ===\n";
    echo "Original Order ID: " . $originalMerchantOrderId . "\n";
    echo "Refund Amount: ₹" . ($refundAmount / 100) . "\n";
    echo "Refund ID: " . $merchantRefundId . "\n";

    // Method 1: Complete refund workflow (recommended for simple use)
    echo "\n--- Using Complete Refund Workflow ---\n";
    $refundResult = $phonePg->processRefund($originalMerchantOrderId, $refundAmount);

    echo "Refund State: " . $refundResult['state'] . "\n";
    echo "Refund ID: " . $refundResult['refundId'] . "\n";
    echo "Merchant Refund ID: " . $refundResult['merchantRefundId'] . "\n";
    echo "Amount: ₹" . ($refundResult['amount'] / 100) . "\n";

    if ($refundResult['isSuccessful']) {
        echo "✓ Refund completed successfully!\n";
    } elseif ($refundResult['isPending']) {
        echo "⏳ Refund is being processed...\n";
    } else {
        echo "✗ Refund failed!\n";
    }

    // Method 2: Manual refund initiation (for more control)
    echo "\n--- Manual Refund Initiation ---\n";
    $refundResponse = $phonePg->initiateRefund(
        $merchantRefundId,
        $originalMerchantOrderId,
        $refundAmount
    );

    $processedRefund = $phonePg->processRefundResponse($refundResponse);

    echo "Manual Refund State: " . $processedRefund['state'] . "\n";
    echo "Manual Refund ID: " . $processedRefund['refundId'] . "\n";

    // Check refund status
    echo "\n=== Checking Refund Status ===\n";
    $refundStatus = $phonePg->getSimpleRefundStatus($merchantRefundId);

    echo "Status: " . $refundStatus['state'] . "\n";
    echo "Original Order ID: " . $refundStatus['originalOrderId'] . "\n";
    echo "Is Completed: " . ($refundStatus['isCompleted'] ? 'Yes' : 'No') . "\n";
    echo "Is Pending: " . ($refundStatus['isPending'] ? 'Yes' : 'No') . "\n";
    echo "Is Failed: " . ($refundStatus['isFailed'] ? 'Yes' : 'No') . "\n";

    // Get detailed refund status
    echo "\n=== Detailed Refund Status ===\n";
    $detailedStatus = $phonePg->checkRefundStatus($merchantRefundId);
    $processedStatus = $phonePg->processRefundStatusResponse($detailedStatus);

    echo "Detailed Status: " . json_encode($processedStatus, JSON_PRETTY_PRINT) . "\n";
} catch (\PhonePe\common\exceptions\PhonePeException $e) {
    echo "PhonePe Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
