<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Зв'язки, які можна підвантажити через ?include=...
     */
    private const ALLOWED_INCLUDES = [
        'project',
        'author',
        'assignee',
        'comments',
        'comments.user',
    ];

    /**
     * Список задач з пагінацією.
     */
    public function index(Request $request)
    {
        $tasks = Task::with($this->includes($request))->paginate(10);

        return TaskResource::collection($tasks);
    }

    /**
     * Створення задачі (валідація — у StoreTaskRequest).
     */
    public function store(StoreTaskRequest $request)
    {
        $task = Task::create($request->validated());

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Деталі задачі. Приклад: GET /api/tasks/1?include=comments
     */
    public function show(Request $request, Task $task)
    {
        $task->load($this->includes($request));

        return new TaskResource($task);
    }

    /**
     * Оновлення задачі (PUT/PATCH).
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        $task->update($request->validated());

        return new TaskResource($task);
    }

    /**
     * Видалення задачі.
     */
    public function destroy(Task $task)
    {
        $task->delete();

        return response()->noContent();
    }

    /**
     * Бере з параметра ?include=a,b тільки дозволені зв'язки.
     */
    private function includes(Request $request): array
    {
        $requested = explode(',', (string) $request->query('include'));

        return array_values(array_intersect($requested, self::ALLOWED_INCLUDES));
    }
}
