<?php

namespace SmartThings;

class Locations extends SmartThingsAPI {

    private $locationId;

    function __construct(string $locationId) {
        parent::__init();
        if (empty($locationId)) {
            throw new \Exception('You need to specify a valid locationId');
        }
        $this->locationId = $locationId;
    }

    function info() {
        $location_resp = parent::apiCall('GET', 'locations/' . $this->locationId);
        if ($location_resp['code'] == 200) {
            return new Location($location_resp['response']);
        }
        return NULL;
    }

    function get_rooms() {
        $rooms_resp = parent::apiCall('GET', 'locations/' . $this->locationId . '/rooms');
        if ($rooms_resp['code'] == 200) {
            $rooms = array();
            foreach ($rooms_resp['response']['items'] as $room) {
                $rooms[] = new Room($room['roomId']);
            }
            return $rooms;
        }
        return NULL;
    }

    function get_room(string $roomId) {
        if (empty($roomId)) {
            throw new \Exception('You need to specify a valid roomId');
        }
        $room_resp = parent::apiCall('GET', 'locations/' . $this->locationId . '/rooms/' . $roomId);
        if ($room_resp['code'] == 200) {
            $room = new Room($room_resp['response']);
            return $room;
        }
        return NULL;
    }

}

class Location {

    private $location_info;

    function __construct($location_info) {
        if (empty($location_info)) {
            throw new \Exception('The location information cannot be empty');
        }
        $this->location_info = $location_info;
    }

    function name() {
        return $this->location_info['name'];
    }

}

class Room {

    private $room_info;

    function __construct(array $room_info) {
        if (empty($room_info)) {
            throw new \Exception('The room information cannot be empty');
        }
        $this->room_info = $room_info;
    }

    function name() {
        return $this->room_info['name'];
    }

}

?>