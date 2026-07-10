<?php return array (
  'admin' => 
  array (
    'name' => 'Dcat Admin',
    'logo' => '<img src="/vendor/dcat-admin/images/logo.png" width="35"> &nbsp;Dcat Admin',
    'logo-mini' => '<img src="/vendor/dcat-admin/images/logo.png">',
    'favicon' => NULL,
    'default_avatar' => '@admin/images/default-avatar.jpg',
    'route' => 
    array (
      'domain' => NULL,
      'prefix' => 'game',
      'namespace' => 'App\\Admin\\Controllers',
      'middleware' => 
      array (
        0 => 'web',
        1 => 'admin',
      ),
      'enable_session_middleware' => false,
    ),
    'directory' => '/www/wwwroot/87713/app/Admin',
    'title' => 'Admin',
    'assets_server' => NULL,
    'https' => true,
    'auth' => 
    array (
      'enable' => true,
      'controller' => 'App\\Admin\\Controllers\\AuthController',
      'guard' => 'admin',
      'guards' => 
      array (
        'admin' => 
        array (
          'driver' => 'session',
          'provider' => 'admin',
        ),
      ),
      'providers' => 
      array (
        'admin' => 
        array (
          'driver' => 'eloquent',
          'model' => 'Dcat\\Admin\\Models\\Administrator',
        ),
      ),
      'remember' => true,
      'except' => 
      array (
        0 => 'auth/login',
        1 => 'auth/logout',
      ),
      'enable_session_middleware' => false,
    ),
    'grid' => 
    array (
      'grid_action_class' => 'Dcat\\Admin\\Grid\\Displayers\\DropdownActions',
      'batch_action_class' => 'Dcat\\Admin\\Grid\\Tools\\BatchActions',
      'paginator_class' => 'Dcat\\Admin\\Grid\\Tools\\Paginator',
      'actions' => 
      array (
        'view' => 'Dcat\\Admin\\Grid\\Actions\\Show',
        'edit' => 'Dcat\\Admin\\Grid\\Actions\\Edit',
        'quick_edit' => 'Dcat\\Admin\\Grid\\Actions\\QuickEdit',
        'delete' => 'Dcat\\Admin\\Grid\\Actions\\Delete',
        'batch_delete' => 'Dcat\\Admin\\Grid\\Tools\\BatchDelete',
      ),
      'column_selector' => 
      array (
        'store' => 'Dcat\\Admin\\Grid\\ColumnSelector\\SessionStore',
        'store_params' => 
        array (
          'driver' => 'file',
        ),
      ),
    ),
    'helpers' => 
    array (
      'enable' => true,
    ),
    'permission' => 
    array (
      'enable' => true,
      'except' => 
      array (
        0 => '/',
        1 => 'auth/login',
        2 => 'auth/logout',
        3 => 'auth/setting',
      ),
    ),
    'menu' => 
    array (
      'cache' => 
      array (
        'enable' => false,
        'store' => 'file',
      ),
      'bind_permission' => true,
      'role_bind_menu' => true,
      'permission_bind_menu' => true,
      'default_icon' => 'feather icon-circle',
    ),
    'upload' => 
    array (
      'disk' => 'public',
      'directory' => 
      array (
        'image' => 'images',
        'file' => 'files',
      ),
    ),
    'database' => 
    array (
      'connection' => '',
      'users_table' => 'admin_users',
      'users_model' => 'Dcat\\Admin\\Models\\Administrator',
      'roles_table' => 'admin_roles',
      'roles_model' => 'Dcat\\Admin\\Models\\Role',
      'permissions_table' => 'admin_permissions',
      'permissions_model' => 'Dcat\\Admin\\Models\\Permission',
      'menu_table' => 'admin_menu',
      'menu_model' => 'Dcat\\Admin\\Models\\Menu',
      'role_users_table' => 'admin_role_users',
      'role_permissions_table' => 'admin_role_permissions',
      'role_menu_table' => 'admin_role_menu',
      'permission_menu_table' => 'admin_permission_menu',
      'settings_table' => 'admin_settings',
      'extensions_table' => 'admin_extensions',
      'extension_histories_table' => 'admin_extension_histories',
    ),
    'layout' => 
    array (
      'color' => 'default',
      'body_class' => 
      array (
      ),
      'horizontal_menu' => false,
      'sidebar_collapsed' => false,
      'sidebar_style' => 'light',
      'dark_mode_switch' => false,
      'navbar_color' => '',
    ),
    'exception_handler' => 'Dcat\\Admin\\Exception\\Handler',
    'enable_default_breadcrumb' => true,
    'extension' => 
    array (
      'dir' => '/www/wwwroot/87713/dcat-admin-extensions',
    ),
  ),
  'app' => 
  array (
    'name' => 'Laravel',
    'env' => 'testing',
    'debug' => false,
    'url' => 'https://wakuang.fakaw.eu.cc',
    'asset_url' => NULL,
    'timezone' => 'Asia/Shanghai',
    'locale' => 'zh_CN',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'key' => 'base64:HIdSQktNR/Mkeh8Gb6BuGeirt0KA9vbRRyWLKRt0vFM=',
    'cipher' => 'AES-256-CBC',
    'providers' => 
    array (
      0 => 'Illuminate\\Auth\\AuthServiceProvider',
      1 => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
      2 => 'Illuminate\\Bus\\BusServiceProvider',
      3 => 'Illuminate\\Cache\\CacheServiceProvider',
      4 => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
      5 => 'Illuminate\\Cookie\\CookieServiceProvider',
      6 => 'Illuminate\\Database\\DatabaseServiceProvider',
      7 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
      8 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
      9 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
      10 => 'Illuminate\\Hashing\\HashServiceProvider',
      11 => 'Illuminate\\Mail\\MailServiceProvider',
      12 => 'Illuminate\\Notifications\\NotificationServiceProvider',
      13 => 'Illuminate\\Pagination\\PaginationServiceProvider',
      14 => 'Illuminate\\Pipeline\\PipelineServiceProvider',
      15 => 'Illuminate\\Queue\\QueueServiceProvider',
      16 => 'Illuminate\\Redis\\RedisServiceProvider',
      17 => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
      18 => 'Illuminate\\Session\\SessionServiceProvider',
      19 => 'Illuminate\\Translation\\TranslationServiceProvider',
      20 => 'Illuminate\\Validation\\ValidationServiceProvider',
      21 => 'Illuminate\\View\\ViewServiceProvider',
      22 => 'App\\Providers\\AppServiceProvider',
      23 => 'App\\Providers\\AuthServiceProvider',
      24 => 'App\\Providers\\EventServiceProvider',
      25 => 'App\\Providers\\RouteServiceProvider',
      26 => 'SimpleSoftwareIO\\QrCode\\QrCodeServiceProvider',
    ),
    'aliases' => 
    array (
      'App' => 'Illuminate\\Support\\Facades\\App',
      'Arr' => 'Illuminate\\Support\\Arr',
      'Artisan' => 'Illuminate\\Support\\Facades\\Artisan',
      'Auth' => 'Illuminate\\Support\\Facades\\Auth',
      'Blade' => 'Illuminate\\Support\\Facades\\Blade',
      'Broadcast' => 'Illuminate\\Support\\Facades\\Broadcast',
      'Bus' => 'Illuminate\\Support\\Facades\\Bus',
      'Cache' => 'Illuminate\\Support\\Facades\\Cache',
      'Config' => 'Illuminate\\Support\\Facades\\Config',
      'Cookie' => 'Illuminate\\Support\\Facades\\Cookie',
      'Crypt' => 'Illuminate\\Support\\Facades\\Crypt',
      'DB' => 'Illuminate\\Support\\Facades\\DB',
      'Eloquent' => 'Illuminate\\Database\\Eloquent\\Model',
      'Event' => 'Illuminate\\Support\\Facades\\Event',
      'File' => 'Illuminate\\Support\\Facades\\File',
      'Gate' => 'Illuminate\\Support\\Facades\\Gate',
      'Hash' => 'Illuminate\\Support\\Facades\\Hash',
      'Lang' => 'Illuminate\\Support\\Facades\\Lang',
      'Log' => 'Illuminate\\Support\\Facades\\Log',
      'Mail' => 'Illuminate\\Support\\Facades\\Mail',
      'Notification' => 'Illuminate\\Support\\Facades\\Notification',
      'Password' => 'Illuminate\\Support\\Facades\\Password',
      'Queue' => 'Illuminate\\Support\\Facades\\Queue',
      'Redirect' => 'Illuminate\\Support\\Facades\\Redirect',
      'Redis' => 'Illuminate\\Support\\Facades\\Redis',
      'Request' => 'Illuminate\\Support\\Facades\\Request',
      'Response' => 'Illuminate\\Support\\Facades\\Response',
      'Route' => 'Illuminate\\Support\\Facades\\Route',
      'Schema' => 'Illuminate\\Support\\Facades\\Schema',
      'Session' => 'Illuminate\\Support\\Facades\\Session',
      'Storage' => 'Illuminate\\Support\\Facades\\Storage',
      'Str' => 'Illuminate\\Support\\Str',
      'URL' => 'Illuminate\\Support\\Facades\\URL',
      'Validator' => 'Illuminate\\Support\\Facades\\Validator',
      'View' => 'Illuminate\\Support\\Facades\\View',
      'QrCode' => 'SimpleSoftwareIO\\QrCode\\Facades\\QrCode',
    ),
  ),
  'auth' => 
  array (
    'defaults' => 
    array (
      'guard' => 'web',
      'passwords' => 'users',
    ),
    'guards' => 
    array (
      'web' => 
      array (
        'driver' => 'session',
        'provider' => 'users',
      ),
      'api' => 
      array (
        'driver' => 'session',
        'provider' => 'users',
      ),
      'admin' => 
      array (
        'driver' => 'session',
        'provider' => 'admin',
      ),
    ),
    'providers' => 
    array (
      'users' => 
      array (
        'driver' => 'eloquent',
        'model' => 'App\\User',
      ),
      'admin' => 
      array (
        'driver' => 'eloquent',
        'model' => 'Dcat\\Admin\\Models\\Administrator',
      ),
    ),
    'passwords' => 
    array (
      'users' => 
      array (
        'provider' => 'users',
        'table' => 'password_resets',
        'expire' => 60,
      ),
    ),
    'enable' => true,
    'controller' => 'App\\Admin\\Controllers\\AuthController',
    'guard' => 'admin',
    'remember' => true,
    'except' => 
    array (
      0 => 'auth/login',
      1 => 'auth/logout',
    ),
    'enable_session_middleware' => false,
  ),
  'broadcasting' => 
  array (
    'default' => 'log',
    'connections' => 
    array (
      'pusher' => 
      array (
        'driver' => 'pusher',
        'key' => '',
        'secret' => '',
        'app_id' => '',
        'options' => 
        array (
          'cluster' => 'mt1',
          'useTLS' => true,
        ),
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'default',
      ),
      'log' => 
      array (
        'driver' => 'log',
      ),
      'null' => 
      array (
        'driver' => 'null',
      ),
    ),
  ),
  'cache' => 
  array (
    'default' => 'array',
    'stores' => 
    array (
      'apc' => 
      array (
        'driver' => 'apc',
      ),
      'array' => 
      array (
        'driver' => 'array',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'table' => 'cache',
        'connection' => NULL,
      ),
      'file' => 
      array (
        'driver' => 'file',
        'path' => '/www/wwwroot/87713/storage/framework/cache/data',
      ),
      'memcached' => 
      array (
        'driver' => 'memcached',
        'persistent_id' => NULL,
        'sasl' => 
        array (
          0 => NULL,
          1 => NULL,
        ),
        'options' => 
        array (
        ),
        'servers' => 
        array (
          0 => 
          array (
            'host' => '127.0.0.1',
            'port' => 11211,
            'weight' => 100,
          ),
        ),
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'cache',
      ),
      'dynamodb' => 
      array (
        'driver' => 'dynamodb',
        'key' => '',
        'secret' => '',
        'region' => 'us-east-1',
        'table' => 'cache',
        'endpoint' => NULL,
      ),
    ),
    'prefix' => 'laravel_cache',
  ),
  'conf' => 
  array (
    'plat_type' => 
    array (
      0 => 'AG',
      1 => 'AP',
      2 => 'BBIN',
      3 => 'BNG',
      4 => 'CQ9',
      5 => 'DT',
      6 => 'FG',
      7 => 'GA',
      8 => 'GG',
      9 => 'GNS',
      10 => 'GPI',
      11 => 'GTI',
      12 => 'HB',
      13 => 'ISB',
      14 => 'JDB',
      15 => 'KY',
      16 => 'LEG',
      17 => 'MG',
      18 => 'MT',
      19 => 'MW',
      20 => 'NW',
      21 => 'PG',
      22 => 'PGS',
      23 => 'PNG',
      24 => 'PP',
      25 => 'PT',
      26 => 'QT',
      27 => 'RMG',
      28 => 'RT',
      29 => 'SA',
      30 => 'SG',
      31 => 'SGP',
      32 => 'SW',
      33 => 'VG',
    ),
    'game_type' => 
    array (
      1 => '真人',
      2 => '老虎机',
      3 => '彩票',
      4 => '体育',
      5 => '电竞',
      6 => '捕鱼',
      7 => '棋牌',
    ),
    'game_name_map' => 
    array (
      'bg' => 'BG',
      'avia' => '泛亚电竞',
      'vrbet' => 'VR彩票',
      'hlgame' => '欢乐棋牌',
      'hbb' => 'Asia365 新宝体育',
      'leg' => '开元棋牌',
      'qg' => 'OG真人',
      'hc' => '皇朝棋牌',
      'play99' => '99Play真人',
      'yb' => '云博彩票',
      'ly' => '乐游棋牌',
      'kx' => '凯旋棋牌',
      'ig' => 'IG官方彩票',
      'ld' => '雷火电竞',
      'xsj' => '新世界棋牌',
      'jdb' => 'JDB棋牌',
      'dg' => 'DG电子',
      'fg' => 'FG电子',
      'wm' => 'WM真人',
      'sbtest' => '沙巴体育',
      'ae' => 'AE电子',
      'oap' => '三昇体育',
      'ia' => 'IA电竞',
      'sy' => '双赢棋牌',
      'dt' => 'DT电子',
      'cmd' => 'CMD体育',
      'xsbo' => 'xsbo游戲',
      'bbin' => 'BBIN',
      'ps' => 'PS电子',
      'bng' => 'BNG电子',
      'habaner' => 'HB电子',
      'jz' => '极致彩票',
      'zeus' => 'ZEUS游戏',
      'cg' => 'CG电子',
      'icg' => 'ICG电子',
      'allBet' => '欧博真人',
      'allbet' => '欧博真人',
      'pp' => 'PP电子',
      'pg' => 'PG游戏',
      'sg' => 'SG游戏',
      'vg' => 'VG棋牌',
      'tc' => 'TC彩票',
      'datqp' => '大唐棋牌',
      'tm' => '天美棋牌',
      'ag' => 'AG',
      'web' => '系统操作',
    ),
    'vip_icon' => 
    array (
      'VIP0' => '',
      'VIP1' => '/web/mb12/image/vip1_small.8bac44c39c5dbd6922747dd0d4109550.png',
      'VIP2' => '/web/mb12/image/vip2_small.0d47ee18d34df487694e72ab55da02fa.png',
      'VIP3' => '/web/mb12/image/vip3_small.ad6255b7c40ba500c3c325f7a3e1f008.png',
      'VIP4' => '/web/mb12/image/vip4_small.3302882f749e2f8971602477bfcf3adc.png',
      'VIP5' => '/web/mb12/image/vip5_small.bb25f4b03e773500266dc138ed398768.png',
      'VIP6' => '/web/mb12/image/vip6_small.be2f7282f24140e33f20e0cc48d4f6d4.png',
      'VIP7' => '/web/mb12/image/vip7_small.064479056b5bf7667cd26282f143356e.png',
      'VIP8' => '/web/mb12/image/vip8_small.a2172c442e0699efd1340be743aaf162.png',
      'VIP9' => '/web/mb12/image/vip9_small.b29accf15b8aabe890cfeb87f1ef145a.png',
      'VIP10' => '/web/mb12/image/vip10_small.d3ec6549ab11ab6bd1c09a0bdab37d1f.png',
    ),
  ),
  'database' => 
  array (
    'default' => 'sqlite',
    'connections' => 
    array (
      'sqlite' => 
      array (
        'driver' => 'sqlite',
        'url' => NULL,
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
      ),
      'mysql' => 
      array (
        'driver' => 'mysql',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => ':memory:',
        'username' => 'xpg',
        'password' => 'xpg_2026_Wakuang',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
      'pgsql' => 
      array (
        'driver' => 'pgsql',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => ':memory:',
        'username' => 'xpg',
        'password' => 'xpg_2026_Wakuang',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'schema' => 'public',
        'sslmode' => 'prefer',
      ),
      'sqlsrv' => 
      array (
        'driver' => 'sqlsrv',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => ':memory:',
        'username' => 'xpg',
        'password' => 'xpg_2026_Wakuang',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
      ),
    ),
    'migrations' => 'migrations',
    'redis' => 
    array (
      'client' => 'phpredis',
      'options' => 
      array (
        'cluster' => 'redis',
        'prefix' => 'laravel_database_',
      ),
      'default' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'password' => NULL,
        'port' => '6379',
        'database' => 0,
      ),
      'cache' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'password' => NULL,
        'port' => '6379',
        'database' => 1,
      ),
    ),
  ),
  'errorcode' => 
  array (
    'code' => 
    array (
      200 => '成功',
      201 => '此用户名已被注册',
      202 => '用户不存在',
      203 => '登录密码错误',
      204 => '您还未成年,无法游玩娱乐城',
      205 => '原密码不正确',
      206 => '充值失败',
      207 => '您最多只能绑3张卡哦',
      208 => '提现金额不能大于账户余额哦',
      209 => 'Tg钱包转入失败，请检查Tg钱包余额',
      210 => '余额不足',
      211 => 'Tg钱包转入失败',
      212 => '充值金额过低',
      213 => '充值金额过高',
      214 => '提款金额过低',
      215 => '提款金额过高',
      216 => '日提款次数达到上限',
      217 => '缺少参数',
      218 => '还没到提现时间哦',
      219 => '今天提现时间已结束，请明天再来提现',
      220 => '密码错误',
      221 => '没有可领取的返水',
      222 => '活动不存在',
      223 => '您已经申请过，等待管理员审核',
      224 => '您已经申请过，已审核通过',
      225 => '您已经申请过，审核未通过',
      226 => '您已申请过代理',
      300 => '设置失败',
      500 => '错误',
      'zh' => 
      array (
        200 => '成功',
        201 => '此用户名已被注册',
        202 => '用户不存在',
        203 => '登录密码错误',
        204 => '您还未成年,无法游玩娱乐城',
        205 => '原密码不正确',
        206 => '充值失败',
        207 => '您最多只能绑3张卡哦',
        208 => '提现金额不能大于账户余额哦',
        209 => 'Tg钱包转入失败，请检查Tg钱包余额',
        210 => '余额不足',
        211 => 'Tg钱包转入失败',
        212 => '充值金额过低',
        213 => '充值金额过高',
        214 => '提款金额过低',
        215 => '提款金额过高',
        216 => '日提款次数达到上限',
        217 => '缺少参数',
        218 => '还没到提现时间哦',
        219 => '今天提现时间已结束，请明天再来提现',
        220 => '密码错误',
        221 => '没有可领取的返水',
        222 => '活动不存在',
        223 => '您已经申请过，等待管理员审核',
        224 => '您已经申请过，已审核通过',
        225 => '您已经申请过，审核未通过',
        226 => '您已申请过代理',
        300 => '设置失败',
        500 => '错误',
      ),
      'en' => 
      array (
        200 => 'success',
        201 => 'This username is already registered',
        202 => 'User does not exist',
        203 => 'Wrong login password',
        204 => 'You are too young to visit the casino',
        205 => 'The original password is incorrect',
        206 => 'Recharge failed',
        207 => 'You can only bind 3 cards at most',
        208 => 'The withdrawal amount cannot be greater than the account balance',
        209 => 'Please check TG wallet balance!',
        210 => 'Sorry, your credit is running low',
        211 => 'TG wallet transfer failed',
        212 => 'The recharge amount is too low',
        213 => 'The recharge amount is too high',
        214 => 'Withdrawal amount is too low',
        215 => 'Withdrawal amount is too high',
        216 => 'The maximum number of withdrawals per day',
        217 => 'Missing parameter',
        218 => 'It\'s not time to withdraw cash',
        219 => 'Today\'s withdrawal time has ended, please come again tomorrow',
        220 => 'Password error',
        221 => 'There is no returnable water',
        222 => 'Activity does not exist',
        223 => 'You have applied, waiting for the administrator to review',
        224 => 'You have applied and passed the review',
        225 => 'You have applied, but failed to pass the review',
        226 => 'You have applied for an agent',
        300 => 'Setting failed',
        500 => 'Error',
      ),
    ),
  ),
  'filesystems' => 
  array (
    'default' => 'local',
    'cloud' => 's3',
    'disks' => 
    array (
      'local' => 
      array (
        'driver' => 'local',
        'root' => '/www/wwwroot/87713/public/uploads',
        'url' => 'https://wakuang.fakaw.eu.cc/uploads',
        'visibility' => 'public',
      ),
      'public' => 
      array (
        'driver' => 'local',
        'root' => '/www/wwwroot/87713/storage/app/public',
        'url' => 'https://wakuang.fakaw.eu.cc/storage',
        'visibility' => 'public',
      ),
      's3' => 
      array (
        'driver' => 's3',
        'key' => '',
        'secret' => '',
        'region' => 'us-east-1',
        'bucket' => '',
        'url' => NULL,
      ),
    ),
  ),
  'hashing' => 
  array (
    'driver' => 'bcrypt',
    'bcrypt' => 
    array (
      'rounds' => '4',
    ),
    'argon' => 
    array (
      'memory' => 1024,
      'threads' => 2,
      'time' => 2,
    ),
  ),
  'logging' => 
  array (
    'default' => 'stack',
    'channels' => 
    array (
      'stack' => 
      array (
        'driver' => 'stack',
        'channels' => 
        array (
          0 => 'daily',
        ),
        'ignore_exceptions' => false,
      ),
      'single' => 
      array (
        'driver' => 'single',
        'path' => '/www/wwwroot/87713/storage/logs/laravel.log',
        'level' => 'debug',
      ),
      'daily' => 
      array (
        'driver' => 'daily',
        'path' => '/www/wwwroot/87713/storage/logs/laravel.log',
        'level' => 'debug',
        'days' => 14,
      ),
      'slack' => 
      array (
        'driver' => 'slack',
        'url' => NULL,
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => 'critical',
      ),
      'papertrail' => 
      array (
        'driver' => 'monolog',
        'level' => 'debug',
        'handler' => 'Monolog\\Handler\\SyslogUdpHandler',
        'handler_with' => 
        array (
          'host' => NULL,
          'port' => NULL,
        ),
      ),
      'stderr' => 
      array (
        'driver' => 'monolog',
        'handler' => 'Monolog\\Handler\\StreamHandler',
        'formatter' => NULL,
        'with' => 
        array (
          'stream' => 'php://stderr',
        ),
      ),
      'syslog' => 
      array (
        'driver' => 'syslog',
        'level' => 'debug',
      ),
      'errorlog' => 
      array (
        'driver' => 'errorlog',
        'level' => 'debug',
      ),
    ),
  ),
  'mail' => 
  array (
    'driver' => 'array',
    'host' => 'smtp.mailtrap.io',
    'port' => '2525',
    'from' => 
    array (
      'address' => 'hello@example.com',
      'name' => 'Example',
    ),
    'encryption' => NULL,
    'username' => NULL,
    'password' => NULL,
    'sendmail' => '/usr/sbin/sendmail -bs',
    'markdown' => 
    array (
      'theme' => 'default',
      'paths' => 
      array (
        0 => '/www/wwwroot/87713/resources/views/vendor/mail',
      ),
    ),
    'log_channel' => NULL,
  ),
  'pay' => 
  array (
    'cgpay' => 
    array (
      'MerchantId' => '商户号',
      'md5key' => '密钥',
      'payurl' => 'https://public.cgpay.io/api/v3/CreateCGPPayOrder',
    ),
  ),
  'queue' => 
  array (
    'default' => 'sync',
    'connections' => 
    array (
      'sync' => 
      array (
        'driver' => 'sync',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
      ),
      'beanstalkd' => 
      array (
        'driver' => 'beanstalkd',
        'host' => 'localhost',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => 0,
      ),
      'sqs' => 
      array (
        'driver' => 'sqs',
        'key' => '',
        'secret' => '',
        'prefix' => 'https://sqs.us-east-1.amazonaws.com/your-account-id',
        'queue' => 'your-queue-name',
        'region' => 'us-east-1',
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => NULL,
      ),
    ),
    'failed' => 
    array (
      'driver' => 'database',
      'database' => 'sqlite',
      'table' => 'failed_jobs',
    ),
  ),
  'services' => 
  array (
    'mailgun' => 
    array (
      'domain' => NULL,
      'secret' => NULL,
      'endpoint' => 'api.mailgun.net',
    ),
    'postmark' => 
    array (
      'token' => NULL,
    ),
    'ses' => 
    array (
      'key' => '',
      'secret' => '',
      'region' => 'us-east-1',
    ),
  ),
  'session' => 
  array (
    'driver' => 'array',
    'lifetime' => '120',
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => '/www/wwwroot/87713/storage/framework/sessions',
    'connection' => NULL,
    'table' => 'sessions',
    'store' => NULL,
    'lottery' => 
    array (
      0 => 2,
      1 => 100,
    ),
    'cookie' => 'laravel_session',
    'path' => '/',
    'domain' => NULL,
    'secure' => false,
    'http_only' => true,
    'same_site' => NULL,
  ),
  'view' => 
  array (
    'paths' => 
    array (
      0 => '/www/wwwroot/87713/resources/views',
    ),
    'compiled' => '/www/wwwroot/87713/storage/framework/views',
  ),
  'trustedproxy' => 
  array (
    'proxies' => NULL,
    'headers' => 30,
  ),
  'tinker' => 
  array (
    'commands' => 
    array (
    ),
    'dont_alias' => 
    array (
      0 => 'App\\Nova',
    ),
  ),
);
