<?php
declare(strict_types=1);

/**
 * Minimal route table matcher.
 *
 * Routes are keyed "METHOD /path"; paths may contain {id} (digits) and
 * {uuid} (36-character UUID) placeholders. Exact routes always win.
 */

function portal_normalize_route_path(string $path): string
{
    $path = '/' . ltrim(trim($path), '/');

    if ($path !== '/') {
        $path = rtrim($path, '/');
    }

    return $path === '' ? '/' : $path;
}

function portal_route_params(string $pattern, string $path): ?array
{
    $patternSegments = explode('/', trim($pattern, '/'));
    $pathSegments = explode('/', trim($path, '/'));

    if (count($patternSegments) !== count($pathSegments)) {
        return null;
    }

    $params = [];

    foreach ($patternSegments as $index => $segment) {
        $value = $pathSegments[$index];

        if ($segment === '{id}') {
            if (!ctype_digit($value)) {
                return null;
            }
            $params['id'] = $value;
            continue;
        }

        if ($segment === '{uuid}') {
            $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
            if (preg_match($uuidPattern, $value) !== 1) {
                return null;
            }
            $params['uuid'] = $value;
            continue;
        }

        if ($segment !== $value) {
            return null;
        }
    }

    return $params;
}

function portal_route_entry(mixed $value, array $params): array
{
    if (is_array($value) && array_key_exists('handler', $value)) {
        return [
            'handler' => $value['handler'],
            'params' => $params,
            'public' => (bool) ($value['public'] ?? false),
        ];
    }

    return [
        'handler' => $value,
        'params' => $params,
        'public' => false,
    ];
}

function portal_route_match(string $method, string $path, array $routes): ?array
{
    $method = strtoupper($method);
    $path = portal_normalize_route_path($path);

    $exactKey = $method . ' ' . $path;

    if (array_key_exists($exactKey, $routes)) {
        return portal_route_entry($routes[$exactKey], []);
    }

    foreach ($routes as $route => $value) {
        $parts = explode(' ', (string) $route, 2);

        if (count($parts) !== 2 || strtoupper($parts[0]) !== $method) {
            continue;
        }

        $params = portal_route_params(portal_normalize_route_path($parts[1]), $path);

        if ($params !== null) {
            return portal_route_entry($value, $params);
        }
    }

    return null;
}

function portal_route_allowed_methods(string $path, array $routes): array
{
    $path = portal_normalize_route_path($path);
    $methods = [];

    foreach ($routes as $route => $value) {
        $parts = explode(' ', (string) $route, 2);

        if (count($parts) !== 2) {
            continue;
        }

        if (portal_route_params(portal_normalize_route_path($parts[1]), $path) !== null) {
            $methods[] = strtoupper($parts[0]);
        }
    }

    return array_values(array_unique($methods));
}
