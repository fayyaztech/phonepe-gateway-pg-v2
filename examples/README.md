# PhonePgV2 Examples

This folder contains example files to demonstrate how to use the PhonePgV2 class for PhonePe payment gateway integration.

## Files Overview

### 1. `basic_usage.php`
- **Purpose**: Demonstrates basic initialization of PhonePgV2 and Environment classes
- **Usage**: Shows how to set up the classes with configuration
- **Dependencies**: No external API calls, safe to run without credentials

### 2. `config.php`
- **Purpose**: Shows different ways to configure PhonePgV2
- **Features**: 
  - Production vs Development configuration
  - Environment variables usage
  - Configuration management class
- **Usage**: Safe to run without credentials

### 3. `payment_example.php`
- **Purpose**: Complete payment workflow demonstration
- **Features**:
  - Payment initiation
  - Payment status checking
  - Response processing
- **Requirements**: Valid PhonePe credentials needed
- **⚠️ Warning**: Will make actual API calls

### 4. `refund_example.php`
- **Purpose**: Complete refund workflow demonstration
- **Features**:
  - Refund initiation (manual and automatic)
  - Refund status checking
  - Detailed refund information
- **Requirements**: Valid PhonePe credentials and existing order ID
- **⚠️ Warning**: Will make actual API calls

### 5. `test_runner.php`
- **Purpose**: Runs all example files for testing
- **Features**:
  - Automated testing of all examples
  - Error handling and reporting
  - Safe execution (skips API-dependent examples by default)

## How to Use

### Prerequisites
1. Install dependencies: `composer install`
2. Ensure your PhonePe credentials are available
3. Update credentials in the example files

### Configuration Required
Replace these placeholders in the example files:
```php
$clientId = "YOUR_CLIENT_ID"; // Your actual PhonePe Client ID
$clientVersion = 1; // Your actual Client Version
$clientSecret = "YOUR_CLIENT_SECRET"; // Your actual Client Secret
$env = Env::UAT; // Use UAT for testing, PRODUCTION for live
```

### Running Examples

#### Safe Examples (No API calls):
```bash
# Basic usage
php examples/basic_usage.php

# Configuration examples
php examples/config.php

# Run all safe tests
php examples/test_runner.php
```

#### API Examples (Require valid credentials):
```bash
# Payment example (requires valid credentials)
php examples/payment_example.php

# Refund example (requires valid credentials + existing order)
php examples/refund_example.php
```

## Environment Setup

### Option 1: Direct Configuration
Update the example files with your credentials directly.

### Option 2: Environment Variables (Recommended)
Set these environment variables:
```bash
export PHONEPE_CLIENT_ID="your_client_id"
export PHONEPE_CLIENT_VERSION="1"
export PHONEPE_CLIENT_SECRET="your_client_secret"
export PHONEPE_ENVIRONMENT="UAT" # or "PRODUCTION"
```

## Testing Workflow

1. **Start with safe examples**:
   ```bash
   php examples/basic_usage.php
   php examples/config.php
   ```

2. **Set up credentials** in the files or environment variables

3. **Test payment workflow**:
   ```bash
   php examples/payment_example.php
   ```

4. **Test refund workflow** (use an existing order ID):
   ```bash
   php examples/refund_example.php
   ```

5. **Run comprehensive tests**:
   ```bash
   php examples/test_runner.php
   ```

## Important Notes

- **UAT vs Production**: Always test with UAT environment first
- **Credentials Security**: Never commit real credentials to version control
- **Error Handling**: All examples include proper exception handling
- **API Limits**: Be mindful of API rate limits during testing

## Example Output

When running the examples, you'll see structured output showing:
- Configuration details
- API responses
- Success/failure indicators
- Detailed transaction information

This helps you understand the complete flow and debug any issues.