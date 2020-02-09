<?php

namespace App\Http\Controllers;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

/**
 * @author Rohit Dhiman | @aimflaiims
 */
class WebSocketController implements MessageComponentInterface
{
    protected static $clients;
    private static $subscriptions;
    private static $users;
    private static $userresources;
    protected static $instance = null;

    protected function __construct()
    {
        self::$clients = new \SplObjectStorage;
        self::$subscriptions = [];
        self::$users = [];
        self::$userresources = [];
    }

    public static function instance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * [onOpen description]
     * @method onOpen
     * @param ConnectionInterface $conn [description]
     * @return void [JSON]                    [description]
     * @example connection               var conn = new WebSocket('ws://localhost:8090');
     */
    public function onOpen(ConnectionInterface $conn)
    {
        self::$clients->attach($conn);
        self::$users[$conn->resourceId] = $conn;
    }

    /**
     * [onMessage description]
     * @method onMessage
     * @param ConnectionInterface $conn [description]
     * @param  [JSON.stringify]              $msg  [description]
     * @return void [JSON]                    [description]
     * @example subscribe                conn.send(JSON.stringify({command: "subscribe", channel: "global"}));
     * @example groupchat                conn.send(JSON.stringify({command: "groupchat", message: "hello glob", channel: "global"}));
     * @example message                  conn.send(JSON.stringify({command: "message", to: "1", from: "9", message: "it needs xss protection"}));
     * @example register                 conn.send(JSON.stringify({command: "register", userId: 9}));
     */
    public function onMessage(ConnectionInterface $conn, $msg)
    {
        echo $msg;
        $data = json_decode($msg);
        if (isset($data->command)) {
            switch ($data->command) {
                case "subscribe":
                    self::$subscriptions[$conn->resourceId] = $data->channel;
                    break;
                case "groupchat":
                    //
                    // $conn->send(json_encode($this->subscriptions));
                    if (isset(self::$subscriptions[$conn->resourceId])) {
                        $target = self::$subscriptions[$conn->resourceId];
                        foreach (self::$subscriptions as $id => $channel) {
                            if ($channel == $target && $id != $conn->resourceId) {
                                self::$users[$id]->send($data->message);
                            }
                        }
                    }
                    break;
                case "message":
                    //
                    if (isset(self::$userresources[$data->to])) {
                        foreach (self::$userresources[$data->to] as $key => $resourceId) {
                            if (isset(self::$users[$resourceId])) {
                                self::$users[$resourceId]->send(json_encode([
                                    'type' => 'message_private',
                                    'data' => json_decode($msg, true),
                                ]));
                            }
                        }
                        $conn->send(json_encode(self::$userresources[$data->to]));
                    }

                    if (isset(self::$userresources[$data->from])) {
                        foreach (self::$userresources[$data->from] as $key => $resourceId) {
                            if (isset(self::$users[$resourceId]) && $conn->resourceId != $resourceId) {
                                self::$users[$resourceId]->send(json_encode([
                                    'type' => 'message_private',
                                    'data' => json_decode($msg, true),
                                ]));
                            }
                        }
                    }
                    break;
                case "register":
                    //
                    if (isset($data->userId)) {
                        if (isset(self::$userresources[$data->userId])) {
                            if (!in_array($conn->resourceId, self::$userresources[$data->userId])) {
                                self::$userresources[$data->userId][] = $conn->resourceId;
                            }
                        } else {
                            self::$userresources[$data->userId] = [];
                            self::$userresources[$data->userId][] = $conn->resourceId;
                        }
                    }

                    foreach (self::$users as $user) {
                        $user->send(json_encode([
                            'type' => 'connections',
                            'data' => self::$users
                        ], true));
                        $user->send(json_encode([
                            'type' => 'users',
                            'data' => collect(self::$userresources)
                        ], true));
                    }
                    break;
                case "file":
                    //
                    if (isset(self::$userresources[$data->to])) {
                        foreach (self::$userresources[$data->to] as $key => $resourceId) {
                            if (isset(self::$users[$resourceId])) {
                                self::$users[$resourceId]->send(json_encode([
                                    'type' => 'file',
                                    'data' => json_decode($msg, true),
                                ]));
                            }
                        }
                        $conn->send(json_encode(self::$userresources[$data->to]));
                    }

                    if (isset(self::$userresources[$data->from])) {
                        foreach (self::$userresources[$data->from] as $key => $resourceId) {
                            if (isset(self::$users[$resourceId]) && $conn->resourceId != $resourceId) {
                                self::$users[$resourceId]->send(json_encode([
                                    'type' => 'file',
                                    'data' => json_decode($msg, true),
                                ]));
                            }
                        }
                    }
                    break;
                default:
                    $example = array(
                        'methods' => [
                            "subscribe" => '{command: "subscribe", channel: "global"}',
                            "groupchat" => '{command: "groupchat", message: "hello glob", channel: "global"}',
                            "message" => '{command: "message", to: "1", message: "it needs xss protection"}',
                            "register" => '{command: "register", userId: 9}',
                        ],
                    );
                    $conn->send(json_encode($example));
                    break;
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        self::$clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
        unset(self::$users[$conn->resourceId]);
        unset(self::$subscriptions[$conn->resourceId]);

        foreach (self::$userresources as &$userId) {
            foreach ($userId as $key => $resourceId) {
                if ($resourceId == $conn->resourceId) {
                    unset($userId[$key]);
                }
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    public static function getData()
    {
        return [
            'connections' => self::$users,
            'resources' => self::$userresources,
            'clients' => self::$clients,
            'subscriptions' => self::$subscriptions,
        ];
    }

    /** protected to prevent cloning */
    protected function __clone()
    {
    }
}
