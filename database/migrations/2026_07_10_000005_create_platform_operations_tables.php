<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlatformOperationsTables extends Migration
{
    public function up()
    {
        Schema::create('admin_module_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('page_code', 20)->index();
            $table->string('record_type', 50)->default('record')->index();
            $table->string('title', 191);
            $table->string('status', 30)->default('enabled')->index();
            $table->integer('sort_order')->default(0)->index();
            $table->decimal('amount', 20, 4)->nullable();
            $table->string('currency', 10)->nullable();
            $table->timestamp('effective_at')->nullable()->index();
            $table->longText('business_data')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();

            $table->index(['page_code', 'status', 'sort_order'], 'amr_page_status_sort');
        });

        Schema::create('admin_module_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('page_code', 20)->index();
            $table->string('section', 50)->default('general')->index();
            $table->string('setting_key', 100);
            $table->longText('setting_value')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();

            $table->unique(
                ['page_code', 'section', 'setting_key'],
                'ams_page_section_key_unique'
            );
        });

        Schema::create('admin_module_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('page_code', 20)->index();
            $table->string('business_no', 100)->unique();
            $table->string('transaction_type', 50)->index();
            $table->string('account_name', 191)->nullable();
            $table->string('account_no', 191)->nullable()->index();
            $table->decimal('amount', 20, 4);
            $table->decimal('balance_before', 20, 4)->nullable();
            $table->decimal('balance_after', 20, 4)->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->text('remark')->nullable();
            $table->longText('business_data')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();

            $table->index(
                ['page_code', 'status', 'occurred_at'],
                'amt_page_status_occurred'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_module_transactions');
        Schema::dropIfExists('admin_module_settings');
        Schema::dropIfExists('admin_module_records');
    }
}

return new CreatePlatformOperationsTables();
