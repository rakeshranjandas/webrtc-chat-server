<?php
class OnlineClients
{
    private $onlineClientsMap;

    public function __construct()
    {
        $this->onlineClientsMap = [];
    }

    public function makeClientOnline($client, $conn)
    {
        $this->onlineClientsMap[$client] = $conn;
    }

    public function makeClientOffline($client)
    {
        unset($this->onlineClientsMap[$client]);
    }

    public function makeClientOfflineWithConnection($conn)
    {
        foreach ($this->onlineClientsMap as $client => $clientConn) {
            if ($clientConn === $conn) {
                $this->makeClientOffline($client);
                break;
            }
        }
    }

    public function isClientOnline($client)
    {
        return isset($this->onlineClientsMap[$client]);
    }

    public function getClientConnection($client)
    {
        if (!$this->isClientOnline($client)) return new NullConnection($client);

        return $this->onlineClientsMap[$client];
    }
};

class NullConnection
{
    private $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function send($message)
    {
        echo "Client {$this->client} is not online anymore. Sending to void. Message: {$message}\n";
    }
}
