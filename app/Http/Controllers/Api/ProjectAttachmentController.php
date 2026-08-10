<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectAttachmentController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:png,jpg,jpeg,webp,pdf,xlsx'],
        ]);

        $file  = $request->file('file');
        $path  = $file->store("projects/{$project->id}", 'public');
        $ext   = strtolower($file->getClientOriginalExtension());
        $kind  = in_array($ext, ['png', 'jpg', 'jpeg', 'webp']) ? 'image'
               : ($ext === 'pdf' ? 'pdf' : 'spreadsheet');

        $attachment = Attachment::create([
            'project_id'    => $project->id,
            'user_id'       => $request->user()->id,
            'original_name' => $file->getClientOriginalName(),
            'filename'      => basename($path),
            'path'          => $path,
            'mime_type'     => $file->getMimeType(),
            'kind'          => $kind,
            'size'          => $file->getSize(),
        ]);

        return (new AttachmentResource($attachment))->response()->setStatusCode(201);
    }
}
