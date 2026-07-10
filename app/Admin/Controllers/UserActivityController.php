<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\UserActivity;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UserActivityController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(UserActivity::with(['user']), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->column('id')->sortable();
            $grid->column('user.username', '会员账号');
            $grid->column('action', '操作');
            $grid->column('details', '详情')->limit(50);
            $grid->column('ip', 'IP地址');
            $grid->column('device', '设备');
            $grid->column('browser', '浏览器');
            $grid->column('os', '操作系统');
            $grid->column('url', '访问URL')->limit(50);
            $grid->column('created_at', '操作时间')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_id', '会员ID');
                $filter->like('user.username', '会员账号');
                $filter->like('action', '操作');
                $filter->like('ip', 'IP地址');
                $filter->like('device', '设备');
                $filter->date('created_at', '操作时间');
            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new UserActivity(), function (Show $show) {
            $show->field('id');
            $show->field('user.username', '会员账号');
            $show->field('action', '操作');
            $show->field('details', '详情');
            $show->field('ip', 'IP地址');
            $show->field('user_agent', 'User Agent');
            $show->field('device', '设备');
            $show->field('browser', '浏览器');
            $show->field('os', '操作系统');
            $show->field('url', '访问URL');
            $show->field('referer', '来源URL');
            $show->field('created_at', '操作时间');
        });
    }
}