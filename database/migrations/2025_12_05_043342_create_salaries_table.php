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
    Schema::create('salaries', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('month'); // e.g., '2025-12'
        $table->integer('month_in_days')->default(0); // number of days in the month

        $table->decimal('basic_salary', 15, 2)->default(0);
        $table->integer('total_working_days')->default(0);
        $table->integer('present_days')->default(0);
        $table->integer('late_days')->default(0);
        $table->integer('leave_days')->default(0);
        $table->integer('absent_days')->default(0);
        $table->integer('holiday')->default(0); // fixed typo
        $table->integer('weekend_days')->default(0);
        $table->integer('late_deduction_days')->default(0);
        $table->decimal('deduction_amount', 15, 2)->default(0);
        $table->integer('total_overtime_minutes')->default(0);
        $table->decimal('overtime_amount', 15, 2)->default(0);
        $table->decimal('per_day_amount', 15, 2)->default(0);
        $table->decimal('final_salary', 15, 2)->default(0);
        $table->enum('status', ['pending','paid'])->default('pending');
        $table->timestamps();
        $table->unique(['user_id','month']);
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
