<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTcgOperationalRecordsTable extends Migration
{
    public function up()
    {
        Schema::create('tcg_operational_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('page_code', 32)->index();
            $table->string('title', 200);
            $table->string('username', 100)->nullable()->index();
            $table->string('target_key', 200)->nullable()->index();
            $table->decimal('amount', 18, 4)->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->json('payload')->nullable();
            $table->text('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['page_code', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('tcg_operational_records');
    }
}
