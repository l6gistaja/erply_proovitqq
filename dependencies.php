<?php 

require_once '../../confs/ErplyConf.php';
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
require_once __DIR__ . '/lib/EAPI.class.php';
require_once __DIR__ . '/lib/ErplyManager.php';
