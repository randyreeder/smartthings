<?php

require __DIR__ . '/../vendor/autoload.php';

# Create a Personal Access Token and add it below
$userBearerToken = parse_ini_file(__DIR__ . '/../bearer.ini')['bearer'];
$device_id = '2352eb81-0b1d-436c-9ad1-e8808c7dfdab';
$value = "off";

$smartAPI = new SmartThings\SmartThingsAPI($userBearerToken);
try {
    $device = $smartAPI->getDeviceById($device_id);
} catch (Exception $e) {
    header('HTTP/1.1 '.$e->getCode() . ' ' . $e->getMessage());
    echo json_encode(Array("error_message" => $e->getMessage(), "error_code" => $e->getCode()));
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// set the value
try {
    $device->set_value($value);
} catch (Exception $e) {
    echo json_encode(Array("error_message" => $e->getMessage(), "error_code" => $e->getCode()));
    exit;
}

try {
    $device = $smartAPI->getDeviceById($device_id);
} catch (Exception $e) {
    header('HTTP/1.1 '.$e->getCode() . ' ' . $e->getMessage());
    echo json_encode(Array("error_message" => $e->getMessage(), "error_code" => $e->getCode()));
    exit;
}
if(method_exists($device, 'get_value'))
{
    $device_details = array(
        'id' => $device->info()->deviceId,
        'name' => $device->info()->name,
        'label' => $device->info()->label,
        'type' => $device->info()->type,
        'value' => $device->get_value()
    );
    echo json_encode(Array("error_code" => 200, "error_message" => "", "message" => "Value set to " . $value, "device_details" => $device_details), JSON_PRETTY_PRINT);
}