
<?php
require('vendor/autoload.php');

use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;


//LINEBOT開始処理
$channel_access_token = 'mrdpGNpUWQPA9bFbQtXo9rWX9S7TKALozNHqRCH9OwGuS/ufzJCbPOrHv8xkRzJJuQZiAiSlhz7aQKhfczrCBS/yO6eaATsd3hbx+uMjaIIw2Gmextay9LpTLILeZ9d6cfakWaPc75a9bxnmPSrBFAdB04t89/1O/w1cDnyilFU=';
$channel_secret = 'c5e8e2269583dbd5bddf88794c308a9e';
$http_client = new CurlHTTPClient($channel_access_token);
$bot = new LINEBot($http_client, ['channelSecret' => $channel_secret]);
$signature = $_SERVER['HTTP_' . HTTPHeader::LINE_SIGNATURE];
$inputData = file_get_contents('php://input');
$events = $bot->parseEventRequest($inputData, $signature);
$event = $events[0];



//ログをとる
//error_log(print_r($event, true) . "\n", 3, 'php.log');

//LINEBOTDB接続処理
try {

    // データベースに接続
    $pdo = new PDO(
        'mysql:dbname=LAA1276112-linebot;host=mysql150.phy.lolipop.lan;charset=utf8mb4',
        'LAA1276112',
        'LINEadmin',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    //error_log('接続成功！'. "\n", 3, 'php.log');

} catch (PDOException $e) {

    error_log('エラー発生:' . $e->getMessage());
    exit($e->getMessage());
}



//LINEBOTユーザ登録処理
/*user_idを取り出し、既に登録されているかどうかを確認*/
$user_id = $event->getUserId();
$stmt = $pdo -> prepare("SELECT count(*) FROM user WHERE user_id = :user_id");
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
$stmt->execute();

//error_log('id'.$stmt->fetch(PDO::FETCH_ASSOC). "\n", 3, 'php.log');
$result = $stmt->fetch(PDO::FETCH_ASSOC);
error_log(print_r($result, true) . "\n", 3, 'php.log');


/*登録されていなかったら（countが0だったら）登録処理を行う*/
if(!(int)array_shift($result)){
    $user_id = $event->getUserId();
    error_log("if".$user_id. "\n", 3, 'php.log');
    $stmt = $pdo -> prepare("INSERT INTO user (user_id) VALUES (:user_id)");
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
    $stmt->execute();
}

//TODO登録処理
$GetText = $event->getText();
/*テキストがTODO:,todo:,Todo:で始まっていたら*/
if(preg_match('/^TODO:/',$GetText) || preg_match('/^Todo:/',$GetText) || preg_match('/^todo:/',$GetText)){
    /* todo:以下の文字列をDB(todo)に登録 */
    $GetText = $event->getText();
    error_log('TODO登録処理開始gettext:'. $GetText . "\n", 3, 'php.log');

    preg_replace('/todo:(\w+)/',$GetText,$match);
    // $str = "http://example.com?name=riki&page=30&count=100";
    // preg_match('/name=(\w+)/', $str, $match);
    error_log('ResisterTodo:'. print_r($match,true) . "\n", 3, 'php.log');
    
}

$reply_token = $event->getReplyToken();
$GetText = $event->getText();

$reply_text = 'NO';

if ($GetText === 'todo') {
    $reply_text = '登録完了';
}

$bot->replyText($reply_token, $reply_text);


//ログをとる
//error_log(print_r($event, true) . "\n", 3, 'php.log');
