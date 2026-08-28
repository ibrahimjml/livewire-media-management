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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('country_id')->nullable()->constrained('countries')->onDelete('cascade');
            $table->foreignId('area_id')->nullable()->constrained('areas')->onDelete('cascade');
            $table->foreignId('city_id')->nullable()->constrained('cities')->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('cascade');
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->onDelete('cascade');
            $table->foreignId('shipper_id')->nullable()->constrained('deliveries')->onDelete('cascade');
            $table->foreignId('shipping_vendor_id')->nullable()->constrained('shipping_vendors')->onDelete('cascade');
              //Customer Info
            $table->string('name')->nullable();
            $table->string('phone')->nullable();

            //Location
            $table->string('flat')->nullable();
            $table->text('address')->nullable();
            //Prices
            $table->decimal('total',10,2)->default(0.00)->nullable();
            $table->decimal('discount',3,2)->default(0.00)->nullable();
            $table->decimal('shipping',3,2)->default(0)->nullable();
            $table->decimal('vat',3,2)->default(0)->nullable();
            //Status
            $table->string('status')->nullable();
              //Notes
            $table->text('notes')->nullable();

            //Return
            $table->boolean('has_returns')->default(0)->nullable();
            $table->decimal('return_total',10,2)->default(0)->nullable();
            $table->string('reason')->nullable();
            //Payments
            $table->boolean('is_payed')->default(0)->nullable();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
