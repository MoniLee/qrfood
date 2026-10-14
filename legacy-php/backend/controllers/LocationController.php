<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Database;
use PDO;

class LocationController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    /**
     * API: Get saved addresses for current user
     */
    public function apiGetAddresses(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            json_response(['success' => false, 'error' => 'Login required'], 401);
        }

        $stmt = $this->pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
        $stmt->execute([$userId]);
        $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response(['success' => true, 'data' => $addresses]);
    }

    /**
     * API: Save/Update address
     */
    public function apiSaveAddress(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            json_response(['success' => false, 'error' => 'Login required'], 401);
        }

        $data = input_json();
        $id = $data['id'] ?? null;
        $type = $data['address_type'] ?? 'HOME';
        $name = $data['full_name'] ?? '';
        $phone = $data['phone_number'] ?? '';
        $flat = $data['flat_number'] ?? '';
        $street = $data['street_address'] ?? '';
        $landmark = $data['landmark'] ?? '';
        $city = $data['city'] ?? '';
        $state = $data['state'] ?? '';
        $pincode = $data['pincode'] ?? '';
        $isDefault = !empty($data['is_default']) ? 1 : 0;

        if (!$name || !$phone || !$street || !$city || !$pincode) {
            json_response(['success' => false, 'error' => 'Required fields missing'], 400);
        }

        if ($isDefault) {
            $this->pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
        }

        if ($id) {
            $stmt = $this->pdo->prepare("UPDATE user_addresses SET 
                address_type = ?, full_name = ?, phone_number = ?, flat_number = ?, 
                street_address = ?, landmark = ?, city = ?, state = ?, pincode = ?, is_default = ?
                WHERE id = ? AND user_id = ?");
            $stmt->execute([$type, $name, $phone, $flat, $street, $landmark, $city, $state, $pincode, $isDefault, $id, $userId]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO user_addresses 
                (user_id, address_type, full_name, phone_number, flat_number, street_address, landmark, city, state, pincode, is_default)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $type, $name, $phone, $flat, $street, $landmark, $city, $state, $pincode, $isDefault]);
        }

        json_response(['success' => true]);
    }

    /**
     * API: Check serviceability for a pincode
     */
    public function apiCheckServiceability(): void
    {
        $pincode = $_GET['pincode'] ?? '';
        if (!$pincode) {
            json_response(['success' => false, 'error' => 'Pincode required'], 400);
        }

        $stmt = $this->pdo->prepare("SELECT * FROM location_zones WHERE is_active = 1 AND FIND_IN_SET(?, REPLACE(pincodes, ' ', ''))");
        $stmt->execute([$pincode]);
        $zone = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($zone) {
            json_response([
                'success' => true,
                'available' => true,
                'data' => [
                    'zone_name' => $zone['name'],
                    'delivery_fee' => (float)$zone['delivery_fee'],
                    'min_order' => (float)$zone['min_order'],
                    'estimated_time' => $zone['estimated_time']
                ]
            ]);
        } else {
            json_response([
                'success' => true,
                'available' => false,
                'message' => 'Coming soon to your location!'
            ]);
        }
    }

    /**
     * API: Reverse Geocode (Mock for this implementation)
     * In a real app, this would call Google Maps or similar API
     */
    public function apiReverseGeocode(): void
    {
        $lat = $_GET['lat'] ?? null;
        $lng = $_GET['lng'] ?? null;

        if (!$lat || !$lng) {
            json_response(['success' => false, 'error' => 'Lat/Lng required'], 400);
        }

        // Mock response
        json_response([
            'success' => true,
            'data' => [
                'full_address' => 'Sample Street 123, Central Area, City, 110001',
                'pincode' => '110001',
                'city' => 'Delhi',
                'state' => 'Delhi',
                'area' => 'Cannaught Place'
            ]
        ]);
    }

    /**
     * API: List all zones (Admin)
     */
    public function apiListZones(): void
    {
        \App\Middleware\require_role('admin');
        $stmt = $this->pdo->query("SELECT * FROM location_zones ORDER BY created_at DESC");
        $zones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        json_response(['success' => true, 'data' => $zones]);
    }

    /**
     * API: Save/Update zone (Admin)
     */
    public function apiSaveZone(): void
    {
        \App\Middleware\require_role('admin');
        $data = input_json();
        $id = $data['id'] ?? null;
        $name = $data['name'] ?? '';
        $pincodes = $data['pincodes'] ?? '';
        $fee = $data['delivery_fee'] ?? 0;
        $min = $data['min_order'] ?? 0;
        $time = $data['estimated_time'] ?? '30-45 mins';
        $active = !empty($data['is_active']) ? 1 : 0;

        if (!$name || !$pincodes) {
            json_response(['success' => false, 'error' => 'Name and pincodes are required'], 400);
        }

        if ($id) {
            $stmt = $this->pdo->prepare("UPDATE location_zones SET 
                name = ?, pincodes = ?, delivery_fee = ?, min_order = ?, estimated_time = ?, is_active = ?
                WHERE id = ?");
            $stmt->execute([$name, $pincodes, $fee, $min, $time, $active, $id]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO location_zones 
                (name, pincodes, delivery_fee, min_order, estimated_time, is_active)
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $pincodes, $fee, $min, $time, $active]);
        }

        json_response(['success' => true]);
    }

    /**
     * API: Delete zone (Admin)
     */
    public function apiDeleteZone(): void
    {
        \App\Middleware\require_role('admin');
        $id = $_GET['id'] ?? null;
        if (!$id) {
            json_response(['success' => false, 'error' => 'ID required'], 400);
        }
        $stmt = $this->pdo->prepare("DELETE FROM location_zones WHERE id = ?");
        $stmt->execute([$id]);
        json_response(['success' => true]);
    }
}
