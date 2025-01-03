<?php

namespace SmartThings;

class EcobeeSensor extends Generic implements Device {

    use CMD_common;

    private $deviceId;
    private $deviceInfo;
    private $deviceStatus;

    public function get_temperature() : string {
        $status = $this->status();
        return $status->temperatureMeasurement['temperature']['value'] . $status->temperatureMeasurement['temperature']['unit'];
    }

    public function get_value() : string {
        return $this->get_temperature();
    }

}

?>