<?php
declare(strict_types=1);

function portal_parse_nominatim(array $json): array
{
    $address = $json['address'] ?? [];
    $street = trim(($address['house_number'] ?? '') . ' ' . ($address['road'] ?? ''));
    return [
        'address_text' => $json['display_name'] ?? null, 'street' => $street !== '' ? $street : null,
        'city' => $address['city'] ?? $address['town'] ?? $address['village'] ?? null,
        'province' => $address['state'] ?? null,
    ];
}

function portal_reverse_geocode(float $lat, float $lng): ?array
{
    $curl = curl_init('https://nominatim.openstreetmap.org/reverse?' . http_build_query(['format' => 'jsonv2', 'lat' => $lat, 'lon' => $lng]));
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 2, CURLOPT_TIMEOUT => 2,
        CURLOPT_USERAGENT => 'TroyEmployeePortal/1.0 (attendance home-location reverse geocoding)',
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    try {
        $body = curl_exec($curl);
        if ($body === false || curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200) return null;
        $json = json_decode($body, true);
        return is_array($json) && !isset($json['error']) ? portal_parse_nominatim($json) : null;
    } catch (Throwable) { return null; }
    finally { curl_close($curl); }
}
