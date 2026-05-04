<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'title' => Str::ucfirst(trim($this->title)),
            'description' => $this->description ? Str::ucfirst(trim($this->description)) : null,
            'task_date' => (new DateTimeResource($this->task_date, includeTime: false))->resolve($request),
            'completed_at' => (new DateTimeResource($this->completed_at))->resolve($request),
            'created_at' => (new DateTimeResource($this->created_at))->resolve($request),
            'updated_at' => (new DateTimeResource($this->updated_at))->resolve($request),
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
