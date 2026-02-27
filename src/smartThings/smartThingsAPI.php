<?php

namespace SmartThings;

require __DIR__ . '/device_wrapper.php';
require __DIR__ . '/locations_rooms.php';
$smartThingsAPI_object;


class SmartThingsAPI {

    private $devices;

    protected $cookieStorage;
    protected $bearer;
    protected $access_token;
    protected $refresh_token;
    protected $client;
    protected $request_body;
    protected $code_mapping;
    protected $client_id;
    protected $client_secret;
    protected $user_token_file;

    function __construct($auth_param, string $refresh_token = null, string $client_id = null, string $client_secret = null, string $user_token_file = null) {
        // Support both bearer token (legacy) and access_token/refresh_token (OAuth)
        if (is_string($auth_param) && $refresh_token === null) {
            // Legacy bearer token mode
            if (!$this->validateBearerToken($auth_param)) {
                throw new \Exception('Invalid bearer token', 401);
            }
            $this->bearer = $auth_param;
        } else {
            // OAuth mode with access_token and refresh_token
            $this->access_token = $auth_param;
            $this->refresh_token = $refresh_token;
            $this->bearer = $this->access_token; // Use access_token as bearer
            $this->client_id = $client_id;
            $this->client_secret = $client_secret;
            $this->user_token_file = $user_token_file;
        }
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

    /**
     * Refresh the access token using the refresh token
     */
    public function refreshAccessToken(string $client_id, string $client_secret, string $config_file_path = null) : bool {
        if (empty($this->refresh_token)) {
            throw new \Exception('No refresh token available', 400);
        }

        $refresh_client = new \GuzzleHttp\Client([
            'base_uri' => 'https://auth-global.api.smartthings.com/',
            'timeout'  => 10.0,
            'http_errors' => false
        ]);

        $response = $refresh_client->request('POST', 'oauth/token', [
            'form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->refresh_token
            ],
            'auth' => [$client_id, $client_secret],
        ]);

        $code = $response->getStatusCode();
        $response_body = $response->getBody()->getContents();
        
        // Log only high-level refresh attempt
        error_log("SmartThingsAPI: REFRESH ATTEMPT - HTTP Code: {$code}");
        
        if ($code === 200) {
            $body = json_decode($response_body, true);
            if (isset($body['access_token'])) {
                error_log("SmartThingsAPI: REFRESH SUCCESS");
                $this->access_token = $body['access_token'];
                $this->bearer = $this->access_token;
                // Update the authorization header
                $this->request_body['headers']['Authorization'] = 'Bearer ' . $this->bearer;
                // Update refresh token if provided
                if (isset($body['refresh_token'])) {
                    $this->refresh_token = $body['refresh_token'];
                }
                // Save tokens to file if path provided
                if (!empty($config_file_path)) {
                    $this->saveTokensToFile($config_file_path);
                }
                // Update user token file if available
                if (!empty($this->user_token_file)) {
                    $this->updateUserTokenFile();
                }
                return true;
            } else {
                error_log("SmartThingsAPI: REFRESH ERROR - No access_token in response");
            }
        } else {
            error_log("SmartThingsAPI: REFRESH ERROR - HTTP {$code}");
        }
        
        throw new \Exception('Failed to refresh access token: HTTP ' . $code . ' - ' . $response_body, $code);
    }

    /**
     * Get the current access token
     */
    public function getAccessToken() : ?string {
        return $this->access_token;
    }

    /**
     * Get the current refresh token
     */
    public function getRefreshToken() : ?string {
        return $this->refresh_token;
    }

    /**
     * Update the user's token file with refreshed tokens
     */
    private function updateUserTokenFile() {
        if (empty($this->user_token_file) || !file_exists($this->user_token_file)) {
            error_log("SmartThingsAPI: updateUserTokenFile() - Token file missing or not set: " . ($this->user_token_file ?? 'NULL'));
            return false;
        }

        $tokens = json_decode(file_get_contents($this->user_token_file), true);
        if ($tokens) {
            $tokens['access_token'] = $this->access_token;
            $tokens['refresh_token'] = $this->refresh_token;
            $tokens['refreshed'] = time();

            $result = file_put_contents($this->user_token_file, json_encode($tokens));
            if ($result !== false) {
                error_log("SmartThingsAPI: updateUserTokenFile() - Token file updated: " . $this->user_token_file);
                error_log("SmartThingsAPI: updateUserTokenFile() - New access_token: " . substr($this->access_token, 0, 8) . "... New refresh_token: " . substr($this->refresh_token, 0, 8) . "...");
                return true;
            } else {
                error_log("SmartThingsAPI: updateUserTokenFile() - Failed to write token file: " . $this->user_token_file);
            }
        } else {
            error_log("SmartThingsAPI: updateUserTokenFile() - Failed to decode token file: " . $this->user_token_file);
        }

        return false;
    }

    /**
     * Save current tokens to the configuration file
     */    /**
     * @deprecated This method is deprecated. OAuth tokens are now stored per-user in individual JSON files.
     * Use the json.php endpoint OAuth flow instead: GET /json.php?setup=1&user_id=YOUR_UNIQUE_ID
     */
    public function saveTokensToFile(string $config_file_path) : bool {
        // This method is deprecated - tokens are now stored per-user in JSON files
        error_log("DEPRECATED: saveTokensToFile() is deprecated. Use per-user token storage instead.");
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

        // Check for Tedee lock first (using static method)
        if (Tedee::isDeviceType($device)) {
            $device_obj = new Tedee($device);
            return $device_obj;
        }

        // Check for garage door (using static method)
        if (GarageDoor::isDeviceType($device)) {
            $device_obj = new GarageDoor($device);
            return $device_obj;
        }

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
                if(property_exists($status, 'temperatureMeasurement')) {
                    $device_obj = new TempHumiditySensor($device);
                    break;
                }
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
        
        $call = $this->makeRequest($request_type, $url, $request_body);
        $code = $call->getStatusCode();
        
        // If we get a 401 and have a refresh token, try to refresh the access token
        if ($code === 401 && !empty($this->refresh_token) && !empty($this->client_id) && !empty($this->client_secret)) {
            try {
                $this->refreshAccessToken($this->client_id, $this->client_secret);
                // Retry the original request with the new token
                $request_body['headers']['Authorization'] = 'Bearer ' . $this->bearer;
                $call = $this->makeRequest($request_type, $url, $request_body);
                $code = $call->getStatusCode();
            } catch (\Exception $e) {
                // Token refresh failed, continue with original error
                error_log("Token refresh failed: " . $e->getMessage());
            }
        }
        
        $response = $call->getBody()->getContents();
        return ['code' => $code, 'response' => json_decode($response, true)];
    }

    /**
     * Make the actual HTTP request
     */
    private function makeRequest(string $request_type, string $url, array $request_body) {
        if ($request_type === 'POST') {
            return $this->client->request('POST', $url, $request_body);
        } else {
            return $this->client->request('GET', $url, $request_body);
        }
    }

}