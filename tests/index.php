<?php

require __DIR__ . '/../vendor/autoload.php';

# Create a Personal Access Token and add it below
$userBearerToken = 'f0566d96-044f-482c-b023-d1e327a6703e';

$smartAPI = new SmartThings\SmartThingsAPI($userBearerToken);
$devices = $smartAPI->list_devices();

$tv = $devices[0];
$tv->power_on();
$tv->volume(10);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartThingsAPI</title>
</head>
<body>
    <pre><code>
        <?=print_r($tv->info())?>
    </code></pre>
</body>
</html>