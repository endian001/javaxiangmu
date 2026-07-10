<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>代理分享</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        .input-group {
            display: flex;
        }
        input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px 0 0 5px;
            font-size: 14px;
        }
        button {
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
            font-size: 14px;
        }
        button:hover {
            background: #45a049;
        }
        .stats {
            display: flex;
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            flex: 1;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-card h3 {
            color: #666;
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        .stat-card p {
            color: #333;
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .share-buttons {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 30px;
        }
        .share-btn {
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .share-btn:hover {
            transform: scale(1.05);
        }
        .wechat { background: #07c160; color: white; }
        .qq { background: #12b7f5; color: white; }
        .weibo { background: #e6162d; color: white; }
        .copy { background: #ff9800; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>代理分享</h1>
        
        <div class="form-group">
            <label>推广链接</label>
            <div class="input-group">
                <input type="text" id="agentLink" value="{{ url('/register?pid=' . $user->id) }}" readonly>
                <button onclick="copyLink()">复制链接</button>
            </div>
        </div>
        
        <div class="form-group">
            <label>邀请码</label>
            <div class="input-group">
                <input type="text" id="agentCode" value="{{ $user->username }}" readonly>
                <button onclick="copyCode()">复制邀请码</button>
            </div>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <h3>直接邀请</h3>
                <p>0 人</p>
            </div>
            <div class="stat-card">
                <h3>有效邀请</h3>
                <p>0 人</p>
            </div>
        </div>
        
        <h2 style="color: #555; margin-top: 30px;">分享方式</h2>
        <div class="share-buttons">
            <div class="share-btn wechat" onclick="shareTo('wechat')">微信</div>
            <div class="share-btn qq" onclick="shareTo('qq')">QQ</div>
            <div class="share-btn weibo" onclick="shareTo('(')')">微博</div>
            <div class="share-btn copy" onclick="shareTo('copy')">复制链接</div>
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
</body>
</html>