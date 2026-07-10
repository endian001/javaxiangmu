@extends('agent.layouts.agent_template')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h5>
        修改密码
    </h5>
</div>
<!-- DataTales Example -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            修改密码
        </h6>
    </div>
    <form class="user" action="{{url('/editPassword')}}" method="post" onsubmit="return checkForm();">
        @csrf
        <div class="modal-body" style="padding-top:35px;">
            <div class="form-group row">
                <div class="col-sm-6a">
                    原密码：
                </div>
                <div class="col-sm-6b">
                    <input type="password" name="old_password" required class="form-control form-control-user" placeholder="请输入原密码">
                </div>
            </div>
            <div class="form-group row">
                <div class="col-sm-6a">
                    新密码：
                </div>
                <div class="col-sm-6b">
                    <input type="password" name="new_password"  id="new_password" class="form-control form-control-user" placeholder="请输入新密码" required>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-sm-6a">
                    确认新密码：
                </div>
                <div class="col-sm-6b">
                    <input type="password" id="re_password"  class="form-control form-control-user" placeholder="请再次输入新密码" required>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-sm-6a">
                </div>
                <div class="col-sm-6b">
                    <button type="submit" class="btn btn-primary btn-user btn-block">
                        修改密码
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
@section('js')
<script>
    
    function checkForm() {
        var new_password = $("#new_password").val();
        var re_password = $("#re_password").val();
        if (new_password.length < 6) {
            alert('密码至少6位数');
            return false;
        }
        if (new_password != re_password) {
            alert('两次密码输入不一致');
            return false;
        }
    }
</script>
@endsection