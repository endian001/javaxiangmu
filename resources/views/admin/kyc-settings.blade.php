@php
    $bool = function ($value) {
        return (int) $value === 1 ? 'checked' : '';
    };
    $selected = function ($value, $expected) {
        return (string) $value === (string) $expected ? 'selected' : '';
    };
    $stepLabels = [
        1 => 'KYC提醒弹窗',
        2 => '填写身份信息',
        3 => '提交资料身份',
        4 => '等待审核验证',
    ];
    $currentBody = $selectedContent ? $selectedContent->body : '';
@endphp
<div
    class="kyc-settings-page"
    id="kycSettingsPage"
    data-module="{{ $module }}"
    data-field-url="{{ admin_url('tcg/kyc/fields') }}"
    data-rule-url="{{ admin_url('tcg/kyc/rules') }}"
    data-content-url="{{ admin_url('tcg/kyc/content') }}"
    data-upload-url="{{ admin_url('tcg/kyc/upload') }}"
>
    <style>
        .kyc-settings-page { color:#303133; }
        .kyc-panel { background:#fff; border:1px solid #e7ebf0; margin-bottom:16px; box-shadow:0 1px 2px rgba(0,0,0,.03); }
        .kyc-panel-head { display:flex; justify-content:space-between; align-items:center; gap:14px; padding:15px 18px; border-bottom:1px solid #edf0f3; }
        .kyc-panel-head h4 { margin:0; font-size:16px; font-weight:600; }
        .kyc-panel-head p { margin:4px 0 0; color:#8b9098; font-size:12px; }
        .kyc-panel-body { padding:16px 18px; }
        .kyc-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:10px; }
        .kyc-table { margin-bottom:0; min-width:1120px; }
        .kyc-table thead th { background:#f7f8fa; color:#606266; font-weight:600; white-space:nowrap; }
        .kyc-table td { vertical-align:middle!important; }
        .kyc-table input[type="text"], .kyc-table input[type="number"], .kyc-table select { min-width:92px; height:32px; padding:4px 8px; }
        .kyc-table .short { width:78px; min-width:78px; }
        .kyc-muted { color:#8b9098; }
        .kyc-required { color:#d93025; }
        .kyc-badge { display:inline-block; padding:3px 8px; border-radius:3px; background:#eef5ff; color:#1769aa; font-size:12px; }
        .kyc-switch-cell { text-align:center; }
        .kyc-stepbar { display:grid; grid-template-columns:repeat(4, minmax(110px, 1fr)); gap:10px; margin-bottom:16px; }
        .kyc-stepbar a { display:flex; align-items:center; gap:8px; border:1px solid #dfe5ec; padding:10px 12px; color:#606266; background:#fff; }
        .kyc-stepbar a.active { border-color:#1769aa; color:#1769aa; background:#f0f7ff; font-weight:600; }
        .kyc-stepno { display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; border-radius:50%; background:#dfe5ec; color:#606266; }
        .kyc-stepbar a.active .kyc-stepno { background:#1769aa; color:#fff; }
        .kyc-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .kyc-form-grid .full { grid-column:1 / -1; }
        .kyc-form-grid label { display:block; color:#606266; font-size:12px; margin-bottom:5px; }
        .kyc-rule-tabs { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:15px; }
        .kyc-rule-tabs a, .kyc-rule-tabs button { border:1px solid #dfe5ec; background:#fff; color:#606266; padding:8px 12px; }
        .kyc-rule-tabs a.active { border-color:#1769aa; color:#1769aa; background:#f0f7ff; font-weight:600; }
        .kyc-preview { border:1px dashed #cfd7df; padding:18px; min-height:180px; background:#fafbfc; }
        .kyc-preview h4 { margin-top:0; }
        .kyc-mask-list { max-height:520px; overflow:auto; border:1px solid #edf0f3; }
        .kyc-mask-row { display:grid; grid-template-columns:1.2fr 1fr 1fr 120px; gap:10px; padding:10px 12px; border-bottom:1px solid #edf0f3; align-items:center; }
        .kyc-mask-row:last-child { border-bottom:none; }
        @media (max-width: 960px) {
            .kyc-form-grid, .kyc-stepbar { grid-template-columns:1fr; }
            .kyc-panel-head { align-items:flex-start; flex-direction:column; }
            .kyc-mask-row { grid-template-columns:1fr; }
        }
    </style>

    <div class="kyc-panel">
        <div class="kyc-panel-head">
            <div>
                <h4>{{ $page['title'] }}</h4>
                <p>{{ $page['summary'] }}</p>
            </div>
            @if($module === 'fields')
                <div class="kyc-toolbar">
                    <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#kycMaskModal">前台安全设置</button>
                    <a class="btn btn-info btn-sm" href="#kycFieldTable">栏位设置</a>
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#kycFieldModal">
                        <i class="fa fa-plus"></i> 添加信息栏
                    </button>
                </div>
            @elseif($module === 'rules')
                <div class="kyc-toolbar">
                    <span class="kyc-badge">KYC验证开关</span>
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#kycRuleModal">
                        <i class="fa fa-plus"></i> 添加标签页
                    </button>
                </div>
            @else
                <div class="kyc-toolbar">
                    <button type="button" class="btn btn-default btn-sm js-content-copy">复制Web</button>
                    <button type="button" class="btn btn-warning btn-sm js-content-default">恢复为默认模版</button>
                </div>
            @endif
        </div>
    </div>

    @if($module === 'fields')
        <div class="kyc-panel" id="kycFieldTable">
            <div class="kyc-panel-body">
                <h4>再次栏位管理</h4>
                <p class="kyc-muted">此处为设置后台-用户管理/玩家用户详情/个人信息、前台-会员中心个人资料及前台-身份验证的栏位。前台玩家修改个人资料 24 小时后才可再次修改。</p>
            </div>
            <div class="table-responsive">
                <table class="table table-hover kyc-table">
                    <thead>
                    <tr>
                        <th>栏位ID</th>
                        <th>预设显示名称</th>
                        <th>自订显示名称</th>
                        <th>栏位类型</th>
                        <th>栏位属性</th>
                        <th>KYC认证</th>
                        <th>显示于前台</th>
                        <th>必填</th>
                        <th>玩家可修改</th>
                        <th>信息唯一性</th>
                        <th>格式</th>
                        <th>字元</th>
                        <th>打码</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($fields as $field)
                        <tr class="js-field-row" data-id="{{ $field->id }}" data-system="{{ (int) $field->is_system }}">
                            <td>
                                <input class="form-control js-field-input" data-name="field_key" value="{{ $field->field_key }}" {{ $field->is_system ? 'disabled' : '' }}>
                            </td>
                            <td>
                                <input class="form-control js-field-input" data-name="default_label" value="{{ $field->default_label }}" {{ $field->is_system ? 'disabled' : '' }}>
                            </td>
                            <td><input class="form-control js-field-input" data-name="custom_label" value="{{ $field->custom_label }}"></td>
                            <td>
                                <select class="form-control js-field-input" data-name="category">
                                    <option value="identity" {{ $selected($field->category, 'identity') }}>身份信息</option>
                                    <option value="social" {{ $selected($field->category, 'social') }}>社交信息</option>
                                </select>
                            </td>
                            <td>
                                <select class="form-control js-field-input" data-name="input_type">
                                    <option value="input" {{ $selected($field->input_type, 'input') }}>输入框</option>
                                    <option value="select" {{ $selected($field->input_type, 'select') }}>下拉</option>
                                    <option value="date" {{ $selected($field->input_type, 'date') }}>日期选择器</option>
                                </select>
                            </td>
                            @foreach(['kyc_enabled', 'frontend_visible', 'required', 'player_editable', 'unique_value'] as $checkField)
                                <td class="kyc-switch-cell">
                                    <input type="checkbox" class="js-field-input" data-name="{{ $checkField }}" value="1" {{ $bool($field->{$checkField}) }}>
                                </td>
                            @endforeach
                            <td>
                                <select class="form-control js-field-input" data-name="format_rule">
                                    <option value="any" {{ $selected($field->format_rule, 'any') }}>任何</option>
                                    <option value="email" {{ $selected($field->format_rule, 'email') }}>标准电子邮件格式</option>
                                    <option value="date" {{ $selected($field->format_rule, 'date') }}>yyyy-MM-dd</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" class="form-control short js-field-input" data-name="min_length" min="0" max="255" value="{{ $field->min_length }}"> ~
                                <input type="number" class="form-control short js-field-input" data-name="max_length" min="0" max="255" value="{{ $field->max_length }}">
                            </td>
                            <td>
                                <select class="form-control js-field-input js-mask-mode" data-name="mask_mode">
                                    <option value="plain" {{ $selected($field->mask_mode, 'plain') }}>明文</option>
                                    <option value="partial" {{ $selected($field->mask_mode, 'partial') }}>部分打码</option>
                                    <option value="masked" {{ $selected($field->mask_mode, 'masked') }}>打码</option>
                                </select>
                            </td>
                            <td class="kyc-switch-cell"><input type="checkbox" class="js-field-input" data-name="status" value="1" {{ $bool($field->status) }}></td>
                            <td>
                                <button type="button" class="btn btn-primary btn-xs js-field-save">提交</button>
                                @if(!$field->is_system)
                                    <button type="button" class="btn btn-danger btn-xs js-field-delete">删除</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="15" class="text-center kyc-muted">暂无栏位</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="kycMaskModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">安全设置 <small>未添加字段默认打码显示</small></h4></div>
                    <div class="modal-body">
                        <div class="kyc-toolbar" style="margin-bottom:12px">
                            <strong>前台会员中心明文显示字段</strong>
                            <button type="button" class="btn btn-default btn-xs" data-dismiss="modal" data-toggle="modal" data-target="#kycFieldModal">
                                <i class="fa fa-plus"></i> 添加字段
                            </button>
                        </div>
                        <div class="kyc-mask-list">
                            @foreach($fields as $field)
                                <div class="kyc-mask-row" data-id="{{ $field->id }}">
                                    <div><strong>{{ $field->field_key }}</strong><br><span class="kyc-muted">{{ $field->custom_label ?: $field->default_label }}</span></div>
                                    <div>{{ $field->category === 'social' ? '社交信息' : '身份信息' }}</div>
                                    <div class="kyc-muted">
                                        @if($field->mask_mode === 'plain')
                                            样本值12345
                                        @elseif($field->mask_mode === 'partial')
                                            1990-01-**
                                        @else
                                            S***************
                                        @endif
                                    </div>
                                    <select class="form-control js-mask-select">
                                        <option value="plain" {{ $selected($field->mask_mode, 'plain') }}>明文</option>
                                        <option value="partial" {{ $selected($field->mask_mode, 'partial') }}>部分打码</option>
                                        <option value="masked" {{ $selected($field->mask_mode, 'masked') }}>打码</option>
                                    </select>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                        <button type="button" class="btn btn-primary js-mask-save">提交</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="kycFieldModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">添加信息栏</h4></div>
                    <div class="modal-body kyc-form-grid">
                        <div><label><span class="kyc-required">*</span>栏位ID</label><input id="newFieldKey" class="form-control" placeholder="passport_number"></div>
                        <div><label><span class="kyc-required">*</span>预设显示名称</label><input id="newFieldLabel" class="form-control" placeholder="护照号码"></div>
                        <div><label>栏位类型</label><select id="newFieldCategory" class="form-control"><option value="identity">身份信息</option><option value="social">社交信息</option></select></div>
                        <div><label>栏位属性</label><select id="newFieldInputType" class="form-control"><option value="input">输入框</option><option value="select">下拉</option><option value="date">日期选择器</option></select></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">取消</button><button type="button" class="btn btn-primary js-field-create">保存</button></div>
                </div>
            </div>
        </div>
    @elseif($module === 'rules')
        <div class="kyc-panel">
            <div class="kyc-panel-body">
                <div class="kyc-rule-tabs">
                    @foreach($rules as $rule)
                        <a class="{{ $selectedRule && (int) $selectedRule->id === (int) $rule->id ? 'active' : '' }}" href="{{ admin_url('tcg/290000') }}?group={{ $rule->id }}">
                            {{ $rule->name }}{{ $rule->is_default ? '（默认）' : '' }}
                        </a>
                    @endforeach
                    <button type="button" data-toggle="modal" data-target="#kycRuleModal"><i class="fa fa-plus"></i> 添加标签页</button>
                </div>

                @if($selectedRule)
                    <form class="js-rule-form" data-id="{{ $selectedRule->id }}">
                        <input type="hidden" name="name" value="{{ $selectedRule->name }}">
                        <input type="hidden" name="is_default" value="{{ (int) $selectedRule->is_default }}">
                        @foreach(['enabled','force_enabled','tag_internal','tag_operation','scenario_login','scenario_deposit','scenario_withdraw','scenario_game','require_id_type','require_id_number','require_withdraw_name','require_document_images'] as $hiddenBool)
                            <input type="hidden" name="{{ $hiddenBool }}" value="0">
                        @endforeach
                        <div class="kyc-form-grid">
                            <div class="full">
                                <label>KYC验证开关</label>
                                <label class="radio-inline"><input type="checkbox" name="enabled" value="1" {{ $bool($selectedRule->enabled) }}> 启用</label>
                            </div>
                            <div class="full">
                                <label>验证方式</label>
                                <label class="radio-inline"><input type="radio" name="review_mode" value="manual" {{ $selected($selectedRule->review_mode, 'manual') ? 'checked' : '' }}> 人工审核(免费)</label>
                                <label class="radio-inline"><input type="radio" name="review_mode" value="automatic" {{ $selected($selectedRule->review_mode, 'automatic') ? 'checked' : '' }} disabled> 自动审核(付费)</label>
                                <p class="kyc-muted">说明：人工审核用户身份验证文件；自动审核需接入付费供应商后启用。</p>
                            </div>
                            <div class="full">
                                <h5>强制KYC认证设置</h5>
                                <label class="checkbox-inline"><input type="checkbox" name="force_enabled" value="1" {{ $bool($selectedRule->force_enabled) }}> 启用强制 KYC</label>
                                <label class="checkbox-inline"><input type="checkbox" name="tag_internal" value="1" {{ $bool($selectedRule->tag_internal) }}> 内部体系</label>
                                <label class="checkbox-inline"><input type="checkbox" name="tag_operation" value="1" {{ $bool($selectedRule->tag_operation) }}> 运营标签</label>
                                <p class="kyc-muted">勾选标签特定：仅针对符合该标签的玩家进行强制 KYC；未勾选任何标签视为所有玩家。</p>
                            </div>
                            <div class="full">
                                <h5>具体设置</h5>
                                <label>验证场景</label>
                                <label class="checkbox-inline"><input type="checkbox" name="scenario_login" value="1" {{ $bool($selectedRule->scenario_login) }}> 登录后</label>
                                <label class="checkbox-inline"><input type="checkbox" name="scenario_deposit" value="1" {{ $bool($selectedRule->scenario_deposit) }}> 充值前</label>
                                <label class="checkbox-inline"><input type="checkbox" name="scenario_withdraw" value="1" {{ $bool($selectedRule->scenario_withdraw) }}> 提现前</label>
                                <label class="checkbox-inline"><input type="checkbox" name="scenario_game" value="1" {{ $bool($selectedRule->scenario_game) }}> 启动游戏前</label>
                            </div>
                            <div class="full">
                                <label>KYC基础信息</label>
                                <label class="checkbox-inline"><input type="checkbox" name="require_id_type" value="1" {{ $bool($selectedRule->require_id_type) }}> 身份证类型</label>
                                <label class="checkbox-inline"><input type="checkbox" name="require_id_number" value="1" {{ $bool($selectedRule->require_id_number) }}> 身份证号码</label>
                                <label class="checkbox-inline"><input type="checkbox" name="require_withdraw_name" value="1" {{ $bool($selectedRule->require_withdraw_name) }}> 提款人姓名</label>
                            </div>
                            <div>
                                <label>审核内容</label>
                                <label class="checkbox-inline"><input type="checkbox" name="require_document_images" value="1" {{ $bool($selectedRule->require_document_images) }}> 身份证件图片</label>
                            </div>
                            <div>
                                <label>身份证图片上传数量张</label>
                                <input type="number" name="image_count" class="form-control" min="1" max="6" value="{{ $selectedRule->image_count }}">
                            </div>
                            <div class="full">
                                <label>图片位置标题</label>
                                @for($i = 0; $i < 6; $i++)
                                    <input name="image_titles[]" class="form-control" style="display:inline-block;width:120px;margin-right:8px;margin-bottom:8px" value="{{ $selectedRule->image_title_list[$i] ?? '' }}" placeholder="front">
                                @endfor
                            </div>
                        </div>
                        <div class="kyc-toolbar" style="margin-top:16px">
                            <button type="button" class="btn btn-default" onclick="window.location.reload()">关闭 取消</button>
                            <button type="submit" class="btn btn-primary"><i class="fa fa-eye"></i> 提交</button>
                            @if(!$selectedRule->is_default)
                                <button type="button" class="btn btn-danger js-rule-delete">删除标签页</button>
                            @endif
                        </div>
                    </form>
                @endif
            </div>
        </div>

        <div class="modal fade" id="kycRuleModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">新增</h4></div>
                    <div class="modal-body"><label><span class="kyc-required">*</span>名称：</label><input id="newRuleName" class="form-control"></div>
                    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">取消</button><button type="button" class="btn btn-primary js-rule-create">保存</button></div>
                </div>
            </div>
        </div>
    @else
        <div class="kyc-panel">
            <div class="kyc-panel-body">
                <div class="kyc-stepbar">
                    @foreach($stepLabels as $stepNo => $stepLabel)
                        <a class="{{ (int) $step === $stepNo ? 'active' : '' }}" href="{{ admin_url('tcg/290004') }}?platform={{ $platform }}&language={{ $language }}&step={{ $stepNo }}">
                            <span class="kyc-stepno">{{ $stepNo }}</span><span>{{ $stepLabel }}</span>
                        </a>
                    @endforeach
                </div>

                <form class="js-content-form">
                    <input type="hidden" name="step" value="{{ $step }}">
                    <div class="kyc-form-grid">
                        <div>
                            <label>平台</label>
                            <select name="platform" class="form-control js-content-jump">
                                <option value="mobile" {{ $selected($platform, 'mobile') }}>移动的</option>
                                <option value="web" {{ $selected($platform, 'web') }}>Web</option>
                                <option value="app" {{ $selected($platform, 'app') }}>APP</option>
                            </select>
                        </div>
                        <div>
                            <label>语系</label>
                            <input name="language" class="form-control js-content-jump" value="{{ $language }}" maxlength="10" placeholder="EN">
                        </div>
                        <div class="full">
                            <label>添加背景图片</label>
                            <div class="input-group">
                                <input name="background_image" id="contentBackgroundImage" class="form-control" value="{{ $selectedContent->background_image ?? '' }}">
                                <span class="input-group-btn"><button type="button" class="btn btn-default js-upload-open"><i class="fa fa-upload"></i> 添加图片</button></span>
                            </div>
                            <input type="file" id="kycContentImage" accept="image/*" style="display:none">
                        </div>
                        <div class="full">
                            <label><span class="kyc-required">*</span>标题</label>
                            <input name="title" class="form-control" value="{{ $selectedContent->title ?? '' }}" placeholder="请输入">
                        </div>
                        <div class="full">
                            <label>内容</label>
                            <textarea name="body" rows="8" class="form-control" placeholder="请输入前台展示内容">{{ $currentBody }}</textarea>
                        </div>
                        <div>
                            <label>是否强制验证</label>
                            <input type="hidden" name="force_verify" value="0">
                            <label class="checkbox-inline"><input type="checkbox" name="force_verify" value="1" {{ $bool($selectedContent->force_verify ?? 0) }}> 启用</label>
                        </div>
                        <div>
                            <label>状态</label>
                            <input type="hidden" name="status" value="0">
                            <label class="checkbox-inline"><input type="checkbox" name="status" value="1" {{ $bool($selectedContent->status ?? 1) }}> 启用</label>
                        </div>
                        <div>
                            <label>按钮文案</label>
                            <input name="secondary_button_text" class="form-control" value="{{ $selectedContent->secondary_button_text ?? '' }}" placeholder="暂未验证">
                        </div>
                        <div>
                            <label><span class="kyc-required">*</span>按钮文案</label>
                            <input name="button_text" class="form-control" value="{{ $selectedContent->button_text ?? '' }}" placeholder="立即验证">
                        </div>
                        <div class="full">
                            <h3>款式预览</h3>
                            <div class="kyc-preview">
                                <h4>{{ $selectedContent->title ?? $stepLabels[$step] }}</h4>
                                <p>{{ strip_tags($selectedContent->body ?? '请配置前台 KYC 内容。') }}</p>
                                <button type="button" class="btn btn-primary">{{ $selectedContent->button_text ?? 'Verify Now' }}</button>
                            </div>
                        </div>
                    </div>
                    <div class="kyc-toolbar" style="margin-top:16px">
                        <button type="button" class="btn btn-default" onclick="window.location.reload()">关闭 取消</button>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-eye"></i> 提交</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

<script>
(function ($) {
    var page = $('#kycSettingsPage');
    if (!page.length) return;
    var token = '{{ csrf_token() }}';

    function notify(type, message) {
        if (window.Dcat && typeof window.Dcat[type] === 'function') {
            window.Dcat[type](message);
            return;
        }
        window.alert(message);
    }

    function errorMessage(xhr) {
        var response = xhr.responseJSON || {};
        if (response.errors) {
            var key = Object.keys(response.errors)[0];
            if (key && response.errors[key] && response.errors[key][0]) {
                return response.errors[key][0];
            }
        }
        return response.message || '操作失败，请检查填写内容后重试。';
    }

    function ajax(options) {
        return $.ajax($.extend(true, {
            headers: {'X-CSRF-TOKEN': token},
            dataType: 'json'
        }, options)).fail(function (xhr) {
            notify('error', errorMessage(xhr));
        });
    }

    function reloadAfter(message) {
        notify('success', message);
        window.setTimeout(function () { window.location.reload(); }, 600);
    }

    function collectFieldRow(row) {
        var data = {};
        row.find('.js-field-input').each(function () {
            var input = $(this);
            var name = input.data('name');
            if (!name) return;
            if (input.attr('type') === 'checkbox') {
                data[name] = input.is(':checked') ? 1 : 0;
            } else if (!input.prop('disabled')) {
                data[name] = input.val();
            }
        });
        return data;
    }

    page.on('click', '.js-field-save', function () {
        var button = $(this).prop('disabled', true);
        var row = button.closest('.js-field-row');
        ajax({
            url: page.data('field-url') + '/' + row.data('id'),
            method: 'PUT',
            data: collectFieldRow(row)
        }).done(function (response) {
            if (response.status) reloadAfter(response.message);
        }).always(function () {
            button.prop('disabled', false);
        });
    });

    page.on('click', '.js-field-create', function () {
        var button = $(this).prop('disabled', true);
        ajax({
            url: page.data('field-url'),
            method: 'POST',
            data: {
                field_key: $('#newFieldKey').val(),
                default_label: $('#newFieldLabel').val(),
                category: $('#newFieldCategory').val(),
                input_type: $('#newFieldInputType').val(),
                format_rule: 'any',
                mask_mode: 'masked',
                min_length: 0,
                max_length: 255,
                status: 1
            }
        }).done(function (response) {
            if (response.status) reloadAfter(response.message);
        }).always(function () {
            button.prop('disabled', false);
        });
    });

    page.on('click', '.js-field-delete', function () {
        var button = $(this);
        var row = button.closest('.js-field-row');
        if (!window.confirm('确认删除这个自订栏位吗？')) return;
        button.prop('disabled', true);
        ajax({
            url: page.data('field-url') + '/' + row.data('id'),
            method: 'DELETE'
        }).done(function (response) {
            if (response.status) reloadAfter(response.message);
        }).always(function () {
            button.prop('disabled', false);
        });
    });

    page.on('click', '.js-mask-save', function () {
        var button = $(this).prop('disabled', true);
        var calls = [];
        $('.kyc-mask-row').each(function () {
            var row = $(this);
            calls.push(ajax({
                url: page.data('field-url') + '/' + row.data('id'),
                method: 'PUT',
                data: {mask_mode: row.find('.js-mask-select').val()}
            }));
        });
        $.when.apply($, calls).done(function () {
            reloadAfter('前台安全设置已保存');
        }).always(function () {
            button.prop('disabled', false);
        });
    });

    page.on('click', '.js-rule-create', function () {
        var button = $(this).prop('disabled', true);
        ajax({
            url: page.data('rule-url'),
            method: 'POST',
            data: {
                name: $('#newRuleName').val(),
                review_mode: 'manual',
                image_count: 6,
                image_titles: ['front', 'back', 'third', 'fourth', 'fifth', 'sixth']
            }
        }).done(function (response) {
            if (response.status) reloadAfter(response.message);
        }).always(function () {
            button.prop('disabled', false);
        });
    });

    page.on('submit', '.js-rule-form', function (event) {
        event.preventDefault();
        var form = $(this);
        var id = form.data('id');
        var button = form.find('[type=submit]').prop('disabled', true);
        ajax({
            url: page.data('rule-url') + '/' + id,
            method: 'PUT',
            data: form.serialize()
        }).done(function (response) {
            if (response.status) reloadAfter(response.message);
        }).always(function () {
            button.prop('disabled', false);
        });
    });

    page.on('click', '.js-rule-delete', function () {
        var form = $(this).closest('.js-rule-form');
        if (!window.confirm('确认删除这个 KYC 标签页吗？')) return;
        ajax({
            url: page.data('rule-url') + '/' + form.data('id'),
            method: 'DELETE'
        }).done(function (response) {
            if (response.status) reloadAfter(response.message);
        });
    });

    page.on('submit', '.js-content-form', function (event) {
        event.preventDefault();
        var form = $(this);
        var button = form.find('[type=submit]').prop('disabled', true);
        ajax({
            url: page.data('content-url'),
            method: 'POST',
            data: form.serialize()
        }).done(function (response) {
            if (response.status) reloadAfter(response.message);
        }).always(function () {
            button.prop('disabled', false);
        });
    });

    page.on('change', '.js-content-jump', function () {
        var form = $('.js-content-form');
        var url = '{{ admin_url('tcg/290004') }}'
            + '?platform=' + encodeURIComponent(form.find('[name=platform]').val())
            + '&language=' + encodeURIComponent(form.find('[name=language]').val())
            + '&step=' + encodeURIComponent(form.find('[name=step]').val());
        window.location.href = url;
    });

    page.on('click', '.js-upload-open', function () {
        $('#kycContentImage').trigger('click');
    });

    page.on('change', '#kycContentImage', function () {
        if (!this.files || !this.files[0]) return;
        var data = new FormData();
        data.append('file', this.files[0]);
        ajax({
            url: page.data('upload-url'),
            method: 'POST',
            data: data,
            processData: false,
            contentType: false
        }).done(function (response) {
            if (response.status) {
                $('#contentBackgroundImage').val(response.data.url || response.data.path);
                notify('success', response.message);
            }
        });
    });

    page.on('click', '.js-content-copy', function () {
        var form = $('.js-content-form');
        var text = location.origin + '/m/home?kyc_preview_step=' + form.find('[name=step]').val()
            + '&lang=' + encodeURIComponent(form.find('[name=language]').val());
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function () {
                notify('success', 'Web 预览地址已复制');
            });
        } else {
            window.prompt('复制Web', text);
        }
    });

    page.on('click', '.js-content-default', function () {
        var form = $('.js-content-form');
        var step = String(form.find('[name=step]').val());
        var defaults = {
            '1': ['Verify Your Identity', 'Dear user, your identity verification is not yet complete. Please submit the relevant information to complete the identity verification process.', 'Verify Now', 'Not verified yet'],
            '2': ['Identity Information', 'Please complete the required identity information.', 'Next', 'Back'],
            '3': ['Submit Documents', 'Upload clear identity documents and confirm the submitted information.', 'Submit', 'Back'],
            '4': ['Pending Review', 'Your identity verification is under review. Please wait for the result.', 'Confirm', '']
        };
        var data = defaults[step];
        if (!data) return;
        form.find('[name=title]').val(data[0]);
        form.find('[name=body]').val(data[1]);
        form.find('[name=button_text]').val(data[2]);
        form.find('[name=secondary_button_text]').val(data[3]);
    });
})(jQuery);
</script>
