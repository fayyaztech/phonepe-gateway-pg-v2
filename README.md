# PhonePe Payment Gateway PG V2

A PHP package for integrating PhonePe Payment Gateway V2 API. This package provides a clean interface for payments, refunds, and status checks using PhonePe's latest PG V2 endpoints.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/fayyaztech/phonepe-gateway-pg-v2.svg)](https://packagist.org/packages/fayyaztech/phonepe-gateway-pg-v2)
[![License](https://img.shields.io/github/license/fayyaztech/phonepe-gateway-pg-v2)](https://github.com/fayyaztech/phonepe-gateway-pg-v2/blob/main/LICENSE)

## Requirements

- PHP 7.4 or higher
- `ext-curl`
- `ext-json`

## Installation

```bash
composer require fayyaztech/phonepe-gateway-pg-v2
```

## Configuration

Set your credentials in `.env` file or pass them directly:

```env
PHONEPE_CLIENT_ID=YOUR_CLIENT_ID
PHONEPE_CLIENT_SECRET=YOUR_CLIENT_SECRET
```

## Quick Start

```php
use fayyaztech\PhonePeGatewayPGV2\PhonePe;

// Initialize with environment (UAT/PROD)
$phonepe = new PhonePe(
    client_id: 'YOUR_CLIENT_ID',
    client_secret: 'YOUR_CLIENT_SECRET',
    mode: PhonePe::MODE_UAT,  // Use MODE_PROD for production
    debug: true
);

// Initiate Payment
$response = $phonepe->initiatePayment(
    merchantOrderId: 'ORDER' . time(),
    amount: 100 * 100,  // Amount in paise
    redirectUrl: 'https://your-domain.com/redirect'
);

// Get payment URL
if (isset($response['orderId'])) {
    $paymentUrl = $response['redirectUrl'];
    // Redirect user to $paymentUrl
}
```

## Available Methods

### Payment Flow

```php
// Initiate Payment
$response = $phonepe->initiatePayment(
    merchantOrderId: 'ORDER123',
    amount: 1000,
    redirectUrl: 'https://your-domain.com/redirect',
    metaInfo: ['udf1' => 'value1'],
    enabledPaymentModes: [
        ['type' => 'UPI_INTENT']
    ]
);

// Check Payment Status
$status = $phonepe->getOrderStatus('ORDER123');

// Process Refund
$refund = $phonepe->initiateRefund(
    merchantRefundId: 'REFUND123',
    originalMerchantOrderId: 'ORDER123',
    amount: 1000
);

// Check Refund Status
$refundStatus = $phonepe->getRefundStatus('REFUND123');
```

## Payment Modes

Available payment modes that can be enabled:

```php
[
    ['type' => 'UPI_INTENT'],
    ['type' => 'UPI_COLLECT'],
    ['type' => 'UPI_QR'],
    ['type' => 'NET_BANKING'],
    ['type' => 'CARD', 'cardTypes' => ['DEBIT_CARD', 'CREDIT_CARD']]
]
```

## Error Handling

```php
use fayyaztech\PhonePeGatewayPGV2\PhonePeApiException;

try {
    $response = $phonepe->initiatePayment(...);
} catch (PhonePeApiException $e) {
    echo $e->getMessage();
}
```

## Debug Mode

Enable debug mode to log API requests and responses:

```php
$phonepe = new PhonePe(
    client_id: 'YOUR_CLIENT_ID',
    client_secret: 'YOUR_CLIENT_SECRET',
    mode: PhonePe::MODE_UAT,
    debug: true
);
```

## Testing

Use UAT mode for testing:

```php
$phonepe = new PhonePe(
    client_id: 'TEST_CLIENT_ID',
    client_secret: 'TEST_CLIENT_SECRET',
    mode: PhonePe::MODE_UAT
);
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email fayyaztech@gmail.com.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.