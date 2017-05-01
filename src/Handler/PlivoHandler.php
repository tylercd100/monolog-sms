<?php

namespace Tylercd100\Monolog\Handler;

use Exception;
use Monolog\Logger;

/**
* Plivo - Monolog Handler
* @url https://www.plivo.com/docs/api/message/
*/
class PlivoHandler extends SMSHandler
{
    /**
     * API version 1
     */
    const API_V1 = 'v1';

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
    public function __construct($authToken, $authId, $fromNumber, $toNumber, $level = Logger::CRITICAL, $bubble = true, $useSSL = true, $host = 'api.plivo.com', $version = self::API_V1, $limit = 160)
    {
        if ($version !== self::API_V1) {
            throw new Exception('API Version \'{$version}\' is not supported!');
        }
        parent::__construct($authToken, $authId, $fromNumber, $toNumber, $level, $bubble, $useSSL, $host, $version, $limit);
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

        $dataArray = array(
            'src'  => $this->fromNumber,
            'dst'  => $this->toNumber,
            'text' => $record['formatted'],
        );
        return json_encode($dataArray);
    }

    /**
     * Builds the URL for the API call
     *
     * @return string
     */
    protected function buildRequestUrl()
    {
        return "POST /{$this->version}/Account/{$this->authId}/Message/ HTTP/1.1\r\n";
    }
}
