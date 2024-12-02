<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBreakCorrectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('break_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('break_id')->constrained('breaks')->cascadeOnDelete();
            $table->foreignId('attendance_correction_id')->constrained('attendance_corrections')->cascadeOnDelete();
            $table->timestamp('corrected_start_time');
            $table->timestamp('corrected_end_time');
            $table->time('corrected_duration'); // 時間フォーマットで保存
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('break_corrections');
    }
}
