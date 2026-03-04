<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_store_push_subscription()
    {
        $user = User::factory()->create();
        
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        $user->assignRole('admin');

        $payload = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-123',
            'keys' => [
                'auth' => 'auth-key-test',
                'p256dh' => 'p256dh-key-test',
            ]
        ];

        $response = $this->actingAs($user)->postJson(route('admin.push-subscriptions.store'), $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id' => $user->id,
            'subscribable_type' => User::class,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-123',
        ]);
    }

    public function test_user_can_delete_push_subscription()
    {
        $user = User::factory()->create();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        $user->assignRole('admin');

        // Creada primero
        $user->updatePushSubscription(
            'https://fcm.googleapis.com/fcm/send/test-endpoint-123',
            'p256dh-key-test',
            'auth-key-test'
        );

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-123',
        ]);

        $response = $this->actingAs($user)->postJson(route('admin.push-subscriptions.destroy'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-123',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('push_subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-123',
        ]);
    }
}
