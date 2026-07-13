<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTcgPlayerOperationTables extends Migration
{
    public function up()
    {
        $this->createOperationTags();
        $this->createOtpVerificationRecords();
        $this->createPlayerLevelHistories();
        $this->createFrontendCopySettings();
    }

    public function down()
    {
        Schema::dropIfExists('tcg_frontend_copy_settings');
        Schema::dropIfExists('tcg_player_level_histories');
        Schema::dropIfExists('tcg_otp_verification_records');
        Schema::dropIfExists('tcg_operation_tags');
    }

    private function createOperationTags()
    {
        if (Schema::hasTable('tcg_operation_tags')) {
            return;
        }

        Schema::create('tcg_operation_tags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tag_code', 80)->index();
            $table->string('tag_name', 120);
            $table->string('tag_color', 40)->nullable();
            $table->string('username', 100)->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('source', 50)->default('manual')->index();
            $table->dateTime('starts_at')->nullable()->index();
            $table->dateTime('ends_at')->nullable()->index();
            $table->string('status', 32)->default('active')->index();
            $table->text('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    private function createOtpVerificationRecords()
    {
        if (Schema::hasTable('tcg_otp_verification_records')) {
            return;
        }

        Schema::create('tcg_otp_verification_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('otp_scene', 80)->index();
            $table->string('channel', 50)->index();
            $table->string('receiver', 150)->index();
            $table->string('username', 100)->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('request_ip', 80)->nullable()->index();
            $table->string('device_id', 150)->nullable()->index();
            $table->string('verify_result', 50)->default('sent')->index();
            $table->string('failure_reason', 255)->nullable();
            $table->dateTime('verified_at')->nullable()->index();
            $table->string('status', 32)->default('sent')->index();
            $table->text('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    private function createPlayerLevelHistories()
    {
        if (Schema::hasTable('tcg_player_level_histories')) {
            return;
        }

        Schema::create('tcg_player_level_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 100)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->integer('old_level')->nullable();
            $table->integer('new_level')->nullable();
            $table->string('change_source', 80)->index();
            $table->string('reason', 255)->nullable();
            $table->dateTime('effective_at')->nullable()->index();
            $table->dateTime('expired_at')->nullable()->index();
            $table->string('status', 32)->default('active')->index();
            $table->text('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    private function createFrontendCopySettings()
    {
        if (Schema::hasTable('tcg_frontend_copy_settings')) {
            return;
        }

        Schema::create('tcg_frontend_copy_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('copy_key', 120);
            $table->string('locale', 20)->index();
            $table->string('client_type', 40)->default('all')->index();
            $table->string('title', 200)->nullable();
            $table->text('body')->nullable();
            $table->string('version', 80)->nullable();
            $table->dateTime('published_at')->nullable()->index();
            $table->string('status', 32)->default('draft')->index();
            $table->text('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['copy_key', 'locale', 'client_type', 'version'], 'tcg_front_copy_unique');
        });
    }
}
