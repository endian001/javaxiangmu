@extends('agent.layouts.agent_template')

@section('content')
<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h5>下级会员列表</h5>
</div>
<div class="input-group" style="z-index:999; padding-left:0; padding-bottom:20px;">
    <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search" action="/member">
        <div class="input-group">
            <input type="text" name="username" class="form-control bg-light border-0 small" placeholder="请输入账号..." aria-label="Search" aria-describedby="basic-addon2">
            <div class="input-group-append">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-search fa-sm"></i>
                </button>
            </div>
        </div>
    </form>
</div>
<!-- DataTales Example -->
<div class="card shadow mb-4">

    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">下级会员列表</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive" style="overflow-x: auto; padding: 0 !important;">
            <table class="table table-bordered" id="dataTable" width="1800" cellspacing="0" style="text-align:center">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>账号</th>
                        <th>姓名</th>
                        <th>帐户类型</th>
                        <th>上级代理</th>
                        <th>代理返点比例</th>
                        <th>系统余额</th>
                        <th>游戏余额</th>
                        <th>创建时间</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($list as $item)
                    <tr>
                        <td>{{$item->id}}</td>
                        <td>
                            <div class="dropdown mb-4a">
                                <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    {{$item->username}}
                                </button>
                                <div class="dropdown-menu animated--fade-in" aria-labelledby="dropdownMenuButton">
                                    <a class="dropdown-item" href="/bet-log?username={{$item->username}}">会员下注记录</a>
                                    <a class="dropdown-item" href="/recharge-log?username={{$item->username}}">会员充值记录</a>
                                    <a class="dropdown-item" href="/withdraw-log?username={{$item->username}}">会员提现记录</a>
                                    <a class="dropdown-item" href="/transfer-log?username={{$item->username}}">会员转账记录</a>
                                    <!-- <a class="dropdown-item" href="commission.html">会员下注流水报表</a> -->
                                    <!-- <a class="dropdown-item" href="rebate.html">会员返水记录</a> -->
                                </div>
                            </div>
                        </td>
                        <td>{{$item->realname}}</td>
                        <td>{{$item->isagent == 1 ? '代理' : '会员'}}</td>
                        <td>{{$item->parent}}</td>
                        <td>
                            @if($user->allowagent)
                                {{$item->fanshuifee}}%
                            @if ($item->is_direct)
                            <a class="btn btn-danger btn-icon-split btn-sm" href="/changefanshui?uid={{$item->id}}">
                                <span class="text">设为代理</span></a>
                            @endif
                            @else
                               没有权限
                            @endif
                        </td>

                        <td>{{$item->balance}}
                            <!-- <a class="btn btn-danger btn-icon-split btn-sm" href="#" data-toggle="modal" data-target="#edModal">
                                <span class="text">调整额度</span>
                            </a> -->
                        </td>
                        <td>- <a href="#" class="btn btn-warning btn-icon-split btn-sm">
                                <span class="text">刷新</span>
                            </a>
                        </td>
                        <td>{{$item->created_at}}</td>
                        <td class="text-center">
                            @if($item->status == 1)
                              <span class="label label-success">正常</span>
                            @else
                              <span class="label label-default">禁用</span>
                            @endif
                        </td>
                        <td>
                            <a href="/recharge?user_id={{$item->id}}" class="btn btn-warning btn-icon-split btn-sm"><span class="text">充值</span></a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="col-sm-12 col-md-7">
                <div class="dataTables_paginate paging_simple_numbers">
                    <ul class="pagination">
                        {{$list->links()}}
                    </ul>
                </div>
            </div>
            <!-- Logout Modal-->
            <div class="modal fade" id="dlModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">代理返点比例</h5>
                            <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                        <form class="user">
                            <div class="modal-body">

                                <div class="form-group row">
                                    <div class="col-sm-6a1">
                                        返点比例：
                                    </div>
                                    <div class="col-sm-6b1">
                                        <input type="text" id="fanshui" class="form-control form-control-user" value="0" >
                                    </div>
                                    <div class="col-sm-6c1" style="padding-top:15px;">
                                        &nbsp; %&nbsp;&nbsp; <font color="#f00">不超过0.1%</font>
                                    </div>
                                </div>

                            </div>
                        </form>
                        <div class="modal-footer">
                            <button class="btn btn-primary saveagentfanshui"  type="button">设置为下级代理</button>
                            <button class="btn btn-secondary" type="button" data-dismiss="modal">取消</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
<script src="/agent/js/laydate/laydate.js"></script>
<script>
    var uid=0;
    $('#collapseFour').addClass('show');
    function opendlModal(id){
        uid = id
        $('#dlModal').modal('show')
    }
    $('.saveagentfanshui').click(function(){
        if($('#fanshui').val()<=0){
            alert("请输入正确的数据");
            return false;
        }
        $.ajax({
            type: 'post',
            url: "/changefanshui",
            data : {fanshui:$('#fanshui').val(),uid:uid},
            dataType: "json",
            success: function(data) {
                $('.modal-footer').show();
                if(data.code == 0) {
                    $('.modal-body').html(data.message);
                } else {
                    $('.modal-body').html(data.message);
                }
            }
        });
        })
</script>
@endsection
