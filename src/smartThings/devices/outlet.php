<?php

/**
 * Generic device class if the device does not fit to any other category
 */

namespace SmartThings;

class Outlet extends Generic implements Device {

    use CMD_common;

    public function get_value() : string {
        $value = $this->status()->switch['switch']['value'];
        if($value == null) {
            return '';
        }
        return $value;
    }

}

?>