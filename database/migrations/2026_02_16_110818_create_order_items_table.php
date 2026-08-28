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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('cascade');
          
            $table->string('item')->nullable();
            $table->decimal('price',10,2)->default(0)->nullable();
            $table->decimal('discount',3,2)->default(0)->nullable();
            $table->decimal('vat',3,2)->default(0)->nullable();
            $table->decimal('total',10,2)->default(0)->nullable();
            $table->decimal('returned_total',10,2)->default(0)->nullable();
          
            $table->double('qty')->default(1)->nullable();
            $table->double('returned_qty')->default(0)->nullable();
            $table->boolean('is_returned')->default(0)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
