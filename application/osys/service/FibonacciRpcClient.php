<?php
/**
 * DateTime: 2018/11/20 16:49
 * Author: John
 * Email: 2639816206@qq.com
 */

namespace app\osys\service;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class FibonacciRpcClient
{
    protected $connection;
    protected $channel;
    protected $callback_queue;
    protected $response;
    protected $corr_id;

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection('192.168.31.200', 5672, 'orait', 'orait2018');
        $this->channel = $this->connection->channel();
        list($this->callback_queue, ,) = $this->channel->queue_declare(
            "",
            false,
            false,
            true,
            false
        );
        $this->channel->basic_consume(
            $this->callback_queue,
            '',
            false,
            false,
            false,
            false,
            array(
                $this,
                'onResponse'
            )
        );
    }

    public function onResponse($rep)
    {
        if ($rep->get('correlation_id') == $this->corr_id) {
            $this->response = $rep->body;
        }
    }

    public function call($n)
    {
        //echo $n;exit;
        $this->response = null;
        $this->corr_id = uniqid();

        $msg = new AMQPMessage(
            (string) $n,
            array(
                'correlation_id' => $this->corr_id,
                'reply_to' => $this->callback_queue
            )
        );
        $this->channel->basic_publish($msg, '', 'hello');
        while (!$this->response) {
            $this->channel->wait();
        }
        //return intval($this->response);
        return $this->response;
    }
}