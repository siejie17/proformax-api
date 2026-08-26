<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectMessage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleEditingAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Role $memberRole;

    private Role $developerRole;

    private Role $quantitySurveyorRole;

    private Role $facilitatorRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->memberRole = Role::create([
            'name' => 'member',
            'display_name' => 'Member',
            'level' => 10,
            'permissions' => ['view_messages', 'view_members'],
        ]);
        $this->developerRole = Role::create([
            'name' => 'developer',
            'display_name' => 'Developer',
            'level' => 20,
            'permissions' => ['view_messages', 'view_members', 'send_messages', 'upload_attachments'],
        ]);
        $this->quantitySurveyorRole = Role::create([
            'name' => 'quantity_surveyor',
            'display_name' => 'Quantity Surveyor',
            'level' => 30,
            'permissions' => ['view_messages', 'view_members', 'send_messages', 'upload_attachments', 'manage_members'],
        ]);
        $this->facilitatorRole = Role::create([
            'name' => 'gbi_facilitator',
            'display_name' => 'GBI Facilitator',
            'level' => 40,
            'permissions' => ['view_messages', 'view_members', 'send_messages', 'upload_attachments', 'manage_members', 'manage_roles', 'admin'],
        ]);
    }

    public function test_authenticated_users_cannot_edit_account_roles_through_public_profile_routes(): void
    {
        $actor = $this->user();
        $target = $this->user();
        Sanctum::actingAs($actor);

        $this->patchJson("/api/users/{$target->id}/role", [
            'role_id' => $this->developerRole->id,
        ])->assertNotFound();

        $this->patchJson('/api/user/role', [
            'role_id' => $this->developerRole->id,
        ])->assertNotFound();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'role_id' => $this->memberRole->id,
        ]);
    }

    public function test_project_owner_can_change_a_members_project_role_and_the_change_is_audited(): void
    {
        $owner = $this->user();
        $member = $this->user();
        $project = $this->project($owner);
        $membership = $this->membership($project, $member, $owner);
        Sanctum::actingAs($owner);

        $this->patchJson("/api/projects/{$project->id}/members/{$member->id}/role", [
            'role_id' => $this->developerRole->id,
        ])->assertOk()
            ->assertJsonPath('member.membership.roleId', $this->developerRole->id);

        $this->assertDatabaseHas('project_members', [
            'id' => $membership->id,
            'project_id' => $project->id,
            'user_id' => $member->id,
            'role_id' => $this->developerRole->id,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $owner->id,
            'action' => 'project_member_role_changed',
            'target_type' => ProjectMember::class,
            'target_id' => $membership->id,
        ]);
    }

    public function test_project_member_can_open_a_shared_project(): void
    {
        $owner = $this->user();
        $member = $this->user();
        $project = $this->project($owner);
        $this->membership($project, $member, $owner);
        Sanctum::actingAs($member);

        $this->getJson("/api/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('projectData.id', $project->id);
    }

    public function test_project_owner_can_add_and_remove_a_member_with_the_default_role(): void
    {
        $owner = $this->user();
        $member = $this->user();
        $project = $this->project($owner);
        Sanctum::actingAs($owner);

        $this->postJson("/api/projects/{$project->id}/members", [
            'user_ids' => [$member->id],
        ])->assertCreated();

        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $member->id,
            'role_id' => $this->memberRole->id,
        ]);

        $this->deleteJson("/api/projects/{$project->id}/members/{$member->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('project_members', [
            'project_id' => $project->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_project_member_cannot_add_or_remove_other_members(): void
    {
        $owner = $this->user();
        $actor = $this->user();
        $target = $this->user();
        $project = $this->project($owner);
        $this->membership($project, $actor, $owner);
        $targetMembership = $this->membership($project, $target, $owner);
        Sanctum::actingAs($actor);

        $this->postJson("/api/projects/{$project->id}/members", [
            'user_ids' => [$this->user()->id],
        ])->assertForbidden();

        $this->deleteJson("/api/projects/{$project->id}/members/{$target->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('project_members', ['id' => $targetMembership->id]);
    }

    public function test_project_role_permissions_are_enforced_by_project_endpoints(): void
    {
        $owner = $this->user();
        $member = $this->user();
        $developer = $this->user();
        $quantitySurveyor = $this->user();
        $facilitator = $this->user();
        $target = $this->user();
        $project = $this->project($owner);

        $this->membership($project, $member, $owner, $this->memberRole);
        $this->membership($project, $developer, $owner, $this->developerRole);
        $this->membership($project, $quantitySurveyor, $owner, $this->quantitySurveyorRole);
        $this->membership($project, $facilitator, $owner, $this->facilitatorRole);

        Sanctum::actingAs($member);
        $this->getJson("/api/projects/{$project->id}/messages")->assertOk();
        $this->getJson("/api/projects/{$project->id}/members")->assertOk();
        $this->postJson("/api/projects/{$project->id}/messages", ['message' => 'Blocked'])->assertForbidden();
        $this->postJson("/api/projects/{$project->id}/attachments")->assertForbidden();

        Sanctum::actingAs($developer);
        $this->postJson("/api/projects/{$project->id}/messages", ['message' => 'Allowed'])->assertCreated();
        $this->postJson("/api/projects/{$project->id}/attachments")->assertUnprocessable();
        $this->postJson("/api/projects/{$project->id}/members", ['user_ids' => [$target->id]])->assertForbidden();

        Sanctum::actingAs($quantitySurveyor);
        $this->postJson("/api/projects/{$project->id}/members", ['user_ids' => [$target->id]])->assertCreated();
        $this->patchJson("/api/projects/{$project->id}/members/{$target->id}/role", [
            'role_id' => $this->developerRole->id,
        ])->assertForbidden();

        Sanctum::actingAs($facilitator);
        $this->patchJson("/api/projects/{$project->id}/members/{$target->id}/role", [
            'role_id' => $this->developerRole->id,
        ])->assertOk();
    }

    public function test_legacy_project_routes_enforce_the_same_role_permissions(): void
    {
        $owner = $this->user();
        $member = $this->user();
        $quantitySurveyor = $this->user();
        $target = $this->user();
        $project = $this->project($owner);

        $this->membership($project, $member, $owner, $this->memberRole);
        $this->membership($project, $quantitySurveyor, $owner, $this->quantitySurveyorRole);

        Sanctum::actingAs($member);
        $this->getJson("/projects/{$project->id}/members")->assertOk();
        $this->postJson("/projects/{$project->id}/members", [
            'user_ids' => [$target->id],
        ])->assertForbidden();

        Sanctum::actingAs($quantitySurveyor);
        $this->postJson("/projects/{$project->id}/members", [
            'user_ids' => [$target->id],
        ])->assertCreated();
        $this->patchJson("/projects/{$project->id}/members/{$target->id}/role", [
            'role_id' => $this->developerRole->id,
        ])->assertForbidden();
    }

    public function test_message_references_must_belong_to_the_current_project(): void
    {
        $owner = $this->user();
        $otherOwner = $this->user();
        $project = $this->project($owner);
        $otherProject = $this->project($otherOwner);
        $attachment = Attachment::create([
            'project_id' => $otherProject->id,
            'user_id' => $owner->id,
            'original_name' => 'private.pdf',
            'filename' => 'private.pdf',
            'path' => 'projects/'.$otherProject->id.'/private.pdf',
            'mime_type' => 'application/pdf',
            'kind' => 'pdf',
            'size' => 100,
        ]);
        $reply = ProjectMessage::create([
            'project_id' => $otherProject->id,
            'user_id' => $otherOwner->id,
            'body' => 'Other project message',
        ]);
        Sanctum::actingAs($owner);

        $this->postJson("/api/projects/{$project->id}/messages", [
            'message' => 'Invalid attachment',
            'attachment_id' => $attachment->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('attachment_id');

        $this->postJson("/api/projects/{$project->id}/messages", [
            'message' => 'Invalid reply',
            'reply_to_id' => $reply->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('reply_to_id');
    }

    public function test_message_can_reply_only_to_a_live_message_in_the_same_project(): void
    {
        $owner = $this->user();
        $project = $this->project($owner);
        $target = ProjectMessage::create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'body' => 'Reply target',
        ]);
        Sanctum::actingAs($owner);

        $this->postJson("/api/projects/{$project->id}/messages", [
            'message' => 'Valid reply',
            'reply_to_id' => $target->id,
        ])->assertCreated()->assertJsonPath('data.replyToId', (string) $target->id);

        $target->delete();
        $this->postJson("/api/projects/{$project->id}/messages", [
            'message' => 'Deleted reply target',
            'reply_to_id' => $target->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('reply_to_id');
    }

    public function test_reactions_toggle_and_return_the_frontend_shape(): void
    {
        $owner = $this->user();
        $developer = $this->user();
        $project = $this->project($owner);
        $this->membership($project, $developer, $owner, $this->developerRole);
        $message = ProjectMessage::create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'body' => 'React to this',
        ]);
        Sanctum::actingAs($developer);

        $response = $this->postJson("/api/messages/{$message->id}/reactions", [
            'emoji' => '👍',
        ])->assertOk();
        $this->assertSame([(string) $developer->id], $response->json('reactions.👍'));
        $this->assertDatabaseHas('message_reactions', [
            'project_id' => $project->id,
            'message_id' => $message->id,
            'user_id' => $developer->id,
            'emoji' => '👍',
        ]);

        $this->postJson("/api/messages/{$message->id}/reactions", [
            'emoji' => '👍',
        ])->assertOk()->assertJsonPath('reactions', []);
        $this->assertDatabaseMissing('message_reactions', [
            'message_id' => $message->id,
            'user_id' => $developer->id,
            'emoji' => '👍',
        ]);
    }

    public function test_reactions_require_send_permission_and_an_allowed_emoji(): void
    {
        $owner = $this->user();
        $member = $this->user();
        $developer = $this->user();
        $project = $this->project($owner);
        $this->membership($project, $member, $owner, $this->memberRole);
        $this->membership($project, $developer, $owner, $this->developerRole);
        $message = ProjectMessage::create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'body' => 'Protected reaction',
        ]);

        Sanctum::actingAs($member);
        $this->postJson("/api/messages/{$message->id}/reactions", [
            'emoji' => '👍',
        ])->assertForbidden();

        Sanctum::actingAs($developer);
        $this->postJson("/api/messages/{$message->id}/reactions", [
            'emoji' => 'not-allowed',
        ])->assertUnprocessable()->assertJsonValidationErrors('emoji');
    }

    public function test_message_pagination_rejects_invalid_bounds(): void
    {
        $owner = $this->user();
        $project = $this->project($owner);
        Sanctum::actingAs($owner);

        $this->getJson("/api/projects/{$project->id}/messages?limit=0")
            ->assertUnprocessable()->assertJsonValidationErrors('limit');
        $this->getJson("/api/projects/{$project->id}/messages?limit=101")
            ->assertUnprocessable()->assertJsonValidationErrors('limit');
        $this->getJson("/api/projects/{$project->id}/messages?before=invalid")
            ->assertUnprocessable()->assertJsonValidationErrors('before');
    }

    public function test_unread_counts_are_server_side_and_not_limited_to_recent_messages(): void
    {
        $owner = $this->user();
        $member = $this->user();
        $project = $this->project($owner);
        $membership = $this->membership($project, $member, $owner, $this->memberRole);

        foreach (range(1, 75) as $number) {
            ProjectMessage::create([
                'project_id' => $project->id,
                'user_id' => $owner->id,
                'body' => 'Unread '.$number,
            ]);
        }
        ProjectMessage::create([
            'project_id' => $project->id,
            'user_id' => $member->id,
            'body' => 'Own message',
        ]);
        ProjectMessage::create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'body' => 'System message',
            'is_system' => true,
        ]);
        Sanctum::actingAs($member);

        $this->getJson('/api/projects/unread-counts')
            ->assertOk()
            ->assertJsonPath('counts.'.$project->id, 75);

        $fiftiethMessageId = $project->messages()
            ->where('is_system', false)
            ->where('user_id', $owner->id)
            ->orderBy('id')
            ->skip(49)
            ->value('id');
        $this->postJson("/api/projects/{$project->id}/messages/read", [
            'message_id' => $fiftiethMessageId,
        ])->assertOk()->assertJsonPath('unreadCount', 25);

        $olderMessageId = $project->messages()->orderBy('id')->value('id');
        $this->postJson("/api/projects/{$project->id}/messages/read", [
            'message_id' => $olderMessageId,
        ])->assertOk()->assertJsonPath('unreadCount', 25);
        $this->assertDatabaseHas('project_members', [
            'id' => $membership->id,
            'last_read_message_id' => $fiftiethMessageId,
        ]);

        $this->postJson("/api/projects/{$project->id}/messages/read")
            ->assertOk()->assertJsonPath('unreadCount', 0);
        $this->getJson('/api/projects/unread-counts')
            ->assertOk()->assertJsonPath('counts.'.$project->id, 0);
    }

    public function test_read_acknowledgement_rejects_a_message_from_another_project(): void
    {
        $owner = $this->user();
        $otherOwner = $this->user();
        $project = $this->project($owner);
        $otherProject = $this->project($otherOwner);
        $message = ProjectMessage::create([
            'project_id' => $otherProject->id,
            'user_id' => $otherOwner->id,
            'body' => 'Other project',
        ]);
        Sanctum::actingAs($owner);

        $this->postJson("/api/projects/{$project->id}/messages/read", [
            'message_id' => $message->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('message_id');
    }

    public function test_incremental_changes_include_live_and_deleted_messages(): void
    {
        $owner = $this->user();
        $project = $this->project($owner);
        $live = ProjectMessage::create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'body' => 'Live change',
        ]);
        $deleted = ProjectMessage::create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'body' => 'Deleted change',
        ]);
        $deleted->delete();
        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/projects/{$project->id}/messages/changes?since=".urlencode(now()->subMinute()->toISOString()))
            ->assertOk()
            ->assertJsonPath('hasMore', false);

        $this->assertContains((string) $live->id, collect($response->json('messages'))->pluck('id')->all());
        $this->assertContains((string) $deleted->id, $response->json('deletedIds'));
        $this->assertNotEmpty($response->json('cursor'));
    }

    public function test_only_the_sender_can_edit_or_delete_a_message(): void
    {
        $owner = $this->user();
        $sender = $this->user();
        $project = $this->project($owner);
        $this->membership($project, $sender, $owner, $this->developerRole);
        $message = ProjectMessage::create([
            'project_id' => $project->id,
            'user_id' => $sender->id,
            'body' => 'Original message',
        ]);

        Sanctum::actingAs($owner);
        $this->patchJson("/api/projects/{$project->id}/messages/{$message->id}", [
            'message' => 'Owner edit',
        ])->assertForbidden();
        $this->deleteJson("/api/projects/{$project->id}/messages/{$message->id}")
            ->assertForbidden();

        Sanctum::actingAs($sender);
        $this->patchJson("/api/projects/{$project->id}/messages/{$message->id}", [
            'message' => 'Edited message',
        ])->assertOk()->assertJsonPath('data.message', 'Edited message');
        $this->deleteJson("/api/projects/{$project->id}/messages/{$message->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('project_messages', ['id' => $message->id]);
    }

    public function test_deleting_a_message_removes_its_unshared_chat_attachment(): void
    {
        Storage::fake('public');
        $owner = $this->user();
        $project = $this->project($owner);
        $path = 'projects/'.$project->id.'/chat.pdf';
        Storage::disk('public')->put($path, 'file');
        $attachment = Attachment::create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'original_name' => 'chat.pdf',
            'filename' => 'chat.pdf',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'kind' => 'pdf',
            'size' => 4,
        ]);
        $message = ProjectMessage::create([
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'body' => 'Attached',
            'attachment_id' => $attachment->id,
        ]);
        Sanctum::actingAs($owner);

        $this->deleteJson("/api/projects/{$project->id}/messages/{$message->id}")
            ->assertNoContent();

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    }

    public function test_message_sending_is_rate_limited_per_user_and_project(): void
    {
        $owner = $this->user();
        $project = $this->project($owner);
        Sanctum::actingAs($owner);

        foreach (range(1, 30) as $attempt) {
            $this->postJson("/api/projects/{$project->id}/messages", [
                'message' => 'Message '.$attempt,
            ])->assertCreated();
        }

        $this->postJson("/api/projects/{$project->id}/messages", [
            'message' => 'Too many',
        ])->assertTooManyRequests();

        RateLimiter::clear($owner->id.'|'.$project->id);
    }

    public function test_project_member_cannot_change_another_members_role(): void
    {
        $owner = $this->user();
        $actor = $this->user();
        $target = $this->user();
        $project = $this->project($owner);
        $this->membership($project, $actor, $owner);
        $targetMembership = $this->membership($project, $target, $owner);
        Sanctum::actingAs($actor);

        $this->patchJson("/api/projects/{$project->id}/members/{$target->id}/role", [
            'role_id' => $this->developerRole->id,
        ])->assertForbidden();

        $this->assertDatabaseHas('project_members', [
            'id' => $targetMembership->id,
            'role_id' => $this->memberRole->id,
        ]);
    }

    public function test_project_owner_cannot_change_the_owner_membership_role(): void
    {
        $owner = $this->user();
        $project = $this->project($owner);
        $membership = $this->membership($project, $owner, $owner);
        Sanctum::actingAs($owner);

        $this->patchJson("/api/projects/{$project->id}/members/{$owner->id}/role", [
            'role_id' => $this->developerRole->id,
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'The project owner role cannot be changed.');

        $this->assertDatabaseHas('project_members', [
            'id' => $membership->id,
            'role_id' => $this->memberRole->id,
        ]);
    }

    public function test_project_role_update_rejects_users_who_are_not_project_members(): void
    {
        $owner = $this->user();
        $outsider = $this->user();
        $project = $this->project($owner);
        Sanctum::actingAs($owner);

        $this->patchJson("/api/projects/{$project->id}/members/{$outsider->id}/role", [
            'role_id' => $this->developerRole->id,
        ])->assertNotFound()
            ->assertJsonPath('message', 'Project member not found.');
    }

    private function user(): User
    {
        static $counter = 0;
        $counter++;

        return User::create([
            'first_name' => 'Role',
            'last_name' => 'User '.$counter,
            'email' => "role-edit-{$counter}@example.test",
            'password' => 'password',
            'email_verified_at' => now(),
            'role_id' => $this->memberRole->id,
            'system_role' => 'user',
        ]);
    }

    private function membership(Project $project, User $user, User $addedBy, ?Role $role = null): ProjectMember
    {
        return ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'added_by' => $addedBy->id,
            'role_id' => ($role ?? $this->memberRole)->id,
        ]);
    }

    private function project(User $owner): Project
    {
        static $counter = 0;
        $counter++;

        $buildingTypeId = DB::table('building_types')->insertGetId([
            'name' => 'Role Building '.$counter,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $structureId = DB::table('structures')->insertGetId([
            'name' => 'Role Structure '.$counter,
            'code' => 'RS'.$counter,
            'building_type_id' => $buildingTypeId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $categoryId = DB::table('categories')->insertGetId([
            'category' => 'Role Category '.$counter,
            'building_type_id' => $buildingTypeId,
        ]);
        $projectId = DB::table('projects')->insertGetId([
            'building_type_id' => $buildingTypeId,
            'category_id' => $categoryId,
            'category' => 'Office',
            'size' => 1000,
            'year' => 2026,
            'location' => 'Kuala Lumpur',
            'structure_id' => $structureId,
            'created_at' => now(),
            'updated_at' => now(),
            'rating' => 70,
            'cost_preview_way' => 'Brief',
            'target_certification' => 'Silver',
            'user_id' => $owner->id,
            'name' => 'Role Project '.$counter,
            'assessment_status' => 'submitted',
        ]);

        return Project::findOrFail($projectId);
    }
}
