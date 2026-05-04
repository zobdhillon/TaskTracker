<?php

namespace App\Http\Resources;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DateTimeResource extends JsonResource
{
    public function __construct(?CarbonInterface $resource, private bool $includeTime = true)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource === null) {
            return [];
        }

        return [
            'display' => $this->resource->format($this->includeTime ? 'M d, Y g:i A' : 'M d, Y'),
            'iso' => $this->resource->toIso8601String(),
            'datetime' => $this->resource->format($this->includeTime ? 'Y-m-d H:i:s' : 'Y-m-d'),
        ];
    }
}
