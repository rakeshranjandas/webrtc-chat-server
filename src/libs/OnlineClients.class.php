<?php
class OnlineClients
{
    private $onlineClientsMap;
    private $connClientMap;

    public function __construct()
    {
        $this->onlineClientsMap = [];
        $this->connClientMap = new SplObjectStorage();
    }

    public function makeClientOnline($client, $conn)
    {
        $this->onlineClientsMap[$client] = $conn;
        $this->connClientMap->offsetSet($conn, $client);
    }

    public function makeClientOffline($client)
    {
        unset($this->onlineClientsMap[$client]);
        $this->connClientMap->offsetUnset($this->getClientConnection($client));
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

    public function getClientFromConnection($conn)
    {
        if (!$this->connClientMap->offsetExists($conn))
            return 0;

        return $this->connClientMap->offsetGet($conn);
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
