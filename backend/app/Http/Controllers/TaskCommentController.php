<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\TaskCommentResource;
use App\Models\Task;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskCommentController extends Controller
{
    public function index(Task $task): AnonymousResourceCollection
    {
        return TaskCommentResource::collection($task->comments()->with('user')->oldest()->get());
    }

    public function store(StoreCommentRequest $request, Task $task): TaskCommentResource
    {
        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'comment' => $request->validated('comment'),
        ]);

        return TaskCommentResource::make($comment->load('user'));
    }
}
