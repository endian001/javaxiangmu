@extends('agent.layouts.agent_template')

@section('content')
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary mb-4">
        下级代理佣金报表
        </h6>
        <div class="input-group" style="z-index:999; padding-left:0; padding-bottom:20px;">
            <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search" action="{{url('/profit')}}" method="get">
                <div class="input-group">
                    <input type="text" class="form-control border-0 small" placeholder="请选择开始时间" id="start1" name="start" value="{{$start}}">&nbsp;&nbsp;
                    <input type="text" class="form-control border-0 small" placeholder="请选择结束时间" id="end1" name="end" value="{{$end}}">&nbsp;&nbsp;
                    <input type="text" class="form-control border-0 small" name="username" placeholder="请输入用户名..." aria-label="Search" aria-describedby="basic-addon2" value="{{$username}}">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search fa-sm">
                            </i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                <tr>
                    <th rowspan="2" style="vertical-align: middle;">账号</th>
                    <th rowspan="2" style="vertical-align: middle;">姓名</th>
                    <th rowspan="2" style="vertical-align: middle;">模式</th>
                    <th colspan="2">下级人数</th>
                    <th colspan="2">下级金流</th>
                    <th colspan="3">下级红利</th>
                    <th rowspan="2"  style="vertical-align: middle;">下级有效投注总金额</th>
                    <th rowspan="2"  style="vertical-align: middle;">下级输赢总金额</th>
                    <th rowspan="2" style="vertical-align: middle;">佣金</th>
                    <th rowspan="2" style="vertical-align: middle;">实际佣金</th>
                </tr>
                <tr>
                    <th>总人数</th>
                    <th>总代理数</th>
                    <th>总存款次数</th>
                    <th>总提款次数</th>
                    <th>总存款金额</th>
                    <th>总红包</th>
                    <th>总反水</th>
                </tr>
                </thead>
                <tbody>
                    @foreach($list as $item)
                    <tr>
                        <td>{{$item->username}}</td>
                        <td>{{$item->realname}}</td>
                        <td>{{$item->isagent == 1 ? '代理' : '会员'}}</td>
                        <td>{{$item->usersum}}</td>
                        <td>{{$item->agentsum}}</td>
                        <td>{{$item->rechage_times}}</td>
                        <td>{{$item->withdraw_times}}</td>
                        <td>{{$item->all_recharge}}</td>
                        <td>{{$item->all_redpacket}}</td>
                        <td>{{$item->all_fanshui}}</td>
                        <td>{{$item->all_valid_bet}}</td>
                        <td>{{$item->all_win_loss}}</td>
                        <td>{{$item->yongjinsum}}</td>
                        <td>{{$item->yongjinsum}}</td>
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
        </div>
    </div>
</div>

@endsection
@section('js')
<script src="/agent/js/laydate/laydate.js"></script>
<script>
    $('#collapseThree').addClass('show');
</script>
<script>
    lay('#version').html('-v' + laydate.v);

    //执行一个laydate实例
    laydate.render({
        elem: '#start1' //指定元素
    });

    laydate.render({
        elem: '#end1' //指定元素
    });
</script>
@endsection
