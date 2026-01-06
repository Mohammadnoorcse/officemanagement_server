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
       Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // Holiday name
            $table->date('start_date');                   // Start of holiday
            $table->date('end_date');                     // End of holiday
            $table->text('description')->nullable();     // Optional description
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
