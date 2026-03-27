<?php

namespace Swilen\Http\Component;

use Swilen\Http\Common\HttpCase;

final class ServerHunt extends ParameterHunt
{
    /**
     * Make default header for request.
     *
     * @return array<string, string>
     */
    public static function headers(array $params = []): array
    {
        $headers    = [];
        $additional = [
            'CONTENT_TYPE' => true,
            'CONTENT_LENGTH' => true,
            'CONTENT_MD5' => true,
        ];

        foreach ($params as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headers[HttpCase::toNormalizeHttp($key)] = $value;
            }
            // Add additional server headers to headers collection
            // CONTENT_* headers are not prefixed with HTTP_.
            elseif (isset($additional[$key])) {
                $headers[HttpCase::toNormalize($key)] = $value;
            }
        }

        if (\function_exists('apache_request_headers')) {
            $apacheRequestHeaders = \apache_request_headers();
            foreach ($apacheRequestHeaders as $key => $value) {
                $key = HttpCase::toNormalize($key);
                if (!isset($headers[$key])) {
                    $headers[$key] = $value;
                }
            }
        }

        $authorization = null;

        if (isset($params['Authorization'])) {
            $authorization = $params['Authorization'];
        } elseif (isset($params['HTTP_AUTHORIZATION'])) {
            $authorization = $params['HTTP_AUTHORIZATION'];
        } elseif (isset($params['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authorization = $params['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if ($authorization !== null) {
            $headers['Authorization'] = trim($authorization);
        }

        return $headers;
    }

    /**
     * Filter INPUT_SERVER with default value.
     *
     * @param string                $key
     * @param string|int|mixed|null $default
     * @param int                   $filters
     *
     * @return mixed
     */
    public function filter(string $key, $default = null, $filters = FILTER_DEFAULT | FILTER_SANITIZE_ENCODED)
    {
        return \filter_var($this->get($key, $default), $filters) ?: $default;
    }
}
