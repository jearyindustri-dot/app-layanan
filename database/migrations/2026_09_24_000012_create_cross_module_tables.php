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
        Schema::create('status_histories', function (Blueprint $table) {
            $table->id();
            $table->string('statusable_type');
            $table->unsignedBigInteger('statusable_id');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['statusable_type', 'statusable_id']);
        });

        Schema::create('dispositions', function (Blueprint $table) {
            $table->id();
            $table->string('dispositionable_type');
            $table->unsignedBigInteger('dispositionable_id');
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_work_unit_id')->constrained('work_units')->cascadeOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('instructions');
            $table->timestampTz('disposed_at');
            $table->timestampsTz();

            $table->index(['dispositionable_type', 'dispositionable_id']);
        });

        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix'); // DTSEN, PBI, ADU, RHS, RJK
            $table->string('period'); // YYYYMM
            $table->integer('last_number')->default(0);
            $table->timestampsTz();

            $table->unique(['prefix', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
        Schema::dropIfExists('dispositions');
        Schema::dropIfExists('status_histories');
    }
};
