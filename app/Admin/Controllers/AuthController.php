<?php

namespace App\Admin\Controllers;

use Dcat\Admin\Admin;
use Dcat\Admin\Http\Controllers\AuthController as BaseAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuthController extends BaseAuthController
{
    public function postLogin(Request $request)
    {
        $username = (string) $request->input($this->username(), '');
        if (!$this->administratorIsEnabled($username)) {
            return $this->validationErrorsResponse([
                $this->username() => '该系统用户已停用，请联系管理员',
            ]);
        }

        $response = parent::postLogin($request);
        if ($this->guard()->check()) {
            $this->touchAdministratorProfile($request);
        }

        return $response;
    }

    protected function administratorIsEnabled($username)
    {
        if (!Schema::hasTable('admin_user_profiles')) {
            return true;
        }

        $user = DB::table(config('admin.database.users_table', 'admin_users'))
            ->where('username', $username)
            ->first(['id']);
        if (!$user) {
            return true;
        }

        $profile = DB::table('admin_user_profiles')
            ->where('admin_user_id', $user->id)
            ->first(['status']);

        return !$profile || (bool) $profile->status;
    }

    protected function touchAdministratorProfile(Request $request)
    {
        if (!Schema::hasTable('admin_user_profiles')) {
            return;
        }

        $admin = Admin::user();
        if (!$admin) {
            return;
        }

        $values = [
            'last_seen_at' => now(),
            'last_login_ip' => $request->ip(),
            'updated_at' => now(),
        ];
        $exists = DB::table('admin_user_profiles')
            ->where('admin_user_id', $admin->getKey())
            ->exists();
        if ($exists) {
            DB::table('admin_user_profiles')
                ->where('admin_user_id', $admin->getKey())
                ->update($values);

            return;
        }

        DB::table('admin_user_profiles')->insert($values + [
            'admin_user_id' => $admin->getKey(),
            'subscribed_brands' => '[]',
            'google_auth_enabled' => 0,
            'status' => 1,
            'created_at' => now(),
        ]);
    }
}
