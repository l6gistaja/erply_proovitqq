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
            'productGroup' => 0
        ]
	];
	
	const RABBITMQ = [
        'host' => '...',
        'port' => ...,
        'username' => '...',
        'password' => '...',
        'erply_queue' => 'Erply_RPC'
	];
	
	const DATABASE = [
        'dsn' => 'mysql:dbname=erply_proovitqq;host=...',
        'username' => 'erply_proovitqq',
        'password' => 'erply_proovitqq'
	];
};
