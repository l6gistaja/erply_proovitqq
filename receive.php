<?php
include 'dependencies.php';
$eapi = new EAPI();
$dbh = ErplyManager::getDbConnection();
$sth = $dbh->prepare('INSERT INTO erply_log (session_id, t1, name, error, field) VALUES (?,?,?,?,?)');
$connection = ErplyManager::getRabbitConnection();
$channel = $connection->channel();
$channel->queue_declare(ErplyConf::RABBITMQ['erply_queue'], false, false, false, false);
echo " [*] Waiting for messages. To exit press CTRL+C\n";
$callback = function ($msg) use ($sth, $eapi) {
    echo ' [x] Received ', $msg->body, "\n";
    $request = json_decode($msg->body, true);
    $eapi->url = ErplyManager::getEApiURL($request['clientCode']);
    $r = json_decode($eapi->sendRequest($request['request'], $request), true);
    $sth->execute([
        $request['sessionKey'],
        mktime(),
        $request['name'],
        $r['status']['errorCode'],
        isset($r['status']['errorField']) ? $r['status']['errorField'] : ''
    ]);
};
$channel->basic_consume(ErplyConf::RABBITMQ['erply_queue'], '', false, true, false, false, $callback);
while (count($channel->callbacks)) {
    $channel->wait();
}
$channel->close();
$connection->close();
$sth = null;
$dbh = null;
