<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Template;
use App\Models\Template as ModelsTemplate;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;
use Dcat\Admin\Layout\Column;
use Dcat\Admin\Widgets\Card;

class TemplateController extends AdminController
{

    protected $type = [1 => 'WEB',2 => 'WAP',3 => 'APP'];

    public function index(Content $content)
    {
        $csrf = csrf_token();

        return $content
            ->header('模板管理')
            ->description('')
            ->body(function (Row $row) use ($csrf) {
                $row->column(12, function (Column $column) {
                    $column->row('<a class="btn btn-sm btn-light shadow-none" href="'.admin_url('templates/create').'">新增</a>');
                });

                $row->column(12, function (Column $column) {
                    $column->row('<br><span>正在使用</span>');
                });
                $list = ModelsTemplate::where('state',2)->orderBy('sort','desc')->get();
                foreach ($list as $k => $v) {
                    $row->column(6, function (Column $column) use ($v) {
                        // 标题和内容
                        $card = Card::make($v->name.'-'.$this->type[$v->client_type], "<img src='/uploads/$v->pic' width='300px;' height='400px;'>");
    
                        // 设置工具按钮
                        // $card->tool('<button class="btn btn-sm btn-light shadow-none">按钮</button>');
    
                        // 设置底部内容
                        $card->footer('模板路径:'.$v->template_id);
                        $column->row($card);
                    });
                }
                

                $row->column(12, function (Column $column) {
                    $column->row('<br><span>其它模板</span>');
                });
                $list = ModelsTemplate::where('state','<>',2)->orderBy('sort','desc')->get();
                foreach ($list as $k => $v) {
                    $row->column(6, function (Column $column) use ($v, $csrf) {
                        // 标题和内容
                        $card = Card::make($v->name.'-'.$this->type[$v->client_type], "<img src='/uploads/$v->pic' width='300px;' height='400px;'>");
    
                        // 设置工具按钮
                        $card->tool(
                            '<form method="post" action="'.admin_url("setDefaultTemplate/$v->id/$v->client_type").'" style="display:inline">'.
                            '<input type="hidden" name="_token" value="'.$csrf.'">'.
                            '<button type="submit" class="btn btn-sm btn-light shadow-none" onclick="return confirm(\'确认设为默认模板？\')">设为默认</button>'.
                            '</form>'
                        );
    
                        // 设置底部内容
                        $card->footer('模板路径:'.$v->template_id);
                        $column->row($card);
                    });
                }
            });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Template(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('name');
            $grid->column('pic');
            $grid->column('client_type');
            $grid->column('sort');
            $grid->column('template_id');
            $grid->column('state');
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();
        
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
        
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
        return Show::make($id, new Template(), function (Show $show) {
            $show->field('id');
            $show->field('name');
            $show->field('pic');
            $show->field('client_type');
            $show->field('sort');
            $show->field('template_id');
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
        return Form::make(new Template(), function (Form $form) {
            $form->display('id');
            $form->text('name')->required();
            $form->image('pic')->uniqueName()->required();
            $form->radio('client_type')->options($this->type)->default(1);
            $form->number('sort');
            $form->text('template_id')->required();
            $form->radio('state')->options([1 => '可用',0 => '禁用',2 => '正在使用'])->default(1);

            $form->saving(function (Form $form) {
                if ($form->state == 2) {
                    ModelsTemplate::where('client_type', $form->client_type)
                        ->where('state', 2)
                        ->update(['state' => 1]);
                }
            });
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }

    public function setDefaultTemplate($id,$type)
    {
        $template = ModelsTemplate::where('id', $id)
            ->where('client_type', $type)
            ->firstOrFail();

        ModelsTemplate::where('client_type', $template->client_type)
            ->where('state', 2)
            ->update(['state' => 1]);

        $template->state = 2;
        $template->save();

        admin_success('设置成功');
        return redirect(admin_url('templates'));
    }
}
