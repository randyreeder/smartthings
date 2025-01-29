<?php

namespace SmartThings;

require __DIR__ . '/device_wrapper.php';
require __DIR__ . '/locations_rooms.php';
$smartThingsAPI_object;


class SmartThingsAPI {

    private $devices;

    protected $cookieStorage;
    protected $bearer;
    protected $client;
    protected $request_body;
    protected $code_mapping;

    function __construct(string $bearer) {
        if (!$this->validateBearerToken($bearer)) {
            throw new \Exception('Invalid bearer token', 401);
        }
        $this->bearer = $bearer;
        $this->cookieStorage = new \GuzzleHttp\Cookie\CookieJar;
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => 'https://api.smartthings.com/',
            'timeout'  => 2.0,
            'cookies' => $this->cookieStorage,
            'http_errors' => false
        ]);
        $this->request_body['headers'] = [
            'Authorization' => 'Bearer ' . $this->bearer,
            'Content-Type' => 'application/json',
            //'Accept' => 'application/vnd.smartthings+json;v=1'
            'Accept' => 'application/vnd.smartthings+json;v=20170916'
        ];
        $this->request_body['allow_redirects'] = [
            'max' => 2,
            'protocols' => ['https']
        ];
        global $smartThingsAPI_object;
        $smartThingsAPI_object = $this;

        $this->code_mapping = Array( 
            200 => 'OK',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error'
        );

    }

    /**
     * Check if the SmartThings API class is already instanciated and set the parent variables
     */
    protected function __init() {
        global $smartThingsAPI_object;
        if (empty($smartThingsAPI_object)) {
            throw new \Exception('You need to instanciate the Smart Things API first');
        }
        $this->client = $smartThingsAPI_object->client;
        $this->request_body = $smartThingsAPI_object->request_body;
        $this->code_mapping = $smartThingsAPI_object->code_mapping;
    }

    
    function getErrorMessageFromCode($code) {
        if(array_key_exists($code, $this->code_mapping)) {
            return $this->code_mapping[$code];
        }
        else {
            return 'Unknown error';
        }
    }



    /**
     * Validate if the Bearer token exists and is a valid GUID
     */
    private function validateBearerToken(string $bearer) : bool {
        if (!empty($bearer) && preg_match('/^\{?[a-zA-Z0-9]{8}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{4}-[a-zA-Z0-9]{12}\}?$/', $bearer)) {
            return true;
        }
        return false;
    }

    public function getDeviceById(string $deviceId) : Generic {
        //$device = $this->apiCall('GET', 'devices/' . $deviceId)['response'];
        $result = $this->apiCall('GET', 'devices/' . $deviceId . '?capabilitiesMode=and&includeStatus=true');
        if($result['code'] != 200) {
            throw new \Exception($this->getErrorMessageFromCode($result['code']), $result['code']);
        }
        $device = $result['response'];
        return $this->getDeviceObject($device);
    }

    /**
     * List account devices as sorted class bojects
     */
    public function list_devices(bool $update = false) : array {
        if (empty($this->devices) || $update) {
            //$result = $this->apiCall('GET', 'devices/');
            $result = $this->apiCall('GET', 'devices?capabilitiesMode=and&includeStatus=true');
            if($result['code'] != 200) {
                // throw exception
                throw new \Exception($this->getErrorMessageFromCode($result['code']), $result['code']);
            }
            $response = $result['response'];
            if($response != null && array_key_exists('items', $response)) {
                $this->devices = $response['items'];
            }
            else {
                $this->devices = array();
            }
        }
        $device_objects = array();
        foreach ($this->devices as $device) {
            $device_objects[] = $this->getDeviceObject($device);
        }
        return $device_objects;
    }

    private function getDeviceObject($device) {
        $generic_device = new Generic($device);
        $device_obj = $generic_device;
        switch($device['name']) {
            case 'Samsung OCF TV':
                $device_obj = new TV($device);
                break;
            case 'ecobee Sensor':
                $device_obj = new EcobeeSensor($device);
                break;
            default:
                // if 'ecobee Thermostat' is in the device name
                if(strpos($device['name'], 'ecobee Thermostat') !== false) {
                    $device_obj = new Ecobee($device);
                    break;
                }
                $status = $generic_device->status();
                if(property_exists($status, 'switch') && array_key_exists('switch', $status->switch) && array_key_exists('value', $status->switch['switch'])) {
                    if(property_exists($status, 'switchLevel') && array_key_exists('level', $status->switchLevel) && array_key_exists('value', $status->switchLevel['level'])) {
                        $device_obj = new Dimmer($device);
                    }
                    else {
                        $device_obj = new Outlet($device);
                    }
                    break;
                }
                break;
        }
        return $device_obj;
    }

    public function getStatusFromInfo($info) {
        $status = Array();
        if(array_key_exists('components', $info) && array_key_exists(0, $info['components']) && array_key_exists('capabilities', $info['components']['0'])) {
            $capabilities = $info['components'][0]['capabilities'];
            foreach ($capabilities as $capability) {
                if(array_key_exists('id', $capability) && array_key_exists('status', $capability)) {
                    $status[$capability['id']] = $capability['status'];
                }
            }
        }
        return $status;
    }

    /**
     * List account devices as an information array
     */
    public function list_devices_info(bool $update = false) : array {
        if (empty($this->devices) || $update) {
            $this->devices = $this->apiCall('GET', 'devices/')['response']['items'];
        }        
        return $this->devices;
    }

    /**
     * Make an API request
     */
    protected function apiCall(string $request_type = 'GET', string $url = '', array $body = NULL) {
        $request_body = $this->request_body;
        if (!is_null($body)) {
            $request_body['json'] = [
                'commands' => [
                    [
                        'component' => 'main',
                        'capability' => $body['capability'] ?? '',
                        'command' => $body['command'] ?? '',
                        'arguments' => $body['arguments'] ?? []
                    ]
                ]
            ];
        }
        if ($request_type === 'POST') {
            $call = $this->client->request('POST', $url, $request_body);
        } else {
            $call = $this->client->request('GET', $url, $request_body);
        }
        $code = $call->getStatusCode();      
        $response = $call->getBody()->getContents();
        return ['code' => $code, 'response' => json_decode($response, true)];
    }

}