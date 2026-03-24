<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'iso2' => $this->iso2,
            'iso3' => $this->iso3,
            'phone_code' => $this->phone_code,
            'is_active' => (bool) $this->is_active,
            'states_count' => $this->when(isset($this->states_count), $this->states_count),
            'states' => StateResource::collection($this->whenLoaded('states')),
        ];
    }
}


