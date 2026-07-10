@extends('agent.layouts.agent_template')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h5>下级会员下注记录</h5>
</div>

<div class="input-group" style="z-index:999; padding-left:0; padding-bottom:20px;">
    <!-- <a href="#" class="btn btn-success btn-icon-split">
        <span class="text">最近3天</span>
    </a>
    &nbsp;&nbsp;
    <a href="#" class="btn btn-success btn-icon-split">
        <span class="text">最近7天</span>
    </a>
    &nbsp;&nbsp;
    <a href="#" class="btn btn-success btn-icon-split">
        <span class="text">最近30天</span>
    </a> -->
</div>


<div class="input-group" style="z-index:999; padding-left:0; padding-bottom:20px;">
    <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search" action="/bet-log">
        <div class="input-group">
            <input type="text" class="form-control bg-light border-0 small" placeholder="请选择开始时间" name="start" id="start1">&nbsp;&nbsp;
            <input type="text" class="form-control bg-light border-0 small" placeholder="请选择结束时间" name="end" id="end1">&nbsp;&nbsp;
            <input type="text" class="form-control bg-light border-0 small" placeholder="请输入账号..." name="username" aria-label="Search" aria-describedby="basic-addon2">
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
        <h6 class="m-0 font-weight-bold text-primary">下级会员下注记录</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>下注单号</th>
                        <th>玩家账号</th>
                        <th>平台</th>
                        <th>游戏</th>
                        <th>下注时间</th>
                        <th>下注金额</th>
                        <th>实际下注金额</th>
                        <th>输赢金额</th>
                        <th>下注详情</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($list as $item)
                    <tr>
                        <td>{{$item->id}}</td>
                        <td>{{$item->bet_id}}</td>
                        <td>{{$item->username}}</td>
                        <td>{{$item->platform_type}}</td>
                        <td>{{$item->game_type}}</td>
                        <td>{{$item->bet_time}}</td>
                        <td>{{$item->bet_amount}}</td>
                        <td>{{$item->valid_amount}}</td>
                        <td>{{$item->win_loss}}</td>
                        <td>
                            @switch($item->status)
                                @case(1)
                                已结算
                                @break
                                @case(2)
                                未结算
                                @break
                                @case(0)
                                无效注单
                                @break
                            @endswitch
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

        </div>
    </div>
</div>
@endsection
@section('js')
<script src="/agent/js/laydate/laydate.js"></script>
<script>
    $('#collapseFour').addClass('show');
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