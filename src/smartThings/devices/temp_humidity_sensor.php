<?php

/**
 * Temperature and humidity sensor device class
 */

namespace SmartThings;

class TempHumiditySensor extends Generic implements Device {
    use CMD_common;

    public function get_temperature() : ?string {
        $status = $this->status();
        if (isset($status->temperatureMeasurement['temperature']['value'])) {
            return $status->temperatureMeasurement['temperature']['value'] . ($status->temperatureMeasurement['temperature']['unit'] ?? '');
        }
        return null;
    }

    public function get_humidity() : ?string {
        $status = $this->status();
        if (isset($status->relativeHumidityMeasurement['humidity']['value'])) {
            return $status->relativeHumidityMeasurement['humidity']['value'] . ($status->relativeHumidityMeasurement['humidity']['unit'] ?? '');
        }
        return null;
    }

    public function get_value() : ?string {
        return $this->get_temperature();
    }
}

?>
