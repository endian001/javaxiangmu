@extends('agent.layouts.agent_template')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h5>
        下级充值
    </h5>
</div>
<!-- DataTales Example -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">
            下级充值
        </h6>
    </div>
    <form class="user" action="{{url('/recharge')}}" method="post" onsubmit="return checkForm();">
        @csrf
        <div class="modal-body" style="padding-top:35px;">
            <div class="form-group row">
                <div class="col-sm-6a">
                    金额：
                </div>
                <div class="col-sm-6b">
                    <input type="number" name="amount" id="amount" required class="form-control form-control-user" placeholder="请输入金额">
                </div>
            </div>
           
            <div class="form-group row">
                <div class="col-sm-6a">
                </div>
                <div class="col-sm-6b">
                    <button type="submit" class="btn btn-primary btn-user btn-block">
                        充值
                    </button>
                </div>
            </div>
        </div>
        <input type="hidden" name="user_id" value="{{$user_id}}">
        <input type="hidden" name="client_order_no" value="{{$client_order_no ?? ''}}">
        
    </form>
</div>
@endsection
@section('js')
<script>
    
    function checkForm() {
        var amount = $("#amount").val();
        if (!amount) {
            alert('请输入金额');
            return false;
        }
    }
</script>
@endsection
