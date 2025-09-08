<?php

namespace EventStore\Http\Exception;

class ClientException extends RequestException
{
    public function getResponse()
    {
        return $this->getPrevious()->getResponse();
    }
}
