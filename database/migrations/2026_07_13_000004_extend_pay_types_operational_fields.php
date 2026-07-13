<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExtendPayTypesOperationalFields extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('pay_types')) {
            return;
        }

        Schema::table('pay_types', function (Blueprint $table) {
            if (!Schema::hasColumn('pay_types', 'category')) {
                $table->string('category', 50)->nullable()->after('name');
            }
            if (!Schema::hasColumn('pay_types', 'bonus_ratio')) {
                $table->decimal('bonus_ratio', 8, 2)->default(0)->after('sort_order');
            }
            if (!Schema::hasColumn('pay_types', 'merchant_no')) {
                $table->string('merchant_no', 191)->nullable()->after('bonus_ratio');
            }
            if (!Schema::hasColumn('pay_types', 'merchant_key')) {
                $table->string('merchant_key', 191)->nullable()->after('merchant_no');
            }
            if (!Schema::hasColumn('pay_types', 'merchant_url')) {
                $table->string('merchant_url', 500)->nullable()->after('merchant_key');
            }
            if (!Schema::hasColumn('pay_types', 'merchant_identifier')) {
                $table->string('merchant_identifier', 100)->nullable()->after('merchant_url');
            }
            if (!Schema::hasColumn('pay_types', 'merchant_code')) {
                $table->string('merchant_code', 100)->nullable()->after('merchant_identifier');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('pay_types')) {
            return;
        }

        Schema::table('pay_types', function (Blueprint $table) {
            foreach (['merchant_code', 'merchant_identifier', 'merchant_url', 'merchant_key', 'merchant_no', 'bonus_ratio', 'category'] as $column) {
                if (Schema::hasColumn('pay_types', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
