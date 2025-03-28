# PhonePe Payment Gateway PG V2

A PHP package for integrating PhonePe Payment Gateway PG V2 into your application.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/fayyaztech/phonepe-payment-gateway.svg?v=1)](https://packagist.org/packages/fayyaztech/phonepe-payment-gateway)
[![License](https://img.shields.io/github/license/fayyaztech/phonepe-payment-gateway)](https://github.com/fayyaztech/phonepe-payment-gateway/blob/main/LICENSE)

## Installation

You can install the package via composer:

```bash
composer require fayyaztech/phonepe-gateway-pg-v2
```

## Configuration

Set the following environment variables in your `.env` file:

```env
PHONEPE_MERCHANT_ID=YOUR_MERCHANT_ID
PHONEPE_CLIENT_SECRET=YOUR_CLIENT_SECRET
PHONEPE_CLIENT_ID=YOUR_CLIENT_ID
```

## Usage

### Basic Implementation

```php
use fayyaztech\PhonePeGatewayPGV2\PhonePe;

// Initialize PhonePe
$phonepe = new PhonePe(
    merchant_id: 'YOUR_MERCHANT_ID',
    client_secret: 'YOUR_CLIENT_SECRET',
    client_id: 'YOUR_CLIENT_ID',
    debug: true // Optional debug mode
);

// Initiate Payment
$response = $phonepe->initiatePayment(
    merchantOrderId: 'ORDER' . time(),
    amount: 100 * 100, // Amount in paise
    redirectUrl: 'https://your-domain.com/redirect',
    metaInfo: [
        'udf1' => 'custom-value-1'
    ],
    enabledPaymentModes: [
        ['type' => 'UPI_INTENT'],
        ['type' => 'CARD', 'cardTypes' => ['CREDIT_CARD']]
    ],
    mode: 'UAT' // Use 'PROD' for production
);

// Check Payment Status
$statusResponse = $phonepe->getOrderStatus(
    orderId: 'ORDER123',
    mode: 'UAT'
);

// Process Refund
$refundResponse = $phonepe->initiateRefund(
    merchantRefundId: 'REFUND' . time(),
    originalMerchantOrderId: 'ORDER123',
    amount: 100 * 100,
    mode: 'UAT'
);

// Check Refund Status
$refundStatusResponse = $phonepe->getRefundStatus(
    merchantRefundId: 'REFUND123',
    mode: 'UAT'
);
```

### Response Handling

```php
// Payment Response
if (isset($response['orderId'])) {
    $paymentUrl = $response['redirectUrl'];
    $orderId = $response['orderId'];
    $state = $response['state'];
    // Redirect user to payment URL
}

// Status Response
if ($statusResponse['success']) {
    $orderDetails = $statusResponse['data'];
    $state = $orderDetails['state'];
    $amount = $orderDetails['amount'];
    // Handle order status
}

// Refund Response
if ($refundResponse['success']) {
    $refundDetails = $refundResponse['data'];
    // Handle refund details
}
```

## Available Methods

- `initiatePayment()`: Start a new payment transaction
- `getOrderStatus()`: Check payment order status
- `initiateRefund()`: Process refund for a transaction
- `getRefundStatus()`: Check refund status

## Supported Payment Methods

```php
const PAYMENT_MODES = [
    ['type' => 'UPI_INTENT'],
    ['type' => 'UPI_COLLECT'],
    ['type' => 'UPI_QR'],
    ['type' => 'NET_BANKING'],
    ['type' => 'CARD', 'cardTypes' => ['DEBIT_CARD', 'CREDIT_CARD']]
];
```

## Testing

For testing, use the UAT mode with your sandbox credentials:

```php
$phonepe = new PhonePe(
    merchant_id: 'TEST_MERCHANT_ID',
    client_secret: 'TEST_CLIENT_SECRET',
    client_id: 'TEST_CLIENT_ID',
    debug: true
);
```

## Error Handling

```php
use fayyaztech\PhonePeGatewayPGV2\PhonePeApiException;

try {
    $response = $phonepe->initiatePayment(...);
} catch (PhonePeApiException $e) {
    echo $e->errorMessage();
}
```

## Security

If you discover any security-related issues, please email [fayyaztech@gmail.com](mailto:fayyaztech@gmail.com)

## Credits

- [fayyaztech](https://github.com/fayyaztech)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.