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
        Schema::create('tbl_subgrupos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('subgrupo', 30);
            $table->unsignedBigInteger('id_sede')->nullable()->index('sede_has_subgrupos');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_subgrupos');
    }
};
