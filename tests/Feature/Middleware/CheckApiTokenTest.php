<?php

use App\Http\Middleware\CheckApiToken;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

it('blocks requests without Authorization header', function () {
    $request = Request::create('/dummy', 'GET');

    $response = new CheckApiToken()->handle($request, fn() => new Response());

    expect($response->getStatusCode())->toBe(401);
});

it('blocks requests with wrong token', function () {
    config(['auth.api_token' => 'correct-token']);

    $request = Request::create('/dummy', 'GET');
    $request->headers->set('Authorization', 'Bearer wrong-token');

    $response = new CheckApiToken()->handle($request, fn() => new Response());

    expect($response->getStatusCode())->toBe(401);
});

it('allows requests with correct token', function () {
    config(['auth.api_token' => 'correct-token']);

    $request = Request::create('/dummy', 'GET');
    $request->headers->set('Authorization', 'Bearer correct-token');

    $expectedResponse = new Response();

    $response = new CheckApiToken()->handle($request, fn() => $expectedResponse);

    expect($response)->toBe($expectedResponse);
});
