<!DOCTYPE html>
<html>

<head>

  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">

  <title>代理后台管理</title>
  
  <!-- Favicon -->
  @php
    $app_logo = App\Models\SystemConfig::getValue('app_logo');
    $app_logo_url = env('APP_URL').'/uploads/'.$app_logo;
  @endphp
  <link rel="icon" href="{{ $app_logo_url }}" type="image/x-icon">
  <link rel="shortcut icon" href="{{ $app_logo_url }}" type="image/x-icon">

  <!-- Custom fonts for this template-->
  <link href="/agent/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">

  <!-- Custom styles for this template-->
  <link href="/agent/css/sb-admin-2.min.css" rel="stylesheet">
  @yield('meta')

</head>
<body id="page-top">
  <!-- Page Wrapper -->
  <div id="wrapper">
    @include('agent.layouts.aside')
    <div id="content-wrapper" class="d-flex flex-column">

      <!-- Main Content -->
      <div id="content">
        @include('agent.layouts.nav')
            <div class="container-fluid">
                @yield('content')
            </div>
      </div>
      <footer class="sticky-footer bg-white">
        <div class="container my-auto">
          <div class="copyright text-center my-auto">
            <span>Copyright © 2022. TG（Throne Games） All rights reserved.</span>
          </div>
        </div>
      </footer>
    </div>
  </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up">
        </i>
    </a>
    <!-- MY Modal-->
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">
                        基本资料
                    </h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">
                            ×
                        </span>
                    </button>
                </div>
                <form class="user">
                    <div class="modal-body">
                        <div class="form-group row">
                            <div class="col-sm-6a">
                                账号：
                            </div>
                            <div class="col-sm-6b">
                                <input type="text" class="form-control form-control-user" disabled=""
                                value="daili">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-6a">
                                姓名：
                            </div>
                            <div class="col-sm-6b">
                                <input type="text" class="form-control form-control-user" disabled=""
                                value="大王">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-sm-6a">
                                额度：
                            </div>
                            <div class="col-sm-6b">
                                <input type="text" class="form-control form-control-user" disabled=""
                                value="0">
                            </div>
                        </div>
                    </div>
                </form>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="button" data-dismiss="modal">
                        关闭
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">
                        友情提示
                    </h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">
                            ×
                        </span>
                    </button>
                </div>
                <div class="modal-body">
                    您确定要退出代理后台管理系统?
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">
                        取消
                    </button>
                    <a class="btn btn-primary" href="{{url('/logout')}}">
                        退出
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
  <script src="/agent/vendor/jquery/jquery.min.js"></script>
  <script src="/agent/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  <!-- Core plugin JavaScript-->
  <script src="/agent/vendor/jquery-easing/jquery.easing.min.js"></script>

  <!-- Custom scripts for all pages-->
  <script src="/agent/js/sb-admin-2.min.js"></script>

  <!-- Page level plugins -->
  <script src="/agent/vendor/chart.js/Chart.min.js"></script>

  <!-- Page level custom scripts -->
  <script src="/agent/js/demo/chart-area-demo.js"></script>
  <script src="/agent/js/demo/chart-pie-demo.js"></script>
  @if (session('opMsg'))
    <!-- Modal -->
    <div class="modal fade" id="opMsgModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="exampleModalCenterTitle">提示</h6>
                </div>
                <div class="modal-body">
                    {{ session('opMsg') }}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-sm" data-dismiss="modal">确定</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        $('#opMsgModal').modal();
    </script>
@endif
  @yield('js')
