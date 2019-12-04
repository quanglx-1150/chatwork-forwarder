<?php

namespace Tests\Feature;

use App\Models\Condition;
use Tests\TestCase;
use App\Models\User;
use App\Models\Webhook;
use App\Models\Payload;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PayloadControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * test Feature remove payload successfully.
     *
     * @return void
     */
    public function testRemovePayloadFeature()
    {
        $user = factory(User::class)->create();
        $webhook = factory(Webhook::class)->create(['user_id' => $user->id]);
        $payload = factory(Payload::class)->create(['webhook_id' => $webhook->id, 'content' => 'test remove payload']);

        $this->actingAs($user);
        $response = $this->delete(route('webhooks.payloads.destroy', ['webhook' => $webhook, 'payload' => $payload]));
        $this->assertDatabaseMissing('payloads', ['id' => $payload->id, 'content' => 'test remove payload']);
        $response->assertRedirect(route('webhooks.edit', $webhook));
        $response->assertStatus(302);
    }

    /**
     * test Feature remove payload fail.
     *
     * @return void
     */
    public function testRemovePayloadFailFeature()
    {
        $user = factory(User::class)->create();
        $webhook = factory(Webhook::class)->create(['user_id' => $user->id]);
        $payload = factory(Payload::class)->create(['webhook_id' => $webhook->id, 'content' => 'test remove payload fail']);

        $this->actingAs($user);
        $response = $this->delete(route('webhooks.payloads.destroy', ['webhook' => $webhook, 'payload_id' => ($payload->id + 99)]));
        $this->assertDatabaseHas('payloads', ['content' => 'test remove payload fail']);
        $response->assertStatus(404);
    }

    /**
     * test Feature remove payload unauthorized
     *
     * @return void
     */
    public function testRemovePayloadUnauthorizedFeature()
    {
        $user = factory(User::class)->create();
        $webhook = factory(Webhook::class)->create(['user_id' => $user->id]);
        $payload = factory(Payload::class)->create(['webhook_id' => $webhook->id, 'content' => 'test remove payload fail']);
        $response = $this->delete(route('webhooks.payloads.destroy', ['webhook' => $webhook, 'payload_id' => 1]));

        $response->assertLocation('/login');
        $response->assertStatus(302);
    }

    /**
    * test Feature remove Permision denied. Payload belong to another user
    *
    * @return void
    */
    public function testRemovePayloadFailPermissionDenied1Feature()
    {
        $user = factory(User::class)->create();
        $another_user = factory(User::class)->create();
        $webhook = factory(Webhook::class)->create(['user_id' => $another_user->id]);
        $payload = factory(Payload::class)->create(['webhook_id' => $webhook->id, 'content' => 'payload of another user']);

        $this->actingAs($user);
        $response = $this->delete(route('webhooks.payloads.destroy', ['webhook' => $webhook, 'payload_id' => ($payload->id)]));
        $this->assertDatabaseHas('payloads', ['content' => 'payload of another user']);
        $response->assertStatus(403);
    }

    /**
     * test Feature remove Permision denied. Payload belong to another webhook
     *
     * @return void
    */
    public function testRemovePayloadFailPermissionDenied2Feature()
    {
        $user = factory(User::class)->create();
        $webhook = factory(Webhook::class)->create(['user_id' => $user->id]);
        $another_webhook = factory(Webhook::class)->create(['user_id' => $user->id]);
        $payload = factory(Payload::class)->create(['webhook_id' => $webhook->id]);
        $another_payload = factory(Payload::class)->create(['webhook_id' => $another_webhook->id, 'content' => 'payload of another webhook']);
        $this->actingAs($user);
        $response = $this->delete(route('webhooks.payloads.destroy', ['webhook' => $webhook, 'payload_id' => ($another_payload->id)]));
        $this->assertDatabaseHas('payloads', ['content' => 'payload of another webhook']);
        $response->assertStatus(403);
    }

    /**
     * test Feature show create payload view successfully
     *
     * @return void
     */
    public function testShowCreateViewPayloadFeature()
    {
        $user = factory(User::class)->create();
        $webhook = factory(Webhook::class)->create(['user_id' => $user->id]);

        $this->actingAs($user);
        $response = $this->get(route('webhooks.payloads.create', $webhook));
        $response->assertStatus(200);
        $response->assertViewHas('webhook');
    }

    /**
     * test Feature show create payload view when user not authorized
     *
     * @return void
     */
    public function testShowCreateViewPayloadUnauthorizedFeature()
    {
        $user = factory(User::class)->create();
        $webhook = factory(Webhook::class)->create(['user_id' => $user->id]);

        $response = $this->get(route('webhooks.payloads.create', $webhook));
        $response->assertLocation('/login');
        $response->assertStatus(302);
    }

    /**
     * test Feature show create payload view when webhook not exist
     *
     * @return void
     */
    public function testShowCreateViewPayloadOfWebhookNotExistFeature()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $response = $this->get(route('webhooks.payloads.create', -1));
        $response->assertStatus(404);
    }

    /**
     * test Feature store payload successfully
     *
     * @return void
     */
    public function testStorePayloadSuccessFeature()
    {
        $user = factory(User::class)->create();
        $webhook = factory(Webhook::class)->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->post(route('webhooks.payloads.store', $webhook), [
            'content' => 'sample content',
            'fields' => ['$payload->name', '$payload->age'],
            'operators' => ['==', '>'],
            'values' => ['rammus', '30']
        ]);
        $response->assertStatus(200);
        $response->assertSessionHas('messageSuccess', 'This payload successfully created');
    }

    /**
     * test Feature store payload violate validation rule
     *
     * @return void
     */
    public function testStorePayloadFailedFeature()
    {
        $user = factory(User::class)->create();
        $webhook = factory(Webhook::class)->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->post(route('webhooks.payloads.store', $webhook), [
            'content' => '',
            'fields' => ['$payload->name', '$payload->age'],
            'operators' => ['==', '>'],
            'values' => ['rammus', '30']
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'content' => 'Please enter content'
        ]);
    }

    /**
     * test Feature store payload with not existed webhook
     *
     * @return void
     */
    public function testStorePayloadOfWebhookNotExistFeature()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);

        $response = $this->post(route('webhooks.payloads.store', -1), [
            'content' => 'sample content',
            'fields' => ['$payload->name', '$payload->age'],
            'operators' => ['==', '>'],
            'values' => ['rammus', '30']
        ]);
        $response->assertStatus(404);
    }

    /**
     * test Feature show payload detail success
     *
     * @return void
     */
    public function testShowPayloadDetailSuccessFeature()
    {
        $user = factory(User::class)->create();
        $webhook = factory(Webhook::class)->create(['user_id' => $user->id]);
        $payload = factory(Payload::class)->create(['webhook_id' => $webhook->id]);
        factory(Condition::class)->create(['payload_id' => $payload->id]);
        $this->actingAs($user);

        $response = $this->get(route('webhooks.payloads.edit', ['webhook' => $webhook, 'payload' => $payload]));
        $response->assertStatus(200);
        $response->assertViewHas(['webhook', 'payload', 'conditions']);
    }

    /**
     * test Feature show payload detail when user not login
     *
     * @return void
     */
    public function testShowPayloadDetailUnauthorizedFeature()
    {
        $user = factory(User::class)->create();
        $webhook = factory(Webhook::class)->create(['user_id' => $user->id]);
        $payload = factory(Payload::class)->create(['webhook_id' => $webhook->id]);
        $response = $this->get(route('webhooks.payloads.edit', ['webhook' => $webhook, 'payload' => $payload]));

        $response->assertLocation('/login');
        $response->assertStatus(302);
    }

    /**
     * test Feature show payload does not exist
     *
     * @return void
     */
    public function testShowNotExistPayloadDetailFeature()
    {
        $user = factory(User::class)->create();
        $webhook = factory(Webhook::class)->create(['user_id' => $user->id]);
        $this->actingAs($user);
        $response = $this->get(route('webhooks.payloads.edit', ['webhook' => $webhook, 'payload' => -1]));

        $response->assertStatus(404);
    }

    /**
     * test Feature show payload not belong to webhook
     *
     * @return void
     */
    public function testShowPayloadNotBelongToWebhookFeature()
    {
        $user = factory(User::class)->create();
        $currentWebhook = factory(Webhook::class)->create(['user_id' => $user->id]);
        $payload = factory(Payload::class)->create(['webhook_id' => $currentWebhook->id, 'content' => 'Sample content']);
        $anotherWebhook = factory(Webhook::class)->create(['user_id' => $user->id]);
        $this->actingAs($user);
        $response = $this->get(route('webhooks.payloads.edit', ['webhook' => $anotherWebhook, 'payload' => $payload]));

        $response->assertStatus(403);
    }

    /**
     * test Feature show webhook belong to another user
     *
     * @return void
     */
    public function testShowWebhookBelongToOtherUserFeature()
    {
        $currentUser = factory(User::class)->create();
        $anotherUser = factory(User::class)->create();
        $webhook = factory(Webhook::class)->create(['user_id' => $anotherUser->id]);
        $payload = factory(Payload::class)->create(['webhook_id' => $webhook->id, 'content' => 'old content']);
        $this->actingAs($currentUser);
        $response = $this->get(route('webhooks.payloads.edit', ['webhook' => $webhook, 'payload' => $payload]));

        $response->assertStatus(403);
    }

    /**
     * test Feature payload is updated successfully
     *
     * @return void
     */
    public function testUpdatePayloadSuccessFeature()
    {
        $user = factory(User::class)->create();
        $webhook = factory(Webhook::class)->create(['user_id' => $user->id]);
        $payload = factory(Payload::class)->create(['webhook_id' => $webhook->id, 'content' => 'old content']);
        $this->actingAs($user);
        $response = $this->put(route('webhooks.payloads.update', ['webhook' => $webhook, 'payload' => $payload]), [
            'content' => 'new content'
        ]);
        $payload = Payload::find($payload->id);

        $response->assertStatus(200);
        $response->assertSessionHas('messageSuccess', 'This payload successfully updated');
        $this->assertEquals($payload->content, 'new content');
    }

    /**
     * test Feature payload with its conditions are updated successfully
     *
     * @return void
     */
    public function testUpdatePayloadWithConditionsSuccessFeature()
    {
        $user = factory(User::class)->create();
        $webhook = factory(Webhook::class)->create(['user_id' => $user->id]);
        $payload = factory(Payload::class)->create(['webhook_id' => $webhook->id, 'content' => 'old content']);
        $condition = factory(Condition::class)->create([
            'payload_id' => $payload->id,
            'field' => '$payload->user->name',
            'operator' => '==',
            'value' => 'rasmus',
        ]);
        $params = [
            'content' => 'New content',
            'ids' => [$condition->id],
            'conditions' => [[
                'id' => $condition->id,
                'field' => '$payload->user->age',
                'operator' => '>=',
                'value' => '18'
            ]]
        ];
        $this->actingAs($user);
        $response = $this->put(route('webhooks.payloads.update', ['webhook' => $webhook, 'payload' => $payload]), $params);
        $condition = Condition::find($condition->id);

        $response->assertStatus(200);
        $response->assertSessionHas('messageSuccess', 'This payload successfully updated');
        $this->assertEquals($condition->field, $params['conditions'][0]['field']);
        $this->assertEquals($condition->operator, $params['conditions'][0]['operator']);
        $this->assertEquals($condition->value, $params['conditions'][0]['value']);
    }

    /**
     * test Feature updating payload when violating validation rule
     *
     * @return void
     */
    public function testUpdatePayloadFailedFeature()
    {
        $user = factory(User::class)->create();
        $webhook = factory(Webhook::class)->create(['user_id' => $user->id]);
        $payload = factory(Payload::class)->create(['webhook_id' => $webhook->id]);
        $this->actingAs($user);
        $response = $this->put(route('webhooks.payloads.update', ['webhook' => $webhook, 'payload' => $payload]), [
            'content' => ''
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'content' => 'Please enter content'
        ]);
    }

    /**
     * test Feature updating an payload does not exist
     *
     * @return void
     */
    public function testUpdatePayloadNotExistFeature()
    {
        $user = factory(User::class)->create();
        $webhook = factory(Webhook::class)->create(['user_id' => $user->id]);
        $this->actingAs($user);
        $response = $this->put(route('webhooks.payloads.update', ['webhook' => $webhook, 'payload' => -1]), [
            'content' => 'New content'
        ]);

        $response->assertStatus(404);
    }

    /**
     * test Feature update payload not belong to webhook
     *
     * @return void
     */
    public function testUpdatePayloadNotBelongToWebhookFeature()
    {
        $user = factory(User::class)->create();
        $currentWebhook = factory(Webhook::class)->create(['user_id' => $user->id]);
        $payload = factory(Payload::class)->create(['webhook_id' => $currentWebhook->id, 'content' => 'old content']);
        $anotherWebhook = factory(Webhook::class)->create(['user_id' => $user->id]);
        $this->actingAs($user);
        $response = $this->put(route('webhooks.payloads.update', ['webhook' => $anotherWebhook, 'payload' => $payload]), [
            'content' => 'new content'
        ]);

        $response->assertStatus(403);
    }

    /**
     * test Feature update webhook belong to another user
     *
     * @return void
     */
    public function testUpdateWebhookBelongToOtherUserFeature()
    {
        $currentUser = factory(User::class)->create();
        $anotherUser = factory(User::class)->create();
        $webhook = factory(Webhook::class)->create(['user_id' => $anotherUser->id]);
        $payload = factory(Payload::class)->create(['webhook_id' => $webhook->id, 'content' => 'old content']);
        $this->actingAs($currentUser);
        $response = $this->put(route('webhooks.payloads.update', ['webhook' => $webhook, 'payload' => $payload]), [
            'content' => 'new content'
        ]);

        $response->assertStatus(403);
    }
}
