<?php

namespace App\Http\Controllers;

use App\Events\TaskChanged;
use App\Http\Requests\IndexTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Support\Realtime;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    public function index(IndexTaskRequest $request): AnonymousResourceCollection
    {
        $tasks = Task::query()
            ->with(['assignee', 'creator'])
            ->withCount(['latestAttachments as attachments_count', 'comments'])
            ->filter($request->validated())
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request): TaskResource
    {
        $task = $request->user()->createdTasks()->create($request->validated());
        Realtime::broadcast(new TaskChanged($task, TaskChanged::CREATED, $request->user()));

        return TaskResource::make($task->refresh()->load(['assignee', 'creator']));
    }

    public function show(Task $task): TaskResource
    {
        return TaskResource::make($task->load(['assignee', 'creator', 'latestAttachments'])->loadCount(['latestAttachments as attachments_count', 'comments']));
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        Gate::authorize('update', $task);

        $task->update($request->validated());
        Realtime::broadcast(new TaskChanged($task, TaskChanged::UPDATED, $request->user()));

        return TaskResource::make($task->load(['assignee', 'creator']));
    }

    public function destroy(Request $request, Task $task): Response
    {
        Gate::authorize('delete', $task);

        $task->delete();
        Realtime::broadcast(new TaskChanged($task, TaskChanged::DELETED, $request->user()));

        return response()->noContent();
    }
}
