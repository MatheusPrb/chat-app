<?php

namespace App\WebSocket;

use Hyperf\Redis\Redis;
use Swoole\WebSocket\Server;
use Swoole\WebSocket\Frame;
use Swoole\Http\Request;

class ChatHandler
{

    public function __construct(
        private Redis $redis
    ) {}
    public function onOpen(Server $server, Request $frame)
    {
        echo "Cliente {$frame->fd} conectado\n";
    }

    public function onMessage($server, $frame): void
    {
        $data = json_decode($frame->data, true);

        if (!$data || !isset($data['type'])) {
            return;
        }

        if ($data['type'] === 'join') {
            $this->joinRoom($server, $frame->fd, $data);
            return;
        }

        if ($data['type'] === 'message') {
            $this->broadcastToRoom($server, $frame->fd, $data);
        }
    }

    private function joinRoom($server, int $fd, array $data): void
    {
        $user = $data['user'];
        $room = $data['room'];

        $this->redis->hSet("fd:{$fd}", 'user', $user);
        $this->redis->hSet("fd:{$fd}", 'room', $room);

        $this->redis->sAdd("room:{$room}", $fd);
        $fds = $this->redis->sMembers("room:{$room}");

        foreach ($fds as $clientFd) {
            if ($server->isEstablished((int)$clientFd)) {
                $server->push((int)$clientFd, json_encode([
                    'type' => 'system',
                    'user' => $user,
                    'text' => "Entrou na sala {$room}"
                ]));
            }
        }
    }

    private function broadcastToRoom($server, int $fd, array $message): void
    {
        $room = $this->redis->hGet("fd:{$fd}", 'room');
        if (!$room) {
            return;
        }

        $fds = $this->redis->sMembers("room:{$room}");
        foreach ($fds as $clientFd) {
            if ($server->isEstablished((int)$clientFd)) {
                $server->push((int)$clientFd, json_encode($message));
            }
        }
    }

    public function onClose($server, int $fd): void
    {
        $room = $this->redis->hGet("fd:{$fd}", 'room');
        $user = $this->redis->hGet("fd:{$fd}", 'user');

        if ($room) {
            $this->redis->sRem("room:{$room}", $fd);
        }

        $this->redis->del("fd:{$fd}");

        $fds = $this->redis->sMembers("room:{$room}");
        foreach ($fds as $clientFd) {
            $server->push((int)$clientFd, json_encode([
                'type' => 'system',
                'user' => $user,
                'text' => "Saiu na sala {$room}"
            ]));
        }
    }
}
