<?php

namespace App\Libraries;

use Config\Services;
use RuntimeException;

/**
 * PaymentService - wrapper QRIS payment gateway (Arie Pay).
 *
 * Mengganti createPayment() & checkPaymentStatus() di config.php legacy.
 * Kredensial dibaca dari .env (payment.apiKey, payment.merchantCode).
 */
class PaymentService
{
    private string $apiKey;
    private string $merchantCode;
    private string $endpoint = 'https://ariepulsa.online/api/h2h';

    public function __construct()
    {
        $this->apiKey       = (string) env('payment.apiKey', '');
        $this->merchantCode = (string) env('payment.merchantCode', '');

        if ($this->apiKey === '' || $this->merchantCode === '') {
            log_message('warning', '[PaymentService] payment.apiKey / payment.merchantCode kosong di .env');
        }
    }

    /**
     * Buat order baru di payment gateway, return ['status' => bool, 'data' => [...]].
     *
     * @return array{status: bool, data?: array<string, mixed>, message?: string}
     */
    public function createPayment(string $orderId, int $amount): array
    {
        $client = Services::curlrequest([
            'timeout'         => 15,
            'connect_timeout' => 10,
        ]);

        try {
            $response = $client->post($this->endpoint . '/deposit/buat', [
                'form_params' => [
                    'api_key'       => $this->apiKey,
                    'merchant_code' => $this->merchantCode,
                    'jumlah'        => $amount,
                    'kode_unik'     => $orderId,
                    'metode'        => 'QRIS',
                ],
                'http_errors' => false,
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException('Payment gateway connection error: ' . $e->getMessage(), 0, $e);
        }

        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        if (! is_array($data)) {
            log_message('error', '[PaymentService] invalid JSON: ' . substr($body, 0, 200));
            return ['status' => false, 'message' => 'Invalid gateway response'];
        }

        return [
            'status' => ! empty($data['status']),
            'data'   => $data['data'] ?? [],
            'message'=> $data['message'] ?? '',
        ];
    }

    /**
     * Cek status pembayaran berdasarkan kode_deposit.
     */
    public function checkPaymentStatus(string $depositCode): bool
    {
        $client = Services::curlrequest([
            'timeout'         => 10,
            'connect_timeout' => 8,
        ]);

        try {
            $response = $client->post($this->endpoint . '/deposit/cek', [
                'form_params' => [
                    'api_key'       => $this->apiKey,
                    'merchant_code' => $this->merchantCode,
                    'kode_deposit'  => $depositCode,
                ],
                'http_errors' => false,
            ]);
        } catch (\Throwable $e) {
            log_message('warning', '[PaymentService] cek status error: ' . $e->getMessage());
            return false;
        }

        $data = json_decode((string) $response->getBody(), true);
        if (! is_array($data)) {
            return false;
        }

        $status = strtolower((string) ($data['data']['status'] ?? ''));
        return in_array($status, ['success', 'paid', 'sukses', 'completed'], true);
    }
}
