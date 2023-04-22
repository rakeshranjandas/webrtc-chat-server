<?php
require_once("libs/OnlineClients.class.php");
require_once("libs/SendRequests.class.php");

class Resolver
{

    private $onlineClients;
    private $sendRequests;

    public function __construct()
    {
        $this->onlineClients = new OnlineClients();
        $this->sendRequests = new SendRequests();
    }

    private function _isValidConnection($conn, $data)
    {

        if ($data->type !== "REGISTER" && $this->onlineClients->getClientFromConnection($conn) === 0) {
            echo "Invalid conn\n";
            return FALSE;
        }

        return TRUE;
    }

    public function closeConnection($conn)
    {
        $this->onlineClients->makeClientOfflineWithConnection($conn);
    }

    public function resolve($conn, $data)
    {

        if (!$this->_isValidConnection($conn, $data))
            return;

        $client = $this->onlineClients->getClientFromConnection($conn);

        echo "{$client} sends message: " . json_encode($data) . "\n";

        switch ($data->type) {
            case "REGISTER":
                $this->_onClientRegister($conn, $data);
                break;

            case "FROM_SENDER_SENDER_REQUESTING":
                $this->_onFromSenderSenderRequesting($client, $data);
                break;

            case "FROM_RECEIVER_RECEIVER_READY":
                $this->_onFromReceiverReceiverReady($client, $data);
                break;


            case "FROM_SENDER_SENDER_SDP":
                $this->_onFromSenderSenderSDP($client, $data);
                break;

            case "FROM_RECEIVER_RECEIVER_SDP":
                $this->_onFromReceiverReceiverSDP($client, $data);
                break;

            case "FROM_SENDER_SENT":
                $this->_onSenderSent($client, $data);
                break;
        }
    }

    private function _onClientRegister($conn, $data)
    {
        // Make client online
        // Check if any send requests are present for this client,
        //   if yes send "FOR_RECEIVER_SENDER_REQUESTING" to this client

        $this->onlineClients->makeClientOnline($data->from, $conn);

        if (count($this->sendRequests->getRequestingClients($data->from))) {

            $conn->send(json_encode([
                "type" => "FOR_RECEIVER_SENDER_REQUESTING"
            ]));
        }
    }

    private function _onFromSenderSenderRequesting($client, $data)
    {
        // Add request
        // If receiver is online, send "FOR_RECEIVER_SENDER_REQUESTING"

        $this->sendRequests->addRequest($client, $data->to);

        if ($this->onlineClients->isClientOnline($data->to)) {

            $this->onlineClients->getClientConnection($data->to)
                ->send(json_encode([
                    "type" => "FOR_RECEIVER_SENDER_REQUESTING"
                ]));
        }
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
        $this->onlineClients->getClientConnection($data->to)
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
