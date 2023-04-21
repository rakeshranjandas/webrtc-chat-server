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
        if (!$this->isClientOnline($client))
            throw new Exception("Client is not online.");

        return $this->onlineClientsMap[$client];
    }
}
