@extends('agent.layouts.agent_template')

@section('content')
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary mb-4">
            今日概况
        </h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>下级会员总数</th>
                        <th>下级代理总数</th>
                        <th>直属会员数</th>
                        <th>下级直属代理数</th>
                        <th>今日新增会员数</th>
                        <th>今日总存款</th>
                        <th>今日总提款</th>
                        <th>今日投注</th>
                        <th>今日有效投注</th>
                        <th>今日输赢</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>{{$child_member_count}}</td>
                        <td>{{$child_agent_count}}</td>
                        <td>{{$directly_member_count}}</td>
                        <td>{{$directly_agent_count}}</td>
                        <td>{{$add_member_count}}</td>
                        <td>{{$all_recharge}}</td>
                        <td>{{$all_withdraw}}</td>
                        <td>{{$all_bet}}</td>
                        <td>{{$all_valid_bet}}</td>
                        <td>{{$win_loss}}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
@section('js')
<script src="/agent/js/laydate/laydate.js"></script>
<script>
    $('#collapseThree').addClass('show');
</script>
@endsection