<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Task;

class CommentController extends Controller
{
    /**
     * Коментарі конкретної задачі: GET /api/tasks/{task}/comments
     */
    public function index(Task $task)
    {
        $comments = $task->comments()->with('user')->paginate(10);

        return CommentResource::collection($comments);
    }

    /**
     * Додати коментар до задачі: POST /api/tasks/{task}/comments
     */
    public function store(StoreCommentRequest $request, Task $task)
    {
        $comment = $task->comments()->create($request->validated());

        return (new CommentResource($comment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Один коментар: GET /api/comments/{comment}
     */
    public function show(Comment $comment)
    {
        return new CommentResource($comment->load('user'));
    }

    /**
     * Оновлення коментаря: PUT/PATCH /api/comments/{comment}
     */
    public function update(UpdateCommentRequest $request, Comment $comment)
    {
        $comment->update($request->validated());

        return new CommentResource($comment);
    }

    /**
     * Видалення коментаря: DELETE /api/comments/{comment}
     */
    public function destroy(Comment $comment)
    {
        $comment->delete();

        return response()->noContent();
    }
}
