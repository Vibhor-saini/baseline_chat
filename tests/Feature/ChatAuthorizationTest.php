<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\ChatBox;
use Tests\TestCase;

class ChatAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_access_another_users_conversation(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $stranger = User::factory()->create();

        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->users()->attach([$user->id, $other->id]);

        $this->actingAs($stranger);

        $this->assertFalse($stranger->isMemberOfConversation($conversation->id));

        Livewire::test(ChatBox::class)
            ->call('loadChat', $conversation->id)
            ->assertSet('activeConversationId', null);
    }

    public function test_user_can_send_message_in_own_conversation(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->users()->attach([$user->id, $other->id]);

        Livewire::actingAs($user)
            ->test(ChatBox::class)
            ->call('loadChat', $conversation->id)
            ->set('messageBody', 'Hello there')
            ->call('sendMessage')
            ->assertSet('messageBody', '');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'body' => 'Hello there',
        ]);
    }

    public function test_membership_check_blocks_unauthorized_conversations(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $stranger = User::factory()->create();

        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->users()->attach([$user->id, $other->id]);

        $this->assertTrue($user->isMemberOfConversation($conversation->id));
        $this->assertTrue($other->isMemberOfConversation($conversation->id));
        $this->assertFalse($stranger->isMemberOfConversation($conversation->id));
    }
}
