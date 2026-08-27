<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AssessmentItemReview;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SystemRoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Role $projectRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRole = Role::create([
            'name' => 'member',
            'display_name' => 'Member',
            'level' => 10,
            'permissions' => [],
        ]);
    }

    public function test_normal_user_cannot_access_admin_dashboard(): void
    {
        Sanctum::actingAs($this->user('user'));

        $this->getJson('/api/administration/admin/dashboard')->assertForbidden();
    }

    public function test_admin_can_access_dashboard_but_not_superadmin_logs(): void
    {
        Sanctum::actingAs($this->user('admin'));

        $this->getJson('/api/administration/admin/dashboard')->assertOk();
        $this->getJson('/api/administration/super-admin/activity-logs')->assertForbidden();
    }

    public function test_superadmin_can_promote_user_to_admin_and_action_is_logged(): void
    {
        $superAdmin = $this->user('super_admin');
        $target = $this->user('user');
        Sanctum::actingAs($superAdmin);

        $this->patchJson("/api/administration/super-admin/users/{$target->id}/role", [
            'system_role' => 'admin',
        ])->assertOk();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'system_role' => 'admin']);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $superAdmin->id, 'action' => 'system_role_changed']);
    }

    public function test_superadmin_can_update_name_and_email_for_each_managed_account_role(): void
    {
        $superAdmin = $this->user('super_admin');
        Sanctum::actingAs($superAdmin);

        foreach (['user', 'facilitator_admin', 'admin'] as $index => $role) {
            $target = $this->user($role);
            $email = "updated{$index}@example.test";

            $this->patchJson("/api/administration/super-admin/users/{$target->id}", [
                'first_name' => 'Updated',
                'last_name' => ucfirst(str_replace('_', ' ', $role)),
                'email' => strtoupper($email),
            ])->assertOk()
                ->assertJsonPath('user.first_name', 'Updated')
                ->assertJsonPath('user.email', $email)
                ->assertJsonPath('user.email_verified_at', null);

            $this->assertDatabaseHas('users', [
                'id' => $target->id,
                'first_name' => 'Updated',
                'email' => $email,
                'system_role' => $role,
                'email_verified_at' => null,
            ]);
        }

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $superAdmin->id,
            'action' => 'account_details_updated',
        ]);
    }

    public function test_admin_cannot_update_account_details_and_superadmin_accounts_stay_protected(): void
    {
        $admin = $this->user('admin');
        $target = $this->user('user');
        Sanctum::actingAs($admin);

        $this->patchJson("/api/administration/super-admin/users/{$target->id}", [
            'first_name' => 'Unauthorized',
            'last_name' => 'Change',
            'email' => 'unauthorized@example.test',
        ])->assertForbidden();

        $superAdmin = $this->user('super_admin');
        Sanctum::actingAs($superAdmin);

        $this->patchJson("/api/administration/super-admin/users/{$superAdmin->id}", [
            'first_name' => 'Changed',
            'last_name' => 'SuperAdmin',
            'email' => 'changed-super@example.test',
        ])->assertForbidden();
    }

    public function test_user_management_lists_accounts_in_role_hierarchy_order(): void
    {
        $regularUser = $this->user('user');
        $facilitator = $this->user('facilitator_admin');
        $admin = $this->user('admin');
        $superAdmin = $this->user('super_admin');
        Sanctum::actingAs($superAdmin);

        $roles = collect($this->getJson('/api/administration/super-admin/users')
            ->assertOk()
            ->json('data'))
            ->pluck('system_role')
            ->all();

        $this->assertSame(['super_admin', 'admin', 'facilitator_admin', 'user'], $roles);
        $this->assertDatabaseHas('users', ['id' => $regularUser->id]);
        $this->assertDatabaseHas('users', ['id' => $facilitator->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_superadmin_inherits_admin_module_access(): void
    {
        $superAdmin = $this->user('super_admin');
        $target = $this->user('user');
        Sanctum::actingAs($superAdmin);

        $this->getJson('/api/administration/admin/dashboard')->assertOk();
        $this->patchJson("/api/administration/admin/users/{$target->id}/role", [
            'system_role' => 'facilitator_admin',
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'system_role' => 'facilitator_admin',
        ]);
    }

    public function test_superadmin_can_demote_admin_to_user(): void
    {
        $superAdmin = $this->user('super_admin');
        $admin = $this->user('admin');
        Sanctum::actingAs($superAdmin);

        $this->patchJson("/api/administration/super-admin/users/{$admin->id}/role", [
            'system_role' => 'user',
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'system_role' => 'user',
        ]);
    }

    public function test_superadmin_can_move_accounts_between_all_managed_roles(): void
    {
        $superAdmin = $this->user('super_admin');
        $facilitator = $this->user('facilitator_admin');
        $admin = $this->user('admin');
        Sanctum::actingAs($superAdmin);

        $this->patchJson("/api/administration/super-admin/users/{$facilitator->id}/role", [
            'system_role' => 'admin',
        ])->assertOk();

        $this->patchJson("/api/administration/super-admin/users/{$admin->id}/role", [
            'system_role' => 'facilitator_admin',
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $facilitator->id,
            'system_role' => 'admin',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'system_role' => 'facilitator_admin',
        ]);
    }

    public function test_demoting_facilitator_revokes_their_active_appointments(): void
    {
        $admin = $this->user('admin');
        $facilitator = $this->user('facilitator_admin');
        $project = $this->project($this->user('user'));

        $assignmentId = DB::table('facilitator_assignments')->insertGetId([
            'project_id' => $project->id,
            'user_id' => $facilitator->id,
            'appointed_by' => $admin->id,
            'status' => 'active',
            'appointed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/administration/admin/users/{$facilitator->id}/role", [
            'system_role' => 'user',
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $facilitator->id,
            'system_role' => 'user',
        ]);
        $this->assertDatabaseHas('facilitator_assignments', [
            'id' => $assignmentId,
            'status' => 'revoked',
        ]);
        $this->assertNotNull(DB::table('facilitator_assignments')->where('id', $assignmentId)->value('revoked_at'));

        $log = ActivityLog::where('user_id', $admin->id)
            ->where('action', 'system_role_changed')
            ->latest('id')
            ->firstOrFail();
        $this->assertSame(1, $log->metadata['revoked_facilitator_assignments']);
    }

    public function test_admin_cannot_change_a_superadmin_role(): void
    {
        $admin = $this->user('admin');
        $superAdmin = $this->user('super_admin');
        Sanctum::actingAs($admin);

        $this->patchJson("/api/administration/admin/users/{$superAdmin->id}/role", [
            'system_role' => 'facilitator_admin',
        ])->assertForbidden();
    }

    public function test_superadmin_can_delete_a_user_and_action_is_logged(): void
    {
        $superAdmin = $this->user('super_admin');
        $target = $this->user('user');
        Sanctum::actingAs($superAdmin);

        $this->deleteJson("/api/administration/super-admin/users/{$target->id}")
            ->assertOk()
            ->assertJson(['message' => 'Account deleted.']);

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $superAdmin->id,
            'action' => 'account_deleted',
            'target_id' => $target->id,
            'target_label' => $target->email,
        ]);
    }

    public function test_administrator_cannot_delete_their_own_account(): void
    {
        $admin = $this->user('admin');
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/administration/admin/users/{$admin->id}")
            ->assertUnprocessable();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_cannot_delete_an_admin_account(): void
    {
        $admin = $this->user('admin');
        $target = $this->user('admin');
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/administration/admin/users/{$target->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_facilitator_can_only_use_facilitator_routes(): void
    {
        Sanctum::actingAs($this->user('facilitator_admin'));

        $this->getJson('/api/administration/facilitator/assessments')->assertOk();
        $this->getJson('/api/administration/admin/assessments')->assertForbidden();
    }

    public function test_superadmin_can_open_an_admin_assessment(): void
    {
        $owner = $this->user('user');
        $project = $this->project($owner);
        Sanctum::actingAs($this->user('super_admin'));

        $this->getJson("/api/administration/admin/assessments/{$project->id}")
            ->assertOk()
            ->assertJsonPath('assessment.id', $project->id);
    }

    public function test_admin_can_view_change_and_revoke_a_project_facilitator(): void
    {
        $owner = $this->user('user');
        $admin = $this->user('admin');
        $originalFacilitator = $this->user('facilitator_admin');
        $replacementFacilitator = $this->user('facilitator_admin');
        $project = $this->project($owner);
        $assignmentId = DB::table('facilitator_assignments')->insertGetId([
            'project_id' => $project->id,
            'user_id' => $originalFacilitator->id,
            'appointed_by' => $admin->id,
            'status' => 'active',
            'appointed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Sanctum::actingAs($admin);

        $this->getJson("/api/administration/admin/assessments/{$project->id}")
            ->assertOk()
            ->assertJsonPath('assessment.facilitator_assignments.0.id', $assignmentId)
            ->assertJsonPath('assessment.facilitator_assignments.0.facilitator.id', $originalFacilitator->id);

        $replacement = $this->patchJson("/api/administration/admin/assignments/{$assignmentId}", [
            'user_id' => $replacementFacilitator->id,
        ])->assertOk()
            ->assertJsonPath('facilitator.id', $replacementFacilitator->id);

        $this->assertDatabaseHas('facilitator_assignments', [
            'id' => $assignmentId,
            'status' => 'revoked',
        ]);
        $this->assertDatabaseHas('facilitator_assignments', [
            'project_id' => $project->id,
            'user_id' => $replacementFacilitator->id,
            'status' => 'active',
        ]);

        Sanctum::actingAs($originalFacilitator);
        $this->getJson("/api/administration/facilitator/assessments/{$project->id}")->assertForbidden();

        Sanctum::actingAs($admin);
        $this->deleteJson('/api/administration/admin/assignments/' . $replacement->json('id'))
            ->assertOk();

        Sanctum::actingAs($replacementFacilitator);
        $this->getJson("/api/administration/facilitator/assessments/{$project->id}")->assertForbidden();
    }

    public function test_assigning_a_facilitator_does_not_change_the_assessment_status(): void
    {
        $owner = $this->user('user');
        $admin = $this->user('admin');
        $facilitator = $this->user('facilitator_admin');
        $project = $this->project($owner);
        Sanctum::actingAs($admin);

        $this->postJson("/api/administration/admin/assessments/{$project->id}/assign", [
            'user_id' => $facilitator->id,
        ])->assertOk()
            ->assertJsonPath('facilitator.id', $facilitator->id);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'assessment_status' => 'submitted',
        ]);
        $this->assertDatabaseHas('facilitator_assignments', [
            'project_id' => $project->id,
            'user_id' => $facilitator->id,
            'status' => 'active',
        ]);
    }

    public function test_dashboard_groups_submission_states_into_awaiting_verification(): void
    {
        $submitted = $this->project($this->user('user'));
        $legacyPending = $this->project($this->user('user'));
        $changesRequested = $this->project($this->user('user'));
        $verified = $this->project($this->user('user'));
        $certified = $this->project($this->user('user'));
        $legacyPending->update(['assessment_status' => 'pending_verification']);
        $changesRequested->update(['assessment_status' => 'requires_changes']);
        $verified->update(['assessment_status' => 'verified']);
        $certified->update(['assessment_status' => 'certified']);
        Sanctum::actingAs($this->user('admin'));

        $this->getJson('/api/administration/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('status_distribution.awaiting_verification', 2)
            ->assertJsonPath('status_distribution.changes_requested', 1)
            ->assertJsonPath('status_distribution.verified', 1)
            ->assertJsonPath('status_distribution.certified', 1)
            ->assertJsonPath('metrics.pending', 2)
            ->assertJsonPath('metrics.verified', 1)
            ->assertJsonMissingPath('status_distribution.submitted')
            ->assertJsonMissingPath('status_distribution.pending_verification')
            ->assertJsonMissingPath('status_distribution.requires_changes');

        $awaitingIds = collect($this->getJson('/api/administration/admin/assessments?status=awaiting_verification')
            ->assertOk()
            ->json('data'))
            ->pluck('id');
        $this->assertTrue($awaitingIds->contains($submitted->id));
        $this->assertTrue($awaitingIds->contains($legacyPending->id));
        $this->assertFalse($awaitingIds->contains($verified->id));
    }

    public function test_admin_can_load_facilitators_with_their_active_project_assignments(): void
    {
        $owner = $this->user('user');
        $admin = $this->user('admin');
        $facilitator = $this->user('facilitator_admin');
        $project = $this->project($owner);
        DB::table('facilitator_assignments')->insert([
            'project_id' => $project->id,
            'user_id' => $facilitator->id,
            'appointed_by' => $admin->id,
            'status' => 'active',
            'appointed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/administration/admin/users?role=facilitator_admin&include_assignments=1')
            ->assertOk();
        $facilitatorRow = collect($response->json('data'))->firstWhere('id', $facilitator->id);

        $this->assertNotNull($facilitatorRow);
        $this->assertCount(1, $facilitatorRow['facilitator_assignments']);
        $this->assertSame($project->id, $facilitatorRow['facilitator_assignments'][0]['project']['id']);
        $this->assertSame($project->name, $facilitatorRow['facilitator_assignments'][0]['project']['name']);
        $this->assertSame($owner->email, $facilitatorRow['facilitator_assignments'][0]['project']['owner']['email']);
    }

    public function test_project_owner_cannot_manage_facilitator_appointments(): void
    {
        $owner = $this->user('user');
        $facilitator = $this->user('facilitator_admin');
        $project = $this->project($owner);
        Sanctum::actingAs($owner);

        $this->getJson("/api/projects/{$project->id}/facilitators")
            ->assertNotFound();

        $this->postJson("/api/projects/{$project->id}/facilitators", [
            'user_id' => $facilitator->id,
        ])->assertNotFound();

        $this->assertDatabaseMissing('facilitator_assignments', [
            'project_id' => $project->id,
            'user_id' => $facilitator->id,
        ]);
    }

    public function test_non_owner_cannot_appoint_a_facilitator_to_a_project(): void
    {
        $owner = $this->user('user');
        $otherClient = $this->user('user');
        $facilitator = $this->user('facilitator_admin');
        $project = $this->project($owner);
        Sanctum::actingAs($otherClient);

        $this->postJson("/api/projects/{$project->id}/facilitators", [
            'user_id' => $facilitator->id,
        ])->assertNotFound();

        $this->assertDatabaseMissing('facilitator_assignments', [
            'project_id' => $project->id,
            'user_id' => $facilitator->id,
        ]);
    }

    public function test_project_review_feedback_is_visible_to_owner_but_not_unrelated_user(): void
    {
        $owner = $this->user('user');
        $otherUser = $this->user('user');
        $project = $this->project($owner);
        $project->update([
            'assessment_status' => 'verified',
            'review_remarks' => 'The submitted evidence has been approved.',
            'reviewed_at' => now(),
        ]);

        Sanctum::actingAs($owner);
        $response = $this->getJson("/api/projects/{$project->id}");
        $response
            ->assertOk()
            ->assertJsonPath('projectData.assessment_status', 'verified')
            ->assertJsonPath('projectData.review_remarks', 'The submitted evidence has been approved.');

        Sanctum::actingAs($otherUser);
        $this->getJson("/api/projects/{$project->id}")->assertForbidden();
    }

    public function test_predicted_scores_are_read_only_for_admin_and_facilitator(): void
    {
        $owner = $this->user('user');
        $admin = $this->user('admin');
        $facilitator = $this->user('facilitator_admin');
        $project = $this->project($owner);
        $itemId = $this->assessmentItem($project, 4);
        $this->selectAssessmentItem($project, $owner, $itemId);

        DB::table('facilitator_assignments')->insert([
            'project_id' => $project->id,
            'user_id' => $facilitator->id,
            'appointed_by' => $admin->id,
            'status' => 'active',
            'appointed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = ['scores' => [['item_id' => $itemId, 'reviewed_score' => 1]]];

        Sanctum::actingAs($admin);
        $this->patchJson("/api/administration/admin/assessments/{$project->id}/scores", $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Predicted assessment values are read-only. Review the Actual assessment instead.');

        Sanctum::actingAs($facilitator);
        $this->patchJson("/api/administration/facilitator/assessments/{$project->id}/scores", $payload)
            ->assertUnprocessable();

        $this->assertDatabaseCount('user_answers', 1);
        $this->assertDatabaseMissing('assessment_item_reviews', ['project_id' => $project->id]);
    }

    public function test_unassigned_facilitator_cannot_view_or_review_actual_assessment(): void
    {
        $owner = $this->user('user');
        $facilitator = $this->user('facilitator_admin');
        $project = $this->project($owner);
        $itemId = $this->assessmentItem($project, 4);
        $this->selectActualAssessmentItem($project, $owner, $itemId);
        Sanctum::actingAs($facilitator);

        $this->getJson("/api/administration/facilitator/assessments/{$project->id}")
            ->assertForbidden();
        $this->patchJson("/api/administration/facilitator/assessments/{$project->id}/actual-selections", [
            'items' => [['item_id' => $itemId, 'accepted_choice_keys' => ["item:{$itemId}"]]],
        ])->assertForbidden();
        $this->postJson("/api/administration/facilitator/assessments/{$project->id}/review", [
            'action' => 'verify',
        ])->assertForbidden();
    }

    public function test_regular_user_cannot_access_administrative_actual_review_endpoints(): void
    {
        $owner = $this->user('user');
        $project = $this->project($owner);
        $itemId = $this->assessmentItem($project, 4);
        $this->selectActualAssessmentItem($project, $owner, $itemId);
        Sanctum::actingAs($owner);

        $this->getJson("/api/administration/admin/assessments/{$project->id}")
            ->assertForbidden();
        $this->patchJson("/api/administration/admin/assessments/{$project->id}/actual-selections", [
            'items' => [['item_id' => $itemId, 'accepted_choice_keys' => ["item:{$itemId}"]]],
        ])->assertForbidden();
        $this->postJson("/api/administration/admin/assessments/{$project->id}/review", [
            'action' => 'verify',
        ])->assertForbidden();

        $this->assertDatabaseMissing('assessment_item_reviews', ['project_id' => $project->id]);
    }

    public function test_regular_user_cannot_change_actual_answers_through_project_endpoint(): void
    {
        $owner = $this->user('user');
        $project = $this->project($owner);
        $itemId = $this->assessmentItem($project, 4);
        Sanctum::actingAs($owner);

        $this->postJson("/api/projects/{$project->id}/save-actual-changes", [
            'actualChanges' => [[
                'type' => 'item',
                'action' => 'add',
                'itemId' => $itemId,
            ]],
        ])->assertForbidden()
            ->assertJsonPath('message', 'Actual assessment selections are locked for users and can only be decided by an administrator.');

        $this->assertDatabaseMissing('actual_user_answers', [
            'project_id' => $project->id,
            'item_id' => $itemId,
        ]);
    }

    public function test_item_evidence_is_visible_beside_the_admin_actual_review(): void
    {
        Storage::fake('public');
        $owner = $this->user('user');
        $admin = $this->user('admin');
        $project = $this->project($owner);
        $itemId = $this->assessmentItem($project, 4);
        $project->update(['assessment_status' => 'verified']);

        Sanctum::actingAs($owner);
        $this->post("/api/projects/{$project->id}/attachments", [
            'file' => UploadedFile::fake()->create('energy-model.pdf', 120, 'application/pdf'),
            'assessment_item_id' => $itemId,
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.assessmentItemId', (string) $itemId);

        Sanctum::actingAs($admin);
        $this->getJson("/api/administration/admin/assessments/{$project->id}")
            ->assertOk()
            ->assertJsonPath('score_review.items.0.evidence.0.original_name', 'energy-model.pdf');
    }

    public function test_evidence_upload_is_limited_to_five_files_per_item(): void
    {
        Storage::fake('public');
        $owner = $this->user('user');
        $project = $this->project($owner);
        $itemId = $this->assessmentItem($project, 4);
        $project->update(['assessment_status' => 'verified']);
        Sanctum::actingAs($owner);

        foreach (range(1, 5) as $index) {
            $this->post("/api/projects/{$project->id}/attachments", [
                'file' => UploadedFile::fake()->create("evidence-{$index}.pdf", 20, 'application/pdf'),
                'assessment_item_id' => $itemId,
            ], ['Accept' => 'application/json'])->assertCreated();
        }

        $this->post("/api/projects/{$project->id}/attachments", [
            'file' => UploadedFile::fake()->create('evidence-6.pdf', 20, 'application/pdf'),
            'assessment_item_id' => $itemId,
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'A maximum of five evidence files can be submitted for each assessment item.');

        $this->assertDatabaseCount('attachments', 5);
    }

    public function test_owner_and_admin_can_remove_item_evidence_but_unrelated_user_cannot(): void
    {
        Storage::fake('public');
        $owner = $this->user('user');
        $unrelated = $this->user('user');
        $admin = $this->user('admin');
        $project = $this->project($owner);
        $itemId = $this->assessmentItem($project, 4);
        $project->update(['assessment_status' => 'verified']);

        Sanctum::actingAs($owner);
        $firstUpload = $this->post("/api/projects/{$project->id}/attachments", [
            'file' => UploadedFile::fake()->create('wrong-file.pdf', 20, 'application/pdf'),
            'assessment_item_id' => $itemId,
        ], ['Accept' => 'application/json'])->assertCreated();
        $firstId = (int) $firstUpload->json('data.id');
        $firstPath = DB::table('attachments')->where('id', $firstId)->value('path');

        Sanctum::actingAs($unrelated);
        $this->deleteJson("/api/projects/{$project->id}/attachments/{$firstId}")->assertForbidden();

        Sanctum::actingAs($owner);
        $this->deleteJson("/api/projects/{$project->id}/attachments/{$firstId}")
            ->assertOk()
            ->assertJsonPath('message', 'Evidence removed.');
        $this->assertDatabaseMissing('attachments', ['id' => $firstId]);
        Storage::disk('public')->assertMissing($firstPath);

        $secondUpload = $this->post("/api/projects/{$project->id}/attachments", [
            'file' => UploadedFile::fake()->create('insufficient-evidence.pdf', 20, 'application/pdf'),
            'assessment_item_id' => $itemId,
        ], ['Accept' => 'application/json'])->assertCreated();
        $secondId = (int) $secondUpload->json('data.id');

        Sanctum::actingAs($admin);
        $this->deleteJson("/api/projects/{$project->id}/attachments/{$secondId}")->assertOk();
        $this->assertDatabaseMissing('attachments', ['id' => $secondId]);
    }

    public function test_admin_item_remark_is_visible_to_the_project_owner(): void
    {
        $owner = $this->user('user');
        $admin = $this->user('admin');
        $project = $this->project($owner);
        $itemId = $this->assessmentItem($project, 4);
        $project->update(['assessment_status' => 'verified']);
        $remark = 'Please provide a signed energy model and the supporting calculation pages.';

        Sanctum::actingAs($admin);
        $this->patchJson("/api/administration/admin/assessments/{$project->id}/actual-selections", [
            'items' => [[
                'item_id' => $itemId,
                'accepted_choice_keys' => [],
                'remarks' => $remark,
            ]],
        ])->assertOk()
            ->assertJsonPath('score_review.items.0.remarks', $remark)
            ->assertJsonPath('score_review.items.0.actual_score', 0);

        $this->assertDatabaseHas('assessment_item_reviews', [
            'project_id' => $project->id,
            'item_id' => $itemId,
            'remarks' => $remark,
            'reviewed_by' => $admin->id,
        ]);

        Sanctum::actingAs($owner);
        $this->getJson("/api/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('projectData.assessment_item_feedback.0.item_id', (string) $itemId)
            ->assertJsonPath('projectData.assessment_item_feedback.0.remarks', $remark)
            ->assertJsonPath('projectData.assessment_item_feedback.0.reviewed_by.first_name', $admin->first_name);
    }

    public function test_actual_review_rejects_a_choice_from_another_item(): void
    {
        $owner = $this->user('user');
        $admin = $this->user('admin');
        $project = $this->project($owner);
        $itemId = $this->assessmentItem($project, 4);
        $this->selectActualAssessmentItem($project, $owner, $itemId);
        $otherItemId = $this->assessmentItem($project, 2);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/administration/admin/assessments/{$project->id}/actual-selections", [
            'items' => [['item_id' => $itemId, 'accepted_choice_keys' => ["item:{$otherItemId}"]]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.accepted_choice_keys.0');

        $this->assertDatabaseMissing('assessment_item_reviews', ['project_id' => $project->id]);
    }

    public function test_backend_calculates_selected_actual_total_and_certification_level(): void
    {
        Storage::fake('local');
        $owner = $this->user('user');
        $admin = $this->user('admin');
        $project = $this->project($owner);
        $firstItemId = $this->assessmentItem($project, 5);
        $secondItemId = $this->assessmentItem($project, 3);
        $this->selectAssessmentItem($project, $owner, $firstItemId);
        $this->selectAssessmentItem($project, $owner, $secondItemId);
        $this->selectActualAssessmentItem($project, $owner, $firstItemId);
        $this->selectActualAssessmentItem($project, $owner, $secondItemId);
        $certification = [
            'name' => 'Gold',
            'min_score' => 6,
            'max_score' => 8,
            'multiplier' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $certification[Schema::hasColumn('certifications', 'building_type_id') ? 'building_type_id' : 'type_id'] = $project->building_type_id;
        DB::table('certifications')->insert($certification);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/administration/admin/assessments/{$project->id}/actual-selections", [
            'total' => 999,
            'items' => [
                ['item_id' => $firstItemId, 'accepted_choice_keys' => ["item:{$firstItemId}"]],
                ['item_id' => $secondItemId, 'accepted_choice_keys' => ["item:{$secondItemId}"]],
            ],
        ])->assertOk()
            ->assertJsonPath('score_review.predicted_total', 8)
            ->assertJsonPath('score_review.actual_total', 8)
            ->assertJsonPath('score_review.calculated_certification_level', 'Gold')
            ->assertJsonPath('assessment.rating', 70);

        $comment = 'Actual evidence reviewed and approved for Gold certification.';
        $issued = $this->postJson("/api/administration/admin/assessments/{$project->id}/review", [
            'action' => 'certify',
            'remarks' => $comment,
        ])->assertOk()
            ->assertJsonPath('assessment.assessment_status', 'certified')
            ->assertJsonPath('assessment.review_remarks', $comment)
            ->assertJsonPath('review.approved_actual_total', 8)
            ->assertJsonPath('review.certification_level', 'Gold')
            ->assertJsonPath('score_review.certification_status', 'certified')
            ->assertJsonPath('certificate.certification_level', 'Gold')
            ->assertJsonPath('certificate.approved_actual_score', 8)
            ->assertJsonPath('certificate.status', 'issued');

        $certificateNumber = $issued->json('certificate.certificate_number');
        $verificationCode = $issued->json('certificate.verification_code');

        $this->assertDatabaseHas('assessment_reviews', [
            'project_id' => $project->id,
            'action' => 'certify',
            'new_status' => 'certified',
            'approved_actual_total' => 8,
            'certification_level' => 'Gold',
            'remarks' => $comment,
        ]);
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'rating' => 70,
            'assessment_status' => 'certified',
            'review_remarks' => $comment,
        ]);
        $this->assertDatabaseHas('project_certificates', [
            'project_id' => $project->id,
            'certificate_number' => $certificateNumber,
            'certification_level' => 'Gold',
            'approved_actual_score' => 8,
            'maximum_score' => 8,
            'status' => 'issued',
        ]);
        Storage::disk('local')->assertExists("certificates/{$certificateNumber}.pdf");

        $unrelatedUser = $this->user('user');
        Sanctum::actingAs($unrelatedUser);
        $this->getJson("/api/projects/{$project->id}/certificate")->assertForbidden();
        $this->get("/api/projects/{$project->id}/certificate/download")->assertForbidden();

        Sanctum::actingAs($owner);
        $this->getJson("/api/projects/{$project->id}/certificate")
            ->assertOk()
            ->assertJsonPath('certificate.certificate_number', $certificateNumber);
        $download = $this->get("/api/projects/{$project->id}/certificate/download")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $download->streamedContent());
        $this->getJson("/api/certificates/verify/{$verificationCode}")
            ->assertOk()
            ->assertJsonPath('certificate.project_name', $project->name)
            ->assertJsonPath('certificate.status', 'issued')
            ->assertJsonMissingPath('certificate.owner_name')
            ->assertJsonMissingPath('certificate.verification_code')
            ->assertJsonMissingPath('certificate.pdf_sha256');

        Sanctum::actingAs($admin);
        $this->postJson("/api/administration/admin/assessments/{$project->id}/review", [
            'action' => 'certify',
        ])->assertOk()
            ->assertJsonPath('certificate.certificate_number', $certificateNumber);
        $this->assertSame(1, DB::table('project_certificates')->where('project_id', $project->id)->count());

        $this->deleteJson("/api/administration/admin/assessments/{$project->id}/certificate", [
            'reason' => 'A corrected final review is required.',
        ])->assertOk()
            ->assertJsonPath('certificate.status', 'revoked')
            ->assertJsonPath('certificate.revocation_reason', 'A corrected final review is required.');
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'assessment_status' => 'verified',
        ]);
        $this->get("/api/projects/{$project->id}/certificate/download")->assertNotFound();
        $this->getJson("/api/certificates/verify/{$verificationCode}")
            ->assertOk()
            ->assertJsonPath('certificate.status', 'revoked');

        $reissued = $this->postJson("/api/administration/admin/assessments/{$project->id}/review", [
            'action' => 'certify',
            'remarks' => 'Corrected final review approved.',
        ])->assertOk()
            ->assertJsonPath('certificate.status', 'issued');
        $this->assertNotSame($certificateNumber, $reissued->json('certificate.certificate_number'));
        $certificateNumber = $reissued->json('certificate.certificate_number');
        $verificationCode = $reissued->json('certificate.verification_code');
        $this->assertSame(2, DB::table('project_certificates')->where('project_id', $project->id)->count());

        $this->patchJson("/api/administration/admin/assessments/{$project->id}/actual-selections", [
            'items' => [
                ['item_id' => $firstItemId, 'accepted_choice_keys' => ["item:{$firstItemId}"]],
                ['item_id' => $secondItemId, 'accepted_choice_keys' => []],
            ],
        ])->assertOk()
            ->assertJsonPath('assessment.assessment_status', 'verified')
            ->assertJsonPath('assessment.review_remarks', null)
            ->assertJsonPath('score_review.actual_total', 5)
            ->assertJsonPath('score_review.verification_status', 'verified')
            ->assertJsonPath('score_review.certification_status', 'not_certified');

        $this->assertDatabaseHas('assessment_reviews', [
            'project_id' => $project->id,
            'action' => 'certify',
            'approved_actual_total' => 8,
            'certification_level' => 'Gold',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'assessment_approval_invalidated',
        ]);
        $this->assertDatabaseHas('project_certificates', [
            'project_id' => $project->id,
            'certificate_number' => $certificateNumber,
            'status' => 'revoked',
            'revoked_by' => $admin->id,
        ]);
        $this->get("/api/projects/{$project->id}/certificate/download")->assertNotFound();
        $this->getJson("/api/certificates/verify/{$verificationCode}")
            ->assertOk()
            ->assertJsonPath('certificate.status', 'revoked');
    }

    public function test_certification_is_blocked_when_actual_does_not_match_a_level(): void
    {
        $owner = $this->user('user');
        $admin = $this->user('admin');
        $project = $this->project($owner);
        $itemId = $this->assessmentItem($project, 5);
        $this->selectActualAssessmentItem($project, $owner, $itemId);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/administration/admin/assessments/{$project->id}/actual-selections", [
            'items' => [['item_id' => $itemId, 'accepted_choice_keys' => ["item:{$itemId}"]]],
        ])->assertOk();

        $this->postJson("/api/administration/admin/assessments/{$project->id}/review", [
            'action' => 'certify',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'The Actual total does not qualify for a configured certification level.');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'assessment_status' => 'verified',
        ]);
        $this->assertDatabaseMissing('assessment_reviews', [
            'project_id' => $project->id,
            'action' => 'certify',
        ]);
    }

    public function test_reviewer_selects_the_exact_actual_answers_that_make_up_the_mark(): void
    {
        $owner = $this->user('user');
        $admin = $this->user('admin');
        $project = $this->project($owner);
        $itemId = $this->assessmentItem($project, 3);
        $choiceKeys = $this->selectActualSubitems($project, $owner, $itemId, [
            'Individual switching (≤100 m² zones)',
            'Auto-sensor lighting for day',
            'Motion sensors ≥25% NLA',
        ]);
        $omittedSubitemId = (int) Str::after($choiceKeys[1], 'subitem:');
        DB::table('actual_user_answers')->where('project_id', $project->id)->where('subitem_id', $omittedSubitemId)->delete();
        foreach ([$choiceKeys[0], $choiceKeys[2]] as $choiceKey) {
            $predictedAnswer = [
                'project_id' => $project->id,
                'item_id' => $itemId,
                'subitem_id' => (int) Str::after($choiceKey, 'subitem:'),
            ];
            if (Schema::hasColumn('user_answers', 'user_id')) $predictedAnswer['user_id'] = $owner->id;
            if (Schema::hasColumn('user_answers', 'created_at')) $predictedAnswer['created_at'] = now();
            if (Schema::hasColumn('user_answers', 'updated_at')) $predictedAnswer['updated_at'] = now();
            DB::table('user_answers')->insert($predictedAnswer);
        }
        Sanctum::actingAs($admin);

        $this->getJson("/api/administration/admin/assessments/{$project->id}")
            ->assertOk()
            ->assertJsonPath('score_review.predicted_total', 2)
            ->assertJsonPath('score_review.items.0.predicted_choices.0.selected', true)
            ->assertJsonPath('score_review.items.0.predicted_choices.1.selected', false)
            ->assertJsonPath('score_review.items.0.predicted_choices.2.selected', true)
            ->assertJsonPath('score_review.items.0.predicted_choices.2.score', 1)
            ->assertJsonPath('score_review.actual_total', 0)
            ->assertJsonPath('score_review.items.0.actual_choices.0.label', 'Individual switching (≤100 m² zones)')
            ->assertJsonPath('score_review.items.0.actual_choices.0.score', 1)
            ->assertJsonPath('score_review.items.0.actual_choices.1.submitted', false)
            ->assertJsonPath('score_review.items.0.actual_choices.1.accepted', false);

        $this->patchJson("/api/administration/admin/assessments/{$project->id}/actual-selections", [
            'items' => [[
                'item_id' => $itemId,
                'accepted_choice_keys' => $choiceKeys,
            ]],
        ])->assertOk()
            ->assertJsonPath('score_review.actual_total', 3)
            ->assertJsonPath('score_review.items.0.actual_choices.0.accepted', true)
            ->assertJsonPath('score_review.items.0.actual_choices.1.submitted', true)
            ->assertJsonPath('score_review.items.0.actual_choices.1.accepted', true)
            ->assertJsonPath('score_review.items.0.actual_choices.2.accepted', true);

        $review = AssessmentItemReview::where('project_id', $project->id)->where('item_id', $itemId)->firstOrFail();
        $this->assertSame($choiceKeys, $review->accepted_actual_choice_keys);
        $this->assertSame(3, $review->reviewed_score);
        $this->assertDatabaseCount('actual_user_answers', 3);

        $acceptedAfterReview = array_slice($choiceKeys, 0, 2);
        $reviewerRemovedSubitemId = (int) Str::after($choiceKeys[2], 'subitem:');
        $this->patchJson("/api/administration/admin/assessments/{$project->id}/actual-selections", [
            'items' => [[
                'item_id' => $itemId,
                'accepted_choice_keys' => $acceptedAfterReview,
            ]],
        ])->assertOk()
            ->assertJsonPath('score_review.actual_total', 2)
            ->assertJsonPath('score_review.items.0.actual_choices.2.submitted', false)
            ->assertJsonPath('score_review.items.0.actual_choices.2.accepted', false);

        $this->assertDatabaseCount('actual_user_answers', 2);
        $this->assertDatabaseMissing('actual_user_answers', [
            'project_id' => $project->id,
            'subitem_id' => $reviewerRemovedSubitemId,
        ]);

        $newClientAnswer = [
            'project_id' => $project->id,
            'item_id' => $itemId,
            'subitem_id' => $reviewerRemovedSubitemId,
        ];
        if (Schema::hasColumn('actual_user_answers', 'user_id')) $newClientAnswer['user_id'] = $owner->id;
        DB::table('actual_user_answers')->insert($newClientAnswer);

        Sanctum::actingAs($admin);
        $this->getJson("/api/administration/admin/assessments/{$project->id}")
            ->assertOk()
            ->assertJsonPath('score_review.items.0.review_status', 'pending')
            ->assertJsonPath('score_review.items.0.actual_choices.2.submitted', true)
            ->assertJsonPath('score_review.items.0.actual_choices.2.accepted', false)
            ->assertJsonPath('score_review.actual_total', 0);
    }

    public function test_assigned_facilitator_can_view_predicted_and_actual_and_review_actual(): void
    {
        $owner = $this->user('user');
        $facilitator = $this->user('facilitator_admin');
        $project = $this->project($owner);
        $itemId = $this->assessmentItem($project, 4);
        $this->selectAssessmentItem($project, $owner, $itemId);
        $answerId = $this->selectActualAssessmentItem($project, $owner, $itemId);

        DB::table('facilitator_assignments')->insert([
            'project_id' => $project->id,
            'user_id' => $facilitator->id,
            'appointed_by' => $owner->id,
            'status' => 'active',
            'appointed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($facilitator);

        $this->getJson("/api/administration/facilitator/assessments/{$project->id}")
            ->assertOk()
            ->assertJsonPath('score_review.items.0.predicted_score', 4)
            ->assertJsonPath('score_review.items.0.predicted_selections.0', 'Selected')
            ->assertJsonPath('score_review.items.0.actual_score', 0)
            ->assertJsonPath('score_review.items.0.actual_selections.0', 'Selected')
            ->assertJsonPath('score_review.predicted_total', 4)
            ->assertJsonPath('score_review.actual_total', 0)
            ->assertJsonPath('score_review.reviewed_items', 0)
            ->assertJsonPath('score_review.all_actual_reviewed', false);

        $this->patchJson("/api/administration/facilitator/assessments/{$project->id}/actual-selections", [
            'items' => [['item_id' => $itemId, 'accepted_choice_keys' => []]],
        ])->assertOk()
            ->assertJsonPath('assessment.rating', 70)
            ->assertJsonPath('score_review.items.0.actual_score', 0)
            ->assertJsonPath('score_review.predicted_total', 4)
            ->assertJsonPath('score_review.actual_total', 0)
            ->assertJsonPath('score_review.reviewed_items', 1);

        $this->assertDatabaseHas('assessment_item_reviews', [
            'project_id' => $project->id,
            'item_id' => $itemId,
            'original_score' => 4,
            'reviewed_score' => 0,
            'review_basis' => 'actual',
            'reviewed_by' => $facilitator->id,
        ]);
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'rating' => 70]);
        $this->assertDatabaseHas('user_answers', ['project_id' => $project->id, 'item_id' => $itemId]);
        $this->assertDatabaseMissing('actual_user_answers', ['id' => $answerId]);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $facilitator->id, 'action' => 'assessment_actual_review_saved']);
    }

    public function test_actual_review_progress_counts_only_explicitly_saved_item_decisions(): void
    {
        $owner = $this->user('user');
        $admin = $this->user('admin');
        $project = $this->project($owner);
        $project->update(['assessment_status' => 'verified']);
        $firstItemId = $this->assessmentItem($project, 1);
        $secondItemId = $this->assessmentItem($project, 2);
        $this->selectActualAssessmentItem($project, $owner, $firstItemId);
        $this->selectActualAssessmentItem($project, $owner, $secondItemId);
        Sanctum::actingAs($admin);

        $this->getJson("/api/administration/admin/assessments/{$project->id}")
            ->assertOk()
            ->assertJsonPath('score_review.reviewed_items', 0)
            ->assertJsonPath('score_review.total_items', 2)
            ->assertJsonPath('score_review.all_actual_reviewed', false)
            ->assertJsonPath('score_review.actual_total', 0)
            ->assertJsonPath('score_review.items.0.review_status', 'pending')
            ->assertJsonPath('score_review.items.1.review_status', 'pending');

        $this->patchJson("/api/administration/admin/assessments/{$project->id}/actual-selections", [
            'items' => [['item_id' => $firstItemId, 'accepted_choice_keys' => []]],
        ])->assertOk()
            ->assertJsonPath('score_review.reviewed_items', 1)
            ->assertJsonPath('score_review.total_items', 2)
            ->assertJsonPath('score_review.all_actual_reviewed', false)
            ->assertJsonPath('score_review.items.0.review_status', 'reviewed')
            ->assertJsonPath('score_review.items.0.actual_score', 0)
            ->assertJsonPath('score_review.items.1.review_status', 'pending');

        $this->assertDatabaseHas('assessment_item_reviews', [
            'project_id' => $project->id,
            'item_id' => $firstItemId,
            'reviewed_score' => 0,
            'review_basis' => 'actual',
        ]);
        $this->assertDatabaseMissing('assessment_item_reviews', [
            'project_id' => $project->id,
            'item_id' => $secondItemId,
        ]);

        $this->getJson("/api/administration/admin/assessments/{$project->id}")
            ->assertOk()
            ->assertJsonPath('score_review.reviewed_items', 1)
            ->assertJsonPath('score_review.items.0.review_status', 'reviewed')
            ->assertJsonPath('score_review.items.1.review_status', 'pending');
    }

    public function test_superadmin_can_adjust_an_actual_value_through_admin_assessment_route(): void
    {
        $owner = $this->user('user');
        $superAdmin = $this->user('super_admin');
        $project = $this->project($owner);
        $itemId = $this->assessmentItem($project, 4);
        $answerId = $this->selectActualAssessmentItem($project, $owner, $itemId);
        Sanctum::actingAs($superAdmin);

        $this->patchJson("/api/administration/admin/assessments/{$project->id}/actual-selections", [
            'items' => [['item_id' => $itemId, 'accepted_choice_keys' => []]],
        ])->assertOk()
            ->assertJsonPath('score_review.items.0.actual_score', 0)
            ->assertJsonPath('score_review.actual_total', 0);

        $this->assertDatabaseMissing('actual_user_answers', ['id' => $answerId]);
        $this->assertDatabaseHas('assessment_item_reviews', [
            'project_id' => $project->id,
            'item_id' => $itemId,
            'reviewed_by' => $superAdmin->id,
            'review_basis' => 'actual',
        ]);
    }

    public function test_admin_can_view_predicted_and_actual_and_review_actual_without_assignment(): void
    {
        $owner = $this->user('user');
        $admin = $this->user('admin');
        $project = $this->project($owner);
        $itemId = $this->assessmentItem($project, 5);
        $this->selectAssessmentItem($project, $owner, $itemId);
        $this->selectActualAssessmentItem($project, $owner, $itemId);

        Sanctum::actingAs($admin);

        $this->getJson("/api/administration/admin/assessments/{$project->id}")
            ->assertOk()
            ->assertJsonPath('score_review.predicted_total', 5)
            ->assertJsonPath('score_review.actual_total', 0)
            ->assertJsonPath('score_review.reviewed_items', 0)
            ->assertJsonPath('score_review.all_actual_reviewed', false);

        $this->patchJson("/api/administration/admin/assessments/{$project->id}/actual-selections", [
            'items' => [['item_id' => $itemId, 'accepted_choice_keys' => ["item:{$itemId}"]]],
        ])->assertOk()
            ->assertJsonPath('assessment.rating', 70)
            ->assertJsonPath('score_review.actual_total', 5)
            ->assertJsonPath('score_review.items.0.reviewed_by.id', $admin->id);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'assessment_status' => 'verified',
        ]);
    }

    public function test_prediction_is_verified_before_actual_certification_review(): void
    {
        $owner = $this->user('user');
        $admin = $this->user('admin');
        $project = $this->project($owner);
        $reviewedItemId = $this->assessmentItem($project, 5);
        $pendingItemId = $this->assessmentItem($project, 3);
        Sanctum::actingAs($admin);
        $this->assertDatabaseMissing('facilitator_assignments', ['project_id' => $project->id]);

        $this->postJson("/api/administration/admin/assessments/{$project->id}/review", [
            'action' => 'verify',
        ])->assertOk()
            ->assertJsonPath('assessment.assessment_status', 'verified')
            ->assertJsonPath('review.approved_actual_total', null);

        $this->patchJson("/api/administration/admin/assessments/{$project->id}/actual-selections", [
            'items' => [['item_id' => $reviewedItemId, 'accepted_choice_keys' => ["item:{$reviewedItemId}"]]],
        ])->assertOk();

        $this->postJson("/api/administration/admin/assessments/{$project->id}/review", [
            'action' => 'certify',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Review and save the Actual score for every assessment item before certifying.');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'assessment_status' => 'verified',
        ]);
    }

    public function test_requiring_prediction_changes_needs_remarks_and_retires_project_version(): void
    {
        Storage::fake('public');
        $owner = $this->user('user');
        $admin = $this->user('admin');
        $project = $this->project($owner);
        $itemId = $this->assessmentItem($project, 5);
        Sanctum::actingAs($admin);

        $this->postJson("/api/administration/admin/assessments/{$project->id}/review", [
            'action' => 'reject',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('remarks');

        $this->postJson("/api/administration/admin/assessments/{$project->id}/review", [
            'action' => 'reject',
            'remarks' => 'This is not good. Please change it.',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('remarks');

        $remark = 'Revise the energy strategy to identify the missing efficiency measures, then upload the corrected calculation sheet and supporting energy model for review.';
        $this->postJson("/api/administration/admin/assessments/{$project->id}/review", [
            'action' => 'reject',
            'remarks' => $remark,
        ])->assertOk()
            ->assertJsonPath('assessment.assessment_status', 'requires_changes')
            ->assertJsonPath('assessment.review_remarks', $remark);

        $this->postJson("/api/administration/admin/assessments/{$project->id}/review", [
            'action' => 'verify',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'The Predicted assessment decision is final for this project version.');

        $this->patchJson("/api/administration/admin/assessments/{$project->id}/actual-selections", [
            'items' => [['item_id' => $itemId, 'accepted_choice_keys' => []]],
        ])->assertUnprocessable();

        Sanctum::actingAs($owner);
        $this->post("/api/projects/{$project->id}/attachments", [
            'file' => UploadedFile::fake()->create('construction-evidence.pdf', 20, 'application/pdf'),
            'assessment_item_id' => $itemId,
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Evidence can be submitted only after the Predicted assessment is verified.');

        Sanctum::actingAs($admin);
        $this->postJson("/api/administration/admin/assessments/{$project->id}/review", [
            'action' => 'reopen',
        ])->assertOk()
            ->assertJsonPath('assessment.assessment_status', 'submitted')
            ->assertJsonPath('assessment.review_remarks', null)
            ->assertJsonPath('review.action', 'reopen')
            ->assertJsonPath('review.previous_status', 'requires_changes')
            ->assertJsonPath('review.new_status', 'submitted');

        $this->assertDatabaseHas('assessment_reviews', [
            'project_id' => $project->id,
            'action' => 'reopen',
            'previous_status' => 'requires_changes',
            'new_status' => 'submitted',
            'user_id' => $admin->id,
        ]);
    }

    public function test_appointed_facilitator_can_verify_and_certify_a_project(): void
    {
        Storage::fake('local');
        $owner = $this->user('user');
        $facilitator = $this->user('facilitator_admin');
        $project = $this->project($owner);
        $itemId = $this->assessmentItem($project, 5);

        DB::table('facilitator_assignments')->insert([
            'project_id' => $project->id,
            'user_id' => $facilitator->id,
            'appointed_by' => $owner->id,
            'status' => 'active',
            'appointed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($facilitator);
        $this->postJson("/api/administration/facilitator/assessments/{$project->id}/review", [
            'action' => 'reject',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('remarks');

        $this->postJson("/api/administration/facilitator/assessments/{$project->id}/review", [
            'action' => 'reject',
            'remarks' => 'Please revise the Predicted submission by correcting the energy calculation assumptions and upload the updated model with supporting evidence for another review.',
        ])->assertOk()
            ->assertJsonPath('assessment.assessment_status', 'requires_changes');

        $this->postJson("/api/administration/facilitator/assessments/{$project->id}/review", [
            'action' => 'reopen',
        ])->assertOk()
            ->assertJsonPath('assessment.assessment_status', 'submitted')
            ->assertJsonPath('assessment.review_remarks', null);

        $this->postJson("/api/administration/facilitator/assessments/{$project->id}/review", [
            'action' => 'verify',
        ])->assertOk()
            ->assertJsonPath('assessment.assessment_status', 'verified');

        $certification = [
            'name' => 'Gold',
            'min_score' => 5,
            'max_score' => 5,
            'multiplier' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $certification[Schema::hasColumn('certifications', 'building_type_id') ? 'building_type_id' : 'type_id'] = $project->building_type_id;
        DB::table('certifications')->insert($certification);

        $this->patchJson("/api/administration/facilitator/assessments/{$project->id}/actual-selections", [
            'items' => [['item_id' => $itemId, 'accepted_choice_keys' => ["item:{$itemId}"]]],
        ])->assertOk();

        $this->postJson("/api/administration/facilitator/assessments/{$project->id}/review", [
            'action' => 'certify',
        ])->assertOk()
            ->assertJsonPath('assessment.assessment_status', 'certified')
            ->assertJsonPath('review.certification_level', 'Gold')
            ->assertJsonPath('certificate.certification_level', 'Gold')
            ->assertJsonPath('certificate.status', 'issued');

        $this->assertDatabaseHas('assessment_reviews', [
            'project_id' => $project->id,
            'user_id' => $facilitator->id,
            'action' => 'reopen',
        ]);
        $this->assertDatabaseHas('project_certificates', [
            'project_id' => $project->id,
            'issued_by' => $facilitator->id,
            'certification_level' => 'Gold',
            'status' => 'issued',
        ]);
    }

    public function test_actual_review_rejects_a_nonexistent_choice(): void
    {
        $owner = $this->user('user');
        $admin = $this->user('admin');
        $project = $this->project($owner);
        $itemId = $this->assessmentItem($project, 5);
        $this->selectActualAssessmentItem($project, $owner, $itemId);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/administration/admin/assessments/{$project->id}/actual-selections", [
            'items' => [['item_id' => $itemId, 'accepted_choice_keys' => ['item:999999']]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.accepted_choice_keys.0');

        $this->assertDatabaseMissing('assessment_item_reviews', [
            'project_id' => $project->id,
            'item_id' => $itemId,
        ]);
    }

    public function test_admin_can_upload_and_register_a_reference_document(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->user('admin'));

        $upload = $this->call(
            'PUT',
            '/api/administration/admin/references/upload',
            [],
            [],
            [],
            ['HTTP_X_FILE_NAME' => 'green-guide.pdf', 'CONTENT_TYPE' => 'application/pdf'],
            '%PDF test document',
        );

        $upload->assertCreated();
        $fileUrl = $upload->json('file_url');
        $path = Str::after((string) parse_url($fileUrl, PHP_URL_PATH), '/storage/');
        Storage::disk('public')->assertExists($path);

        $reference = $this->postJson('/api/administration/admin/references', [
            'title' => 'Green Guide',
            'description' => 'Uploaded through the reference form.',
            'category' => 'Guideline',
            'file_url' => $fileUrl,
        ])->assertCreated();

        $this->deleteJson('/api/administration/admin/references/' . $reference->json('id'))
            ->assertOk();
        Storage::disk('public')->assertMissing($path);
    }

    public function test_admin_can_rename_a_section_and_manage_multiple_guidance_entries(): void
    {
        $admin = $this->user('admin');
        Sanctum::actingAs($admin);

        $this->patchJson('/api/administration/admin/recommendation-section', [
            'certification_level' => 'Platinum',
            'title' => 'Platinum Guidance',
        ])->assertOk()
            ->assertJsonPath('title', 'Platinum Guidance');

        $recommendation = $this->postJson('/api/administration/admin/recommendations', [
            'certification_level' => 'Platinum',
            'title' => 'Performance leadership',
            'content' => 'Maintain exemplary green-building performance.',
            'is_active' => true,
        ])->assertCreated();

        $id = $recommendation->json('id');

        $this->postJson('/api/administration/admin/recommendations', [
            'certification_level' => 'Platinum',
            'title' => 'Evidence checklist',
            'content' => 'Retain calculations and commissioning records.',
            'is_active' => true,
        ])->assertCreated();

        $this->patchJson("/api/administration/admin/recommendations/{$id}", [
            'certification_level' => 'Platinum',
            'title' => 'Performance leadership priorities',
            'content' => 'Maintain exemplary green-building performance and monitor outcomes.',
            'is_active' => true,
        ])->assertOk()
            ->assertJsonPath('title', 'Performance leadership priorities');

        $this->getJson('/api/administration/admin/recommendations')
            ->assertOk()
            ->assertJsonPath('sections.0.title', 'Platinum Guidance')
            ->assertJsonCount(2, 'recommendations');

        $this->deleteJson("/api/administration/admin/recommendations/{$id}")
            ->assertOk()
            ->assertJson(['message' => 'Recommendation deleted.']);

        $this->assertDatabaseMissing('recommendations', ['id' => $id]);
    }

    private function user(string $systemRole): User
    {
        static $counter = 0;
        $counter++;

        return User::create([
            'first_name' => 'Test',
            'last_name' => 'User ' . $counter,
            'email' => "role{$counter}@example.test",
            'password' => 'password',
            'email_verified_at' => now(),
            'role_id' => $this->projectRole->id,
            'system_role' => $systemRole,
        ]);
    }

    private function project(User $owner): Project
    {
        static $counter = 0;
        $counter++;

        $buildingTypeId = DB::table('building_types')->insertGetId([
            'name' => 'Test Building ' . $counter,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $structureId = DB::table('structures')->insertGetId([
            'name' => 'Test Structure ' . $counter,
            'code' => 'TS' . $counter,
            'building_type_id' => $buildingTypeId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $categoryId = DB::table('categories')->insertGetId([
            'category' => 'Office ' . $counter,
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
            'name' => 'Test Project ' . $counter,
            'assessment_status' => 'submitted',
        ]);

        return Project::findOrFail($projectId);
    }

    private function assessmentItem(Project $project, int $marks): int
    {
        $criterionId = DB::table('criteria')->insertGetId([
            'name' => 'Energy efficiency',
            'building_type_id' => $project->building_type_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $criterionColumn = Schema::hasColumn('subcriteria', 'criterion_id') ? 'criterion_id' : 'criteria_id';
        $subcriterionId = DB::table('subcriteria')->insertGetId([
            'name' => 'Energy performance',
            $criterionColumn => $criterionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $subcriterionColumn = Schema::hasColumn('items', 'subcriterion_id') ? 'subcriterion_id' : 'subcriteria_id';
        $item = [
            'description' => 'Improve modeled energy performance',
            'info' => 'Test assessment item',
            'marks' => $marks,
            'subitems_exist' => false,
            $subcriterionColumn => $subcriterionId,
        ];

        foreach (['suggestions', 'esg'] as $column) {
            if (Schema::hasColumn('items', $column)) $item[$column] = 'Test';
        }
        if (Schema::hasColumn('items', 'created_at')) $item['created_at'] = now();
        if (Schema::hasColumn('items', 'updated_at')) $item['updated_at'] = now();

        return DB::table('items')->insertGetId($item);
    }

    private function selectAssessmentItem(Project $project, User $owner, int $itemId): void
    {
        $answer = ['project_id' => $project->id, 'item_id' => $itemId];
        if (Schema::hasColumn('user_answers', 'user_id')) $answer['user_id'] = $owner->id;
        if (Schema::hasColumn('user_answers', 'created_at')) $answer['created_at'] = now();
        if (Schema::hasColumn('user_answers', 'updated_at')) $answer['updated_at'] = now();
        DB::table('user_answers')->insert($answer);
    }

    private function selectActualAssessmentItem(Project $project, User $owner, int $itemId): int
    {
        $project->update(['assessment_status' => 'verified']);
        $answer = ['project_id' => $project->id, 'item_id' => $itemId];
        if (Schema::hasColumn('actual_user_answers', 'user_id')) $answer['user_id'] = $owner->id;
        return DB::table('actual_user_answers')->insertGetId($answer);
    }

    private function selectActualSubitems(Project $project, User $owner, int $itemId, array $descriptions): array
    {
        $project->update(['assessment_status' => 'verified']);
        DB::table('items')->where('id', $itemId)->update(['subitems_exist' => true]);

        return collect($descriptions)->map(function (string $description) use ($project, $owner, $itemId) {
            $subitemId = DB::table('subitems')->insertGetId([
                'description' => $description,
                'item_id' => $itemId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $answer = [
                'project_id' => $project->id,
                'item_id' => $itemId,
                'subitem_id' => $subitemId,
            ];
            if (Schema::hasColumn('actual_user_answers', 'user_id')) $answer['user_id'] = $owner->id;

            DB::table('actual_user_answers')->insert($answer);

            return "subitem:{$subitemId}";
        })->all();
    }
}
