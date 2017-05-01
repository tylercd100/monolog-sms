<?php

namespace Tylercd100\Monolog\Handler;

use Exception;
use Monolog\Logger;

/**
* Clickatell - Monolog Handler
* @url https://www.clickatell.com/developers/api-documentation/rest-api-request-parameters
*/
class ClickatellHandler extends SMSHandler
{
    /**
     * API version 1
     */
    const API_V1 = '2010-04-01';

    /**
     * @param string $secret     Twilio API Secret Token
     * @param string $fromNumber The phone number that will be shown as the sender ID
     * @param string $toNumber   The phone number to which the message will be sent
     * @param int    $level      The minimum logging level at which this handler will be triggered
     * @param bool   $bubble     Whether the messages that are handled can bubble up the stack or not
     * @param bool   $useSSL     Whether to connect via SSL.
     * @param string $host       The Twilio server hostname.
     * @param string $version    The Twilio API version (default ClickatellHandler::API_V1)
     * @param int    $limit      The character limit
     */
    public function __construct($secret, $fromNumber, $toNumber, $level = Logger::CRITICAL, $bubble = true, $useSSL = true, $host = 'platform.clickatell.com', $version = self::API_V1, $limit = 160)
    {
        if ($version !== self::API_V1) {
            throw new Exception("API Version \'{$version}\' is not supported!");
        }
        parent::__construct($secret, null, $fromNumber, $toNumber, $level, $bubble, $useSSL, $host, $version, $limit);
    }

    /**
     * {@inheritdoc}
     *
     * @param  array  $record
     * @return string
     */
    protected function buildContent($record)
    {
        if (strlen($record['formatted']) > $this->limit) {
            $record['formatted'] = substr($record['formatted'], 0, $this->limit);
        }

        $dataArray = [
            'content' => $record['formatted'],
            'to' => (!is_array($this->toNumber)? [$this->toNumber] : $this->toNumber)
        ];

        ($this->fromNumber)? $dataArray['from'] = $this->fromNumber : false;

        return json_encode($dataArray);
    }

    /**
     * Builds the URL for the API call
     *
     * @return string
     */
    protected function buildRequestUrl()
    {
        return "POST /messages HTTP/1.1\r\n";
    }
}
