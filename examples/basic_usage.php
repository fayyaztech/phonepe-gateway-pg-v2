<?php

/**
 * Basic Usage Example for PhonePgV2
 * This file demonstrates how to initialize and use the PhonePgV2 class
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/PhonePgV2.php';
require_once __DIR__ . '/../src/Environment.php';

use Fayyaztech\PhonePgV2\PhonePgV2;
use Fayyaztech\PhonePgV2\Environment;
use PhonePe\Env;

// Configuration
$clientId = "YOUR_CLIENT_ID"; // Replace with your Client ID
$clientVersion = 1; // Replace with your Client Version
$clientSecret = "YOUR_CLIENT_SECRET"; // Replace with your Client Secret
$env = Env::PRODUCTION; // Use Env::PRODUCTION for live environment or Env::UAT for testing

try {
    // Initialize PhonePgV2
    $phonePg = new PhonePgV2($clientId, $clientVersion, $clientSecret, $env);

    // Initialize Environment (optional - for custom environment handling)
    $environment = new Environment(Environment::PRODUCTION);

    echo "PhonePgV2 initialized successfully!\n";
    echo "Environment: " . $environment->getEnvironment() . "\n";
    echo "Base URL: " . $environment->getBaseUrl() . "\n";
    echo "Is Production: " . ($environment->isProduction() ? 'Yes' : 'No') . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
