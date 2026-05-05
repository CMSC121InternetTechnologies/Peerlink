<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('Programs')) {
            return;
        }

        Schema::create('Programs', function (Blueprint $table) {
            $table->string('program_code', 15)->primary();
            $table->string('division_id', 10)->nullable();
            $table->text('program_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Programs');
    }
};
