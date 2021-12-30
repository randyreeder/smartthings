<?php

/**
 * Common commands available to all devices
 */

namespace SmartThings;

trait CMD_common {

    public function power_on() : bool {
        $request = [
            'capability' => 'switch',
            'command' => 'on'
        ];
        $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
        return $this->validate_response($command_resp);
    }

    public function power_off() : bool {
        $request = [
            'capability' => 'switch',
            'command' => 'off'
        ];
        $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
        return $this->validate_response($command_resp);
    }

    public function is_on(bool $as_bool = false) {
        $command_resp = parent::apiCall('GET', 'devices/' . $this->deviceId . '/components/main/capabilities/switch/status');
        if ($as_bool) {
            return $this->as_bool($command_resp['code'], $command_resp['response'], 'switch', ['on', 'off']);
        }
        return ($command_resp['code'] == 200) ? $command_resp['response']['switch']['value'] : '';
    }

    public function firmware_version() {
        $command_resp = parent::apiCall('GET', 'devices/' . $this->deviceId . '/components/main/capabilities/samsungvd.firmwareVersion/status');
        return ($command_resp['code'] == 200) ? $command_resp['response']['firmwareVersion']['value'] : '';
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