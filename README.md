# PhonePe Gateway V2 - PHP SDK

A comprehensive PHP SDK for integrating PhonePe Payment Gateway V2 with your applications. This package provides a simplified interface for payment initiation, status checking, refunds, and callback handling.

## 📦 Installation

### Via Composer (Recommended)

```bash
composer require fayyaztech/phonepe-gateway-v2
```

### Manual Installation

1. Clone the repository:
```bash
git clone https://github.com/fayyaztech/phonepe-gateway-pg-v2.git
cd phonepe-gateway-pg-v2
```

2. Install dependencies:
```bash
composer install
```

## 🚀 Quick Start

### Basic Setup

```php
<?php

require_once 'vendor/autoload.php';

use Fayyaztech\PhonePgV2\PhonePgV2;
use Fayyaztech\PhonePgV2\Environment;
use PhonePe\Env;

// Configuration
$clientId = "YOUR_CLIENT_ID";
$clientVersion = 1;
$clientSecret = "YOUR_CLIENT_SECRET";
$env = Env::UAT; // Use Env::PRODUCTION for live

// Initialize PhonePgV2
$phonePg = new PhonePgV2($clientId, $clientVersion, $clientSecret, $env);
```

### Environment Configuration

```php
// Using built-in Environment class
$environment = new Environment(Environment::PRODUCTION); // or Environment::DEVELOPMENT

echo "Environment: " . $environment->getEnvironment();
echo "Base URL: " . $environment->getBaseUrl();
echo "Is Production: " . ($environment->isProduction() ? 'Yes' : 'No');
```

## 💳 Payment Integration

### 1. Initiate Payment

```php
try {
    $merchantOrderId = "ORDER_" . time();
    $amount = 1000; // Amount in paisa (1000 = ₹10.00)
    $redirectUrl = "https://yoursite.com/payment-status?orderid=" . $merchantOrderId;
    $message = "Payment for Order #" . $merchantOrderId;
    
    // Optional meta information
    $metaInfo = [
        'udf1' => 'Customer ID: 12345',
        'udf2' => 'Product: Electronics',
        'udf3' => 'Category: Mobile'
    ];
    
    // Initiate payment
    $payResponse = $phonePg->initiatePayment(
        $merchantOrderId,
        $amount,
        $redirectUrl,
        $message,
        $metaInfo
    );
    
    // Process response
    $responseData = $phonePg->processPaymentResponse($payResponse, false);
    
    if ($responseData['state'] === 'PENDING') {
        // Redirect user to PhonePe payment page
        header("Location: " . $responseData['redirectUrl']);
        exit();
    }
    
} catch (\PhonePe\common\exceptions\PhonePeException $e) {
    echo "Payment Error: " . $e->getMessage();
}
```

### 2. Check Payment Status

```php
try {
    // Simple status check
    $status = $phonePg->getSimpleOrderStatus($merchantOrderId);
    
    if ($status['isCompleted']) {
        echo "Payment completed successfully!";
    } elseif ($status['isPending']) {
        echo "Payment is pending...";
    } elseif ($status['isFailed']) {
        echo "Payment failed.";
    }
    
    // Detailed status check
    $detailedStatus = $phonePg->checkOrderStatus($merchantOrderId, true);
    $processedStatus = $phonePg->processOrderStatusResponse($detailedStatus);
    
} catch (\PhonePe\common\exceptions\PhonePeException $e) {
    echo "Status Check Error: " . $e->getMessage();
}
```

### 3. Handle Payment Callback

Create a callback handler to process payment redirects:

```php
<?php
// payment-status.php

require_once 'vendor/autoload.php';
use Fayyaztech\PhonePgV2\PhonePgV2;

$orderIdFromUrl = $_GET['orderid'] ?? null;

if ($orderIdFromUrl) {
    $phonePg = new PhonePgV2($clientId, $clientVersion, $clientSecret, $env);
    
    try {
        $status = $phonePg->getSimpleOrderStatus($orderIdFromUrl);
        
        // Display status to user
        if ($status['isCompleted']) {
            echo "<h1>Payment Successful!</h1>";
            echo "<p>Order ID: " . $status['merchantOrderId'] . "</p>";
            echo "<p>Amount: ₹" . ($status['amount'] / 100) . "</p>";
        } else {
            echo "<h1>Payment " . $status['state'] . "</h1>";
        }
        
    } catch (Exception $e) {
        echo "<h1>Error checking payment status</h1>";
    }
}
?>
```

## 💰 Refund Integration

### 1. Initiate Refund

```php
try {
    $originalOrderId = "ORDER_123456789";
    $refundAmount = 500; // Amount in paisa
    
    // Complete refund workflow (recommended)
    $refundResult = $phonePg->processRefund($originalOrderId, $refundAmount);
    
    if ($refundResult['isSuccessful']) {
        echo "Refund completed successfully!";
    } elseif ($refundResult['isPending']) {
        echo "Refund is being processed...";
    }
    
    // Manual refund initiation (for more control)
    $merchantRefundId = "REFUND_" . time();
    $refundResponse = $phonePg->initiateRefund(
        $merchantRefundId,
        $originalOrderId,
        $refundAmount
    );
    
} catch (\PhonePe\common\exceptions\PhonePeException $e) {
    echo "Refund Error: " . $e->getMessage();
}
```

### 2. Check Refund Status

```php
try {
    // Simple refund status
    $refundStatus = $phonePg->getSimpleRefundStatus($merchantRefundId);
    
    echo "Refund Status: " . $refundStatus['state'];
    echo "Is Completed: " . ($refundStatus['isCompleted'] ? 'Yes' : 'No');
    
    // Detailed refund status
    $detailedRefundStatus = $phonePg->checkRefundStatus($merchantRefundId);
    $processedRefundStatus = $phonePg->processRefundStatusResponse($detailedRefundStatus);
    
} catch (\PhonePe\common\exceptions\PhonePeException $e) {
    echo "Refund Status Error: " . $e->getMessage();
}
```

## 🔧 Configuration Options

### Environment Variables (Recommended)

Set these environment variables for secure configuration:

```bash
export PHONEPE_CLIENT_ID="your_client_id"
export PHONEPE_CLIENT_VERSION="1"
export PHONEPE_CLIENT_SECRET="your_client_secret"
export PHONEPE_ENVIRONMENT="UAT" # or "PRODUCTION"
```

### Configuration Class

```php
class PhonePeConfig
{
    public static function getConfig($env = 'uat')
    {
        $configs = [
            'production' => [
                'clientId' => getenv('PHONEPE_CLIENT_ID'),
                'clientVersion' => (int)getenv('PHONEPE_CLIENT_VERSION'),
                'clientSecret' => getenv('PHONEPE_CLIENT_SECRET'),
                'env' => \PhonePe\Env::PRODUCTION
            ],
            'uat' => [
                'clientId' => getenv('PHONEPE_CLIENT_ID'),
                'clientVersion' => (int)getenv('PHONEPE_CLIENT_VERSION'),
                'clientSecret' => getenv('PHONEPE_CLIENT_SECRET'),
                'env' => \PhonePe\Env::UAT
            ]
        ];
        
        return $configs[$env] ?? $configs['uat'];
    }
}

// Usage
$config = PhonePeConfig::getConfig('production');
$phonePg = new PhonePgV2(
    $config['clientId'],
    $config['clientVersion'],
    $config['clientSecret'],
    $config['env']
);
```

## 📋 API Reference

### PhonePgV2 Class Methods

#### Payment Methods
- `initiatePayment($merchantOrderId, $amount, $redirectUrl, $message, $metaInfo)` - Initiate a payment
- `processPaymentResponse($payResponse, $autoRedirect)` - Process payment response
- `checkOrderStatus($merchantOrderId, $details)` - Check order status
- `processOrderStatusResponse($statusResponse)` - Process status response
- `getSimpleOrderStatus($merchantOrderId)` - Get simplified order status

#### Refund Methods
- `initiateRefund($merchantRefundId, $originalOrderId, $amount)` - Initiate refund
- `processRefundResponse($refundResponse)` - Process refund response
- `checkRefundStatus($merchantRefundId)` - Check refund status
- `processRefundStatusResponse($refundStatusResponse)` - Process refund status response
- `getSimpleRefundStatus($merchantRefundId)` - Get simplified refund status
- `processRefund($originalOrderId, $amount, $merchantRefundId)` - Complete refund workflow

#### Environment Class Methods
- `getEnvironment()` - Get current environment
- `getBaseUrl()` - Get base URL for environment
- `isProduction()` - Check if production environment

## 🧪 Testing

### Run Examples

```bash
# Basic usage (no API calls)
php examples/basic_usage.php

# Configuration examples
php examples/config.php

# Payment workflow (requires valid credentials)
php examples/payment_example.php

# Refund workflow (requires valid credentials)
php examples/refund_example.php

# Run all tests
php examples/test_runner.php
```

### Local Testing Server

```bash
cd examples
php -S localhost:8080
```

Then visit: `http://localhost:8080?orderid=ORDER_123456`

## 📊 Response Formats

### Payment Response
```php
[
    'state' => 'PENDING',
    'redirectUrl' => 'https://mercury-uat.phonepe.com/transact/...',
    'orderId' => 'OMO2510011514481372331018',
    'expireAt' => 1759335288137,
    'merchantOrderId' => 'ORDER_123456',
    'transactionId' => 'T2510011514481372331018',
    'amount' => 1000,
    'isPending' => true,
    'isCompleted' => false,
    'isFailed' => false
]
```

### Refund Response
```php
[
    'refundId' => 'R2510011514481372331018',
    'state' => 'COMPLETED',
    'amount' => 500,
    'merchantRefundId' => 'REFUND_123456',
    'originalMerchantOrderId' => 'ORDER_123456',
    'isSuccessful' => true,
    'isPending' => false,
    'isFailed' => false
]
```

## 🔒 Security Best Practices

### 1. Environment Variables
Never hardcode credentials in your source code:

```php
// ❌ Bad
$clientSecret = "YjNlYzIyODYtMjZkOS00OTM3LThmYWMtYWE4OTUyMjcxYzg2";

// ✅ Good
$clientSecret = getenv('PHONEPE_CLIENT_SECRET');
```

### 2. HTTPS Only
Always use HTTPS for production:

```php
// ✅ Ensure HTTPS redirect URLs
$redirectUrl = "https://yoursite.com/payment-status?orderid=" . $merchantOrderId;
```

### 3. Validate Callbacks
Always verify payment status on your server:

```php
// Don't trust client-side status, always verify
$verifiedStatus = $phonePg->getSimpleOrderStatus($orderIdFromCallback);
```

## 🐛 Error Handling

### Common Exceptions

```php
try {
    $payResponse = $phonePg->initiatePayment($orderId, $amount, $redirectUrl);
} catch (\PhonePe\common\exceptions\PhonePeException $e) {
    // PhonePe API specific errors
    error_log("PhonePe Error: " . $e->getMessage());
} catch (\Exception $e) {
    // General errors
    error_log("General Error: " . $e->getMessage());
}
```

### Error Codes
- `BAD_REQUEST` - Invalid request parameters
- `AUTHORIZATION_FAILED` - Invalid credentials
- `INTERNAL_SERVER_ERROR` - PhonePe server error
- `TRANSACTION_NOT_FOUND` - Order not found

## 📝 Changelog

### Version 1.0.0
- Initial release
- Payment initiation and status checking
- Refund initiation and status checking
- Environment configuration
- Comprehensive error handling
- Example implementations

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

- **Documentation**: Check the `/examples` folder for detailed examples
- **Issues**: Report bugs on [GitHub Issues](https://github.com/fayyaztech/phonepe-gateway-pg-v2/issues)
- **Email**: Support via repository issues only

## 🙏 Credits

- **PhonePe**: For providing the payment gateway APIs
- **Contributors**: All contributors to this project

---

**⚠️ Important Notes:**
- Always test with UAT environment before going live
- Keep your credentials secure and never commit them to version control
- Verify payment status server-side for security
- Follow PhonePe's integration guidelines and terms of service