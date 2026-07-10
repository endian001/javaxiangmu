<?php return array (
  'dcat/laravel-admin' => 
  array (
    'providers' => 
    array (
      0 => 'Dcat\\Admin\\AdminServiceProvider',
    ),
  ),
  'fideloper/proxy' => 
  array (
    'providers' => 
    array (
      0 => 'Fideloper\\Proxy\\TrustedProxyServiceProvider',
    ),
  ),
  'guanguans/dcat-login-captcha' => 
  array (
    'providers' => 
    array (
      0 => 'Guanguans\\DcatLoginCaptcha\\LoginCaptchaServiceProvider',
    ),
    'aliases' => 
    array (
      'CaptchaBuilder' => 'Guanguans\\DcatLoginCaptcha\\Facades\\CaptchaBuilder',
      'PhraseBuilder' => 'Guanguans\\DcatLoginCaptcha\\Facades\\PhraseBuilder',
    ),
  ),
  'laravel/tinker' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Tinker\\TinkerServiceProvider',
    ),
  ),
  'nesbot/carbon' => 
  array (
    'providers' => 
    array (
      0 => 'Carbon\\Laravel\\ServiceProvider',
    ),
  ),
  'nunomaduro/collision' => 
  array (
    'providers' => 
    array (
      0 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',
    ),
  ),
);