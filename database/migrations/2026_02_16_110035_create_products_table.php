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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId("category_id")->nullable()->constrained('categories')->onDelete('cascade');
            $table->json("name");
            $table->string("slug")->unique()->index();
            $table->string("sku")->nullable();
            $table->string("barcode")->nullable();
            $table->string('image');

            $table->json("about")->nullable();
            $table->json("description")->nullable();
          
            $table->decimal("price",10,2);
            $table->decimal("discount",3,2)->default(0.00)->nullable();
            $table->decimal("vat",3,2)->default(0.00)->nullable();
            $table->decimal('change',10,2)->default(0.00);
            $table->boolean("is_activated")->default(1)->nullable();
            $table->boolean("is_in_stock")->default(1)->nullable();
            $table->boolean("is_shipped")->default(0)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
