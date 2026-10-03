<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
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

        return TaskResource::make($task->load(['assignee', 'creator']));
    }

    public function destroy(Task $task): Response
    {
        Gate::authorize('delete', $task);

        $task->delete();

        return response()->noContent();
    }
}
