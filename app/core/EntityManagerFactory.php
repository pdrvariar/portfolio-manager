<?php
namespace App\Core;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class EntityManagerFactory
{
    private static ?EntityManager $entityManager = null;

    public static function createEntityManager(): EntityManager
    {
        if (self::$entityManager !== null) {
            return self::$entityManager;
        }

        $isDevMode = ($_ENV['APP_ENV'] ?? 'production') === 'development';

        $config = \Doctrine\ORM\ORMSetup::createAttributeMetadataConfiguration(
            [dirname(__DIR__) . '/Entities'],
            $isDevMode,
            null,
            $isDevMode ? new ArrayAdapter() : new FilesystemAdapter()
        );

        $connectionParams = [
            'driver'   => 'pdo_mysql',
            'host'     => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port'     => $_ENV['DB_PORT'] ?? '3306',
            'user'     => $_ENV['DB_USER'] ?? 'root',
            'password' => trim($_ENV['DB_PASS'] ?? '', "'\""),
            'dbname'   => $_ENV['DB_NAME'] ?? 'portfolio_db',
            'charset'  => 'utf8mb4',
        ];

        $connection = DriverManager::getConnection($connectionParams, $config);
        self::$entityManager = new EntityManager($connection, $config);

        return self::$entityManager;
    }
}
