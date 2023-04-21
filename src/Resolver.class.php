<?php
require_once("libs/OnlineClients.class.php");
require_once("libs/SendRequests.class.php");

class Resolver
{

    private $onlineClients;
    private $sendRequests;
    private $connClientMap;

    public function __construct()
    {
        $this->onlineClients = new OnlineClients();
        $this->sendRequests = new SendRequests();
        $this->connClientMap = new SplObjectStorage();
    }

    private function _addToConnClientMap($conn, $client)
    {
        $this->connClientMap[$conn] = $client;
    }

    private function _getClientFromConn($conn)
    {
        if (!$this->connClientMap->offsetExists($conn))
            throw new Exception("Conn does not exist");

        return $this->connClientMap->offsetGet($conn);
    }

    public function closeConnection($conn)
    {
        $this->onlineClients->makeClientOfflineWithConnection($conn);
    }

    public function resolve($conn, $data)
    {
        switch ($data->type) {
            case "REGISTER":
                $this->_onClientRegister($conn, $data);
                $this->_addToConnClientMap($conn, $data->from);
                break;

            case "FROM_SENDER_SENDER_REQUESTING":
                $this->_onFromSenderSenderRequesting($this->_getClientFromConn($conn), $data);
                break;

            case "FROM_RECEIVER_RECEIVER_READY":
                $this->_onFromReceiverReceiverReady($this->_getClientFromConn($conn), $data);
                break;


            case "FROM_SENDER_SENDER_SDP":
                $this->_onFromSenderSenderSDP($this->_getClientFromConn($conn), $data);
                break;

            case "FROM_RECEIVER_RECEIVER_SDP":
                $this->_onFromReceiverReceiverSDP($this->_getClientFromConn($conn), $data);
                break;

            case "FROM_SENDER_SENT":
                $this->_onSenderSent($this->_getClientFromConn($conn), $data);
                break;
        }
    }

    private function _onClientRegister($conn, $data)
    {
        $this->connClientMap[$conn] = $data->from;
        $this->onlineClients->makeClientOnline($data->from, $conn);

        //
    }

    private function _onFromSenderSenderRequesting($client, $data)
    {
        $this->sendRequests->addRequest($client, $data->to);

        //
    }

    private function _onFromReceiverReceiverReady($client, $data)
    {
        // Get clients_with_send_requests
        // Filter clients who are online
        // Send "FOR_SENDER_RECEIVER_READY" to online clients_with_send_requests

        foreach ($this->sendRequests->getRequestingClients($client) as $sendRequestingClient) {

            if ($this->onlineClients->isClientOnline($sendRequestingClient)) {

                $this->onlineClients->getClientConnection($sendRequestingClient)
                    ->send(json_encode([
                        "type" => "FOR_SENDER_RECEIVER_READY",
                        "to" => $client
                    ]));
            }
        }
    }

    private function _onFromSenderSenderSDP($client, $data)
    {
        $this->onlineClients->getClientConnection($client)
            ->send(json_encode([
                "type" => "FOR_RECEIVER_SENDER_SDP",
                "from" => $client,
                "sdp" => $data->sdp
            ]));
    }

    private function _onFromReceiverReceiverSDP($client, $data)
    {
        $this->onlineClients->getClientConnection($data->from)->send(json_encode([
            "type" => "FOR_SENDER_RECEIVER_SDP",
            "to" => $client,
            "sdp" => $data->sdp
        ]));
    }

    private function _onSenderSent($client, $data)
    {
        $this->sendRequests->removeRequest($client, $data->to);
    }
}
