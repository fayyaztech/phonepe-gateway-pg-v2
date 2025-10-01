<?php

/**
 * Test Runner for PhonePgV2 Examples
 * This file runs all example files to test the functionality
 */

echo "=== PhonePgV2 Test Runner ===\n\n";

$examples = [
    'basic_usage.php' => 'Basic Usage Test',
    'config.php' => 'Configuration Test',
    // Note: payment_example.php and refund_example.php require valid credentials
    // Uncomment these when you have valid PhonePe credentials
    // 'payment_example.php' => 'Payment Test',
    // 'refund_example.php' => 'Refund Test',
];

foreach ($examples as $file => $description) {
    echo "--- Running: $description ---\n";

    $filePath = __DIR__ . '/' . $file;

    if (file_exists($filePath)) {
        try {
            ob_start();
            include $filePath;
            $output = ob_get_clean();

            echo "✓ $description completed successfully\n";
            echo "Output:\n" . $output . "\n";
        } catch (Exception $e) {
            echo "✗ $description failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✗ File not found: $filePath\n";
    }

    echo "\n" . str_repeat('-', 50) . "\n\n";
}

echo "=== Test Runner Completed ===\n";
echo "\nNote: To test payment and refund functionality:\n";
echo "1. Update the credentials in payment_example.php and refund_example.php\n";
echo "2. Uncomment those files in the \$examples array above\n";
echo "3. Run this test runner again\n";
