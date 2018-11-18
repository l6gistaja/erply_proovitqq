<?php

class ErplyManager
{

    public $debug;
    public $api;
    public $conf;
    public $response;

    public function __construct($debug)
    {
        $this->debug = $debug;
        $this->site = ErplyConf::SITES[ErplyConf::SITE];
        $this->api = new EAPI(
            self::getEApiURL($this->site['api']['clientCode']),
            $this->site['api']['clientCode'],
            $this->site['api']['username'],
            $this->site['api']['password']
        );
        $this->response = [
            'data' => [
                'name' => isset($_REQUEST['name']) ? trim($_REQUEST['name']) : ''
            ],
            'msg' => '',
            'debug' => []
        ];
    }
    
    public static function getEApiURL($clientCode) {
        return "https://".$clientCode.".erply.com/api/";
    }
    
    public static function getRabbitConnection() {
        return new PhpAmqpLib\Connection\AMQPStreamConnection(
            ErplyConf::RABBITMQ['host'],
            ErplyConf::RABBITMQ['port'],
            ErplyConf::RABBITMQ['username'],
            ErplyConf::RABBITMQ['password']
        );
    }
    
    public static function getDbConnection() {
        return new PDO(
            ErplyConf::DATABASE['dsn'],
            ErplyConf::DATABASE['username'],
            ErplyConf::DATABASE['password']
        );
    }
    
    public function getSessionId() {
        return isset($_SESSION['EAPISessionKey'][$this->api->clientCode][$this->api->username])
            ? $_SESSION['EAPISessionKey'][$this->api->clientCode][$this->api->username] : '';
    }
    
    private function getDebugKey($line) {
        return is_null($line) ? 'T'.time() : (is_string($line) ? $line : 'L'.$line);
    }
    
    private function addDebug($data, $line = null) {
        if($this->debug) $this->response['debug'][$this->getDebugKey($line)] = $data;
    }
    
    public function sendRequest($method, $parameters, $line = null) {
        $apiResponse = json_decode($this->api->sendRequest($method, $parameters) ,true);
        if($this->debug) {
            $this->addDebug($parameters, $this->getDebugKey($line).' Erply API request parameters');
            $this->addDebug($apiResponse, $this->getDebugKey($line).' Erply API response');
        }
        return $apiResponse;
    }
    
    private function isValidProductName() {
        if($this->response['data']['name'] == '') {
            $this->response['msg'] = "Product name shouldn't be empty.";
            return false;
        }
        $apir = $this->sendRequest("getProducts",
            ["groupID" => $this->site['productGroup'], "name" => $this->response['data']['name']],
            __LINE__);
        if($apir['status']['recordsInResponse'] > 0) {
            $this->response['msg'] = "Product with name '".$this->response['data']['name']."' already exists.";
            return false;
        }
        return true;
    }
    
    public static function getErrorText($data, $defaultOk = '') {
        if(isset($data['errorCode'])) {
            $error = $data['errorCode'];
            $field = isset($data['errorField']) ? $data['errorField'] : '';
        } else {
            $error = $data['error'];
            $field = $data['field'];
        }
        return $error ? '<a href="https://learn-api.erply.com/error-codes" target="_blank">Error '.$error.'</a> @ '.$field : $defaultOk;
    }
    private function actionAddProdViaApi() {
        if(!$this->isValidProductName()) return;
        $apir = $this->sendRequest("saveProduct",
            ["groupID" => $this->site['productGroup'], "name" => $this->response['data']['name']],
            __LINE__);
        $error = self::getErrorText($apir['status']);
        $this->response['msg'] = "Product with name '".$this->response['data']['name']."' was "
            .($error == '' ? '' : 'NOT ')
            ."saved. ".$error;
        $this->response['data']['name'] = "";
    }
    
    private function actionAddProdViaRabbit() {
        if(!$this->isValidProductName()) return;
        $connection = self::getRabbitConnection();
        $channel = $connection->channel();
        $channel->queue_declare(ErplyConf::RABBITMQ['erply_queue'], false, false, false, false);
        $msg = new PhpAmqpLib\Message\AMQPMessage(json_encode([
            'request' => 'saveProduct',
            'clientCode' => $this->site['api']['clientCode'],
            'sessionKey' => $this->getSessionId(),
            'groupID' => $this->site['productGroup'],
            'name' => $this->response['data']['name']
        ]));
        $channel->basic_publish($msg, '', ErplyConf::RABBITMQ['erply_queue']);
        $channel->close();
        $connection->close();
        $this->response['msg'] = "Product sent to Rabbit. View Rabbit log to see was it saved.";
    }
    
    private function actionEndSession() {
        $_SESSION = array();
        session_destroy();
        $this->response['msg'] = "Session was forcibly ended.";
    }
    
    private function actionGetProductGroups() {
        if(!$this->debug) {
            $this->response['msg'] = "Set ErplyManager constructor's second parameter to true in index.php to see product groups.";
            return;
        }
        $this->sendRequest("getProductGroups", [], __LINE__);
        $this->response['msg'] = "See product group data below in Debug Data section.";
    }
    
    private function actionShowLog() {
        $dbh = self::getDbConnection();
        $sth = $dbh->prepare("SELECT * FROM erply_log WHERE session_id = ? ORDER BY t1 DESC");
        $sth->execute([$this->getSessionId()]);
        $this->response['log'] = [];
        while($result = $sth->fetch(PDO::FETCH_ASSOC)) { $this->response['log'][] = $result; }
    }
    
    private function actionDeleteLog() {
        $dbh = self::getDbConnection();
        $sth = $dbh->prepare("DELETE FROM erply_log WHERE session_id = ?");
        $sth->execute([$this->getSessionId()]);
        $this->response['msg'] = "Rabbit's log deleted.";
    }
    
    public function process() { 
        try {
            foreach(['actionAddProdViaApi','actionEndSession','actionGetProductGroups','actionShowLog','actionDeleteLog','actionAddProdViaRabbit'] as $action) {
                if(isset($_REQUEST[$action])) {
                    $this->$action();
                    return;
                }
            }
        } catch (Exception $e) {
            $this->response['msg'] = 'Technical error occured: '.$e->getMessage();
            $this->addDebug($e->getTrace(), 'STACKTRACE');
        }
    }
}
