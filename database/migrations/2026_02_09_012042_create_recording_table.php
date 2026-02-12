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
        Schema::create('datalist', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('model', 20);
            $table->string('part_number', 50);
            $table->smallInteger('qty');
            $table->string('fg_code', 50);
            $table->string('pckg_number', 50);
            $table->string('pic', 50);
            $table->text('reprinting_reaseon');
        });

        Schema::create('models', function (Blueprint $table){
            $table->id();
            $table->timestamps();
            $table->string('model_name', 50);
            $table->string('part_number', 50);
            $table->string('fg_code', 20);
        });

        Schema::create('ip_address', function (Blueprint $table){
            $table->id();
            $table->timestamps();
            $table->string('ip_address', 15);
            $table->string('SATO_ip_address', 15);
            $table->smallInteger('horizontal_offset');
            $table->smallInteger('vertical_offset');
            $table->text('remarks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('datalist');
        Schema::dropIfExists('models');
        Schema::dropIfExists('ip_address');
    }
};
