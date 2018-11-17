<?php

include 'dependencies.php';

$connection = ErplyManager::getRabbitConnection();
$channel = $connection->channel();
$channel->queue_declare(ErplyConf::RABBITMQ['erply_queue'], false, false, false, false);

echo " [x] Awaiting RPC requests\n";
$callback = function ($req) {
    echo ' [.] '.$req->get('correlation_id')." =>  ".$req->body."\n";
    $request = json_decode($req->body, true);
    $tmp = explode('.', $req->get('correlation_id'));
    $response = ['session_id' => $tmp[0], 't0' => (int) $tmp[1], 't1' => mktime(), 'name' => $request['name'], 'error' => 0];
    $msg = new PhpAmqpLib\Message\AMQPMessage(
        json_encode($response),
        array('correlation_id' => $req->get('correlation_id'))
    );
    $req->delivery_info['channel']->basic_publish(
        $msg,
        '',
        $req->get('reply_to')
    );
    $req->delivery_info['channel']->basic_ack(
        $req->delivery_info['delivery_tag']
    );
};
$channel->basic_qos(null, 1, null);
$channel->basic_consume(ErplyConf::RABBITMQ['erply_queue'], '', false, false, false, false, $callback);
while (count($channel->callbacks)) {
    $channel->wait();
}
$channel->close();
$connection->close();
