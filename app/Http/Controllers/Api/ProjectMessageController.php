<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectMessage;
use App\Models\Role;
use App\Models\User;
use App\Services\NotificationDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProjectMessageController extends Controller
{
    public function __construct(private readonly NotificationDeliveryService $notifications) {}

    public function index(Request $request, Project $project)
    {
        $data = $request->validate([
            'before' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $before = $data['before'] ?? null;
        $limit = (int) ($data['limit'] ?? 25);

        $query = ProjectMessage::query()
            ->where('project_id', $project->id)
            ->with(['attachment', 'reactions', 'user']);

        if ($before !== null) {
            $query->where('created_at', '<', $before);
        }

        $messages = $query->orderBy('created_at', 'desc')->limit($limit + 1)->get();
        $hasMore  = $messages->count() > $limit;
        $messages = $messages->take($limit)->sortBy('created_at')->values();

        return response()->json([
            'messages' => MessageResource::collection($messages),
            'hasMore'  => $hasMore,
            'cursor' => now()->toISOString(),
            'unreadCount' => $this->unreadCountFor($project, $request->user()->id),
        ]);
    }

    public function changes(Request $request, Project $project)
    {
        $data = $request->validate(['since' => ['required', 'date']]);
        $since = Carbon::parse($data['since']);
        $cursor = now();
        $changed = ProjectMessage::withTrashed()
            ->where('project_id', $project->id)
            ->where('updated_at', '>', $since)
            ->where('updated_at', '<=', $cursor)
            ->with(['attachment', 'reactions', 'user'])
            ->orderBy('updated_at')
            ->limit(101)
            ->get();
        $hasMore = $changed->count() > 100;
        $changed = $changed->take(100);

        return response()->json([
            'messages' => MessageResource::collection($changed->whereNull('deleted_at')->values()),
            'deletedIds' => $changed->whereNotNull('deleted_at')->pluck('id')->map(fn ($id) => (string) $id)->values(),
            'cursor' => $hasMore ? $changed->last()?->updated_at?->toISOString() : $cursor->toISOString(),
            'hasMore' => $hasMore,
        ]);
    }

    public function unreadCounts(Request $request)
    {
        $userId = $request->user()->id;
        $projectIds = Project::where('user_id', $userId)->pluck('id')
            ->merge(ProjectMember::where('user_id', $userId)->pluck('project_id'))
            ->unique()
            ->values();
        $counts = $projectIds->mapWithKeys(fn ($projectId) => [(string) $projectId => 0])->all();

        if ($projectIds->isNotEmpty()) {
            $rows = ProjectMessage::query()
                ->leftJoin('project_members as reader_membership', function ($join) use ($userId) {
                    $join->on('reader_membership.project_id', '=', 'project_messages.project_id')
                        ->where('reader_membership.user_id', '=', $userId);
                })
                ->whereIn('project_messages.project_id', $projectIds)
                ->where('project_messages.user_id', '!=', $userId)
                ->where('project_messages.is_system', false)
                ->whereRaw('project_messages.id > COALESCE(reader_membership.last_read_message_id, 0)')
                ->select('project_messages.project_id', DB::raw('COUNT(*) as unread_count'))
                ->groupBy('project_messages.project_id')
                ->get();

            foreach ($rows as $row) {
                $counts[(string) $row->project_id] = (int) $row->unread_count;
            }
        }

        return response()->json(['counts' => $counts]);
    }

    public function markRead(Request $request, Project $project)
    {
        $data = $request->validate([
            'message_id' => [
                'nullable',
                Rule::exists('project_messages', 'id')->where(fn ($query) => $query
                    ->where('project_id', $project->id)
                    ->whereNull('deleted_at')),
            ],
        ]);
        $user = $request->user();
        $latestMessageId = isset($data['message_id'])
            ? (int) $data['message_id']
            : (int) ($project->messages()->max('id') ?? 0);
        $membership = $project->members()->where('user_id', $user->id)->first();

        if (! $membership && (int) $project->user_id === (int) $user->id) {
            $ownerRoleId = Role::where('name', 'gbi_facilitator')->value('id');
            abort_unless($ownerRoleId, 500, 'The project owner role is not configured.');
            $membership = ProjectMember::firstOrCreate([
                'project_id' => $project->id,
                'user_id' => $user->id,
            ], [
                'added_by' => $user->id,
                'role_id' => $ownerRoleId,
            ]);
        }

        abort_unless($membership, 403);
        if ($latestMessageId > (int) ($membership->last_read_message_id ?? 0)) {
            $membership->update(['last_read_message_id' => $latestMessageId]);
        }
        $lastReadMessageId = $membership->fresh()->last_read_message_id;

        return response()->json([
            'lastReadMessageId' => $lastReadMessageId ? (string) $lastReadMessageId : null,
            'unreadCount' => $this->unreadCountFor($project, $user->id),
        ]);
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'message' => ['required_without:attachment_id', 'string', 'max:2000'],
            'attachment_id' => [
                'nullable',
                Rule::exists('attachments', 'id')->where(fn ($query) => $query
                    ->where('project_id', $project->id)
                    ->where('user_id', $request->user()->id)
                    ->whereNull('assessment_item_id')),
            ],
            'reply_to_id' => [
                'nullable',
                Rule::exists('project_messages', 'id')->where(fn ($query) => $query
                    ->where('project_id', $project->id)
                    ->whereNull('deleted_at')),
            ],
        ]);

        $message = ProjectMessage::create([
            'project_id'    => $project->id,
            'user_id'       => $request->user()->id,
            'body'          => $data['message'] ?? '',
            'attachment_id' => $data['attachment_id'] ?? null,
            'reply_to_id'   => $data['reply_to_id'] ?? null,
            'is_system'     => false,
        ])->load(['attachment', 'reactions', 'user', 'replyTo']);

        Broadcast::on('projects.'.$project->id)->as('message.created')->with([
            'message' => (new MessageResource($message))->resolve(request()),
        ])->send();

        $recipientIds = $project->members()->pluck('user_id')
            ->push($project->user_id)
            ->unique()
            ->reject(fn ($userId) => (int) $userId === (int) $request->user()->id);

        $preview = str($message->body ?: 'Sent an attachment')->squish()->limit(120)->toString();
        foreach (User::whereIn('id', $recipientIds)->get() as $recipient) {
            $this->notifications->deliver(
                $recipient,
                'New message in '.$project->name,
                $request->user()->first_name.': '.$preview,
                '/projects/'.$project->id,
                false,
            );
        }

        return (new MessageResource($message))->response()->setStatusCode(201);
    }

    public function update(Request $request, Project $project, ProjectMessage $message)
    {
        $this->authorizeMessageMutation($request, $project, $message);

        $data = $request->validate([
            'message' => [Rule::requiredIf(! $message->attachment_id), 'nullable', 'string', 'max:2000'],
        ]);

        $message->update(['body' => trim((string) ($data['message'] ?? ''))]);

        return new MessageResource($message->fresh(['attachment', 'reactions', 'user', 'replyTo']));
    }

    public function destroy(Request $request, Project $project, ProjectMessage $message)
    {
        $this->authorizeMessageMutation($request, $project, $message);

        $attachment = $message->attachment;
        $message->delete();

        if ($attachment
            && ! $attachment->assessment_item_id
            && (int) $attachment->user_id === (int) $request->user()->id
            && ! ProjectMessage::where('attachment_id', $attachment->id)->exists()) {
            Storage::disk('public')->delete($attachment->path);
            $attachment->delete();
        }

        return response()->noContent();
    }

    private function authorizeMessageMutation(Request $request, Project $project, ProjectMessage $message): void
    {
        abort_if((int) $message->project_id !== (int) $project->id, 404);
        abort_if($message->is_system, 422, 'System messages cannot be changed.');
        abort_unless((int) $message->user_id === (int) $request->user()->id, 403);
    }

    private function unreadCountFor(Project $project, int $userId): int
    {
        $lastReadMessageId = $project->members()
            ->where('user_id', $userId)
            ->value('last_read_message_id') ?? 0;

        return $project->messages()
            ->where('id', '>', $lastReadMessageId)
            ->where('user_id', '!=', $userId)
            ->where('is_system', false)
            ->count();
    }
}
