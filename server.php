<?php
require 'vendor/autoload.php';
require 'MyApp/Chat.php';


use MyApp\Chat;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\Socket\SocketServer;
use React\Socket\SecureServer;

$loop = Loop::get();
$webSocket = new SocketServer('0.0.0.0:8080', [], $loop);
$secureWebSocket = new SecureServer($webSocket, $loop, [
    'local_cert' => 'SSL/cert.pem',    
    'local_pk' => 'SSL/private.key',      
    'allow_self_signed' => true,     
    'verify_peer' => false           
]);

$server = new IoServer(
  new HttpServer(new WsServer(new Chat())),
  $secureWebSocket,
  $loop
);
 $server->run();