<?php

use Pimple\Container;
use Slim\Factory\AppFactory;
use GuzzleHttp\Psr7\ServerRequest;
use Pimple\Psr11\Container as PsrContainer;

require __DIR__ . '/vendor/autoload.php';

/**
 * Class ServerlessRequestCreator
 */
class ServerlessRequestCreator {

    public $event;

    public function __construct($event)
    {
        $this->event = $event;
    }

    public function createRequestFromEvent(): ServerRequest
    {
        $bodyString = $this->event['body'] ?? '';
        return new ServerRequest(
            $this->event['httpMethod'],
            $this->event['path'],
            $this->event['headers'],
            $bodyString
        );
    }
}

function handler($event, $context) {
    $event = json_decode(json_encode($event), true);
    $container = require __DIR__ . '/container.php';
    if ($container instanceof Container) {
        $psrContainer = new PsrContainer($container);
    } else {
        $psrContainer = $container;
    }
    AppFactory::setContainer($psrContainer);
    $app = require __DIR__ . '/app.php';
    $container['event'] = $event;
    $container['context'] = $context;
    $requestCreator = new ServerlessRequestCreator($event);
    $request = $requestCreator->createRequestFromEvent();
    $response = $app->handle($request);

    $headers = $response->getHeaders();

    if (empty($headers['Content-Type'])) {
        $headers['Content-Type'] = 'text/plain';
    } else {
        $headers['Content-Type'] = $headers['Content-Type'][0];
    }

    $body = $response->getBody()->getContents();

    return [
        'isBase64Encoded' => false,
        'statusCode' => $response->getStatusCode(),
        'headers' => $headers,
        'body' => $body,
    ];
}

