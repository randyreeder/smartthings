<?php

require __DIR__ . '/../vendor/autoload.php';

# Create a Personal Access Token and add it below
$userBearerToken = parse_ini_file(__DIR__ . '/../bearer.ini')['bearer'];

$smartAPI = new SmartThings\SmartThingsAPI($userBearerToken);
try {
    $devices = $smartAPI->list_devices();
} catch (Exception $e) {
    header('HTTP/1.1 '.$e->getCode() . ' ' . $e->getMessage());
    echo json_encode(Array("error_message" => $e->getMessage(), "error_code" => $e->getCode()));
    exit;
}

/*
$tv = $devices[0];
$tv->power_on();
$tv->volume(10);
*/

header('Content-Type: application/json; charset=utf-8');

$devices_array = array();
if(count($devices) > 0)
{
    foreach ($devices as $device)
    {
        if(method_exists($device, 'get_value'))
        {
            $devices_array[] = array(
                'id' => $device->info()->deviceId,
                'name' => $device->info()->name,
                'label' => $device->info()->label,
                'type' => $device->info()->type,
                'value' => $device->get_value()
            );
        }
    }
}
echo json_encode($devices_array, JSON_PRETTY_PRINT);