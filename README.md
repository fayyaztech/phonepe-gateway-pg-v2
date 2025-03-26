# PhonePe Payment Gateway Integration

A PHP package for integrating PhonePe payment gateway into your application.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/fayyaztech/phonepe-payment-gateway.svg)](https://packagist.org/packages/fayyaztech/phonepe-payment-gateway)
[![License](https://img.shields.io/github/license/fayyaztech/phonepe-payment-gateway)](https://github.com/fayyaztech/phonepe-payment-gateway/blob/main/LICENSE)

## Installation

You can install the package via composer:

```bash
composer require fayyaztech/phonepe-payment-gateway
```

## Configuration

Set the following environment variables in your `.env` file:

```env
PHONEPE_MERCHANT_ID=YOUR_MERCHANT_ID
PHONEPE_SALT_KEY=YOUR_SALT_KEY
PHONEPE_SALT_INDEX=1
```

## Usage

### Basic Implementation

```php
use fayyaztech\phonePePaymentGateway\PhonePe;

// Initialize PhonePe
$phonepe = new PhonePe(
    merchant_id: 'YOUR_MERCHANT_ID',
    salt_key: 'YOUR_SALT_KEY',
    salt_index: 1
);

// Initiate Payment
$response = $phonepe->PaymentCall(
    merchantTransactionId: 'TXN' . time(),
    merchantUserId: 'USER123',
    amount: 100 * 100, // Amount in paise
    redirectUrl: 'https://your-domain.com/redirect',
    callbackUrl: 'https://your-domain.com/callback',
    mobileNumber: '9999999999',
    mode: 'UAT' // Use 'PROD' for production
);

// Check Payment Status
$statusResponse = $phonepe->PaymentStatus(
    merchantId: 'YOUR_MERCHANT_ID',
    merchantTransactionId: 'TXN123',
    mode: 'UAT'
);

// Process Refund
$refundResponse = $phonepe->PaymentRefund(
    merchantId: 'YOUR_MERCHANT_ID',
    refundtransactionId: 'REFUND' . time(),
    orderTransactionId: 'TXN123',
    callbackUrl: 'https://your-domain.com/refund-callback',
    amount: 100 * 100,
    mode: 'UAT'
);
```

### Response Handling

```php
// Payment Response
if ($response['responseCode'] === 200) {
    $paymentUrl = $response['url'];
    // Redirect user to payment URL
} else {
    $errorMessage = $response['msg'];
    // Handle error
}

// Status Response
if ($statusResponse['responseCode'] === 200) {
    $transactionStatus = $statusResponse['status'];
    // Handle transaction status
}

// Refund Response
if ($refundResponse['responseCode'] === 200) {
    $refundStatus = $refundResponse['state'];
    // Handle refund status
}
```

## Available Methods

- `PaymentCall()`: Initiate a payment transaction
- `PaymentStatus()`: Check payment status
- `PaymentRefund()`: Process refund for a transaction

## Testing

For testing, use the UAT mode and test credentials provided by PhonePe.

```php
$phonepe = new PhonePe(
    merchant_id: 'TEST_MERCHANT_ID',
    salt_key: 'TEST_SALT_KEY',
    salt_index: 1
);
```

## Error Handling

```php
use fayyaztech\phonePePaymentGateway\PhonePeApiException;

try {
    $response = $phonepe->PaymentCall(...);
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