<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\AgentApply\Pass;
use App\Admin\Actions\Grid\AgentApply\Refuse;
use App\Admin\Controllers\Concerns\ReadOnlyResource;
use App\Admin\Repositories\AgentApply;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Admin;

class AgentApplyController extends AdminController
{
    use ReadOnlyResource;

    protected $state = [1 => '待审核',2 => '通过',3 => '拒绝'];
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        //Admin::js('/admin/js/agent_apply.js');
        return Grid::make(new AgentApply(['user']), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->column('id')->sortable();
            $grid->column('user.username','用户名');
            $grid->column('apply_info');
            $grid->column('state')->using($this->state);
            $grid->column('created_at');
            
            $grid->disableCreateButton();
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user.username','用户名');
                $filter->between('created_at')->datetime();
            });
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableEdit();
                $actions->disableView();
                if ($actions->row->state == 1) {
                    $actions->append(new Pass());
                    $actions->append(new Refuse());
                }
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
        return Show::make($id, new AgentApply(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('apply_info');
            $show->field('state');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new AgentApply(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('apply_info');
            $form->text('state');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
