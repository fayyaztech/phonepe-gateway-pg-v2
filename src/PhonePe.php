<?php

/**
 * PhonePe Payment Gateway PG V2 Integration
 * 
 * @author fayyaztech
 * @package fayyaztech\PhonePeGatewayPGV2
 */

namespace fayyaztech\PhonePeGatewayPGV2;

use Exception;
use fayyaztech\phonePePaymentGateway\PhonePeApiException;

class PhonePe
{
    // API endpoint URLs for different services
    private const PROD_AUTH_URL = 'https://api.phonepe.com/apis/identity-manager/v1/oauth/token';
    private const PROD_CHECKOUT_URL = 'https://api.phonepe.com/apis/pg/checkout/v2/';
    private const PROD_PAYMENTS_URL = 'https://api.phonepe.com/apis/pg/payments/v2/';

    private const UAT_AUTH_URL = 'https://api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token';
    private const UAT_CHECKOUT_URL = 'https://api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/';
    private const UAT_PAYMENTS_URL = 'https://api-preprod.phonepe.com/apis/pg-sandbox/payments/v2/';

    // Fixed values
    private const CLIENT_VERSION = '1';
    private const GRANT_TYPE = 'client_credentials';

    // Payment modes
    private const MODE_UAT = 'UAT';  // Testing/Sandbox mode
    private const MODE_PROD = 'PROD'; // Production mode

    /**
     * @var array Default payment modes if none specified
     */
    private const DEFAULT_PAYMENT_MODES = [
        ['type' => 'UPI_INTENT'],
        ['type' => 'UPI_COLLECT'],
        ['type' => 'UPI_QR'],
        ['type' => 'NET_BANKING'],
        ['type' => 'CARD', 'cardTypes' => ['DEBIT_CARD', 'CREDIT_CARD']]
    ];

    /**
     * @var string Client secret used for request signature
     */
    protected string $client_secret;

    /**
     * @var string Client ID assigned by PhonePe
     */
    protected string $client_id;

    /**
     * @var string Access token for API calls
     */
    private string $access_token = '';

    /**
     * @var string Encrypted version of the access token
     */
    private string $encrypted_access_token = '';

    /**
     * @var string Refresh token for getting new access token
     */
    private string $refresh_token = '';

    /**
     * @var string Token type (usually O-Bearer)
     */
    private string $token_type = '';

    /**
     * @var int Token expiry timestamp
     */
    private int $token_expires_at = 0;

    /**
     * @var bool Enable request/response logging
     */
    private bool $debug = false;

    public function __construct(
        ?string $client_id = null,
        ?string $client_secret = null,
        bool $debug = false
    ) {
        $this->client_id = $client_id ?? getenv('PHONEPE_CLIENT_ID');
        $this->client_secret = $client_secret ?? getenv('PHONEPE_CLIENT_SECRET');
        $this->debug = $debug;

        if (empty($this->client_id)) throw new PhonePeApiException("Client ID is required");
        if (empty($this->client_secret)) throw new PhonePeApiException("Client Secret is required");

        // Initialize token on construction
        try {
            $this->getAccessToken();
        } catch (Exception $e) {
            throw new PhonePeApiException("Failed to initialize token: " . $e->getMessage());
        }
    }

    /**
     * Gets or refreshes the access token
     * 
     * @param string|null $mode Payment mode (UAT/PROD)
     * @return string Valid access token
     * @throws PhonePeApiException When token request fails
     */
    private function getAccessToken(?string $mode = null): string
    {
        // Return existing token if still valid and not empty
        if (!empty($this->access_token) && time() < $this->token_expires_at) {
            return $this->access_token;
        }

        $postFields = http_build_query([
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'client_version' => self::CLIENT_VERSION,
            'grant_type' => self::GRANT_TYPE
        ]);

        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ];

        try {
            $response = $this->makeApiCall(
                $mode == 'UAT' ? self::UAT_AUTH_URL : self::PROD_AUTH_URL,
                $headers,
                $postFields,
                'POST',
                true
            );

            if (!isset($response->access_token)) {
                throw new PhonePeApiException("Invalid token response");
            }

            // Store all token information
            $this->access_token = $response->access_token;
            $this->encrypted_access_token = $response->encrypted_access_token;
            $this->refresh_token = $response->refresh_token;
            $this->token_type = $response->token_type;

            // Calculate expiry time
            $this->token_expires_at = $response->expires_at ??
                (time() + ($response->expires_in ?? 3600));

            return $this->access_token;
        } catch (Exception $e) {
            throw new PhonePeApiException("Failed to get access token: " . $e->getMessage());
        }
    }

    /**
     * Initiates a payment transaction through PhonePe gateway v2
     * 
     * @param string $merchantOrderId Unique order identifier
     * @param int $amount Amount in lowest denomination (paisa for INR)
     * @param string $redirectUrl URL where customer will be redirected after payment
     * @param array $metaInfo Optional additional information
     * @param array $enabledPaymentModes Optional array of enabled payment modes
     * @param int $expireAfter Time in seconds after which payment link expires (default 1200)
     * @param string|null $mode Payment mode (UAT/PROD)
     * @return array{orderId: string, state: string, redirectUrl: string} Payment response
     * @throws PhonePeApiException When API call fails
     */
    public function initiatePayment(
        string $merchantOrderId,
        int $amount,
        string $redirectUrl,
        array $metaInfo = [],
        array $enabledPaymentModes = [],
        int $expireAfter = 1200,
        ?string $mode = null
    ): array {
        $this->validateAmount($amount);

        $payload = [
            "merchantOrderId" => $merchantOrderId,
            "amount" => $amount,
            "expireAfter" => $expireAfter,
            "metaInfo" => $metaInfo ?: [
                "udf1" => "",
                "udf2" => "",
                "udf3" => "",
                "udf4" => "",
                "udf5" => ""
            ],
            "paymentFlow" => [
                "type" => "PG_CHECKOUT",
                "message" => "Payment for order {$merchantOrderId}",
                "merchantUrls" => [
                    "redirectUrl" => $redirectUrl
                ],
                "paymentModeConfig" => [
                    "enabledPaymentModes" => $enabledPaymentModes ?: self::DEFAULT_PAYMENT_MODES
                ]
            ]
        ];

        $baseUrl = $mode == 'UAT' ? self::UAT_CHECKOUT_URL : self::PROD_CHECKOUT_URL;
        $url = $baseUrl . 'pay';

        try {
            $res = $this->makeApiCall($url, [], $payload, 'POST');

            if (!isset($res->orderId)) {
                throw new PhonePeApiException("Invalid payment response");
            }

            return [
                'orderId' => $res->orderId,
                'state' => $res->state,
                'redirectUrl' => $res->redirectUrl,
                'expireAt' => $res->expireAt
            ];
        } catch (Exception $e) {
            throw new PhonePeApiException("Payment initiation failed: " . $e->getMessage());
        }
    }

    /**
     * Check the status of a payment order
     * 
     * @param string $orderId PhonePe Order ID to check
     * @param string|null $mode Payment mode (UAT/PROD)
     * @return array{
     *     success: bool,
     *     data?: array{
     *         orderId: string,
     *         state: string,
     *         amount: int,
     *         expireAt: int,
     *         metaInfo: array,
     *         paymentDetails: array
     *     },
     *     error?: string
     * }
     * @throws PhonePeApiException When API call fails
     */
    public function getOrderStatus(string $orderId, ?string $mode = null): array
    {
        $baseUrl = $mode == 'UAT' ? self::UAT_CHECKOUT_URL : self::PROD_CHECKOUT_URL;
        $url = $baseUrl . "order/{$orderId}/status";

        try {
            $res = $this->makeApiCall($url, [], null, 'GET');

            return [
                'success' => true,
                'data' => [
                    'orderId' => $res->orderId,
                    'state' => $res->state,
                    'amount' => $res->amount,
                    'expireAt' => $res->expireAt,
                    'metaInfo' => (array) ($res->metaInfo ?? []),
                    'paymentDetails' => (array) ($res->paymentDetails ?? [])
                ]
            ];
        } catch (PhonePeApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process a refund for a completed transaction using v2 API
     * 
     * @param string $merchantRefundId Unique ID for this refund
     * @param string $originalMerchantOrderId Original order ID to refund
     * @param int $amount Amount to refund in lowest denomination
     * @param string|null $mode Payment mode (UAT/PROD)
     * @return array Refund response
     * @throws PhonePeApiException When API call fails
     */
    public function initiateRefund(
        string $merchantRefundId,
        string $originalMerchantOrderId,
        int $amount,
        ?string $mode = null
    ): array {
        $this->validateAmount($amount);

        $payload = [
            "merchantRefundId" => $merchantRefundId,
            "originalMerchantOrderId" => $originalMerchantOrderId,
            "amount" => $amount
        ];

        $baseUrl = $mode == 'UAT' ? self::UAT_PAYMENTS_URL : self::PROD_PAYMENTS_URL;
        $url = $baseUrl . 'refund';

        try {
            $res = $this->makeApiCall($url, [], $payload, 'POST');

            return [
                'success' => true,
                'data' => $res
            ];
        } catch (PhonePeApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check the status of a refund
     * 
     * @param string $merchantRefundId The refund ID to check status for
     * @param string|null $mode Payment mode (UAT/PROD)
     * @return array Status response with refund details
     * @throws PhonePeApiException When API call fails
     */
    public function getRefundStatus(string $merchantRefundId, ?string $mode = null): array
    {
        $baseUrl = $mode == 'UAT' ? self::UAT_PAYMENTS_URL : self::PROD_PAYMENTS_URL;
        $url = $baseUrl . "refund/{$merchantRefundId}/status";

        try {
            $res = $this->makeApiCall($url, [], null, 'GET');

            return [
                'success' => true,
                'data' => $res
            ];
        } catch (PhonePeApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validates payment amount
     * 
     * @param int $amount Amount to validate
     * @throws PhonePeApiException If amount is invalid
     */
    private function validateAmount(int $amount): void
    {
        if ($amount <= 0) {
            throw new PhonePeApiException('Amount must be greater than 0');
        }
    }

    /**
     * Makes an HTTP request to PhonePe API
     * 
     * @param string $url API endpoint URL
     * @param array $headers Request headers
     * @param string|array|null $postData POST data (optional)
     * @param string $method HTTP method (GET, POST, etc.)
     * @param bool $isAuthCall Whether this is an auth token request
     * @return object Response from API
     * @throws PhonePeApiException When API call fails
     */
    private function makeApiCall(
        string $url,
        array $headers,
        $postData = null,
        string $method = 'GET',
        bool $isAuthCall = false
    ): object {
        if (!$isAuthCall && $this->access_token) {
            $headers[] = 'Authorization: ' . $this->token_type . ' ' . $this->getAccessToken();
        }

        if (
            !in_array('Content-Type: application/json', $headers) &&
            !in_array('Content-Type: application/x-www-form-urlencoded', $headers)
        ) {
            $headers[] = 'Content-Type: application/json';
        }

        if (!in_array('Accept: application/json', $headers)) {
            $headers[] = 'Accept: application/json';
        }

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers
        ];

        if ($postData !== null) {
            $options[CURLOPT_POSTFIELDS] = is_array($postData) ?
                json_encode($postData) : $postData;
        }

        if ($this->debug) {
            error_log("PhonePe API Request: $url");
            error_log("Headers: " . print_r($headers, true));
            error_log("Payload: " . print_r($postData, true));
        }

        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if ($this->debug) {
            error_log("Response Code: $httpCode");
            error_log("Response: $response");
            if ($err) error_log("Error: $err");
        }

        if ($err) {
            throw new PhonePeApiException("API Call Failed: {$err}");
        }

        $decoded = json_decode($response);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new PhonePeApiException("Invalid JSON response");
        }

        if ($httpCode >= 400) {
            throw new PhonePeApiException(
                "API request failed with code $httpCode: " .
                    ($decoded->message ?? 'Unknown error')
            );
        }

        return $decoded;
    }
}
