<?php
/**
 * Copyright (C) 2014 David Young
 *
 * Tests the MySQL PDO driver
 */
namespace RDev\Models\Databases\SQL\PDO\MySQL;
use RDev\Models\Databases\SQL;
use RDev\Tests\Models\Databases\SQL\Mocks;

class DriverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests creating the DSN with a Unix socket specified
     */
    public function testCreatingDSNWithUnixSocket()
    {
        $server = new Mocks\Server();
        $driver = new Driver();
        $unixSocket = "fakesocket";
        $expectedResult = "mysql: host=" . $server->getHost() . ";dbname=" . $server->getDatabaseName() . ";"
            . "port=" . $server->getPort() . ";charset=" . $server->getCharset() . ";"
            . "unix_socket=" . $unixSocket . ";";
        $this->assertEquals($expectedResult, $this->getDSN($driver, $server, ["unix_socket" => $unixSocket]));
    }

    /**
     * Tests creating the DSN without a Unix socket specified
     */
    public function testCreatingDSNWithoutUnixSocket()
    {
        $server = new Mocks\Server();
        $driver = new Driver();
        $expectedResult = "mysql: host=" . $server->getHost() . ";dbname=" . $server->getDatabaseName() . ";"
            . "port=" . $server->getPort() . ";charset=" . $server->getCharset() . ";";
        $this->assertEquals($expectedResult, $this->getDSN($driver, $server));
    }

    /**
     * Gets the DSN for the input driver and server
     *
     * @param Driver $driver The driver to connect with
     * @param SQL\Server $server The server to connect to
     * @param array $connectionOptions The connection options
     * @return string The DSN for the input driver and server
     */
    private function getDSN(Driver $driver, SQL\Server $server, array $connectionOptions = [])
    {
        $class = new \ReflectionClass(get_class($driver));
        $method = $class->getMethod("getDSN");
        $method->setAccessible(true);

        return $method->invokeArgs($driver, [$server, $connectionOptions, $connectionOptions]);
    }
} 