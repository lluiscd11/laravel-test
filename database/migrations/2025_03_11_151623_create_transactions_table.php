<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // Nombre del proveedor de pago (easyMoney o superWalletz)
            $table->decimal('amount', 10, 2); // Monto de la transacción
            $table->string('currency'); // Moneda utilizada (USD, EUR)
            $table->string('external_transaction_id')->nullable(); // ID de la transacción externa (solo para SuperWalletz)
            $table->enum('status', ['pending', 'processing', 'success', 'failed'])->default('pending'); // Estado de la transacción
            $table->json('request_data'); // Almacena la solicitud enviada a la API del pago
            $table->json('response_data')->nullable(); // Respuesta recibida de la API del pago
            $table->timestamps(); // Fechas de creación y actualización
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions'); 
    }
};
