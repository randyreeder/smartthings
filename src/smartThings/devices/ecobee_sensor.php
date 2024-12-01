<?php

namespace SmartThings;

class EcobeeSensor extends SmartThingsAPI implements Device {

    use CMD_common;

    private $deviceId;
    private $deviceInfo;
    private $deviceStatus;

    function __construct(array $deviceInfo) {
        parent::__init();
        if (empty($deviceInfo['deviceId'])) {
            throw new \Exception('You need to specify a valid deviceId');
        }
        $this->deviceId = $deviceInfo['deviceId'];
        $this->deviceInfo = $deviceInfo;
        $this->deviceStatus = $this->status();
    }

    public function info(bool $update = false) : object {
        if (empty($deviceInfo) || $update) {
            $this->deviceInfo = parent::apiCall('GET', 'devices/' . $this->deviceId)['response'];
        }
        return (object) $this->deviceInfo;
    }

    public function status(bool $update = false) : object {
        if (empty($this->deviceStatus) || $update) {
            $command_resp = parent::apiCall('GET', 'devices/' . $this->deviceId . '/status');
            if ($command_resp['code'] == 200) {
                $this->deviceStatus = $command_resp['response']['components']['main'];
            }
        }
        
        return (object) $this->deviceStatus;
    }

    public function get_temperature() : string {
        $status = $this->status();
        return $status->temperatureMeasurement['temperature']['value'] . $status->temperatureMeasurement['temperature']['unit'];
    }

    public function get_value() : string {
        return $this->get_temperature();
    }

    /**
     * Set the current picture mode
     * Accepted values: 1-3
     * 1: Standard, 2: Smart, 3: Amplified
     */
    public function sound_mode(int $snd_mode) : bool {
        if ($snd_mode >= 1 && $snd_mode <= 3) {
            $modes = ((array) $this->deviceStatus)['custom.soundmode']['supportedSoundModes']['value'];
            $request = [
                'capability' => 'custom.soundmode',
                'command' => 'setSoundMode',
                'arguments' => [$modes[$snd_mode - 1]]
            ];
            $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
            return $this->validate_response($command_resp);
        }
        return false;
    }

}

?>