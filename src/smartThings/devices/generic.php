<?php

/**
 * Generic device class if the device does not fit to any other category
 */

namespace SmartThings;

class Generic extends SmartThingsAPI implements Device {

    use CMD_common;

    protected $deviceId = null;
    private $deviceInfo = null;
    private $deviceStatus = null;

    function __construct(array $deviceInfo) {
        parent::__init();
        if (empty($deviceInfo['deviceId'])) {
            throw new \Exception('You need to specify a valid deviceId');
        }
        $this->deviceId = $deviceInfo['deviceId'];
        $this->deviceInfo = $deviceInfo;
        $this->deviceStatus = $this->getStatusFromInfo($this->deviceInfo);
    }

    public function info(bool $update = false) : object {
        if (empty($this->deviceInfo) || $update) {
            $this->deviceInfo = parent::apiCall('GET', 'devices/' . $this->deviceId)['response'];
        }
        return (object) $this->deviceInfo;
    }

    public function status(bool $update = false) : object {
        if ($this->deviceStatus === null || $update) {
            $command_resp = parent::apiCall('GET', 'devices/' . $this->deviceId . '/status');
            if ($command_resp['code'] == 200 && array_key_exists('main', $command_resp['response']['components']))
            {
                $this->deviceStatus = $command_resp['response']['components']['main'];
            }
        }
        
        return (object) $this->deviceStatus;
    }

}

?>