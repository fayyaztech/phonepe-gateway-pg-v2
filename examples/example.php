<?php

require_once __DIR__ . "/../vendor/autoload.php";

use fayyaztech\PhonePeGatewayPGV2\PhonePe;
use fayyaztech\PhonePeGatewayPGV2\PhonePeApiException;

try {
    // Initialize PhonePe with your credentials
    $phonepe = new PhonePe(
        client_id: 'CLIENT_ID',       // Your client ID (first parameter)
        client_secret: 'CLIENT_SECRET', // Your client secret
        mode: 'UAT',                 // Set mode once here
        debug: true                   // Enable debug logging
    );

    // Example: Initiate Payment
    $response = $phonepe->initiatePayment(
        merchantOrderId: 'ORDER' . time(),    // Unique order ID
        amount: 100 * 100,                   // Amount in paise (Rs. 100)
        redirectUrl: 'https://your-domain.com/redirect',
        metaInfo: [                          // Optional metadata
            'udf1' => 'custom-value-1',
            'udf2' => 'custom-value-2'
        ],
        enabledPaymentModes: [               // Optional payment modes
            ['type' => 'UPI_INTENT'],
            ['type' => 'CARD', 'cardTypes' => ['CREDIT_CARD']]
        ],
        expireAfter: 1200                   // Optional expiry in seconds
    );

    // Print payment details
    if (isset($response['orderId'])) {
        echo "Order ID: " . $response['orderId'] . "\n";
        echo "Payment URL: " . $response['redirectUrl'] . "\n";
        echo "Status: " . $response['state'] . "\n";
    }

    // Example: Check Payment Status
    $statusResponse = $phonepe->getOrderStatus(
        orderId: 'ORDER123'
    );

    // Print status
    if ($statusResponse['success']) {
        echo "Payment Status: " . $statusResponse['data']['state'] . "\n";
        echo "Amount: " . $statusResponse['data']['amount'] . "\n";
    }

    // Example: Process Refund
    $refundResponse = $phonepe->initiateRefund(
        merchantRefundId: 'REFUND' . time(),
        originalMerchantOrderId: 'ORDER123',
        amount: 100 * 100
    );

    // Print refund status
    if ($refundResponse['success']) {
        echo "Refund Status: " . print_r($refundResponse['data'], true) . "\n";
    }

    // Example: Check Refund Status
    $refundStatusResponse = $phonepe->getRefundStatus(
        merchantRefundId: 'REFUND123'
    );

    // Print refund status details
    if ($refundStatusResponse['success']) {
        echo "Refund Status Details: " . print_r($refundStatusResponse['data'], true) . "\n";
    }
} catch (PhonePeApiException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
