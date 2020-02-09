<?php

namespace App\Console\Commands;

use App\Http\Controllers\WebSocketController;
use Illuminate\Console\Command;
use Ratchet\ConnectionInterface;
use React\Socket\Connection;

class WebSocketSendMessageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        // Get the image and convert into string
        $url = 'http://www.hdfondos.eu/pictures/2015/1012/1/orig_269813.jpg';
        $img = file_get_contents($url);

        // Encode the image string data into base64
        $data = base64_encode($img);
        $mimeType = get_headers($url);

        \Ratchet\Client\connect('ws://localhost:8090')
            ->then(function ($conn) use ($data, $mimeType) {
                $conn->on('message', function ($msg) use ($conn) {
                    echo "Received: {$msg}\n";
                    $conn->close();
                });

                $conn->send(json_encode([
                    'command' => 'file',
                    'from' => 'server',
                    'to' => 'some01',
                    'message' => [
                        'mime' => $mimeType,
                        'file' => $data
                    ]
                ]));
            }, function ($e) {
                echo "Could not connect: {$e->getMessage()}\n";
            });
    }
}
