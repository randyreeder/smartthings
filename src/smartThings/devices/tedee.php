<?php

namespace SmartThings;

/**
 * Tedee Smart Lock Device Wrapper
 * 
 * Handles Tedee GO and other Tedee smart locks integrated via SmartThings Cloud-to-Cloud (VIPER)
 * 
 * Supported Capabilities:
 * - lock: Lock/unlock control with status monitoring
 * - battery: Battery level monitoring
 * - refresh: Manual device refresh
 * - healthCheck: Device connectivity status
 */
class Tedee extends Generic implements Device
{
    use CMD_common;
    
    /**
     * Check if device is a Tedee lock
     */
    public static function isDeviceType($device): bool
    {
        // Check for Tedee manufacturer code and VIPER type (Cloud-to-Cloud integration)
        if (isset($device['deviceManufacturerCode']) && 
            $device['deviceManufacturerCode'] === 'Tedee' &&
            isset($device['type']) && 
            $device['type'] === 'VIPER') {
            return true;
        }
        
        // Also check for Tedee in device model name
        if (isset($device['deviceModel']) && 
            stripos($device['deviceModel'], 'tedee') !== false) {
            return true;
        }
        
        // Check presentation ID for Tedee lock patterns
        if (isset($device['presentationId']) && 
            stripos($device['presentationId'], 'c2c-lock') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get current lock status
     */
    public function get_value(): string
    {
        $status = $this->status();
        
        if (property_exists($status, 'lock') && 
            isset($status->lock['lock']['value'])) {
            return $status->lock['lock']['value']; // 'locked' or 'unlocked'
        }
        
        return 'unknown';
    }
    
    /**
     * Get battery level
     */
    public function get_battery(): int
    {
        $status = $this->status();
        
        if (property_exists($status, 'battery') && 
            isset($status->battery['battery']['value'])) {
            return (int)$status->battery['battery']['value'];
        }
        
        return 0;
    }
    
    /**
     * Get device online status
     */
    public function get_online_status(): bool
    {
        $status = $this->status();
        
        if (property_exists($status, 'healthCheck') && 
            isset($status->healthCheck['DeviceWatch-DeviceStatus']['value'])) {
            return $status->healthCheck['DeviceWatch-DeviceStatus']['value'] === 'online';
        }
        
        return false;
    }
    
    /**
     * Lock the device
     */
    public function lock(): bool
    {
        $request = [
            'capability' => 'lock',
            'command' => 'lock'
        ];
        $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
        return $this->validate_response($command_resp);
    }
    
    /**
     * Unlock the device
     */
    public function unlock(): bool
    {
        $request = [
            'capability' => 'lock',
            'command' => 'unlock'
        ];
        $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
        return $this->validate_response($command_resp);
    }
    
    /**
     * Refresh device status
     */
    public function refresh(): bool
    {
        $request = [
            'capability' => 'refresh',
            'command' => 'refresh'
        ];
        $command_resp = parent::apiCall('POST', 'devices/' . $this->deviceId . '/commands', $request);
        return $this->validate_response($command_resp);
    }
    
    /**
     * Set lock state (for compatibility with set_value interface)
     */
    public function set_value($value): bool
    {
        $value = trim(strtolower($value));
        
        switch ($value) {
            case 'lock':
            case 'locked':
            case 'on':      // Power interface compatibility
            case '1':       // Boolean compatibility
            case 'true':    // Boolean string compatibility
                return $this->lock();
                
            case 'unlock':
            case 'unlocked':
            case 'off':     // Power interface compatibility
            case '0':       // Boolean compatibility
            case 'false':   // Boolean string compatibility
                return $this->unlock();
                
            default:
                error_log("Tedee::set_value() - Invalid value: " . $value . " (expected: lock/unlock)");
                return false;
        }
    }
}

?>
