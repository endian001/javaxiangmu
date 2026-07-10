<!DOCTYPE html>
<html lang="zh-CN">

<head>

  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="代理后台管理系统">
  <meta name="author" content="">

  <title>代理后台登录</title>
  
  <!-- Favicon -->
  @php
    $app_logo = App\Models\SystemConfig::getValue('app_logo');
    $app_logo_url = env('APP_URL').'/uploads/'.$app_logo;
  @endphp
  <link rel="icon" href="{{ $app_logo_url }}" type="image/x-icon">
  <link rel="shortcut icon" href="{{ $app_logo_url }}" type="image/x-icon">

  <!-- Custom fonts for this template-->
  <link href="/agent/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Custom styles for this template-->
  <link href="/agent/css/sb-admin-2.min.css" rel="stylesheet">
  
  <style>
    * {
      font-family: 'Inter', sans-serif;
    }
    
    body {
      background: linear-gradient(135deg, #1a2a6c 0%, #b21f1f 50%, #fdbb2d 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background-size: 200% 200%;
      animation: gradientAnimation 15s ease infinite;
    }
    
    @keyframes gradientAnimation {
      0% {
        background-position: 0% 50%;
      }
      50% {
        background-position: 100% 50%;
      }
      100% {
        background-position: 0% 50%;
      }
    }
    
    .login-container {
      width: 100%;
      max-width: 480px;
      padding: 0 20px;
    }
    
    .login-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      transition: all 0.3s ease;
    }
    
    .login-card:hover {
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
      transform: translateY(-5px);
    }
    
    .login-header {
      text-align: center;
      padding: 40px 0 30px;
      background: linear-gradient(135deg, #1a2a6c 0%, #b21f1f 50%, #fdbb2d 100%);
      color: white;
      background-size: 200% 200%;
      animation: gradientAnimation 15s ease infinite;
    }
    
    .login-header h1 {
      font-size: 28px;
      font-weight: 600;
      margin: 0;
    }
    
    .login-header p {
      font-size: 14px;
      opacity: 0.9;
      margin: 10px 0 0;
    }
    
    .login-body {
      padding: 40px;
    }
    
    .form-group {
      margin-bottom: 25px;
      position: relative;
    }
    
    .form-group i {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #667eea;
      font-size: 18px;
    }
    
    .form-control {
      height: 55px;
      border-radius: 10px;
      padding-left: 50px;
      font-size: 15px;
      border: 1px solid #e0e0e0;
      transition: all 0.3s ease;
    }
    
    .form-control:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
      outline: none;
    }
    
    #captcha {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    #captcha .form-control {
      flex: 1;
    }
    
    #captcha img {
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    #captcha img:hover {
      transform: scale(1.05);
    }
    
    .btn-login {
      width: 100%;
      height: 55px;
      border-radius: 10px;
      font-size: 16px;
      font-weight: 600;
      background: linear-gradient(135deg, #1a2a6c 0%, #b21f1f 50%, #fdbb2d 100%);
      border: none;
      color: white;
      transition: all 0.3s ease;
      margin-top: 10px;
      background-size: 200% 200%;
      animation: gradientAnimation 15s ease infinite;
    }
    
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(182, 31, 31, 0.4);
    }
    
    .btn-login:active {
      transform: translateY(0);
    }
    
    .form-group.checkbox-group {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 30px;
    }
    
    .custom-checkbox .custom-control-input:checked ~ .custom-control-label::before {
      background-color: #667eea;
      border-color: #667eea;
    }
    
    .forgot-password {
      color: #667eea;
      text-decoration: none;
      font-size: 14px;
      transition: color 0.3s ease;
    }
    
    .forgot-password:hover {
      color: #764ba2;
      text-decoration: underline;
    }
    
    @media (max-width: 768px) {
      .login-body {
        padding: 30px 25px;
      }
      
      .login-header {
        padding: 30px 0 20px;
      }
      
      .login-header h1 {
        font-size: 24px;
      }
    }
    
    /* 动画效果 */
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .login-card {
      animation: fadeIn 0.6s ease-out;
    }
    
    .form-group {
      animation: fadeIn 0.6s ease-out;
    }
    
    .form-group:nth-child(1) { animation-delay: 0.1s; }
    .form-group:nth-child(2) { animation-delay: 0.2s; }
    .form-group:nth-child(3) { animation-delay: 0.3s; }
    .form-group:nth-child(4) { animation-delay: 0.4s; }
    .btn-login { animation-delay: 0.5s; }
  </style>

</head>

<body>

  <div class="login-container">

    <div class="login-card">
      <div class="login-header">
        <h1>代理后台管理</h1>
        <p>专业的代理管理系统</p>
      </div>
      <div class="login-body">
        <form method="post" action="{{url('/login')}}">
          @csrf
          <div class="form-group">
            <i class="fas fa-user"></i>
            <input type="text" class="form-control" name="name" id="exampleInputEmail" aria-describedby="emailHelp" placeholder="请输入账号" required>
          </div>
          <div class="form-group">
            <i class="fas fa-lock"></i>
            <input type="password" class="form-control" name="password" id="exampleInputPassword" placeholder="请输入密码" required>
          </div>
          <div class="form-group" id="captcha">
            <div style="position: relative; flex: 1;">
              <i class="fas fa-shield-alt"></i>
              <input type="text" class="form-control" name="captcha" aria-describedby="emailHelp" placeholder="请输入验证码" required>
            </div>
            <a onclick="javascript:re_captcha();" ><img src="{{ URL('kit/captcha/1') }}"  alt="验证码" title="刷新图片" width="120" height="55" id="c2c98f0de5a04167a9e427d883690ff6" border="0"></a>
          </div>
          <div class="form-group checkbox-group">
            <div class="custom-control custom-checkbox small">
              <input type="checkbox" class="custom-control-input" id="customCheck" name="remember_me" value="1">
              <label class="custom-control-label" for="customCheck">记住账号</label>
            </div>
            <a href="#" class="forgot-password">忘记密码?</a>
          </div>
          <button type="submit" class="btn btn-login">
            登录后台
          </button>
        </form>
      </div>
    </div>

  </div>

  <!-- Bootstrap core JavaScript-->
  <script src="/agent/vendor/jquery/jquery.min.js"></script>
  <script src="/agent/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  <!-- Core plugin JavaScript-->
  <script src="/agent/vendor/jquery-easing/jquery.easing.min.js"></script>

  <!-- Custom scripts for all pages-->
  <script src="/agent/js/sb-admin-2.min.js"></script>

  @if(session('opMsg'))
  <script>
      var msg = "{{session('opMsg')}}";
      alert(msg);
  </script>
  @endif
  
  <script>  
  function re_captcha() {
    $url = "{{ URL('kit/captcha') }}";
        $url = $url + "/" + Math.random();
        document.getElementById('c2c98f0de5a04167a9e427d883690ff6').src=$url;
  }
</script>

</body>

</html>
