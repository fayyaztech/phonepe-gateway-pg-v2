<?php

/**
 * Payment Example for PhonePgV2
 * This file demonstrates how to initiate payments and check their status
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/PhonePgV2.php';

use Fayyaztech\PhonePgV2\PhonePgV2;
use PhonePe\Env;

// Configuration
$clientId = "TEST-M22DO0TV8T0O4_25062"; // Replace with your actual Client ID
$clientVersion = 1; // Replace with your actual Client Version
$clientSecret = "YjNlYzIyODYtMjZkOS00OTM3LThmYWMtYWE4OTUyMjcxYzg2"; // Replace with your actual Client Secret
$env = Env::UAT; // Use UAT for testing, PRODUCTION for live

// Payment details
$merchantOrderId = "ORDER_" . time(); // Unique order ID
$amount = 100000; // Amount in paisa (1000 = ₹10.00)
$redirectUrl = "http://localhost:8080?orderid=$merchantOrderId";
$message = "Test payment for order #" . $merchantOrderId;

// Optional meta information
$metaInfo = [
    'udf1' => 'Customer ID: 12345',
    'udf2' => 'Product: Test Product',
    'udf3' => 'Category: Electronics'
];

try {
    // Initialize PhonePgV2
    $phonePg = new PhonePgV2($clientId, $clientVersion, $clientSecret, $env);

    echo "=== Initiating Payment ===\n";
    echo "Order ID: " . $merchantOrderId . "\n";
    echo "Amount: ₹" . ($amount / 100) . "\n";

    // Initiate payment
    $payResponse = $phonePg->initiatePayment(
        $merchantOrderId,
        $amount,
        $redirectUrl,
        $message,
        $metaInfo
    );

    // Process payment response (without auto-redirect for testing)
    $responseData = $phonePg->processPaymentResponse($payResponse, false);

    echo "\n=== Payment Response ===\n";
    echo "State: " . $responseData['state'] . "\n";
    echo "Order ID: " . $responseData['orderId'] . "\n";
    echo "Redirect URL: " . $responseData['redirectUrl'] . "\n";
    echo "Expires At: " . date('Y-m-d H:i:s', $responseData['expireAt']) . "\n";

    if ($responseData['state'] === 'PENDING') {
        echo "\n✓ Payment initiated successfully!\n";
        echo "Redirect user to: " . $responseData['redirectUrl'] . "\n";
    } else {
        echo "\n✗ Payment initiation failed: " . $responseData['state'] . "\n";
    }

    // Example: Check payment status
    echo "\n=== Checking Payment Status ===\n";
    $simpleStatus = $phonePg->getSimpleOrderStatus($merchantOrderId);

    echo "Status: " . $simpleStatus['state'] . "\n";
    echo "Is Pending: " . ($simpleStatus['isPending'] ? 'Yes' : 'No') . "\n";
    echo "Is Completed: " . ($simpleStatus['isCompleted'] ? 'Yes' : 'No') . "\n";
    echo "Is Failed: " . ($simpleStatus['isFailed'] ? 'Yes' : 'No') . "\n";
} catch (\PhonePe\common\exceptions\PhonePeException $e) {
    echo "PhonePe Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
