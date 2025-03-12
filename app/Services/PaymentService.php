<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Procesa un pago con EasyMoney
     * EasyMoney no acepta montos con decimales, por lo que se convierten a enteros.
     */
    public function processEasyMoney($amount, $currency)
    {
        $amount = (int) $amount; // Convertir a entero para evitar errores

        //Crear y guardar la transacción antes de hacer la solicitud
        $transaction = $this->createTransaction('easyMoney', $amount, $currency, [
            'amount' => $amount,
            'currency' => $currency
        ]);

        //Enviar solicitud 
        $response = Http::post('http://localhost:3000/process', [
            'amount' => $amount,
            'currency' => $currency
        ]);

        // Manejar la respuesta de EasyMoney y actualizar la transacción
        return $this->handleEasyMoneyResponse($transaction, $response);
    }

    /**
     * Procesa un pago con SuperWalletz
     * SuperWalletz requiere un "callback_url" para enviar la confirmación del pago.
     */
    public function processSuperWalletz($amount, $currency, $description = '')
    {
        //Crear la transacción en la base de datos antes de enviar la solicitud
        $transaction = $this->createTransaction('superWalletz', $amount, $currency, [
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'callback_url' => route('superwalletz.callback')
        ]);

        // Enviar solicitud de pago a SuperWalletz
        $response = Http::post('http://localhost:3003/pay', [
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'callback_url' => route('superwalletz.callback')
        ]);

        // Si la solicitud es exitosa, actualizar el estado a "processing"
        if ($response->successful()) {
            $transaction->update([
                'status' => 'processing',
                'response_data' => json_encode($response->json()),
                'external_transaction_id' => $response->json()['transaction_id'] ?? null
            ]);
        } else {
            $transaction->update(['status' => 'failed']);
        }

        return $transaction;
    }

    /**
     * Maneja el webhook de SuperWalletz que confirma el pago.
     */
    public function handleWebhook($transactionId, $status)
    {
        // Buscar la transacción usando el ID externo de SuperWalletz
        $transaction = Transaction::where('external_transaction_id', $transactionId)->first();

        if (!$transaction) {
            Log::warning("Webhook recibido con ID de transacción no encontrado: $transactionId");
            return ['message' => 'Transaction not found'];
        }

        // Evita procesar webhooks duplicados
        if ($transaction->status === 'success') {
            Log::info("Webhook duplicado ignorado para transacción ID: $transactionId");
            return ['message' => 'Duplicate webhook ignored'];
        }

        // Actualizar la transacción con el estado confirmado
        $transaction->update([
            'status' => $status,
            'response_data' => json_encode(['transaction_id' => $transactionId, 'status' => $status])
        ]);

        return ['message' => 'Webhook processed successfully'];
    }

    /**
     * Crea y almacena una nueva transacción en la base de datos.
     */
    private function createTransaction($provider, $amount, $currency, $requestData)
    {
        return Transaction::create([
            'provider' => $provider,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'request_data' => json_encode($requestData)
        ]);
    }

    /**
     * Maneja la respuesta de EasyMoney y actualiza la transacción.
     */
    private function handleEasyMoneyResponse($transaction, $response)
    {
        if ($response->successful() && $response->body() === 'ok') {
            $transaction->update([
                'status' => 'success',
                'response_data' => json_encode(['message' => 'Payment successful'])
            ]);
        } else {
            $transaction->update([
                'status' => 'failed',
                'response_data' => json_encode(['message' => 'Payment failed'])
            ]);
        }

        return $transaction;
    }
}
