<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCheckin()
    {
        // Mock authenticated user
        $user = Factory(User::class)->create();

        // Mock IP address and GPS coordinates
        $ipAddress = '192.168.1.1';
        $latitude = 37.7749;
        $longitude = -122.4194;

        // Make a request to the check-in endpoint
        $response = $this->actingAs($user, 'api')
            ->json('POST', '/checkin', [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Check-in recorded successfully',
            ]);

        // Assert that the attendance record was created in the database
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $user->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            // Add more assertions as needed
        ]);
    }

    public function testCheckout()
    {
        // Mock authenticated user
        $user = factory(User::class)->create();

        // Mock IP address and GPS coordinates
        $ipAddress = '192.168.1.1';
        $latitude = 37.7749;
        $longitude = -122.4194;

        // Make a request to the check-out endpoint
        $response = $this->actingAs($user, 'api')
            ->json('POST', '/checkout', [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);

        // Assert the response
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Check-out recorded successfully',
            ]);

        // Assert that the checkout record was created in the database
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $user->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            // Add more assertions as needed
        ]);
    }

    public function testSummary()
    {
        // Mock authenticated user
        $user = factory(User::class)->create();

        // Make a request to the summary endpoint
        $response = $this->actingAs($user, 'api')
            ->json('GET', '/attendance/summary');

        // Assert the response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'attendance_summary' => [
                        // Define the expected structure of the response
                    ]
                ]
            ]);
    }

    public function testNotifications()
    {
        // Mock authenticated user
        $user = factory(User::class)->create();

        // Make a request to the notifications endpoint
        $response = $this->actingAs($user, 'api')
                        ->json('GET', '/notifications');

        // Assert the response
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'notifications' => [
                        // Define the expected structure of the notifications array
                    ]
                ]);
    }

}
