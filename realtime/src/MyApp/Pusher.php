<?php

namespace MyApp;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

$is_live = true;

require_once __DIR__ .'/../../../includes/class-tournament-in-progress.php';

class Pusher implements WampServerInterface {
    /**
     * A lookup of all the topics clients have subscribed to
     */
    protected $subscribedTopics = array();

    public function onSubscribe(ConnectionInterface $conn, $topic) {

        $this->subscribedTopics[$topic->getId()] = $topic;

        echo "New connection! ({$topic})\n";

        switch($topic){

            case "live" :

                $live_state = \tournament_in_progress::get_live_state();

                $topic->broadcast($live_state);

                break;

        }


    }

    /**
     * @param string JSON'ified string we'll receive from ZeroMQ
     */
    public function onRealtimeEvent($entry) {
        $entryData = json_decode($entry, true);

        // If the lookup topic object isn't set there is no one to publish to
        if (!in_array($entryData['subscription'], $this->subscribedTopics)) {
            return;
        }

        $topic = $this->subscribedTopics[$entryData['subscription']];


        echo "New update on{$topic}\n";

        // re-send the data to all the clients subscribed to that category
        $topic->broadcast($entryData);
    }

    public function onUnSubscribe(ConnectionInterface $conn, $topic) {
    }
    public function onOpen(ConnectionInterface $conn) {
    }
    public function onClose(ConnectionInterface $conn) {
    }
    public function onCall(ConnectionInterface $conn, $id, $topic, array $params) {
        // In this application if clients send data it's because the user hacked around in console
        $conn->callError($id, $topic, 'You are not allowed to make calls')->close();
    }
    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible) {
        // In this application if clients send data it's because the user hacked around in console
        $conn->close();
    }
    public function onError(ConnectionInterface $conn, \Exception $e) {
    }
}