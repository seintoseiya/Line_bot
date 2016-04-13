<?php
error_log("callback start.");

// アカウント情報設定
$channel_id = getenv('LINE_CHANNEL_ID');
$channel_secret = getenv('LINE_CHANNEL_SECRET');
$mid = getenv('LINE_CHANNEL_MID');
$proxy = getenv('FIXIE_URL');

// メッセージ受信
$json_string = file_get_contents('php://input');
$json_object = json_decode($json_string);
$content = $json_object->result{0}->content;
$text = $content->text;
$from = $content->from;
$message_id = $content->id;
$content_type = $content->contentType;

// メッセージコンテンツ生成
$sticker_content = <<< EOM
        "contentType":8,
        "contentMetadata":{
          "STKID":"100",
          "STKPKGID":"1",
          "STKVER":"100"
        }
EOM;

// 受信メッセージに応じて返すメッセージを変更
$event_type = "138311608800106203";
if ($content_type != 1) {
    $text = "テキスト以外";
}
$content = <<< EOM
        "contentType":1,
        "text":"{$text}"
EOM;
$post = <<< EOM
{
    "to":["{$from}"],
    "toChannel":1383378250,
    "eventType":"{$event_type}",
    "content":{
        "toType":1,
        {$content}
    }
}
EOM;

api_post_request("/v1/events", $post);

error_log("callback end.");

function api_post_request($path, $post) {
    $url = "https://trialbot-api.line.me{$path}";
    $headers = array(
        "Content-Type: application/json",
        "X-Line-ChannelID: {$GLOBALS['channel_id']}",
        "X-Line-ChannelSecret: {$GLOBALS['channel_secret']}",
        "X-Line-Trusted-User-With-ACL: {$GLOBALS['mid']}"
    );

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    //プロキシ経由フラグ
    curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, 1);
    //プロキシアドレス設定（プロキシのアドレス:ポート名）
    curl_setopt($curl, CURLOPT_PROXY, $GLOBALS['proxy']);
    $output = curl_exec($curl);
    error_log($output);
}
