<?php
class SendRequests
{

    private $requestsMap;

    public function __construct()
    {
        $this->requestsMap = [];
    }

    public function addRequest($from, $to)
    {
        $this->requestsMap[$to][$from] = 1;
    }

    public function removeRequest($from, $to)
    {
        unset($this->requestsMap[$to][$from]);
    }

    public function getRequestingClients($to)
    {
        if (!isset($this->requestsMap[$to])) return [];
        return array_keys($this->requestsMap[$to]);
    }
}
