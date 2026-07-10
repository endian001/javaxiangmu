@extends('agent.layouts.agent_template')

@section('content')

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h5>公告信息</h5>
</div>

<!-- DataTales Example -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">公告内容</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <tbody>
                    <tr>
                        <td align="center"><b>{{$item->title}}</b></td>
                    </tr>
                    <tr>
                        <td align="center">发布时间：{{$item->created_at}}</td>
                    </tr>
                    <tr>
                        <td>{!! $item->content !!}</td>
                    </tr>
                    <tr>
                        <!-- <td align="right">
                            <a href="notice.html" class="btn btn-warning btn-icon-split">
                                <span class="icon text-white-50">
                                    <i class="fas fa-arrow-right"></i>
                                </span>
                                <span class="text">返回公告列表</span>
                            </a>
                        </td> -->

                    </tr>
                </tbody>
            </table>

        </div>
    </div>
</div>
@endsection