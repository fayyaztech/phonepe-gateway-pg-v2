<?php

//Use composer autoload to load class files
require_once __DIR__ . "/../vendor/autoload.php";

//Required package/libraries
use fayyaztech\phonePePaymentGateway\PhonePe;
use fayyaztech\phonePePaymentGateway\PhonePeApiException;

try {
    // Initialize PhonePe with your credentials
    $phonepe = new PhonePe(
        merchant_id: 'MERCHANTID',  // Your PhonePe merchant ID
        salt_key: 'SALTKEY',        // Your salt key
        salt_index: 1               // Your salt index
    );

    // Example: Initiate Payment
    $response = $phonepe->PaymentCall(
        merchantTransactionId: 'TXN' . time(),  // Unique transaction ID
        merchantUserId: 'USER123',              // Customer ID
        amount: 100 * 100,                      // Amount in paise (Rs. 100)
        redirectUrl: 'https://your-domain.com/redirect',
        callbackUrl: 'https://your-domain.com/callback',
        mobileNumber: '9999999999',
        mode: 'UAT'                             // Use UAT for testing
    );

    // Print payment URL
    if ($response['responseCode'] === 200) {
        echo "Payment URL: " . $response['url'] . "\n";
    } else {
        echo "Error: " . $response['msg'] . "\n";
    }

    // Example: Check Payment Status
    $statusResponse = $phonepe->PaymentStatus(
        merchantId: 'MERCHANTID',
        merchantTransactionId: 'TXN123',
        mode: 'UAT'
    );

    // Print status
    echo "Payment Status: " . $statusResponse['status'] . "\n";

    // Example: Process Refund
    $refundResponse = $phonepe->PaymentRefund(
        merchantId: 'MERCHANTID',
        refundtransactionId: 'REFUND' . time(),
        orderTransactionId: 'TXN123',
        callbackUrl: 'https://your-domain.com/refund-callback',
        amount: 100 * 100,
        mode: 'UAT'
    );

    // Print refund status
    echo "Refund Status: " . $refundResponse['state'] . "\n";
} catch (PhonePeApiException $e) {
    echo "Error: " . $e->errorMessage() . "\n";
}
