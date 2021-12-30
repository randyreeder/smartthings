# PHP - Samsung Smart Things API
This library is a simple PHP wrapper for the Smart Things API. At the moment the library utilizes only a single endpoint from the API since I don't have all the devices listed to test it with. If you want support for more devices you can implement your own calls & functions and create a pull request to merge it to the main branch.
For the API calls the [GuzzleHTTP](https://github.com/guzzle/guzzle) library is used as a dependency and is already included in the main package.

#### Supported devices:
 - TV

#### Installation:
You can install the library using `composer` or by simply downloading this repository and including it in your project.
Installation using `composer`:

    composer require giannisftaras/smartthings

#### Usage:

Follow the instructions at the Smart Things Developer page on how to create a [Personal Access (Bearer) Token](https://developer-preview.smartthings.com/docs/advanced/authorization-and-permissions/#personal-access-tokens) for authenticating with the API. Then you can use the following code for testing:

    <?php
      require  __DIR__  .  '/../vendor/autoload.php';
      use SmartThings\SmartThingsAPI; 
      
      # Create a Personal Access Token and add it below
      $userBearerToken = '<<TOKEN>>';
      $smartAPI = new SmartThingsAPI($userBearerToken);
      $devices = $smartAPI->list_devices(); 
      
      $tv = $devices[0];
      $tv->power_on();
      $tv->volume(10);
    ?>

You can view the TV class in `/src/smartThings/devices/tv.php` for all available functions and commands.

You can also make a basic usage of Locations and Rooms:

    $location = new SmartThings\Locations('<<LOCATION_ID>>');
    $location->get_rooms();
    
    $room = new SmartThings\Room('<<ROOM_ID>>');
    $room->name();
