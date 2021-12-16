<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Bot;
use App\Models\User;
use App\Models\Webhook;
use App\Enums\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Repositories\Eloquents\BotRepository;
use Exception;
use Mockery;

class BotControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * test Feature list bots.
     *
     * @return void
     */
    public function testShowListChatbotFeature()
    {
        $chatbotList = factory(Bot::class, 2)->create();
        $user = $chatbotList[0]->user;

        $this->actingAs($user);
        $response = $this->get('/bots');
        $response->assertStatus(200);
        $response->assertViewHas('bots');
    }

    /**
     * test Feature list bots in page has no record.
     *
     * @return void
     */
    public function testShowListChatbotInNoRecordPageFeature()
    {
        $chatbotList = factory(Bot::class, 2)->create();
        $user = $chatbotList[0]->user;

        $this->actingAs($user);
        $response = $this->get(route('bots.index', ['page' => 2]));
        $response->assertLocation(route('bots.index', ['page' => 1]));
    }

    /**
     * test user can see create bot form
     *
     * @return void
     */
    public function testUserCanSeeCreateBotForm()
    {
        $user = factory(User::class)->make();

        $this->actingAs($user);
        $response = $this->get(route('bots.create'));

        $response
            ->assertStatus(200)
            ->assertViewIs('bots.create');
    }

    /**
     * test user unauthorized cannot see create bot form
     *
     * @return void
     */
    public function testUnauthorizedUserCannotSeeCreateBotForm()
    {
        $user = factory(User::class)->make();

        $response = $this->get(route('bots.create'));

        $response
            ->assertStatus(302)
            ->assertRedirect('/');
    }

    /**
     * test user authorized can create a new bot
     *
     * @return void
     */
    public function testUserCanCreateANewBot()
    {
        $user = factory(User::class)->create();
        $params = [
            'name' => 'Test Bot',
            'bot_key' => 'asdg12asd3423adasdasd23sdasdas23',
        ];

        $this->actingAs($user);
        $response = $this->post(route('bots.store'), $params);

        $response->assertRedirect('bots');
        $this->assertEquals(1, Bot::all()->count());
    }

    public function testCreateChatbotWithExceptionFeature()
    {
        $user = factory(User::class)->create();
        $params = [
            'name' => 'Test Bot',
            'bot_key' => 'asdg12asd3423adasdasd23sdasdas23',
        ];
        $this->actingAs($user);

        $mock = Mockery::mock(BotRepository::class);
        $mock->shouldReceive('create')->once()->andReturn(false)->andThrow(new Exception());
        $this->app->instance(BotRepository::class, $mock);
        $response = $this->post(route('bots.store'), $params);
        $response->assertSessionHas('messageFail', [
            'status' => 'Create failed',
            'message' => 'Create failed. Something went wrong',
        ]);
    }

    /**
     * test bot required name
     *
     * @return void
     */
    public function testBotRequireName()
    {
        $user = factory(User::class)->create();
        $params = [
            'name' => null,
            'bot_key' => 'asdg12asd3423adasdasd23sdasdas23',
        ];

        $this->actingAs($user);
        $response = $this->post(route('bots.store'), $params);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors('name');
    }

    /**
     * test bot unique name with a user
     *
     * @return void
     */
    public function testBotUniqueNameWithUser()
    {
        $bot = factory(Bot::class)->create();
        $user = $bot->user;

        $params = [
            'name' => $bot->name,
            'bot_key' => 'asdg12asd3423adasdasd23sdasdas23',
        ];

        $this->actingAs($user);
        $response = $this->post(route('bots.store'), $params);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors('name');
    }

    /**
     * test bot name have maximum length is 50 characters
     *
     * @return void
     */
    public function testBotNameMaximumLength()
    {
        $bot = factory(Bot::class)->create();
        $user = $bot->user;

        $params = [
            'name' => 'assdkdkdkdassdkdkdkdassdkdkdkdassdkdkdkdassdkdkdkd1',
            'bot_key' => 'asdg12asd3423adasdasd23sdasdas23',
        ];

        $this->actingAs($user);
        $response = $this->post(route('bots.store'), $params);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors('name');
    }

    /**
     * test bot required bot_key
     *
     * @return void
     */
    public function testBotRequiredBotKey()
    {
        $user = factory(User::class)->create();

        $params = [
            'name' => 'asd',
            'bot_key' => null,
        ];

        $this->actingAs($user);
        $response = $this->post(route('bots.store'), $params);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors('bot_key');
    }

    /**
     * test bot unique bot_key with user
     *
     * @return void
     */
    public function testBotUniqueBotKeyWithUser()
    {
        $bot = factory(Bot::class)->create();
        $user = $bot->user;

        $params = [
            'name' => 'asd',
            'bot_key' => $bot->bot_key,
        ];

        $this->actingAs($user);
        $response = $this->post(route('bots.store'), $params);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors('bot_key');
    }

    /**
     * test bot bot_key have maximum length is 50 characters
     *
     * @return void
     */
    public function testBotKeyMaximumLength()
    {
        $bot = factory(Bot::class)->create();
        $user = $bot->user;

        $params = [
            'name' => 'asd',
            'bot_key' => 'asdasdasdwasdasdasdwasdasdasdwasdasdasdwasdasdasdweasdasdasdwasdasdasdwasdasdasdwasdasdasdwasdasdasdw',
        ];

        $this->actingAs($user);
        $response = $this->post(route('bots.store'), $params);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors('bot_key');
    }

    /**
     * test Feature remove bot successfully.
     *
     * @return void
     */
    public function testRemoveChatbotFeature()
    {
        $bot = factory(Bot::class)->create(['name' => 'test remove bot']);
        $user = $bot->user;

        $this->actingAs($user);
        $response = $this->delete(route('bots.destroy', $bot->id));
        $this->assertDatabaseMissing('bots', [
            'id' => $bot->id,
            'name' => 'test remove bot',
            'deleted_at' => null,
        ]);
        $response->assertRedirect('/bots');
        $response->assertStatus(302);
    }

    public function testRemoveChatbotWithExceptionFeature()
    {
        $bot = factory(Bot::class)->create(['name' => 'test remove bot']);
        $user = $bot->user;

        $this->actingAs($user);

        $mock = Mockery::mock(BotRepository::class);
        $mock->shouldReceive('delete')->andThrowExceptions([new Exception('Exception', 100)]);
        $this->app->instance(BotRepository::class, $mock);
        $response = $this->delete(route('bots.destroy', $bot->id));
        $response->assertSessionHas('messageFail', [
            'status' => 'Delete failed',
            'message' => 'Delete failed. Something went wrong',
        ]);
    }

    /**
     * test Feature remove bot at second page successfully.
     *
     * @return void
     */
    public function testRemoveChatbotAtSecondPageFeature()
    {
        $bot = factory(Bot::class)->create(['name' => 'test remove bot']);
        $user = $bot->user;

        $this->actingAs($user);
        $response = $this->delete(route('bots.destroy', ['page' => 2, 'bot' => $bot->id]));
        $this->assertDatabaseMissing('bots', [
            'id' => $bot->id,
            'name' => 'test remove bot',
            'deleted_at' => NULL,
        ]);
        $response->assertRedirect(route('bots.index', ['page' => 2]));
        $response->assertStatus(302);
    }

    /**
     * test Feature remove bot fail.
     *
     * @return void
     */
    public function testRemoveChatbotFailFeature()
    {
        $bot = factory(Bot::class)->create(['name' => 'test remove bot fail']);
        $user = $bot->user;

        $this->actingAs($user);
        $response = $this->delete(route('bots.destroy', ($bot->id + 99)));
        $this->assertDatabaseHas('bots', ['name' => 'test remove bot fail']);
        $response->assertStatus(404);
    }

    /**
     * test Feature remove bot fail.
     *
     * @return void
     */
    public function testRemoveChatbotAddedToWebhook()
    {
        $bot = factory(Bot::class)->create(['name' => 'test remove bot fail']);
        $user = $bot->user;
        factory(Webhook::class)->create(['bot_id' => $bot->id]);

        $this->actingAs($user);
        $response = $this->delete(route('bots.destroy', $bot->id));
        $this->assertDatabaseHas('bots', ['id' => $bot->id]);
        $response->assertStatus(302);
        $response->assertSessionHas('messageFail', [
            'status' => 'Delete failed',
            'message' => 'This bot has been added to some webhooks, please remove it first',
        ]);
    }

    /**
     * test Feature remove bot unauthorized
     *
     * @return void
     */
    public function testRemoveChatbotUnauthorizedFeature()
    {
        $response = $this->delete(route('bots.destroy', 1));

        $response->assertLocation('/');
        $response->assertStatus(302);
    }

    /**
     * test remove bot permission denine
     *
     * @return void
     */
    public function testRemoveBotPermissionDenine()
    {
        $bot = factory(Bot::class)->create();
        $user = factory(User::class)->create();

        $this->actingAs($user);
        $response = $this->delete(route('bots.destroy', $bot->id));

        $response->assertStatus(403);
    }

    /**
     * test edit bot permission denine
     *
     * @return void
     */
    public function testEditBotPermissionDenine()
    {
        $bot = factory(Bot::class)->create();
        $user = factory(User::class)->create();

        $this->actingAs($user);
        $response = $this->get(route('bots.edit', $bot->id));

        $response->assertStatus(403);
    }

    /**
     * test user can see edit bot form
     *
     * @return void
     */
    public function testUserCanSeeEditBotForm()
    {
        $user = factory(User::class)->create();
        $bot = factory(Bot::class)->create(['name' => 'test remove bot fail', 'user_id' => $user->id]);
        $this->actingAs($user);
        $response = $this->get(route('bots.edit', $bot->id));
        $response
            ->assertStatus(200)
            ->assertViewIs('bots.edit');
    }

    /**
     * test user unauthorized cannot see edit bot form
     *
     * @return void
     */
    public function testUnauthorizedUserCannotSeeEditBotForm()
    {
        $user = factory(User::class)->create();
        $bot = factory(Bot::class)->create(['name' => 'test remove bot fail', 'user_id' => $user->id]);
        $response = $this->get(route('bots.edit', $bot->id));
        $response
            ->assertStatus(302)
            ->assertRedirect('/');
    }

    /**
     * test user authorized can edit bot
     *
     * @return void
     */
    public function testUserCanEditBot()
    {
        $user = factory(User::class)->create();
        $bot = factory(Bot::class)->create(['name' => 'Created Bot', 'user_id' => $user->id]);
        $params = [
            'name' => 'Updated Bot',
            'bot_key' => 'asdg12asd3423adasdasd23sdasdas23',
        ];

        $this->actingAs($user);
        $response = $this->put(route('bots.update', $bot->id), $params);
        $response->assertRedirect('bots/' . $bot->id . '/edit');
        $this->assertDatabaseHas('bots', ['id' => $bot->id, 'name' => 'Updated Bot', 'bot_key' => 'asdg12asd3423adasdasd23sdasdas23']);
    }
    public function testUserCanEditBotBotKeyRequire()
    {
        $user = factory(User::class)->create();
        $bot = factory(Bot::class)->create(['name' => 'Created Bot', 'user_id' => $user->id]);
        $params = [
            'name' => 'Updated Bot',
            'bot_key' => '',
        ];

        $this->actingAs($user);
        $response = $this->put(route('bots.update', $bot->id), $params);
        $response->assertRedirect('bots/' . $bot->id . '/edit');
        $this->assertDatabaseHas('bots', ['id' => $bot->id, 'name' => 'Updated Bot', 'bot_key' => $bot->bot_key]);
    }
    public function testUpdateChatbotWithExceptionFeature()
    {
        $user = factory(User::class)->create();
        $bot = factory(Bot::class)->create(['name' => 'Created Bot', 'user_id' => $user->id]);
        $params = [
            'name' => 'Updated Bot',
            'bot_key' => 'asdg12asd3423adasdasd23sdasdas23',
        ];
        $this->actingAs($user);
        $mock = Mockery::mock(BotRepository::class);
        $mock->shouldReceive('update')->once()->andReturn(false)->andThrow(new Exception());
        $this->app->instance(BotRepository::class, $mock);
        $response = $this->put(route('bots.update', $bot->id), $params);
        $response->assertSessionHas('messageFail', [
            'status' => 'Update failed',
            'message' => 'Update failed. Something went wrong',
        ]);
    }

    /**
     * test bot required name
     *
     * @return void
     */
    public function testUpdateBotRequireName()
    {
        $user = factory(User::class)->create();
        $bot = factory(Bot::class)->create(['name' => 'Created Bot', 'user_id' => $user->id]);
        $params = [
            'name' => null,
            'bot_key' => 'asdg12asd3423adasdasd23sdasdas23',
        ];

        $this->actingAs($user);
        $response = $this->put(route('bots.update', $bot->id), $params);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors('name');
    }

    /**
     * test bot unique name with a user
     *
     * @return void
     */
    public function testUpdateBotUniqueNameWithUser()
    {
        $user = factory(User::class)->create();
        $bot_1 = factory(Bot::class)->create(['name' => 'Created Bot 1', 'user_id' => $user->id]);
        $bot_2 = factory(Bot::class)->create(['name' => 'Created Bot 2', 'user_id' => $user->id]);

        $params = [
            'name' => $bot_1->name,
            'bot_key' => 'asdg12asd3423adasdasd23sdasdas23',
        ];

        $this->actingAs($user);
        $response = $this->put(route('bots.update', $bot_2->id), $params);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors('name');
    }

    /**
     * test bot name have maximum length is 50 characters
     *
     * @return void
     */
    public function testUpdateBotNameMaximumLength()
    {
        $user = factory(User::class)->create();
        $bot = factory(Bot::class)->create(['name' => 'Created Bot', 'user_id' => $user->id]);
        $params = [
            'name' => 'assdkdkdkdassdkdkdkdassdkdkdkdassdkdkdkdassdkdkdkd1',
            'bot_key' => 'asdg12asd3423adasdasd23sdasdas23',
        ];

        $this->actingAs($user);
        $response = $this->put(route('bots.update', $bot->id), $params);
        $response
            ->assertStatus(302)
            ->assertSessionHasErrors('name');
    }

    /**
     * test bot unique bot_key with user
     *
     * @return void
     */
    public function testUpdateBotUniqueBotKeyWithUser()
    {
        $user = factory(User::class)->create();
        $bot = factory(Bot::class)->create(['name' => 'Created Bot', 'user_id' => $user->id]);
        $params = [
            'name' => 'asd',
            'bot_key' => $bot->bot_key,
        ];

        $this->actingAs($user);
        $response = $this->put(route('bots.update', $bot->id), $params);
        $response
            ->assertStatus(302)
            ->assertSessionHasErrors('bot_key');
    }

    /**
     * test bot bot_key have maximum length is 50 characters
     *
     * @return void
     */
    public function testUpdateBotKeyMaximumLength()
    {
        $user = factory(User::class)->create();
        $bot = factory(Bot::class)->create(['name' => 'Created Bot', 'user_id' => $user->id]);
        $params = [
            'name' => 'asd',
            'bot_key' => 'asdasdasdwasdasdasdwasdasdasdwasdasdasdwasdasdasdweasdasdasdwasdasdasdwasdasdasdwasdasdasdwasdasdasdw',
        ];

        $this->actingAs($user);
        $response = $this->put(route('bots.update', $bot->id), $params);
        $response
            ->assertStatus(302)
            ->assertSessionHasErrors('bot_key');
    }
}
