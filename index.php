<?php
// ===== إعدادات RCON =====
$rcon_host = 'localhost';     
$rcon_port = 25575;           
$rcon_pass = 'Youssef22571';  // كلمة المرور حقك

// ===== دالة الاتصال =====
function sendRcon($command) {
    global $rcon_host, $rcon_port, $rcon_pass;
    
    try {
        $socket = fsockopen($rcon_host, $rcon_port, $errno, $errstr, 5);
        if (!$socket) {
            return "❌ فشل الاتصال: السيرفر غير متاح أو البورت مسدود";
        }
        
        // حزمة RCON
        $packet = pack('VV', 0, 3) . $rcon_pass . "\x00";
        fwrite($socket, pack('V', strlen($packet)) . $packet);
        
        $response = fread($socket, 4096);
        fclose($socket);
        
        if (strlen($response) > 14) {
            return substr($response, 14, -2);
        }
        return "✅ تم تنفيذ الأمر بنجاح";
    } catch (Exception $e) {
        return "❌ خطأ: " . $e->getMessage();
    }
}

// ===== معالجة الأوامر =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cmd'])) {
    $cmd = $_POST['cmd'];
    
    // الأوامر السريعة
    $responses = [
        'status' => sendRcon('list'),
        'day' => sendRcon('time set day'),
        'night' => sendRcon('time set night'),
        'sun' => sendRcon('weather clear'),
        'rain' => sendRcon('weather rain'),
        'heal' => sendRcon('effect give @p regeneration 30 5'),
        'food' => sendRcon('effect give @p saturation 30 5'),
        'stop' => sendRcon('say ⚠️ السيرفر سيغلق الآن!') . "\n" . sendRcon('stop'),
        'ping' => sendRcon('list')
    ];
    
    if (isset($responses[$cmd])) {
        $response = $responses[$cmd];
    } else {
        $response = sendRcon($cmd);
    }
    
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['response' => $response]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎮 لوحة تحكم السيرفر</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            font-family: 'Segoe UI', Tahoma, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 15px;
        }
        .dashboard {
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 35px;
            max-width: 750px;
            width: 100%;
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 25px 50px rgba(0,0,0,0.6);
        }
        h1 {
            color: #fff;
            text-align: center;
            font-size: 2em;
            margin-bottom: 5px;
            text-shadow: 0 0 30px rgba(100,200,255,0.2);
        }
        .subtitle {
            color: rgba(255,255,255,0.5);
            text-align: center;
            margin-bottom: 25px;
            font-size: 0.9em;
        }
        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(0,0,0,0.3);
            padding: 5px 15px;
            border-radius: 20px;
        }
        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }
        .online { background: #2ecc71; box-shadow: 0 0 20px #2ecc71; }
        .offline { background: #e74c3c; box-shadow: 0 0 20px #e74c3c; }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }
        .btn {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            padding: 12px 8px;
            border-radius: 14px;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.3s;
            font-weight: 500;
            text-align: center;
        }
        .btn:hover {
            transform: translateY(-3px);
            background: rgba(255,255,255,0.15);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        .btn:active { transform: scale(0.95); }
        .btn-blue { background: #4a6cf7; border-color: #4a6cf7; }
        .btn-blue:hover { background: #5a7cf7; }
        .btn-green { background: #2ecc71; border-color: #2ecc71; }
        .btn-green:hover { background: #3ddc81; }
        .btn-red { background: #e74c3c; border-color: #e74c3c; }
        .btn-red:hover { background: #f05c4c; }
        .btn-yellow { background: #f39c12; border-color: #f39c12; }
        .btn-yellow:hover { background: #f4ac32; }
        .btn-purple { background: #8e44ad; border-color: #8e44ad; }
        .btn-purple:hover { background: #9e55bd; }
        
        .input-row {
            display: flex;
            gap: 10px;
            margin: 15px 0;
        }
        .input-row input {
            flex: 1;
            padding: 12px 16px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.05);
            color: #fff;
            font-size: 0.95em;
            outline: none;
            transition: 0.3s;
        }
        .input-row input:focus {
            border-color: #4a6cf7;
            background: rgba(255,255,255,0.1);
        }
        .input-row input::placeholder {
            color: rgba(255,255,255,0.3);
        }
        .input-row button {
            padding: 12px 25px;
            border-radius: 14px;
            border: none;
            background: #4a6cf7;
            color: #fff;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        .input-row button:hover { background: #5a7cf7; }
        
        #output {
            background: rgba(0,0,0,0.4);
            border-radius: 14px;
            padding: 18px;
            color: #a8d8ea;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            min-height: 100px;
            max-height: 250px;
            overflow-y: auto;
            border: 1px solid rgba(255,255,255,0.05);
            white-space: pre-wrap;
            word-break: break-all;
        }
        #output:empty::before {
            content: '💬 أنتظر أمرك...';
            color: rgba(255,255,255,0.2);
        }
        .footer {
            margin-top: 15px;
            color: rgba(255,255,255,0.15);
            text-align: center;
            font-size: 0.75em;
        }
        #output::-webkit-scrollbar { width: 5px; }
        #output::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); border-radius: 10px; }
        #output::-webkit-scrollbar-thumb { background: #4a6cf7; border-radius: 10px; }
        
        @media (max-width: 500px) {
            .dashboard { padding: 20px; }
            .grid { grid-template-columns: repeat(3, 1fr); }
            .btn { font-size: 0.8em; padding: 10px 5px; }
        }
    </style>
</head>
<body>
<div class="dashboard">
    <h1>⚡ لوحة التحكم</h1>
    <div class="subtitle">
        <span class="status">
            <span class="dot online" id="statusDot"></span>
            <span id="statusText">متصل</span>
        </span>
        · سيرفر ماينكرافت
    </div>

    <div class="grid">
        <button class="btn btn-green" onclick="sendCmd('say 🌟 مرحباً بالجميع!')">📢 ترحيب</button>
        <button class="btn btn-blue" onclick="sendCmd('list')">👥 اللاعبين</button>
        <button class="btn btn-yellow" onclick="sendCmd('time set day')">☀️ نهار</button>
        <button class="btn" onclick="sendCmd('time set night')" style="background:#2c3e50;">🌙 ليل</button>
        <button class="btn btn-blue" onclick="sendCmd('weather clear')">☀️ صفو</button>
        <button class="btn" onclick="sendCmd('weather rain')" style="background:#5d6d7e;">🌧️ مطر</button>
        <button class="btn btn-purple" onclick="sendCmd('effect give @p regeneration 30 5')">❤️ شفاء</button>
        <button class="btn btn-yellow" onclick="sendCmd('effect give @p saturation 30 5')">🍖 طعام</button>
        <button class="btn btn-red" onclick="if(confirm('متأكد؟')) sendCmd('stop')">⏹️ إيقاف</button>
    </div>

    <div class="input-row">
        <input type="text" id="customInput" placeholder="✏️ اكتب أمراً مخصصاً...">
        <button onclick="sendCustom()">🚀 إرسال</button>
    </div>

    <div id="output"></div>
    <div class="footer">🔐 RCON · تم التهيئة بكلمة المرور Youssef22571</div>
</div>

<script>
async function sendCmd(command) {
    const output = document.getElementById('output');
    output.innerHTML = '⏳ جاري التنفيذ...';
    
    try {
        const formData = new FormData();
        formData.append('cmd', command);
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        
        const data = await response.json();
        output.innerHTML = '🟢 ' + (data.response || '✅ تم التنفيذ');
    } catch (error) {
        output.innerHTML = '❌ خطأ: ' + error.message;
    }
}

function sendCustom() {
    const input = document.getElementById('customInput');
    if (input.value.trim()) {
        sendCmd(input.value.trim());
        input.value = '';
    }
}

document.getElementById('customInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') sendCustom();
});

async function checkStatus() {
    try {
        const formData = new FormData();
        formData.append('cmd', 'list');
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        const data = await response.json();
        document.getElementById('statusDot').className = 'dot online';
        document.getElementById('statusText').textContent = 'متصل';
    } catch {
        document.getElementById('statusDot').className = 'dot offline';
        document.getElementById('statusText').textContent = 'غير متصل';
    }
}

setInterval(checkStatus, 30000);
checkStatus();
</script>
</body>
</html>
