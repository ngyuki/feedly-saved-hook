<?php
namespace App;

use Zend\Http\Client;

class HttpClient extends Client
{
    public function addHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            $this->getRequest()->getHeaders()->addHeaderLine($name, $value);
        }
    }

    public function setAccessToken($access_token)
    {
        $this->addHeaders([
            'Authorization' => "OAuth $access_token",
        ]);
    }

    public function setJsonBody($data)
    {
        $this->addHeaders([
            'Content-Type' => 'application/json',
        ]);
        $this->setRawBody(json_encode($data));
    }

    public function safeRequest()
    {
        $response = $this->send();
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException($response->toString());
        }
        return $response;
    }

    public function safeRequestAsJson()
    {
        $response = $this->safeRequest();
        $data = json_decode($response->getBody(), true);
        if ($data === false) {
            throw new \RuntimeException('unable json decode');
        }
        return $data;
    }
}
