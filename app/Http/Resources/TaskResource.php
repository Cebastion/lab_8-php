<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'status'      => $this->status,
            'due_date'    => $this->due_date,
            'project_id'  => $this->project_id,
            'author_id'   => $this->author_id,
            'assigned_to' => $this->assigned_to,
            'project'     => new ProjectResource($this->whenLoaded('project')),
            'author'      => $this->whenLoaded('author', fn () => [
                'id'   => $this->author->id,
                'name' => $this->author->name,
            ]),
            'assignee'    => $this->whenLoaded('assignee', fn () => $this->assignee ? [
                'id'   => $this->assignee->id,
                'name' => $this->assignee->name,
            ] : null),
            'comments'    => CommentResource::collection($this->whenLoaded('comments')),
            'created_at'  => $this->created_at->toDateTimeString(),
        ];
    }
}
