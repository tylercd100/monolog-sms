<?php

namespace Tylercd100\Monolog\Handler;

use Exception;
use Monolog\Handler\SocketHandler;
use Monolog\Logger;
use Tylercd100\Monolog\Formatter\PlivoFormatter;

/**
* Plivo - Monolog Handler
* @url https://www.plivo.com/docs/api/message/
*/
class PlivoHandler extends SocketHandler
{

    /**
     * Use API version 1
     */
    const API_V1 = 'v1';

    /**
     * @var string
     */
    private $authToken;

    /**
     * @var string
     */
    private $authId;

    /**
     * @var string
     */
    private $fromNumber;

    /**
     * @var string
     */
    private $toNumber;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $version;

    /**
     * @param string $authToken  Plivo API Auth Token
     * @param string $authId     Plivo API Auth ID
     * @param string $fromNumber The phone number that will be shown as the sender ID
     * @param string $toNumber   The phone number to which the message will be sent
     * @param int    $level      The minimum logging level at which this handler will be triggered
     * @param bool   $bubble     Whether the messages that are handled can bubble up the stack or not
     * @param bool   $useSSL     Whether to connect via SSL.
     * @param string $host       The Plivo server hostname.
     * @param string $version    The Plivo API version (default PlivoHandler::API_V1)
     */
    public function __construct($authToken, $authId, $fromNumber, $toNumber, $level = Logger::CRITICAL, $bubble = true, $useSSL = true, $host = 'api.plivo.com', $version = self::API_V1)
    {

        if($version !== self::API_V1){
            throw new Exception('API Version \'{$version}\' is not supported!');
        }

        $connectionString = $useSSL ? 'ssl://'.$host.':443' : $host.':80';
        parent::__construct($connectionString, $level, $bubble);

        $this->authToken  = $authToken;
        $this->authId     = $authId;
        $this->fromNumber = $fromNumber;
        $this->toNumber   = $toNumber;
        $this->host       = $host;
        $this->version    = $version;

    }

    /**
     * {@inheritdoc}
     *
     * @param  array  $record
     * @return string
     */
    protected function generateDataStream($record)
    {
        $content = $this->buildContent($record);
        return $this->buildHeader($content) . $content;
    }

    /**
     * Builds the body of API call
     *
     * @param  array  $record
     * @return string
     */
    private function buildContent($record)
    {
        $dataArray = array(
            'src'  => $this->fromNumber,
            'dst'  => $this->toNumber,
            'text' => $record['formatted'],
        );
        return json_encode($dataArray);
    }

    /**
     * Builds the header of the API Call
     *
     * @param  string $content
     * @return string
     */
    private function buildHeader($content)
    {
        $auth = base64_encode($this->authId.":".$this->authToken);

        if ($this->version == self::API_V1) {
            $header = "POST /v1/Account/{$this->authId}/Message/ HTTP/1.1\r\n";
        } else {
            throw new Exception('API Version \'{$version}\' is not supported!');
        }

        $header .= "Host: {$this->host}\r\n";
        $header .= "Authorization: Basic ".$auth."\r\n";;
        $header .= "Content-Type: application/json\r\n";
        $header .= "Content-Length: " . strlen($content) . "\r\n";
        $header .= "\r\n";
        return $header;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $record
     */
    protected function write(array $record)
    {
        parent::write($record);
        $this->closeSocket();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultFormatter()
    {
        return new PlivoFormatter();
    }
}
