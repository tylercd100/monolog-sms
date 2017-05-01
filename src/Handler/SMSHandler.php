<?php

namespace Tylercd100\Monolog\Handler;

use Exception;
use Monolog\Handler\SocketHandler;
use Monolog\Logger;
use Tylercd100\Monolog\Formatter\SMSFormatter;

abstract class SMSHandler extends SocketHandler
{

    /**
     * @var string
     */
    protected $authToken;

    /**
     * @var string
     */
    protected $authId;

    /**
     * @var string
     */
    protected $fromNumber;

    /**
     * @var string
     */
    protected $toNumber;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var integer
     */
    protected $limit;

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
     * @param int    $limit      The character limit
     */
    public function __construct($authToken, $authId, $fromNumber, $toNumber, $level = Logger::CRITICAL, $bubble = true, $useSSL = true, $host = 'api.plivo.com', $version = null, $limit = 160)
    {
        if (empty($version)) {
            throw new Exception('API Version is empty');
        }

        $connectionString = $useSSL ? 'ssl://'.$host.':443' : $host.':80';
        parent::__construct($connectionString, $level, $bubble);

        $this->authToken  = $authToken;
        $this->authId     = $authId;
        $this->fromNumber = $fromNumber;
        $this->toNumber   = $toNumber;
        $this->host       = $host;
        $this->version    = $version;
        $this->limit      = $limit;
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
    abstract protected function buildContent($record);

    /**
     * Builds the URL for the API call
     *
     * @return string
     */
    abstract protected function buildRequestUrl();

    /**
     * Builds the header of the API call
     *
     * @param  string $content
     * @return string
     */
    private function buildHeader($content)
    {
        $auth = $this->authToken;

        if ($this->authId) {
            $auth = "Basic " . base64_encode($this->authId.":".$this->authToken);
        }

        $header = $this->buildRequestUrl();

        $header .= "Host: {$this->host}\r\n";
        $header .= "Authorization: ".$auth."\r\n";
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
        return new SMSFormatter();
    }
}
