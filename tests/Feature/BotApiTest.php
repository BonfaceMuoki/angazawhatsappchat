<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $superAdmin = Role::where('role_name', 'super_admin')->first();
        $this->assertNotNull($superAdmin, 'super_admin role should exist after seeding');

        $this->user = User::factory()->create([
            'email' => 'bot-test@example.com',
        ]);
        $this->user->roles()->attach($superAdmin->id);

        $jwt = $this->app->make(JwtService::class);
        $this->token = $jwt->fromUser($this->user);
    }

    public function test_bot_flows_index_returns_200_and_data_array(): void
    {
        $response = $this->getJson('/api/admin/bot/flows', [
            'Authorization' => 'Bearer ' . $this->token,
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => []]);
        $this->assertIsArray($response->json('data'));
    }

    public function test_bot_flows_can_create_and_show(): void
    {
        $create = $this->postJson('/api/admin/bot/flows', [
            'name' => 'Test Survey',
            'description' => 'For testing',
            'show_in_router' => true,
            'display_order' => 0,
            'is_active' => true,
        ], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);
        $create->assertStatus(201);
        $create->assertJsonPath('data.name', 'Test Survey');
        $flowId = $create->json('data.id');

        $show = $this->getJson("/api/admin/bot/flows/{$flowId}", [
            'Authorization' => 'Bearer ' . $this->token,
        ]);
        $show->assertStatus(200);
        $show->assertJsonPath('data.id', $flowId);
        $show->assertJsonPath('data.name', 'Test Survey');
    }

    public function test_bot_nodes_can_create_for_flow(): void
    {
        $flow = $this->postJson('/api/admin/bot/flows', [
            'name' => 'Node Test Flow',
            'show_in_router' => false,
            'display_order' => 0,
            'is_active' => true,
        ], [
            'Authorization' => 'Bearer ' . $this->token,
        ])->json('data');

        $node = $this->postJson('/api/admin/bot/nodes', [
            'flow_id' => $flow['id'],
            'node_key' => 'step_1',
            'type' => 'buttons',
            'message' => 'Hello, choose one:',
            'is_entry' => true,
            'is_active' => true,
        ], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);
        $node->assertStatus(201);
        $node->assertJsonPath('data.node_key', 'step_1');
        $node->assertJsonPath('data.type', 'buttons');
        $node->assertJsonPath('data.message', 'Hello, choose one:');
    }

    public function test_bot_edges_can_create_between_nodes(): void
    {
        $flow = $this->postJson('/api/admin/bot/flows', [
            'name' => 'Edge Test Flow',
            'show_in_router' => false,
            'display_order' => 0,
            'is_active' => true,
        ], [
            'Authorization' => 'Bearer ' . $this->token,
        ])->json('data');

        $node1 = $this->postJson('/api/admin/bot/nodes', [
            'flow_id' => $flow['id'],
            'node_key' => 'step_1',
            'type' => 'buttons',
            'message' => 'First?',
            'is_entry' => true,
            'is_active' => true,
        ], [
            'Authorization' => 'Bearer ' . $this->token,
        ])->json('data');

        $node2 = $this->postJson('/api/admin/bot/nodes', [
            'flow_id' => $flow['id'],
            'node_key' => 'step_2',
            'type' => 'text',
            'message' => 'Thanks.',
            'is_entry' => false,
            'is_active' => true,
        ], [
            'Authorization' => 'Bearer ' . $this->token,
        ])->json('data');

        $edge = $this->postJson('/api/admin/bot/edges', [
            'source_node_id' => $node1['id'],
            'target_node_id' => $node2['id'],
            'option_label' => 'Yes',
            'option_value' => 'yes',
            'order' => 0,
        ], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);
        $edge->assertStatus(201);
        $edge->assertJsonPath('data.option_label', 'Yes');
        $edge->assertJsonPath('data.option_value', 'yes');
    }

    public function test_bot_flows_require_permission_returns_403_without_bot_permission(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $supportRole = Role::where('role_name', 'customer_support')->first();
        $this->assertNotNull($supportRole);

        $supportUser = User::factory()->create(['email' => 'support@example.com']);
        $supportUser->roles()->attach($supportRole->id);

        $jwt = $this->app->make(JwtService::class);
        $supportToken = $jwt->fromUser($supportUser);

        $response = $this->getJson('/api/admin/bot/flows', [
            'Authorization' => 'Bearer ' . $supportToken,
        ]);
        $response->assertStatus(403);
    }
}
