<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlatformSettingsTables extends Migration
{
    public function up()
    {
        Schema::create('platform_customer_services', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('service_type', 50)->index();
            $table->string('display_name', 100);
            $table->string('service_url', 1000);
            $table->unsignedInteger('position')->default(0)->index();
            $table->unsignedInteger('min_player_level')->default(0);
            $table->boolean('status')->default(true)->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_app_builds', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('build_no', 40)->unique();
            $table->string('package_name', 191);
            $table->string('domain', 191)->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->string('ios_url', 1000)->nullable();
            $table->string('android_url', 1000)->nullable();
            $table->unsignedBigInteger('requested_by')->nullable()->index();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->longText('details')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('platform_app_builds');
        Schema::dropIfExists('platform_customer_services');
    }
}

return new CreatePlatformSettingsTables();
