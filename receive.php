<?php
include 'dependencies.php';
$eapi = new EAPI();
$dbh = ErplyManager::getDbConnection();
$sth = $dbh->prepare('INSERT INTO erply_log (session_id, t1, name, error, field) VALUES (?,?,?,?,?)');
$connection = ErplyManager::getRabbitConnection();
$channel = $connection->channel();
$channel->queue_declare(ErplyConf::RABBITMQ['erply_queue'], false, false, false, false);
echo " [*] Waiting for messages. To exit press CTRL+C\n\n";
$callback = function ($msg) use ($sth, $eapi) {
    echo ' [x] Request: '.$msg->body."\n";
    try {
        $request = json_decode($msg->body, true);
        $log = [$request['sessionKey'], mktime(), $request['name']];
        $eapi->url = ErplyManager::getEApiURL($request['clientCode']);
        $json = $eapi->sendRequest($request['request'], $request);
        $r = json_decode($json, true);
        if(is_null($r)) { throw new Exception('Erply API response JSON cannot be decoded or the encoded data is deeper than the recursion limit: '.$json); }
        $log[3] = $r['status']['errorCode'];
        $log[4] = isset($r['status']['errorField']) ? $r['status']['errorField'] : '';
    } catch (Exception $e) {
        echo ' [x] Exception: '.$e->getMessage()."\n";
        $log[3] = 1;
        $log[4] = $e->getMessage();
    }
    echo ' [x] Log: '.json_encode($log)."\n\n";
    $sth->execute($log);
};
$channel->basic_consume(ErplyConf::RABBITMQ['erply_queue'], '', false, true, false, false, $callback);
while (count($channel->callbacks)) {
    $channel->wait();
}
$channel->close();
$connection->close();
$sth = null;
$dbh = null;
