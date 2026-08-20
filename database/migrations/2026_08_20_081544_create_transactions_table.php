<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chart_of_account_id')->constrained()->restrictOnDelete();
            $table->date('transaction_date')->index();
            $table->unsignedBigInteger('amount');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['transaction_date', 'chart_of_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
