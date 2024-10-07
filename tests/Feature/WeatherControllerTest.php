<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WeatherControllerTest extends TestCase
{
    public function test_location_gets_passed_from_path_params()
    {

        $response = $this->getJson('api/v1/weather/london');

        $location = 'london';

        $response->assertStatus(201);

        $this->assertEquals('london', $location);
    }

    public function test_if_invalid_location_gives_appropriate_error_message()
    {
        $response = $this->getJson('api/v1/weather/londonnnnnn');
        $response->assertStatus(400);

        $response->assertJson([
            "status_code" => 400,
            "message" => "Invalid Location",
        ]);
    }

    public function test_if_redis_caches_location_and_retrieves_location_correctly()
    {
        $response = $this->getJson('api/v1/weather/monaco');
        $response->assertStatus(201);
        $response->assertJson([
            "status_code" => 201,
            "message" => "Weather data retrieved successfully",
        ]);

        $cached_response = $this->getJson('api/v1/weather/monaco');
        $cached_response->assertStatus(201);
        $cached_response->assertJson([
            "status_code" => 201,
            "message" => "Weather data retrieved from cache successfully",
        ]);
    }
}
