<?php

/**
 * PhonePe Payment Gateway Integration Class
 * 
 * This class provides methods to integrate with PhonePe's payment gateway services
 * including payment initiation, status checking, and refund processing.
 * 
 * @author fayyaztech
 * @package fayyaztech\phonePePaymentGateway
 */

namespace fayyaztech\phonePePaymentGateway;

class PhonePe
{
    // API endpoint URLs
    private const PROD_URL = 'https://api.phonepe.com/apis/hermes/pg/v1/';
    protected const UAT_URL = 'https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/';

    // Payment modes
    private const MODE_UAT = 'UAT';  // Testing/Sandbox mode
    private const MODE_PROD = 'PROD'; // Production mode

    /**
     * @var int Salt index used for request signature
     */
    private int $salt_index;

    /**
     * @var string Salt key used for request signature
     */
    protected string $salt_key;

    /**
     * @var string Merchant ID assigned by PhonePe
     */
    protected string $merchant_id;

    public function __construct(?string $merchant_id = null, ?string $salt_key = null, ?int $salt_index = null)
    {
        $this->merchant_id = $merchant_id ?? getenv('PHONEPE_MERCHANT_ID');
        $this->salt_key = $salt_key ?? getenv('PHONEPE_SALT_KEY');
        $this->salt_index = $salt_index ?? (int)getenv('PHONEPE_SALT_INDEX');

        if (empty($this->merchant_id)) throw new PhonePeApiException("Merchant ID is required");
        if (empty($this->salt_key)) throw new PhonePeApiException("Salt Key is required");
        if (empty($this->salt_index)) throw new PhonePeApiException("Salt Index is required");
    }

    /**
     * Initiates a payment transaction through PhonePe gateway
     * 
     * @param string $merchantTransactionId Unique identifier for this transaction
     * @param string $merchantUserId Identifier for the customer
     * @param int $amount Amount in lowest currency denomination (paisa for INR)
     * @param string $redirectUrl URL where customer will be redirected after payment
     * @param string $callbackUrl Webhook URL for payment notifications
     * @param string $mobileNumber Customer's mobile number
     * @param string|null $mode Payment mode (UAT/PROD)
     * @return array{responseCode: int, url: string, msg: string, status: string} Payment response
     * @throws PhonePeApiException When API call fails
     */
    public function PaymentCall(
        string $merchantTransactionId,
        string $merchantUserId,
        int $amount,
        string $redirectUrl,
        string $callbackUrl,
        string $mobileNumber,
        ?string $mode = null
    ): array {
        $paymentMsg = "";
        $paymentCode = "";
        $payUrl = "";
        $payload = array(
            "merchantId" => "$this->merchant_id",
            "merchantTransactionId" => "$merchantTransactionId",
            "merchantUserId" => "$merchantUserId",
            "amount" => $amount,
            "redirectUrl" => "$redirectUrl",
            "redirectMode" => "POST",
            "callbackUrl" => "$callbackUrl",
            "mobileNumber" => "$mobileNumber",
            "paymentInstrument" => array(
                "type" => "PAY_PAGE"
            )
        );

        $payload_str = json_encode($payload);
        $base64_payload = base64_encode($payload_str);
        $hashString = $base64_payload . "/pg/v1/pay" . $this->salt_key;
        $hashedValue = hash('sha256', $hashString);
        $result = $hashedValue . "###" . $this->salt_index;

        if ($mode == 'UAT')
            $url = self::UAT_URL . 'pay';
        else
            $url = self::PROD_URL . 'pay';

        $headers = [
            "Content-Type: application/json",
            "accept: application/json",
            "X-VERIFY: $result",
        ];

        try {
            $res = $this->makeApiCall($url, $headers, ['request' => "$base64_payload"]);
        } catch (PhonePeApiException $e) {
            return [
                'responseCode' => 400,
                'error' => $e->getMessage()
            ];
        }

        if (isset($res->success) && $res->success == '1') {
            $paymentCode = $res->code;
            $paymentMsg = $res->message;
            $payUrl = $res->data->instrumentResponse->redirectInfo->url;
        } else {
            return [
                'responseCode' => $res->code,
                'url' => '',
                'msg' => $res->message,
                'status' => $res->status ?? 'Error from PhonePe Server',
            ];
        }

        return [
            'responseCode' => 200,
            'url' => $payUrl,
            'msg' => $paymentMsg,
            'status' => $paymentCode,
        ];
    }

    /**
     * Check the status of a payment transaction
     * 
     * @param string $merchantId Merchant ID for the transaction
     * @param string $merchantTransactionId Transaction ID to check
     * @param string|null $mode Payment mode (UAT/PROD)
     * @return array{responseCode: int, txn: string, msg: string, status: string} Status response
     * @throws PhonePeApiException When API call fails
     */
    public function PaymentStatus($merchantId, $merchantTransactionId, $mode = null): array
    {
        $paymentMsg = "";
        $paymentStatus = "";
        $txnid = "";
        $hashString = "/pg/v1/status/$merchantId/$merchantTransactionId" . $this->salt_key;
        $hashedValue = hash('sha256', $hashString);
        $result = $hashedValue . "###" . $this->salt_index;
        if ($mode == 'UAT')
            $url = self::UAT_URL . 'status/' . $merchantId . '/' . $merchantTransactionId;
        else
            $url = self::PROD_URL . 'status/' . $merchantId . '/' . $merchantTransactionId;

        $headers = [
            "Content-Type: application/json",
            "X-MERCHANT-ID:$merchantId",
            "X-VERIFY:$result",
            "accept: application/json",
        ];

        try {
            $res = $this->makeApiCall($url, $headers);
        } catch (PhonePeApiException $e) {
            return [
                'responseCode' => 400,
                'error' => $e->getMessage()
            ];
        }

        if (isset($res->success) && $res->success == '1') {
            $paymentStatus = $res->data->responseCode;
            $paymentMsg = $res->message;
            $txnid = $res->data->merchantTransactionId;
        }

        return [
            'responseCode' => 200,
            'txn' => $txnid,
            'msg' => $paymentMsg,
            'status' => $paymentStatus,
        ];
    }

    /**
     * Process a refund for a completed transaction
     * 
     * @param string $merchantId Merchant ID for the transaction
     * @param string $refundtransactionId Unique ID for this refund
     * @param string $orderTransactionId Original transaction ID to refund
     * @param string $callbackUrl Webhook URL for refund notifications
     * @param int $amount Amount to refund in lowest denomination
     * @param string|null $mode Payment mode (UAT/PROD)
     * @return array{responseCode: int, state: string, msg: string, status: string} Refund response
     * @throws PhonePeApiException When API call fails
     */
    public function PaymentRefund($merchantId, $refundtransactionId, $orderTransactionId, $callbackUrl, $amount, $mode = null): array
    {
        $paymentMsg = "";
        $paymentCode = "";
        $state = "";
        $payload = array(
            "merchantId" => "$merchantId",
            "merchantTransactionId" => "$refundtransactionId",
            "originalTransactionId" => "$orderTransactionId",
            "amount" => $amount,
            "callbackUrl" => "$callbackUrl"
        );

        $payload_str = json_encode($payload);
        $base64_payload = base64_encode($payload_str);
        $hashString = $base64_payload . "/pg/v1/refund" . $this->salt_key;
        $hashedValue = hash('sha256', $hashString);
        $result = $hashedValue . "###" . $this->salt_index;

        if ($mode == self::MODE_UAT) {
            $url = self::UAT_URL . 'refund';
        } else {
            $url = self::PROD_URL . 'refund';
        }

        $headers = [
            "Content-Type: application/json",
            "accept: application/json",
            "X-VERIFY: $result",
        ];

        try {
            $res = $this->makeApiCall($url, $headers, ['request' => "$base64_payload"]);
        } catch (PhonePeApiException $e) {
            return [
                'responseCode' => 400,
                'error' => $e->getMessage()
            ];
        }

        if (isset($res->success) && $res->success == '1') {
            $paymentCode = $res->code;
            $paymentMsg = $res->message;
            $state = $res->data->state;
        } else {
            return [
                'responseCode' => $res->code,
                'state' => $res->code,
                'msg' => $res->message ?? 'Error from PhonePe Server' . $res->code,
                'status' => $res->status ?? 'Error from PhonePe Server' . $res->code,
            ];
        }

        return [
            'responseCode' => 200,
            'state' => $state,
            'msg' => $paymentMsg,
            'status' => $paymentCode,
        ];
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
     * @param array|null $postData POST data (optional)
     * @return object Response from API
     * @throws PhonePeApiException When API call fails
     */
    private function makeApiCall(string $url, array $headers, ?array $postData = null): object
    {
        $curl = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => $headers
        ];

        if ($postData !== null) {
            $options[CURLOPT_CUSTOMREQUEST] = 'POST';
            $options[CURLOPT_POSTFIELDS] = json_encode($postData);
        }

        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            throw new PhonePeApiException("API Call Failed: {$err}");
        }

        return json_decode($response);
    }
}
