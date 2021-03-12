
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
    $dbh = new PDO(
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
$stmt = $dbh -> prepare("SELECT id FROM user WHERE user_id = :user_id");
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
$stmt->execute();

//error_log('id'.$stmt->fetch(PDO::FETCH_ASSOC). "\n", 3, 'php.log');
$result = $stmt->fetch(PDO::FETCH_ASSOC);
error_log(print_r($result, true) . "\n", 3, 'php.log');


/*登録されていなかったら（countが0だったら）登録処理を行う*/
if(!(int)reset($result)){
    $user_id = $event->getUserId();
    error_log("if".$user_id. "\n", 3, 'php.log');
    $stmt = $dbh -> prepare("INSERT INTO user (user_id) VALUES (:user_id)");
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_STR);
    $stmt->execute();
    $id = (int)reset($result);
    error_log('ユーザID:'. $id . "\n", 3, 'php.log');
}else{
    $id = (int)reset($result);
    error_log('ユーザID:'. $id . "\n", 3, 'php.log');

}

//TODO登録処理
$GetText = $event->getText();
/*テキストがTODO:,todo:,Todo:で始まっていたら*/
if(preg_match('/^TODO:/',$GetText) || preg_match('/^Todo:/',$GetText) || preg_match('/^todo:/',$GetText)){
    /* todo:以下の文字列をDB(todo)に登録 */
    error_log('TODO登録処理開始gettext:'. $GetText . "\n", 3, 'php.log');

    preg_match('/:([\wぁ-んァ-ヶ一-龠々]+)/',$GetText,$match);
    error_log('ResisterTodo:'. print_r($match,true) . "\n", 3, 'php.log');
    
    /* TODOをINSERTする */
    $stmt = $dbh -> prepare("INSERT INTO todo (content,u_id) VALUES (:content,:u_id)");
    $stmt->bindValue(':content', $match[1], PDO::PARAM_STR);
    $stmt->bindValue(':u_id', $id, PDO::PARAM_INT);

    $stmt->execute();
    $reply_token = $event->getReplyToken();
    $bot->replyText($reply_token, 'TODO登録完了！');

}

//TODO確認処理
if($GetText === 'list' || $GetText === 'リスト'){
    error_log('TODO確認開始'. $GetText . "\n", 3, 'php.log');
    $stmt = $dbh -> prepare("SELECT c_id,content FROM todo WHERE u_id = :u_id");
    $stmt->bindValue(':u_id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log('TODOのなかみ'.print_r($result,true) . "\n", 3, 'php.log');
    
    $reply_token = $event->getReplyToken();
    $count = count($result);
    $todo_list = "やることリスト!!\n";
    for($i = 0;$i < $count;$i++){
        if($i === ($count-1)){
            error_log('c_id'.$result[$i]['c_id'] .'content'.$result[$i]['content']. "\n", 3, 'php.log');
            $todo_list .= $result[$i]['c_id'].': '.$result[$i]['content'];
            error_log('TODOの表示内容'.$todo_list . "\n", 3, 'php.log');
        }else{
            error_log('c_id'.$result[$i]['c_id'] .'content'.$result[$i]['content']. "\n", 3, 'php.log');
            $todo_list .= $result[$i]['c_id'].': '.$result[$i]['content']."\n";
            error_log('TODOの表示内容'.$todo_list . "\n", 3, 'php.log');
        }

    }

    $bot->replyText($reply_token, $todo_list);

}



//TODO完了処理
if(preg_match('/^DONE:/',$GetText) || preg_match('/^Done:/',$GetText) || preg_match('/^だん:/',$GetText) || preg_match('/^done:/',$GetText)){
    /* todo:以下の文字列をDB(todo)に登録 */
    error_log('TODO完了処理開始gettext:'. $GetText . "\n", 3, 'php.log');

    preg_match('/:(\d{1,4})/',$GetText,$match);
    error_log('c_id:'. print_r($match,true) . "\n", 3, 'php.log');
    
    $delete_c_id = intval($match[1]);
    error_log('debug:'. '1' . "\n", 3, 'php.log');

    /* TODOをDELETEする */
    $stmt = $dbh -> prepare("DELETE FROM todo WHERE c_id = :c_id");
    error_log('debug:'. '2' . "\n", 3, 'php.log');

    $stmt->bindValue(':c_id', $delete_c_id, PDO::PARAM_INT);
    error_log('debug:'. '3'.print_r($dbh->errorInfo()) . "\n", 3, 'php.log');

    $stmt->execute();
    error_log('debug:'.'4'. print_r($dbh->errorInfo()) . "\n", 3, 'php.log');

    error_log('rowcount:'. $stmt->rowCount() . "\n", 3, 'php.log');
    if($stmt->rowCount()){
        $reply_token = $event->getReplyToken();
        $bot->replyText($reply_token, 'TODO削除完了！');
    }else{
        $reply_token = $event->getReplyToken();
        $bot->replyText($reply_token, 'TODO削除できませんでした...');
    }


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
