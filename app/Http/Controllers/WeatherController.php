<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;

class WeatherController extends Controller
{
    public function index($location)
    {

        $weather_location = $location;

        $validator = Validator::make(["location" => $weather_location], [
            "location" => "string"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status_code" => 422,
                "message" => $validator->errors()
            ], 422);
        }


        $api_key = config('services.api.key');


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
        } catch (\Exception $e) {
            return response()->json([
                "status_code" => 400,
                "message" => $e->getMessage(),
            ], 400);
        }

        return response()->json([
            "status_code" => 201,
            "message" => "Weather data retrieved successfully",
            "data" => [
                "content" => $response->json()
            ]
        ], 201);
    }
}
