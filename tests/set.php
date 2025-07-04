<?php

require __DIR__ . '/../../../git/smartthings/vendor/autoload.php';

$userBearerToken = $_REQUEST['token'];
if(!$userBearerToken)
{
     error_log("set.php..missing token:");
     echo json_encode(Array("error_message" => "Missing token", "error_code" => 401));
     exit;
} 
$what = array_key_exists('what', $_REQUEST) ? $_REQUEST['what'] : 'value';
$device_id = $_REQUEST['device_id'];
$value = $_REQUEST['value'];
error_log("set.php..token:".$userBearerToken.", device_id: ".$device_id.", value: ".$value);

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
    if($what == 'value')
    {
        $device->set_value($value);
    }
    else if($what == 'level')
    {
        $device->set_level(intval($value));
    }
    else
    {
        echo json_encode(Array("error_message" => "invalid what", "error_code" => 123));
        exit;
    }
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
/*
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
else {
    echo json_encode(Array("error_code" => 200, "error_message" => "Not a switchable device"), JSON_PRETTY_PRINT);
}
 */

try {
    $devices = $smartAPI->list_devices();
} catch (Exception $e) {
    error_log("set.php....".$e->getMessage()."...".$e->getCode());
    echo json_encode(Array("error_message" => $e->getMessage(), "error_code" => $e->getCode()));
    exit;
}   

$devices_array = array();
if(count($devices) > 0)
{
    foreach ($devices as $device)
    {
        if(method_exists($device, 'get_value'))
        {
            $device_details = array(
                'id' => $device->info()->deviceId,
                'name' => $device->info()->name,
                'label' => $device->info()->label,
                'type' => $device->info()->type,
                'value' => $device->get_value()
            );
            if(method_exists($device, 'get_level'))
            {
                $device_details['level'] = $device->get_level();
            }
            $devices_array[] = $device_details;
        }
    }
}
echo json_encode(Array("error_code" => 200, "error_message" => "", "devices" => $devices_array), JSON_PRETTY_PRINT);     
