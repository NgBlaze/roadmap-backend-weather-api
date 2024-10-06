<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WeatherControllerTest extends TestCase
{
    public function test_location_gets_passed_from_path_params()
    {

        $response = $this->getJson('api/v1/weather/london');

        $location = 'london';

        $response->assertStatus(201);

        $this->assertEquals('london', $location);
    }
}
