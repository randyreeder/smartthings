<?php

/**
 * Garage Door device class for devices with doorControl capability
 */

namespace SmartThings;

class GarageDoor extends Generic implements Device {

    // Don't use CMD_common trait since garage doors don't have switch capability
    // We'll implement our own power_on/power_off that map to open/close

    /**
     * Power on maps to opening the garage door
     * @return bool Returns true if command accepted
     */
    public function power_on() : bool {
        return $this->open();
    }

    /**
     * Power off maps to closing the garage door
     * @return bool Returns true if command accepted
     */
    public function power_off() : bool {
        return $this->close();
    }

    /**
     * Get the current door status
     * @return string Returns door status: "open", "closed", "opening", "closing", etc.
     */
    public function get_value() : string {
        $status = $this->status();
        if (property_exists($status, 'doorControl') && 
            array_key_exists('door', $status->doorControl) && 
            array_key_exists('value', $status->doorControl['door'])) {
            $value = $status->doorControl['door']['value'];
            if ($value == null) {
                return '';
            }
            return $value;
        }
        return '';
    }

    /**
     * Set the door state (for compatibility with generic device control)
     * @param string $value "open" or "close" or "closed" or "on" (maps to open) or "off" (maps to close)
     * @return bool Returns true if command accepted
     */
    public function set_value($value) {
        // Map switch values to door control values
        if ($value === 'on' || $value === 'open') {
            return $this->open();
        } elseif ($value === 'off' || $value === 'close' || $value === 'closed') {
            return $this->close();
        }
        return false;
    }

    /**
     * Open the garage door
     * @return bool Returns true if command accepted
     */
    public function open() : bool {
        $request = [
            'capability' => 'doorControl',
            'command' => 'open'
        ];
        $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
        return $this->validate_response($command_resp);
    }

    /**
     * Close the garage door
     * @return bool Returns true if command accepted
     */
    public function close() : bool {
        $request = [
            'capability' => 'doorControl',
            'command' => 'close'
        ];
        $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
        return $this->validate_response($command_resp);
    }

    /**
     * Check if the door is currently open
     * @param bool $as_bool Return as boolean instead of string
     * @return bool|string|string Returns door status
     */
    public function is_open(bool $as_bool = false) {
        $command_resp = parent::apiCall('GET', 'devices/' . $this->deviceId . '/components/main/capabilities/doorControl/status');
        if ($as_bool) {
            return $this->as_bool($command_resp['code'], $command_resp['response'], 'door', ['open', 'closed']);
        }
        return ($command_resp['code'] == 200) ? $command_resp['response']['door']['value'] : '';
    }

    /**
     * Helper method to check if a device is a garage door
     * @param array $device Device info array
     * @return bool
     */
    public static function isDeviceType($device) : bool {
        // Check for doorControl capability
        if (isset($device['components'][0]['capabilities'])) {
            foreach ($device['components'][0]['capabilities'] as $capability) {
                if ($capability['id'] === 'doorControl') {
                    return true;
                }
            }
        }
        
        // Check for garage door device type
        if (isset($device['ocfDeviceType']) && $device['ocfDeviceType'] === 'oic.d.garagedoor') {
            return true;
        }
        
        // Check for garage door category
        if (isset($device['components'][0]['categories'])) {
            foreach ($device['components'][0]['categories'] as $category) {
                if (isset($category['name']) && $category['name'] === 'GarageDoor') {
                    return true;
                }
            }
        }
        
        return false;
    }

    private function as_bool(int $resp_code, array $response, string $capability, array $values) {
        if ($resp_code == 200 && $response[$capability]['value'] == $values[0]) {
            return true;
        } elseif ($resp_code == 200 && $response[$capability]['value'] == $values[1]) {
            return false;
        } else {
            return 'NaN';
        }
    }

    private function validate_response(array $response) : bool {
        if ($response['code'] == 200 && $response['response']['results'][0]['status'] == 'ACCEPTED') {
            return true;
        }
        return false;
    }

}

?>
