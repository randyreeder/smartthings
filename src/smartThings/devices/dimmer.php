<?php

/**
 * Generic device class if the device does not fit to any other category
 */

namespace SmartThings;

class Dimmer extends Outlet implements Device {

    use CMD_common;

    public function get_level() : string {
        $level = $this->status()->switchLevel['level']['value'];
        if($level == null) {
            return '';
        }
        return $level;
    }

    public function set_level($value) {
        $request = [
            'capability' => 'levelSwitch',
            'command' => $value
        ];
        $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
        return $this->validate_response($command_resp);
    }

}

?>