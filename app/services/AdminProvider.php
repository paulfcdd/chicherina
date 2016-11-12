<?php

namespace Services;

use Doctrine\DBAL\Portability\Connection;
use Silex\Provider\DoctrineServiceProvider;

class AdminProvider extends DoctrineServiceProvider
{
    private $conn;

    public function __construct(Connection $connection)
    {
        $this->conn = $connection;
    }

    public function deleteFromDbAction(){
        
    }
}