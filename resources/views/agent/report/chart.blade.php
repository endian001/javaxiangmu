@extends('agent.layouts.agent_template')

@section('content')
<!-- Page Heading -->
<h5>图表统计</h1>
    <p class="mb-4"></p>

    <!-- Content Row -->
    <div class="row">

        <div class="col-xl-8 col-lg-7">

            <!-- Area Chart -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">下级会员资金存取数据汇总</h6>
                </div>
                <div class="card-body">
                    <div class="mt-4a text-center small">
                        <span class="mr-2">
                            <i class="fas fa-circle text-primary"></i> 存款
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-warning"></i> 提款
                        </span>
                        <span class="mr-2">刷新时间：{{date('Y-m-d H:i:s')}}</span>
                    </div>
                    <div class="chart-area">
                        <canvas id="myAreaChart"></canvas>
                    </div>
                    <hr>
                </div>
            </div>

            <!-- Area Chart -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">盈亏报表统计</h6>
                </div>
                <div class="card-body">
                    <div class="mt-4a text-center small">
                        <span class="mr-2">
                            <i class="fas fa-circle text-primary"></i> 存款
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-warning"></i> 提款
                        </span>
                        <span class="mr-2">刷新时间：{{date('Y-m-d H:i:s')}}</span>
                    </div>
                    <div class="chart-area">
                        <canvas id="myAreaChart2"></canvas>
                    </div>
                    <hr>
                </div>
            </div>


        </div>

        <!-- Donut Chart -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <!-- Card Header - Dropdown -->
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">下级会员存取总金额比例</h6>
                </div>
                <!-- Card Body -->
                <div class="card-body">
                    <div class="mt-4a text-center small">
                        <span class="mr-2">刷新时间：{{date('Y-m-d H:i:s')}}</span>
                    </div>
                    <div class="chart-pie pt-4">
                        <canvas id="myPieChart"></canvas>
                    </div>
                    <div style="padding-bottom:16px;"></div>
                    <hr>
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
    @endsection
@section('js')
<script src="/agent/js/laydate/laydate.js"></script>
<script>
    $('#collapseThree').addClass('show');
</script>
@endsection
