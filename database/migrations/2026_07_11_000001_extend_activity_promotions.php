<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExtendActivityPromotions extends Migration
{
    public function up()
    {
        Schema::table('activities', function (Blueprint $table) {
            if (!Schema::hasColumn('activities', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('app_img');
            }
            if (!Schema::hasColumn('activities', 'starts_at')) {
                $table->dateTime('starts_at')->nullable()->after('sort_order');
            }
            if (!Schema::hasColumn('activities', 'ends_at')) {
                $table->dateTime('ends_at')->nullable()->after('starts_at');
            }
            if (!Schema::hasColumn('activities', 'is_popup')) {
                $table->tinyInteger('is_popup')->default(0)->after('ends_at');
            }
            if (!Schema::hasColumn('activities', 'popup_frequency')) {
                $table->string('popup_frequency', 20)->default('once')->after('is_popup');
            }
            if (!Schema::hasColumn('activities', 'popup_delay_seconds')) {
                $table->unsignedInteger('popup_delay_seconds')->default(0)->after('popup_frequency');
            }
            if (!Schema::hasColumn('activities', 'popup_image')) {
                $table->string('popup_image')->nullable()->after('popup_delay_seconds');
            }
            if (!Schema::hasColumn('activities', 'app_popup_image')) {
                $table->string('app_popup_image')->nullable()->after('popup_image');
            }
            if (!Schema::hasColumn('activities', 'detail_image')) {
                $table->string('detail_image')->nullable()->after('app_popup_image');
            }
            if (!Schema::hasColumn('activities', 'app_detail_image')) {
                $table->string('app_detail_image')->nullable()->after('detail_image');
            }
            if (!Schema::hasColumn('activities', 'action_url')) {
                $table->string('action_url')->nullable()->after('app_detail_image');
            }
            if (!Schema::hasColumn('activities', 'button_text')) {
                $table->string('button_text', 80)->nullable()->after('action_url');
            }
            if (!Schema::hasColumn('activities', 'requires_auth')) {
                $table->tinyInteger('requires_auth')->default(0)->after('button_text');
            }
        });

        Schema::table('activity_types', function (Blueprint $table) {
            if (!Schema::hasColumn('activity_types', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('icon');
            }
        });

        if (!Schema::hasTable('promotion_exposures')) {
            Schema::create('promotion_exposures', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('activity_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('session_key', 120)->nullable()->index();
                $table->string('channel', 20)->default('desktop');
                $table->string('source', 40)->default('promotion_center');
                $table->timestamps();
            });
        }

        DB::statement('DELETE aa1 FROM activity_apply aa1 INNER JOIN activity_apply aa2 ON aa1.activity_id = aa2.activity_id AND aa1.user_id = aa2.user_id AND aa1.id > aa2.id');
        if (!$this->hasIndex('activity_apply', 'activity_apply_activity_id_user_id_unique')) {
            Schema::table('activity_apply', function (Blueprint $table) {
                $table->unique(['activity_id', 'user_id'], 'activity_apply_activity_id_user_id_unique');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('promotion_exposures')) {
            Schema::dropIfExists('promotion_exposures');
        }

        if ($this->hasIndex('activity_apply', 'activity_apply_activity_id_user_id_unique')) {
            Schema::table('activity_apply', function (Blueprint $table) {
                $table->dropUnique('activity_apply_activity_id_user_id_unique');
            });
        }

        Schema::table('activity_types', function (Blueprint $table) {
            if (Schema::hasColumn('activity_types', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
        });

        Schema::table('activities', function (Blueprint $table) {
            foreach ([
                'requires_auth',
                'button_text',
                'action_url',
                'app_detail_image',
                'detail_image',
                'app_popup_image',
                'popup_image',
                'popup_delay_seconds',
                'popup_frequency',
                'is_popup',
                'ends_at',
                'starts_at',
                'sort_order',
            ] as $column) {
                if (Schema::hasColumn('activities', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function hasIndex($table, $index)
    {
        $rows = DB::select('SHOW INDEX FROM '.$table);
        foreach ($rows as $row) {
            if (isset($row->Key_name) && $row->Key_name === $index) {
                return true;
            }
        }

        return false;
    }
}
