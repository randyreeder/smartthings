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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartThingsAPI</title>
    <style>
        table {  border-collapse: collapse; }
        table td, th { border: 1px solid black;}
    </style>
</head>
<body>
    <?php
    function get_breadcrumb_string($object)
    {
        $breadcrumb = '';
        foreach($object as $key => $value)
        {
            if(is_array($value))
            {
                $breadcrumb .= $key.': '.get_breadcrumb_string($value);
            }
            else
            {
                $breadcrumb .= $key.': '.$value.'<br>';
            }
        }
        return $breadcrumb;
    }
    echo '<table><tr><th>Name</th><th>Label</th><th>Type</th><th>Value</th></tr>';
    foreach ($devices as $device)
    {
        echo '<tr><td>'.$device->info()->name.'</td><td>'.$device->info()->label.'</td><td>'.$device->info()->type.'</td>';
        // if $device has method get_value() then call it
        if(method_exists($device, 'get_value'))
        {
            echo '<td>'.$device->get_value().'</td>';
        }
        else
        {
            echo '<td>'.get_breadcrumb_string($device->status()).'</td>';
            /*
            echo '<td><pre>';
            print_r($device->status());
            echo '</pre></td>';
            exit;
            */
        }
        echo '</tr>';
    }

    echo '</table>';
    ?>
</body>
</html>