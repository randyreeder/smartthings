<?php

namespace SmartThings;

class TV extends SmartThingsAPI implements Device {

    use CMD_common;

    private $deviceId;
    private $deviceInfo;
    private $deviceStatus;

    function __construct(array $deviceInfo) {
        parent::__init();
        if (empty($deviceInfo['deviceId'])) {
            throw new \Exception('You need to specify a valid deviceId');
        }
        $this->deviceId = $deviceInfo['deviceId'];
        $this->deviceInfo = $deviceInfo;
        $this->deviceStatus = $this->status();
    }

    public function info(bool $update = false) : object {
        if (empty($deviceInfo) || $update) {
            $this->deviceInfo = parent::apiCall('GET', 'devices/' . $this->deviceId)['response'];
        }
        return (object) $this->deviceInfo;
    }

    public function status(bool $update = false) : object {
        if (empty($this->deviceStatus) || $update) {
            $command_resp = parent::apiCall('GET', 'devices/' . $this->deviceId . '/status');
            if ($command_resp['code'] == 200) {
                $this->deviceStatus = $command_resp['response']['components']['main'];
            }
        }
        
        return (object) $this->deviceStatus;
    }

    public function volume_up() : bool {
        $request = [
            'capability' => 'audioVolume',
            'command' => 'volumeUp'
        ];
        $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
        return $this->validate_response($command_resp);
    }

    public function volume_down() : bool {
        $request = [
            'capability' => 'audioVolume',
            'command' => 'volumeDown'
        ];
        $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
        return $this->validate_response($command_resp);
    }

    public function volume(int $vol = -1) : bool {
        if ($vol >= 0 && $vol <= 100) {
            $request = [
                'capability' => 'audioVolume',
                'command' => 'setVolume',
                'arguments' => [$vol]
            ];
            $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
            return $this->validate_response($command_resp);
        }
        return false;
    }

    public function get_volume() : string {
        $command_resp = parent::apiCall('GET', 'devices/' . $this->deviceId . '/components/main/capabilities/audioVolume/status');
        return ($command_resp['code'] == 200) ? $command_resp['response']['volume']['value'] : '';
    }

    public function mute() : bool {
        $request = [
            'capability' => 'audioMute',
            'command' => 'mute'
        ];
        $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
        return $this->validate_response($command_resp);
    }

    public function unmute() : bool {
        $request = [
            'capability' => 'audioMute',
            'command' => 'unmute'
        ];
        $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
        return $this->validate_response($command_resp);
    }

    public function is_muted(bool $as_bool = false) {
        $command_resp = parent::apiCall('GET', 'devices/' . $this->deviceId . '/components/main/capabilities/audioMute/status');
        if ($as_bool) {
            return $this->as_bool($command_resp['code'], $command_resp['response'], 'mute', ['muted', 'unmuted']);
        }
        return ($command_resp['code'] == 200) ? $command_resp['response']['mute']['value'] : '';
    }

    public function channel_up() : bool {
        $request = [
            'capability' => 'tvChannel',
            'command' => 'channelUp'
        ];
        $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
        return $this->validate_response($command_resp);
    }

    public function channel_dowm() : bool {
        $request = [
            'capability' => 'tvChannel',
            'command' => 'channelDown'
        ];
        $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
        return $this->validate_response($command_resp);
    }

    public function channel(int $channel) : bool {
        if ($channel > 0) {
            $request = [
                'capability' => 'tvChannel',
                'command' => 'setTvChannel',
                'arguments' => [strval($channel)]
            ];
            $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
            return $this->validate_response($command_resp);
        }
        return false;
    }

    # NOTICE: Not working as tested at the moment
    public function channel_name(string $channelName) : bool {
        if (!empty($channelName) && strlen($channelName) <= 255) {
            $request = [
                'capability' => 'tvChannel',
                'command' => 'setTvChannelName',
                'arguments' => [$channelName]
            ];
            $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
            return $this->validate_response($command_resp);
        }
        return false;
    }

    public function get_channel() : string {
        $command_resp = parent::apiCall('GET', 'devices/' . $this->deviceId . '/components/main/capabilities/tvChannel/status');
        return ($command_resp['code'] == 200) ? $command_resp['response']['tvChannel']['value'] : '';
    }

    public function get_channel_name() : string {
        $command_resp = parent::apiCall('GET', 'devices/' . $this->deviceId . '/components/main/capabilities/tvChannel/status');
        return ($command_resp['code'] == 200) ? $command_resp['response']['tvChannelName']['value'] : '';
    }

    public function source(string $source) : bool {
        if (!empty($source) && strlen($source) <= 255) {
            $request = [
                'capability' => 'mediaInputSource',
                'command' => 'setInputSource',
                'arguments' => [$source]
            ];
            $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
            return $this->validate_response($command_resp);
        }
        return false;
    }

    public function get_sources() : array {
        $command_resp = parent::apiCall('GET', 'devices/' . $this->deviceId . '/components/main/capabilities/mediaInputSource/status');
        return ($command_resp['code'] == 200) ? $command_resp['response']['supportedInputSources'] : [];
    }

    public function get_selected_source() : string {
        $command_resp = parent::apiCall('GET', 'devices/' . $this->deviceId . '/components/main/capabilities/mediaInputSource/status');
        return ($command_resp['code'] == 200) ? $command_resp['response']['inputSource']['value'] : '';
    }

    public function play() : bool {
        $request = [
            'capability' => 'mediaPlayback',
            'command' => 'play'
        ];
        $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
        return $this->validate_response($command_resp);
    }

    public function pause() : bool {
        $request = [
            'capability' => 'mediaPlayback',
            'command' => 'pause'
        ];
        $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
        return $this->validate_response($command_resp);
    }

    public function stop() : bool {
        $request = [
            'capability' => 'mediaPlayback',
            'command' => 'stop'
        ];
        $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
        return $this->validate_response($command_resp);
    }

    public function fast_forward() : bool {
        $request = [
            'capability' => 'mediaPlayback',
            'command' => 'fastForward'
        ];
        $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
        return $this->validate_response($command_resp);
    }

    public function rewind() : bool {
        $request = [
            'capability' => 'mediaPlayback',
            'command' => 'rewind'
        ];
        $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
        return $this->validate_response($command_resp);
    }

    public function next() : bool {
        $request = [
            'capability' => 'mediaTrackControl',
            'command' => 'nextTrack'
        ];
        $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
        return $this->validate_response($command_resp);
    }

    public function previous() : bool {
        $request = [
            'capability' => 'mediaTrackControl',
            'command' => 'previousTrack'
        ];
        $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
        return $this->validate_response($command_resp);
    }

    public function now_playing() : string {
        $command_resp = parent::apiCall('GET', 'devices/' . $this->deviceId . '/components/main/capabilities/mediaPlayback/status');
        return ($command_resp['code'] == 200) ? (is_null($command_resp['response']['playbackStatus']['value']) ? '' : $command_resp['response']['playbackStatus']['value']) : '';
    }

    public function get_picture_mode() : string {
        $command_resp = parent::apiCall('GET', 'devices/' . $this->deviceId . '/components/main/capabilities/custom.picturemode/status');
        return ($command_resp['code'] == 200) ? $command_resp['response']['pictureMode']['value'] : '';
    }

    /**
     * Set the current picture mode
     * Accepted values: 1-4
     * 1: Dynamic, 2: Standard, 3: Natural, 4: Movie
     */
    public function picture_mode(int $pic_mode) : bool {
        if ($pic_mode >= 1 && $pic_mode <= 4) {
            $modes = ((array) $this->deviceStatus)['custom.picturemode']['supportedPictureModes']['value'];
            $request = [
                'capability' => 'custom.picturemode',
                'command' => 'setPictureMode',
                'arguments' => [$modes[$pic_mode - 1]]
            ];
            $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
            return $this->validate_response($command_resp);
        }
        return false;
    }

    public function get_sound_mode() : string {
        $command_resp = parent::apiCall('GET', 'devices/' . $this->deviceId . '/components/main/capabilities/custom.soundmode/status');
        return ($command_resp['code'] == 200) ? $command_resp['response']['soundMode']['value'] : '';
    }

    /**
     * Set the current picture mode
     * Accepted values: 1-3
     * 1: Standard, 2: Smart, 3: Amplified
     */
    public function sound_mode(int $snd_mode) : bool {
        if ($snd_mode >= 1 && $snd_mode <= 3) {
            $modes = ((array) $this->deviceStatus)['custom.soundmode']['supportedSoundModes']['value'];
            $request = [
                'capability' => 'custom.soundmode',
                'command' => 'setSoundMode',
                'arguments' => [$modes[$snd_mode - 1]]
            ];
            $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
            return $this->validate_response($command_resp);
        }
        return false;
    }

}

?>