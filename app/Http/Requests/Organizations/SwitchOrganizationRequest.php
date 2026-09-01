<?php

namespace App\Http\Requests\Organizations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SwitchOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'organization_id' => [
                'required',
                'integer',
                Rule::exists('organization_memberships', 'organization_id')
                    ->where(fn ($query) => $query
                        ->where('user_id', $this->user()?->id)
                        ->whereNull('removed_at')),
            ],
        ];
    }
}
