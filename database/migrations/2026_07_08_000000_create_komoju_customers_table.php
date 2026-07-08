<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('komoju_customers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('komoju_customer_id')->unique();
            $table->string('payment_resource_id')->nullable();
            $table->string('card_brand')->nullable();
            $table->string('card_last4')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('komoju_customers');
    }
};
