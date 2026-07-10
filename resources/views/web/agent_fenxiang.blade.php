@extends('web.template.mb12.index')

@section('content')
    <div class="container mt-5">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title">代理分享</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="agentLink">推广链接</label>
                            <div class="input-group">
                                <input type="text" id="agentLink" class="form-control" value="{{ url('/register?pid=' . $user->id) }}" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-secondary" onclick="copyLink()">复制链接</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="agentCode">邀请码</label>
                            <div class="input-group">
                                <input type="text" id="agentCode" class="form-control" value="{{ $user->username }}" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-secondary" onclick="copyCode()">复制邀请码</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="card-title">直接邀请</h5>
                                <p class="card-text">您已邀请 <strong>0</strong> 位会员</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="card-title">有效邀请</h5>
                                <p class="card-text">您的有效邀请 <strong>0</strong> 位会员</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h4>分享方式</h4>
                    <div class="row mt-2">
                        <div class="col-md-3">
                            <button class="btn btn-block btn-outline-primary" onclick="shareTo('wechat')">
                                <i class="fab fa-weixin mr-2"></i>微信
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-block btn-outline-primary" onclick="shareTo('qq')">
                                <i class="fab fa-qq mr-2"></i>QQ
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-block btn-outline-primary" onclick="shareTo('weibo')">
                                <i class="fab fa-weibo mr-2"></i>微博
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-block btn-outline-primary" onclick="shareTo('copy')">
                                <i class="fas fa-copy mr-2"></i>复制链接
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function copyLink() {
            var linkInput = document.getElementById('agentLink');
            linkInput.select();
            document.execCommand('copy');
            alert('链接已复制到剪贴板');
        }
        
        function copyCode() {
            var codeInput = document.getElementById('agentCode');
            codeInput.select();
            document.execCommand('copy');
            alert('邀请码已复制到剪贴板');
        }
        
        function shareTo(platform) {
            var link = document.getElementById('agentLink').value;
            var title = '加入我们，一起赚钱！';
            var desc = '注册即送好礼，快来加入吧！';
            
            switch(platform) {
                case 'wechat':
                    alert('请在微信中打开链接分享');
                    break;
                case 'qq':
                    window.open('https://connect.qq.com/widget/shareqq/index.html?url=' + encodeURIComponent(link) + '&title=' + encodeURIComponent(title) + '&desc=' + encodeURIComponent(desc));
                    break;
                case 'weibo':
                    window.open('https://service.weibo.com/share/share.php?url=' + encodeURIComponent(link) + '&title=' + encodeURIComponent(title) + '&content=utf-8&sourceUrl=' + encodeURIComponent(link));
                    break;
                case 'copy':
                    copyLink();
                    break;
            }
        }
    </script>
@endsection