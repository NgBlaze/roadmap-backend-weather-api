<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;

class WeatherController extends Controller
{
    public function index($location)
    {

        $weather_location = $location;

        $validator = Validator::make(["location" => $weather_location], [
            "location" => "string|alpha"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status_code" => 422,
                "message" => $validator->errors()
            ], 422);
        }

        $geo_code_api_key = config('services.geo_code.key');




        $api_key = config('services.api.key');
        $valid_location = Http::get("https://geocode.maps.co/search?q={$weather_location}&api_key={$geo_code_api_key}");
        $location_data = $valid_location->json();

        if (empty($location_data) || (is_array($location_data) && count($location_data) === 0)) {
            return response()->json([
                "status_code" => 400,
                "message" => "Invalid Location",
            ], 400);
        }


        try {

            $response = RateLimiter::attempt(
                'weather-location:' . $weather_location,
                $perMinute = 10,
                function () use ($weather_location, $api_key) {
                    return Http::get("https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/{$weather_location}?key={$api_key}");
                }
            );

            if (! $response) {
                return response()->json([
                    "status_code" => 429,
                    "message" => "Too many Requests"
                ], 429);
            }

            if ($response->failed()) {
                return response()->json([
                    "status_code" => 400,
                    "message" => "Request Failed"
                ], 400);
            }

            if ($response->serverError()) {
                return response()->json([
                    "status_code" => 500,
                    "message" => "Server Error",
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                "status_code" => 400,
                "message" => $e->getMessage(),
            ], 400);
        }

        $redis_key = 'weather_' . $weather_location;

        if (Redis::exists($redis_key)) {
            $cached_weather_data = Redis::get($redis_key);
            return response()->json([
                "status_code" => 201,
                "message" => "Weather data retrieved from cache successfully",
                "data" => json_decode($cached_weather_data)
            ], 201);
        }
        if ($response->successful()) {
            Redis::setex($redis_key, 3600, $response->body());
            return response()->json([
                "status_code" => 201,
                "message" => "Weather data retrieved successfully",
                "data" => [
                    "content" => $response->json()
                ]
            ], 201);
        }
    }
}
