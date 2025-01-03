<?php

require __DIR__ . '/../vendor/autoload.php';

# Create a Personal Access Token and add it below
$userBearerToken = parse_ini_file(__DIR__ . '/../bearer.ini')['bearer'];

$smartAPI = new SmartThings\SmartThingsAPI($userBearerToken);
$devices = $smartAPI->list_devices();

/*
$tv = $devices[0];
$tv->power_on();
$tv->volume(10);
*/

header('Content-Type: application/json; charset=utf-8');

$devices_array = array();
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
echo json_encode($devices_array, JSON_PRETTY_PRINT);