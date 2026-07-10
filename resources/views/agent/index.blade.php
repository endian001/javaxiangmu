@extends('agent.layouts.agent_template')

@section('content')
<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h5>代理概况</h5>

</div>

<!-- Content Row -->
<div class="row">

    <!-- Earnings (Monthly) Card Example -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">下级会员充值总额</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{$all_recharge}}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Earnings (Monthly) Card Example -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">下级会员提现总额</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{$all_withdraw}}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Earnings (Monthly) Card Example -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">下级会员总投注</div>
                        <div class="row no-gutters align-items-center">
                            <div class="col-auto">
                                <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">{{$all_valid_bet}}</div>
                            </div>
                            <div class="col">
                                <div class="progress progress-sm mr-2">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: 50%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Requests Card Example -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">下级会员返水总额</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{$all_win_loss}}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-comments fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Content Row -->

<div class="row">

    <!-- Area Chart -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <!-- Card Header - Dropdown -->
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">下级会员资金存取数据汇总</h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                        <div class="dropdown-header">存取投注记录</div>
                        <a class="dropdown-item" href="#">下级会员充值记录</a>
                        <a class="dropdown-item" href="#">下级会员提现记录</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#">下级会员下注记录</a>
                    </div>
                </div>
            </div>
            <!-- Card Body -->
            <div class="card-body">
                <div class="mt-4a text-center small">
                    <span class="mr-2">
                        <i class="fas fa-circle text-primary"></i> 存款
                    </span>
                    <span class="mr-2">
                        <i class="fas fa-circle text-warning"></i> 提款
                    </span>
                    <span class="mr-2">刷新时间：2020-08-18 00:00:00</span>
                </div>
                <div class="chart-area">
                    <canvas id="myAreaChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Pie Chart -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <!-- Card Header - Dropdown -->
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">下级会员存取总金额比例</h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                        <div class="dropdown-header">统计报表</div>
                        <a class="dropdown-item" href="#">盈亏报表</a>
                        <a class="dropdown-item" href="#">佣金报表</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#">下级代理佣金报表</a>
                    </div>
                </div>
            </div>
            <!-- Card Body -->
            <div class="card-body">
                <div class="mt-4a text-center small">
                    <span class="mr-2">刷新时间：2020-08-18 00:00:00</span>
                </div>
                <div class="chart-pie pt-4 pb-2">
                    <canvas id="myPieChart"></canvas>
                </div>
                <div class="mt-4 text-center small">
                    <span class="mr-2">
                        <i class="fas fa-circle text-primary"></i> 利润
                    </span>
                    <span class="mr-2">
                        <i class="fas fa-circle text-success"></i> 总支出
                    </span>
                    <span class="mr-2">
                        <i class="fas fa-circle text-info"></i> 总收入
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Content Row -->
<div class="row">

    <!-- Content Column -->
    <div class="col-lg-6 mb-4">

        <!-- Project Card Example -->
        <!-- Approach -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">最新公告</h6>
            </div>
            <div class="card-body">
                @foreach($list as $item)
                    <div style="padding-bottom:12px; padding-top:3px;">
                        <div class="text-truncate"><i class="fas fa-info-circle"></i>
                            <a href="/notice_detail/{{$item->id}}" rel="nofollow" target="_blank" title="{{$item->title}}">{{$item->title}}</a></div>
                        <div class="small text-gray-500" style="text-align:left; padding-left:20px;"> {{$item->created_at}}</div>
                    </div>
                @endforeach

            </div>
        </div>

    </div>

    <div class="col-lg-6 mb-4">

        <!-- Illustrations -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">代理推广</h6>

            </div>
            <div class="card-body">
                <p>尊敬的代理 <font color="#f00">{{$user->username}}</font>，欢迎您的加入！</p>
                <p>您的推荐码：<font color="burlywood"><b>{{$user->id}}</b></font>
                </p>
                <p>电脑端链接：<font color="burlywood"><b>{{$pc_url}}</b></font>
                </p>
                <p>手机端链接：<font color="burlywood"><b>{{$wap_url}}</b></font>
                </p>
                <div class="text-center">
                    <img class="img-fluid px-3 px-sm-4 mt-3 mb-4" style="width: 10rem;" id="girlImg" src="/uploads/agent/qrcode/{{$user->id}}.png" alt="我的推广二维码">
                    <a href="/download-qrcode" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-download fa-sm text-white-50"></i> 保存我的推广二维码</a>

                </div>
            </div>
        </div>
    </div>
</div>

@endsection
