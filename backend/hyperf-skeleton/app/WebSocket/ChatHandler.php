<?php

namespace App\WebSocket;

use Swoole\WebSocket\Server;
use Swoole\WebSocket\Frame;

class ChatHandler
{
    public function onOpen(Server $server, $frame)
    {
        echo "Cliente {$frame->fd} conectado\n";
    }

    public function onMessage(Server $server, Frame $frame)
    {
        $msg = $frame->data;
        echo "Mensagem recebida: $msg\n";

        foreach ($server->connections as $fd) {
            $server->push($fd, $msg);
        }
    }

    public function onClose(Server $server, $fd, $reactorId)
    {
        echo "Cliente {$fd} desconectou\n";
    }
}
