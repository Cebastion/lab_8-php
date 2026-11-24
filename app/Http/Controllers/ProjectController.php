<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Список проєктів з пагінацією.
     */
    public function index()
    {
        $projects = Project::with('user')->paginate(10);

        return ProjectResource::collection($projects);
    }

    /**
     * Створення нового проєкту.
     */
    public function store(StoreProjectRequest $request)
    {
        $project = Project::create($request->validated());

        return (new ProjectResource($project))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Один проєкт. Додаткові зв'язки: ?include=tasks
     */
    public function show(Request $request, Project $project)
    {
        $project->load('user');

        if (in_array('tasks', explode(',', (string) $request->query('include')))) {
            $project->load('tasks');
        }

        return new ProjectResource($project);
    }

    /**
     * Оновлення проєкту.
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        $project->update($request->validated());

        return new ProjectResource($project);
    }

    /**
     * Видалення проєкту.
     */
    public function destroy(Project $project)
    {
        $project->delete();

        return response()->noContent();
    }
}
