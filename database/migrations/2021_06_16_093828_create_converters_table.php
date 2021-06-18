<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConvertersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('converters', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('after_name');
            $table->boolean('is_processed')->default(0);
            $table->boolean('is_mailed')->default(0);
            $table->foreignId('user_id')->nullable()->index();
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
        Schema::dropIfExists('converters');
    }
}
