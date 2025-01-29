<?php

namespace SmartThings;

require __DIR__ . '/devices/common.php';
require __DIR__ . '/devices/generic.php';
require __DIR__ . '/devices/tv.php';
require __DIR__ . '/devices/ecobee.php';
require __DIR__ . '/devices/ecobee_sensor.php';
require __DIR__ . '/devices/outlet.php';
require __DIR__ . '/devices/dimmer.php';

interface Device {

    public function info(bool $update = false) : object;
    public function status(bool $update = false) : object;
    
}

?>