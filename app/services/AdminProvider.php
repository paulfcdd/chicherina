<?php

namespace Services;

use Doctrine\DBAL\Portability\Connection as Conn;
use Silex\Provider\DoctrineServiceProvider;

class AdminProvider extends DoctrineServiceProvider
{
    private $conn;

    public function __construct(Conn $connection)
    {
        $this->conn = $connection;
    }

    public function deleteFromDbAction(){}
}