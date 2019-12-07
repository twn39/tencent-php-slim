<?php

use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\StreamFactory;

require __DIR__ . '/vendor/autoload.php';

/**
 * Class ServerlessRequestCreator
 */
class ServerlessRequestCreator {

    public $event;

    public $context;

    public function __construct($event, $context)
    {
        $this->event = $event;
        $this->context = $context;
    }

    public function createRequestFromEvent(): ServerRequest
    {
        $bodyString = $this->event->body ?? '';
        $streamFactory = new StreamFactory();
        $bodyStream = $streamFactory->createStream($bodyString);
        return new ServerRequest(
            [],[],
            $this->event->path,
            $this->event->httpMethod,
            $bodyStream,
            (array)$this->event->headers,
            [],
            (array)$this->event->queryString,
            $bodyString
        );
    }
}

function handler($event, $context) {
    $app = require __DIR__ . '/app.php';
    $container = $app->getContainer();
    $container['event'] = $event;
    $container['context'] = $context;
    $requestCreator = new ServerlessRequestCreator($event, $context);
    $request = $requestCreator->createRequestFromEvent();
    $response = $app->handle($request);

    $headers = $response->getHeaders();
    if (isset($headers['Content-Type'])) {
        $headers['Content-Type'] = $headers['Content-Type'][0];
    } else {
        $headers['Content-Type'] = 'text/plain';
    }

    $body = $response->getBody()->getContents();

    return [
        'isBase64Encoded' => false,
        'statusCode' => $response->getStatusCode(),
        'headers' => $headers,
        'body' => $body,
    ];
}

