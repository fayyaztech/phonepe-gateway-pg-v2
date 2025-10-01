<?php

namespace Fayyaztech\PhonePgV2;

require_once __DIR__ . '/../vendor/autoload.php'; // Include Composer autoloader

use PhonePe\Env;
use PhonePe\payments\v2\models\request\builders\StandardCheckoutPayRequestBuilder;
use PhonePe\payments\v2\models\request\builders\StandardCheckoutRefundRequestBuilder;
use PhonePe\payments\v2\standardCheckout\StandardCheckoutClient;

class PhonePgV2
{
    private $clientId;
    private $clientVersion;
    private $clientSecret;
    private $env;

    /**
     * Constructor function
     * 
     * @param string $clientId Replace with your Client ID
     * @param int $clientVersion Replace with your Client Version
     * @param string $clientSecret Replace with your Client Secret
     * @param string $env Use Env::PRODUCTION for live environment
     */
    public function __construct($clientId, $clientVersion, $clientSecret, $env)
    {
        $this->clientId = $clientId;
        $this->clientVersion = $clientVersion;
        $this->clientSecret = $clientSecret;
        $this->env = $env;
    }

    /**
     * Initiate Payment with PhonePe SDK
     * 
     * @param string $merchantOrderId Unique order ID assigned by you (Max length: 63 characters, no special characters except "_" and "-")
     * @param int $amount Order amount in paisa (Minimum value: 100)
     * @param string $redirectUrl URL to which the user will be redirected after the payment (success or failure)
     * @param string $message Optional message for the order
     * @param array $metaInfo Optional meta information array (udf1-5 with max 256 characters each)
     * @return object StandardCheckoutPayResponse object
     * @throws \PhonePe\common\exceptions\PhonePeException
     */
    public function initiatePayment($merchantOrderId, $amount, $redirectUrl = null, $message = null, $metaInfo = null)
    {
        try {
            // Create StandardCheckoutClient instance
            $client = StandardCheckoutClient::getInstance($this->clientId, $this->clientVersion, $this->clientSecret, $this->env);

            // Build payment request
            $payRequestBuilder = StandardCheckoutPayRequestBuilder::builder()
                ->merchantOrderId($merchantOrderId)
                ->amount($amount);

            // Add optional parameters if provided
            if ($redirectUrl !== null) {
                $payRequestBuilder->redirectUrl($redirectUrl);
            }

            if ($message !== null) {
                $payRequestBuilder->message($message);
            }

            // Add meta information if provided
            if ($metaInfo !== null && is_array($metaInfo)) {
                foreach ($metaInfo as $key => $value) {
                    if (in_array($key, ['udf1', 'udf2', 'udf3', 'udf4', 'udf5']) && strlen($value) <= 256) {
                        $payRequestBuilder->$key($value);
                    }
                }
            }

            $payRequest = $payRequestBuilder->build();

            // Call the pay method
            $payResponse = $client->pay($payRequest);

            return $payResponse;
        } catch (\PhonePe\common\exceptions\PhonePeException $e) {
            throw $e;
        }
    }

    /**
     * Process payment response and redirect if pending
     * 
     * @param object $payResponse StandardCheckoutPayResponse object
     * @param bool $autoRedirect Whether to automatically redirect for pending payments
     * @return array Response data
     */
    public function processPaymentResponse($payResponse, $autoRedirect = true)
    {
        $responseData = [
            'state' => $payResponse->getState(),
            'redirectUrl' => $payResponse->getRedirectUrl(),
            'orderId' => $payResponse->getOrderId(),
            'expireAt' => $payResponse->getExpireAt()
        ];

        // Handle automatic redirection for pending payments
        if ($payResponse->getState() === "PENDING" && $autoRedirect) {
            header("Location: " . $payResponse->getRedirectUrl());
            exit();
        }

        return $responseData;
    }

    /**
     * Check Order Status with PhonePe SDK
     * 
     * @param string $merchantOrderId The merchant order ID for which the status is fetched
     * @param bool $details true → Returns all payment attempt details under the paymentDetails list. false → Returns only the latest payment attempt details
     * @return object StatusCheckResponse object
     * @throws \PhonePe\common\exceptions\PhonePeException
     */
    public function checkOrderStatus($merchantOrderId, $details = true)
    {
        try {
            // Create StandardCheckoutClient instance
            $client = StandardCheckoutClient::getInstance($this->clientId, $this->clientVersion, $this->clientSecret, $this->env);

            // Get order status
            $statusCheckResponse = $client->getOrderStatus($merchantOrderId, $details);

            return $statusCheckResponse;
        } catch (\PhonePe\common\exceptions\PhonePeException $e) {
            throw $e;
        }
    }

    /**
     * Process order status response and extract key information
     * 
     * @param object $statusCheckResponse StatusCheckResponse object
     * @return array Formatted response data
     */
    public function processOrderStatusResponse($statusCheckResponse)
    {
        $responseData = [
            'orderId' => $statusCheckResponse->getOrderId(),
            'state' => $statusCheckResponse->getState(),
            'amount' => $statusCheckResponse->getAmount(),
            'expireAt' => $statusCheckResponse->getExpireAt(),
            'metaInfo' => $statusCheckResponse->getMetaInfo()
        ];

        // Add error information if available (safely access typed properties)
        // Check if properties are initialized before accessing them
        $reflection = new \ReflectionObject($statusCheckResponse);

        if ($reflection->hasProperty('errorCode')) {
            $errorCodeProperty = $reflection->getProperty('errorCode');
            if ($errorCodeProperty->isInitialized($statusCheckResponse)) {
                $responseData['errorCode'] = $statusCheckResponse->errorCode;
            }
        }

        if ($reflection->hasProperty('detailedErrorCode')) {
            $detailedErrorCodeProperty = $reflection->getProperty('detailedErrorCode');
            if ($detailedErrorCodeProperty->isInitialized($statusCheckResponse)) {
                $responseData['detailedErrorCode'] = $statusCheckResponse->detailedErrorCode;
            }
        }        // Add transaction ID from payment details if available
        $transactionId = null;
        if ($statusCheckResponse->getPaymentDetails()) {
            $paymentDetails = [];
            foreach ($statusCheckResponse->getPaymentDetails() as $paymentDetail) {
                // Handle both object methods and properties for PaymentDetail
                $detail = [];

                // Get transaction ID
                if (is_object($paymentDetail) && property_exists($paymentDetail, 'transactionId')) {
                    $detail['transactionId'] = $paymentDetail->transactionId;
                    if ($transactionId === null) $transactionId = $paymentDetail->transactionId;
                } elseif (method_exists($paymentDetail, 'getTransactionId')) {
                    $detail['transactionId'] = $paymentDetail->getTransactionId();
                    if ($transactionId === null) $transactionId = $paymentDetail->getTransactionId();
                }

                // Get other properties with fallback
                $detail['paymentMode'] = $this->getPaymentDetailProperty($paymentDetail, 'paymentMode', 'getPaymentMode');
                $detail['timestamp'] = $this->getPaymentDetailProperty($paymentDetail, 'timestamp', 'getTimestamp');
                $detail['state'] = $this->getPaymentDetailProperty($paymentDetail, 'state', 'getState');
                $detail['amount'] = $this->getPaymentDetailProperty($paymentDetail, 'amount', 'getAmount');
                $detail['errorCode'] = $this->getPaymentDetailProperty($paymentDetail, 'errorCode', 'getErrorCode');
                $detail['detailedErrorCode'] = $this->getPaymentDetailProperty($paymentDetail, 'detailedErrorCode', 'getDetailedErrorCode');
                $detail['splitInstruments'] = $this->getPaymentDetailProperty($paymentDetail, 'splitInstruments', 'getSplitInstruments');

                $paymentDetails[] = $detail;
            }
            $responseData['paymentDetails'] = $paymentDetails;
        }

        // Add transaction ID to main response if found
        if ($transactionId !== null) {
            $responseData['transactionId'] = $transactionId;
        }

        return $responseData;
    }

    /**
     * Helper method to safely get PaymentDetail properties
     * 
     * @param object $paymentDetail PaymentDetail object or stdClass
     * @param string $property Property name
     * @param string $method Method name
     * @return mixed Property value or null
     */
    private function getPaymentDetailProperty($paymentDetail, $property, $method)
    {
        if (is_object($paymentDetail) && property_exists($paymentDetail, $property)) {
            return $paymentDetail->$property;
        } elseif (method_exists($paymentDetail, $method)) {
            return $paymentDetail->$method();
        }
        return null;
    }

    /**
     * Get simplified order status (just the essential information)
     * 
     * @param string $merchantOrderId The merchant order ID for which the status is fetched
     * @return array Simple status information
     * @throws \PhonePe\common\exceptions\PhonePeException
     */
    public function getSimpleOrderStatus($merchantOrderId)
    {
        try {
            $statusCheckResponse = $this->checkOrderStatus($merchantOrderId, false);

            // Get transaction ID from payment details if available
            $transactionId = null;
            if ($statusCheckResponse->getPaymentDetails()) {
                foreach ($statusCheckResponse->getPaymentDetails() as $paymentDetail) {
                    // Access property directly as PaymentDetails may be stdClass objects
                    if (is_object($paymentDetail) && property_exists($paymentDetail, 'transactionId')) {
                        $transactionId = $paymentDetail->transactionId;
                    } elseif (method_exists($paymentDetail, 'getTransactionId')) {
                        $transactionId = $paymentDetail->getTransactionId();
                    }
                    if ($transactionId !== null) {
                        break; // Get first transaction ID
                    }
                }
            }

            return [
                'merchantOrderId' => $merchantOrderId,
                'orderId' => $statusCheckResponse->getOrderId(),
                'transactionId' => $transactionId,
                'state' => $statusCheckResponse->getState(),
                'amount' => $statusCheckResponse->getAmount(),
                'isPending' => $statusCheckResponse->getState() === 'PENDING',
                'isCompleted' => $statusCheckResponse->getState() === 'COMPLETED',
                'isFailed' => $statusCheckResponse->getState() === 'FAILED'
            ];
        } catch (\PhonePe\common\exceptions\PhonePeException $e) {
            throw $e;
        }
    }

    /**
     * Initiate Refund with PhonePe SDK
     * 
     * @param string $merchantRefundId Unique refund ID assigned by you (Max length: 63 characters)
     * @param string $originalMerchantOrderId The original order ID against which the refund is requested
     * @param int $amount Refund amount in paisa (Min value = 100, Max value = order amount)
     * @return object StandardCheckoutRefundResponse object
     * @throws \PhonePe\common\exceptions\PhonePeException
     */
    public function initiateRefund($merchantRefundId, $originalMerchantOrderId, $amount)
    {
        try {
            // Create StandardCheckoutClient instance
            $client = StandardCheckoutClient::getInstance($this->clientId, $this->clientVersion, $this->clientSecret, $this->env);

            // Build refund request
            $refundRequest = StandardCheckoutRefundRequestBuilder::builder()
                ->merchantRefundId($merchantRefundId)
                ->originalMerchantOrderId($originalMerchantOrderId)
                ->amount($amount)
                ->build();

            // Call the refund method
            $refundResponse = $client->refund($refundRequest);

            return $refundResponse;
        } catch (\PhonePe\common\exceptions\PhonePeException $e) {
            throw $e;
        }
    }

    /**
     * Process refund response and extract key information
     * 
     * @param object $refundResponse StandardCheckoutRefundResponse object
     * @return array Formatted refund response data
     */
    public function processRefundResponse($refundResponse)
    {
        return [
            'refundId' => $refundResponse->getRefundId(),
            'state' => $refundResponse->getState(),
            'amount' => $refundResponse->getAmount(),
            'isSuccessful' => $refundResponse->getState() === 'COMPLETED',
            'isPending' => $refundResponse->getState() === 'PENDING',
            'isFailed' => $refundResponse->getState() === 'FAILED'
        ];
    }

    /**
     * Check Refund Status with PhonePe SDK
     * 
     * @param string $merchantRefundId The refund ID created at the time of refund initiation
     * @return object RefundStatusCheckResponse object
     * @throws \PhonePe\common\exceptions\PhonePeException
     */
    public function checkRefundStatus($merchantRefundId)
    {
        try {
            // Create StandardCheckoutClient instance
            $client = StandardCheckoutClient::getInstance($this->clientId, $this->clientVersion, $this->clientSecret, $this->env);

            // Get refund status
            $refundStatusResponse = $client->getRefundStatus($merchantRefundId);

            return $refundStatusResponse;
        } catch (\PhonePe\common\exceptions\PhonePeException $e) {
            throw $e;
        }
    }

    /**
     * Process refund status response and extract key information
     * 
     * @param object $refundStatusResponse RefundStatusCheckResponse object
     * @return array Formatted refund status data
     */
    public function processRefundStatusResponse($refundStatusResponse)
    {
        $responseData = [
            'originalMerchantOrderId' => $refundStatusResponse->getOriginalMerchantOrderId(),
            'merchantRefundId' => $refundStatusResponse->getMerchantRefundId(),
            'refundId' => $refundStatusResponse->getRefundId(),
            'state' => $refundStatusResponse->getState(),
            'amount' => $refundStatusResponse->getAmount(),
            'isCompleted' => $refundStatusResponse->getState() === 'COMPLETED',
            'isPending' => $refundStatusResponse->getState() === 'PENDING',
            'isFailed' => $refundStatusResponse->getState() === 'FAILED'
        ];

        // Add payment details if available
        if ($refundStatusResponse->getPaymentDetails()) {
            $paymentDetails = [];
            foreach ($refundStatusResponse->getPaymentDetails() as $paymentDetail) {
                $paymentDetails[] = [
                    'transactionId' => $paymentDetail->getTransactionId(),
                    'paymentMode' => $paymentDetail->getPaymentMode(),
                    'timestamp' => $paymentDetail->getTimestamp(),
                    'amount' => $paymentDetail->getAmount(),
                    'state' => $paymentDetail->getState(),
                    'errorCode' => $paymentDetail->getErrorCode(),
                    'detailedErrorCode' => $paymentDetail->getDetailedErrorCode(),
                    'splitInstruments' => $paymentDetail->getSplitInstruments()
                ];
            }
            $responseData['paymentDetails'] = $paymentDetails;
        }

        return $responseData;
    }

    /**
     * Get simple refund status (just the essential information)
     * 
     * @param string $merchantRefundId The refund ID to check
     * @return array Simple refund status information
     * @throws \PhonePe\common\exceptions\PhonePeException
     */
    public function getSimpleRefundStatus($merchantRefundId)
    {
        try {
            $refundStatusResponse = $this->checkRefundStatus($merchantRefundId);

            return [
                'merchantRefundId' => $merchantRefundId,
                'refundId' => $refundStatusResponse->getRefundId(),
                'originalOrderId' => $refundStatusResponse->getOriginalMerchantOrderId(),
                'state' => $refundStatusResponse->getState(),
                'amount' => $refundStatusResponse->getAmount(),
                'isCompleted' => $refundStatusResponse->getState() === 'COMPLETED',
                'isPending' => $refundStatusResponse->getState() === 'PENDING',
                'isFailed' => $refundStatusResponse->getState() === 'FAILED'
            ];
        } catch (\PhonePe\common\exceptions\PhonePeException $e) {
            throw $e;
        }
    }

    /**
     * Complete refund workflow - initiate refund and return processed response
     * 
     * @param string $originalMerchantOrderId The original order ID to refund
     * @param int $amount Refund amount in paisa
     * @param string $merchantRefundId Optional custom refund ID, auto-generated if not provided
     * @return array Complete refund response data
     * @throws \PhonePe\common\exceptions\PhonePeException
     */
    public function processRefund($originalMerchantOrderId, $amount, $merchantRefundId = null)
    {
        try {
            // Generate refund ID if not provided
            if ($merchantRefundId === null) {
                $merchantRefundId = "REFUND_" . time() . "_" . rand(1000, 9999);
            }

            // Initiate refund
            $refundResponse = $this->initiateRefund($merchantRefundId, $originalMerchantOrderId, $amount);

            // Process and return response
            $processedResponse = $this->processRefundResponse($refundResponse);
            $processedResponse['merchantRefundId'] = $merchantRefundId;
            $processedResponse['originalMerchantOrderId'] = $originalMerchantOrderId;

            return $processedResponse;
        } catch (\PhonePe\common\exceptions\PhonePeException $e) {
            throw $e;
        }
    }
}
