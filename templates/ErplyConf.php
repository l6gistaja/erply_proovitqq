<?php

class ErplyConf
{
    const SITE = 'demo';
    
	const SITES = [
        'demo' => [
            'api' => [
                'clientCode' => ...,
                'username' => '...',
                'password' => '...'
            ],
            'productGroup' => 1
        ]
	];
	
	const RABBITMQ = [
        'host' => 'localhost',
        'port' => 5672,
        'username' => 'guest',
        'password' => 'guest',
        'erply_queue' => 'Erply_request'
	];
	
	const DATABASE = [
        'dsn' => 'mysql:dbname=erply_proovitqq;host=127.0.0.1',
        'username' => 'erply_proovitqq',
        'password' => 'erply_proovitqq'
	];
};
