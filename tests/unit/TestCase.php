<?php

declare(strict_types=1);

namespace NiceBase\Tests;

use NiceBase\Database\Connection;
use NiceBase\Database\Exception;
use NiceBase\Mapper;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $db = new Connection('sqlite:' . 'tests/data/tests.sqlite');
        try {
            $db->execute('pragma foreign_keys = off;');
            foreach (['tests/init.sql', 'tests/seed.sql'] as $fileName) {
                $sql = file_get_contents($fileName);
                if (false !== $sql) {
                    $db->execute($sql);
                }
            }
            // SQLite requires you to manually enable foreign key constraints
            // for each connection, for each db
            $db->execute('pragma foreign_keys = on;');
        } catch (Exception $e) {
            echo 'Unable to initialise database: ', $e->getMessage(), "\n";
            exit(4);
        }
        Mapper::init(db: $db);
    }
}
