<?php

include 'dependencies.php';

class FibonacciRpcClient
{
    private $connection;
    private $channel;
    private $callback_queue;
    private $response;
    private $corr_id;
    public function __construct()
    {
        $this->connection = ErplyManager::getRabbitConnection();
        $this->channel = $this->connection->channel();
        list($this->callback_queue, ,) = $this->channel->queue_declare(
            "",
            false,
            false,
            true,
            false
        );
        $this->channel->basic_consume(
            $this->callback_queue,
            '',
            false,
            false,
            false,
            false,
            array(
                $this,
                'onResponse'
            )
        );
    }
    public function onResponse($rep)
    {
        if ($rep->get('correlation_id') == $this->corr_id) {
            $this->response = $rep->body;
        }
    }
    public function call($n)
    {
        $this->response = null;
        $this->corr_id = 'fdlcc7a66dd317a841125040de1220cc507555fcca5f.'.mktime();
        $msg = new PhpAmqpLib\Message\AMQPMessage(
            json_encode([
                'request' => 'saveProduct',
                'clientCode' => 502115,
                'sessionKey' => 'fdlcc7a66dd317a841125040de1220cc507555fcca5f',
                'groupID' => 2,
                'name' => md5(mktime())
            ]),
            array(
                'correlation_id' => $this->corr_id,
                'reply_to' => $this->callback_queue
            )
        );
        $this->channel->basic_publish($msg, '', ErplyConf::RABBITMQ['erply_queue']);
        while (!$this->response) {
            $this->channel->wait();
        }
        return $this->response;
    }
}
$fibonacci_rpc = new FibonacciRpcClient();
$response = $fibonacci_rpc->call(2);
echo ' [.] Got ', $response, "\n";

$data = json_decode($response, true);
$dbh = ErplyManager::getDbConnection();
$sth = $dbh->prepare('INSERT INTO erply_log (session_id, t0, t1, name, error) VALUES (?,?,?,?,?)');
$sth->execute([$data['session_id'], $data['t0'], $data['t1'], $data['name'], $data['error']]);
