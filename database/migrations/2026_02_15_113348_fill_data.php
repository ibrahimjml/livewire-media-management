<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
          $countires = \Illuminate\Support\Facades\File::get(base_path('database/data/countries.sql'));
        DB::connection()->getPdo()->exec($countires);

        $citites = \Illuminate\Support\Facades\File::get(base_path('database/data/cities.sql'));
        DB::connection()->getPdo()->exec($citites);

        $areas = \Illuminate\Support\Facades\File::get(base_path('database/data/areas.sql'));
        DB::connection()->getPdo()->exec($areas);

        $currencies = \Illuminate\Support\Facades\File::get(base_path('database/data/currencies.sql'));
        DB::connection()->getPdo()->exec($currencies);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
