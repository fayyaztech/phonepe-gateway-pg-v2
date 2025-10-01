<?php

/**
 * Payment Redirect Handler - index.php
 * This file handles the redirect from PhonePe payment gateway
 * and checks the order status automatically
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/PhonePgV2.php';

use Fayyaztech\PhonePgV2\PhonePgV2;
use PhonePe\Env;

// Configuration (same as payment_example.php)
$clientId = "TEST-M22DO0TV8T0O4_25062";
$clientVersion = 1;
$clientSecret = "YjNlYzIyODYtMjZkOS00OTM3LThmYWMtYWE4OTUyMjcxYzg2";
$env = Env::UAT;

// Get order ID from URL parameter
$orderIdFromUrl = $_GET['orderid'] ?? null;

if (!$orderIdFromUrl) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Payment Status</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .error { color: #d32f2f; }
            .success { color: #388e3c; }
            .pending { color: #f57c00; }
            .failed { color: #d32f2f; }
            .status-card { padding: 20px; border-radius: 6px; margin: 20px 0; }
            .error-card { background: #ffebee; border-left: 4px solid #d32f2f; }
            .success-card { background: #e8f5e8; border-left: 4px solid #388e3c; }
            .pending-card { background: #fff8e1; border-left: 4px solid #f57c00; }
            .failed-card { background: #ffebee; border-left: 4px solid #d32f2f; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>Payment Status</h1>
            <div class='status-card error-card'>
                <h2 class='error'>❌ Error</h2>
                <p>No order ID found in the URL. Please ensure you're accessing this page through the proper payment redirect.</p>
                <p><strong>Expected URL format:</strong> http://localhost:8080?orderid=ORDER_123456</p>
            </div>
        </div>
    </body>
    </html>";
    exit;
}

try {
    // Initialize PhonePgV2
    $phonePg = new PhonePgV2($clientId, $clientVersion, $clientSecret, $env);

    // Check order status
    $orderStatus = $phonePg->getSimpleOrderStatus($orderIdFromUrl);

    // Get detailed status for more information
    $detailedStatus = $phonePg->checkOrderStatus($orderIdFromUrl, true);
    $processedDetails = $phonePg->processOrderStatusResponse($detailedStatus);

    // Determine status display
    $statusClass = '';
    $statusIcon = '';
    $statusMessage = '';
    $cardClass = '';

    if ($orderStatus['isCompleted']) {
        $statusClass = 'success';
        $statusIcon = '✅';
        $statusMessage = 'Payment Completed Successfully!';
        $cardClass = 'success-card';
    } elseif ($orderStatus['isPending']) {
        $statusClass = 'pending';
        $statusIcon = '⏳';
        $statusMessage = 'Payment is Being Processed...';
        $cardClass = 'pending-card';
    } elseif ($orderStatus['isFailed']) {
        $statusClass = 'failed';
        $statusIcon = '❌';
        $statusMessage = 'Payment Failed';
        $cardClass = 'failed-card';
    } else {
        $statusClass = 'pending';
        $statusIcon = '❓';
        $statusMessage = 'Payment Status Unknown';
        $cardClass = 'pending-card';
    }

    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Payment Status - {$orderStatus['state']}</title>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .success { color: #388e3c; }
            .pending { color: #f57c00; }
            .failed { color: #d32f2f; }
            .status-card { padding: 20px; border-radius: 6px; margin: 20px 0; }
            .success-card { background: #e8f5e8; border-left: 4px solid #388e3c; }
            .pending-card { background: #fff8e1; border-left: 4px solid #f57c00; }
            .failed-card { background: #ffebee; border-left: 4px solid #d32f2f; }
            .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .info-table th, .info-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
            .info-table th { background-color: #f8f9fa; font-weight: bold; }
            .refresh-btn { background: #1976d2; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 10px 5px; }
            .refresh-btn:hover { background: #1565c0; }
            .back-btn { background: #666; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 10px 5px; }
            .back-btn:hover { background: #555; }
            .payment-details { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 15px 0; }
            .auto-refresh { font-size: 14px; color: #666; margin: 10px 0; }
        </style>";

    // Add auto-refresh for pending payments
    if ($orderStatus['isPending']) {
        echo "<meta http-equiv='refresh' content='10'>";
    }

    echo "</head>
    <body>
        <div class='container'>
            <h1>Payment Status</h1>
            
            <div class='status-card {$cardClass}'>
                <h2 class='{$statusClass}'>{$statusIcon} {$statusMessage}</h2>
                <p><strong>Order ID:</strong> {$orderIdFromUrl}</p>
                <p><strong>PhonePe Order ID:</strong> {$orderStatus['orderId']}</p>
                <p><strong>Status:</strong> {$orderStatus['state']}</p>
            </div>";

    if ($orderStatus['isPending']) {
        echo "<div class='auto-refresh'>
                <p><em>This page will automatically refresh every 10 seconds to check for payment updates...</em></p>
              </div>";
    }

    echo "<div class='payment-details'>
            <h3>Payment Details</h3>
            <table class='info-table'>
                <tr><th>Merchant Order ID</th><td>{$orderStatus['merchantOrderId']}</td></tr>
                <tr><th>PhonePe Order ID</th><td>{$orderStatus['orderId']}</td></tr>
                <tr><th>Transaction ID</th><td>" . ($orderStatus['transactionId'] ?? 'Not available') . "</td></tr>
                <tr><th>Amount</th><td>₹" . ($orderStatus['amount'] / 100) . "</td></tr>
                <tr><th>Status</th><td><strong class='{$statusClass}'>{$orderStatus['state']}</strong></td></tr>
                <tr><th>Expires At</th><td>" . (isset($processedDetails['expireAt']) ? date('Y-m-d H:i:s', $processedDetails['expireAt']) : 'Not available') . "</td></tr>
            </table>
          </div>";

    // Show error details if payment failed
    if ($orderStatus['isFailed'] && isset($processedDetails['errorCode'])) {
        echo "<div class='status-card failed-card'>
                <h3>Error Details</h3>
                <p><strong>Error Code:</strong> {$processedDetails['errorCode']}</p>";
        if (isset($processedDetails['detailedErrorCode'])) {
            echo "<p><strong>Detailed Error:</strong> {$processedDetails['detailedErrorCode']}</p>";
        }
        echo "</div>";
    }

    // Show payment attempts if available
    if (isset($processedDetails['paymentDetails']) && !empty($processedDetails['paymentDetails'])) {
        echo "<div class='payment-details'>
                <h3>Payment Attempts</h3>
                <table class='info-table'>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Payment Mode</th>
                        <th>Status</th>
                        <th>Timestamp</th>
                        <th>Amount</th>
                    </tr>";

        foreach ($processedDetails['paymentDetails'] as $detail) {
            $timestamp = isset($detail['timestamp']) ? date('Y-m-d H:i:s', $detail['timestamp'] / 1000) : 'N/A';
            $amount = isset($detail['amount']) ? '₹' . ($detail['amount'] / 100) : 'N/A';

            echo "<tr>
                    <td>" . ($detail['transactionId'] ?? 'N/A') . "</td>
                    <td>" . ($detail['paymentMode'] ?? 'N/A') . "</td>
                    <td>" . ($detail['state'] ?? 'N/A') . "</td>
                    <td>{$timestamp}</td>
                    <td>{$amount}</td>
                  </tr>";
        }

        echo "</table>
              </div>";
    }

    echo "<div style='margin-top: 30px; text-align: center;'>
            <button class='refresh-btn' onclick='window.location.reload()'>🔄 Refresh Status</button>
            <button class='back-btn' onclick='window.history.back()'>← Go Back</button>
          </div>
          
          <div style='margin-top: 20px; font-size: 12px; color: #666; text-align: center;'>
            <p>Last checked: " . date('Y-m-d H:i:s') . "</p>
          </div>
        </div>
        
        <script>
            // Show notification for completed payments
            " . ($orderStatus['isCompleted'] ? "
            if ('Notification' in window) {
                Notification.requestPermission().then(function(permission) {
                    if (permission === 'granted') {
                        new Notification('Payment Successful!', {
                            body: 'Your payment for order {$orderIdFromUrl} has been completed successfully.',
                            icon: '/favicon.ico'
                        });
                    }
                });
            }
            " : "") . "
        </script>
    </body>
    </html>";
} catch (\PhonePe\common\exceptions\PhonePeException $e) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Payment Status Error</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .error { color: #d32f2f; }
            .error-card { background: #ffebee; border-left: 4px solid #d32f2f; padding: 20px; border-radius: 6px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>Payment Status Check Error</h1>
            <div class='error-card'>
                <h2 class='error'>❌ PhonePe API Error</h2>
                <p><strong>Order ID:</strong> {$orderIdFromUrl}</p>
                <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                <p>Please try again in a few moments or contact support if the issue persists.</p>
            </div>
            <div style='margin-top: 20px; text-align: center;'>
                <button onclick='window.location.reload()' style='background: #1976d2; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>🔄 Try Again</button>
            </div>
        </div>
    </body>
    </html>";
} catch (Exception $e) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>System Error</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .error { color: #d32f2f; }
            .error-card { background: #ffebee; border-left: 4px solid #d32f2f; padding: 20px; border-radius: 6px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>System Error</h1>
            <div class='error-card'>
                <h2 class='error'>⚠️ System Error</h2>
                <p><strong>Order ID:</strong> {$orderIdFromUrl}</p>
                <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                <p>A system error occurred while checking the payment status. Please contact technical support.</p>
            </div>
        </div>
    </body>
    </html>";
}
