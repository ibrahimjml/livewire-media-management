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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->index();
            $table->boolean('is_limited')->default(0)->nullable();
            $table->date('end_at')->nullable();
            $table->integer('order_total_limit')->default(0)->nullable();
            $table->boolean('is_activated')->default(0)->nullable();
            $table->json('apply_to')->nullable();
            $table->json('except')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
