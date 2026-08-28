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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->index();
            $table->string('code', 2)->unique(); 
            $table->string('phone', 10)->nullable();
            $table->json('translations')->nullable();
            $table->json('timezones')->nullable();
            $table->string('numeric_code', 3)->nullable();
            $table->string('iso3', 3)->nullable();
            $table->string('nationality')->nullable();
            $table->string('capital')->nullable();
            $table->string('tld', 10)->nullable();
            $table->string('native')->nullable();
            $table->string('region')->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('currency_name')->nullable();
            $table->string('currency_symbol', 10)->nullable();
            $table->string('wikiDataId')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->string('emoji', 10)->nullable();
            $table->string('emojiU', 50)->nullable();
            $table->string('flag')->nullable();
            $table->boolean('is_activated')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
