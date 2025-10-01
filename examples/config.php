<?php

/**
 * Configuration Example for PhonePgV2
 * This file shows different ways to configure the PhonePgV2 class
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/PhonePgV2.php';
require_once __DIR__ . '/../src/Environment.php';

use Fayyaztech\PhonePgV2\PhonePgV2;
use Fayyaztech\PhonePgV2\Environment;
use PhonePe\Env;

// Example configurations
echo "=== PhonePgV2 Configuration Examples ===\n\n";

// 1. Production Configuration
echo "1. Production Configuration:\n";
$prodConfig = [
    'clientId' => 'PROD_CLIENT_ID_HERE',
    'clientVersion' => 1,
    'clientSecret' => 'PROD_CLIENT_SECRET_HERE',
    'env' => Env::PRODUCTION
];

$prodPhonePg = new PhonePgV2(
    $prodConfig['clientId'],
    $prodConfig['clientVersion'],
    $prodConfig['clientSecret'],
    $prodConfig['env']
);

$prodEnv = new Environment(Environment::PRODUCTION);
echo "   Client ID: " . $prodConfig['clientId'] . "\n";
echo "   Environment: " . $prodEnv->getEnvironment() . "\n";
echo "   Base URL: " . $prodEnv->getBaseUrl() . "\n";
echo "   Is Production: " . ($prodEnv->isProduction() ? 'Yes' : 'No') . "\n\n";

// 2. Development/UAT Configuration
echo "2. Development/UAT Configuration:\n";
$devConfig = [
    'clientId' => 'UAT_CLIENT_ID_HERE',
    'clientVersion' => 1,
    'clientSecret' => 'UAT_CLIENT_SECRET_HERE',
    'env' => Env::UAT
];

$devPhonePg = new PhonePgV2(
    $devConfig['clientId'],
    $devConfig['clientVersion'],
    $devConfig['clientSecret'],
    $devConfig['env']
);

$devEnv = new Environment(Environment::DEVELOPMENT);
echo "   Client ID: " . $devConfig['clientId'] . "\n";
echo "   Environment: " . $devEnv->getEnvironment() . "\n";
echo "   Base URL: " . $devEnv->getBaseUrl() . "\n";
echo "   Is Production: " . ($devEnv->isProduction() ? 'Yes' : 'No') . "\n\n";

// 3. Configuration from Environment Variables (recommended)
echo "3. Configuration from Environment Variables:\n";
echo "   Set these environment variables:\n";
echo "   - PHONEPE_CLIENT_ID\n";
echo "   - PHONEPE_CLIENT_VERSION\n";
echo "   - PHONEPE_CLIENT_SECRET\n";
echo "   - PHONEPE_ENVIRONMENT (PRODUCTION or UAT)\n\n";

// Example using environment variables
$envClientId = getenv('PHONEPE_CLIENT_ID') ?: 'YOUR_CLIENT_ID';
$envClientVersion = (int)(getenv('PHONEPE_CLIENT_VERSION') ?: 1);
$envClientSecret = getenv('PHONEPE_CLIENT_SECRET') ?: 'YOUR_CLIENT_SECRET';
$envEnvironment = getenv('PHONEPE_ENVIRONMENT') === 'PRODUCTION' ? Env::PRODUCTION : Env::UAT;

echo "   Loaded Configuration:\n";
echo "   Client ID: " . $envClientId . "\n";
echo "   Client Version: " . $envClientVersion . "\n";
echo "   Environment: " . ($envEnvironment === Env::PRODUCTION ? 'PRODUCTION' : 'UAT') . "\n\n";

// 4. Configuration Class Example
class PhonePeConfig
{
    private $configs = [
        'production' => [
            'clientId' => 'PROD_CLIENT_ID',
            'clientVersion' => 1,
            'clientSecret' => 'PROD_CLIENT_SECRET',
            'env' => Env::PRODUCTION
        ],
        'uat' => [
            'clientId' => 'UAT_CLIENT_ID',
            'clientVersion' => 1,
            'clientSecret' => 'UAT_CLIENT_SECRET',
            'env' => Env::UAT
        ]
    ];

    public function getConfig($environment = 'uat')
    {
        return $this->configs[$environment] ?? $this->configs['uat'];
    }

    public function createPhonePgV2($environment = 'uat')
    {
        $config = $this->getConfig($environment);
        return new PhonePgV2(
            $config['clientId'],
            $config['clientVersion'],
            $config['clientSecret'],
            $config['env']
        );
    }
}

echo "4. Using Configuration Class:\n";
$configManager = new PhonePeConfig();
$uatPhonePg = $configManager->createPhonePgV2('uat');
$productionPhonePg = $configManager->createPhonePgV2('production');

echo "   UAT PhonePgV2 instance created\n";
echo "   Production PhonePgV2 instance created\n\n";

echo "Configuration examples completed!\n";
