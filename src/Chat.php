<?php

namespace MyApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

require_once("Resolver.class.php");

class Chat implements MessageComponentInterface
{
    protected $resolver;

    public function __construct()
    {
        $this->resolver = new \Resolver();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $conn, $msg)
    {
        // foreach ($this->clients as $client) {
        //     if ($from !== $client) {
        //         // The sender is not the receiver, send to each client connected
        //         $client->send($msg);
        //     }
        // }

        echo "Message: $msg";

        $data = json_decode($msg);
        $this->resolver->resolve($conn, $data);
    }

    public function onClose(ConnectionInterface $conn)
    {
        // // The connection is closed, remove it, as we can no longer send it messages
        // $this->clients->detach($conn);

        $this->resolver->closeConnection($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}
