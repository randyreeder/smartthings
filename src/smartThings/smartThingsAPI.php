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

    function __construct(string $bearer) {
        if (!$this->validateBearerToken($bearer)) {
            throw new \Exception('You need to specify a valid bearer token');
        }
        $this->bearer = $bearer;
        $this->cookieStorage = new \GuzzleHttp\Cookie\CookieJar;
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => 'https://api.smartthings.com/v1/',
            'timeout'  => 2.0,
            'cookies' => $this->cookieStorage,
            'http_errors' => false
        ]);
        $this->request_body['headers'] = [
            'Authorization' => 'Bearer ' . $this->bearer,
            'Content-Type' => 'application/json'
        ];
        $this->request_body['allow_redirects'] = [
            'max' => 2,
            'protocols' => ['https']
        ];
        global $smartThingsAPI_object;
        $smartThingsAPI_object = $this;
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

    /**
     * List account devices as sorted class bojects
     */
    public function list_devices(bool $update = false) : array {
        if (empty($this->devices) || $update) {
            $this->devices = $this->apiCall('GET', 'devices/')['response']['items'];
        }
        $device_obj = array();
        foreach ($this->devices as $device) {
            switch($device['name']) {
                case 'Samsung OCF TV':
                    $device_obj[] = new TV($device);
                    break;
                case 'ecobee Sensor':
                    $device_obj[] = new EcobeeSensor($device);
                    break;
                default:
                    // if 'ecobee Thermostat' is in the device name
                    if(strpos($device['name'], 'ecobee Thermostat') !== false) {
                        $device_obj[] = new Ecobee($device);
                        break;
                    }
                    $device_obj[] = new Generic($device);
                    break;
            }
        }
        return $device_obj;
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