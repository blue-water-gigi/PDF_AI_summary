<?php

declare(strict_types=1);

namespace App\Http\Requests\Subscription;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Override;

class ChangePlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $currentPlanId = Auth::user()->plan_id;

        return [
            'new_plan_id' => [
                'required',
                'integer',
                Rule::exists('plans', 'id'),
                Rule::notIn([$currentPlanId]),
            ],
            'gateway' => ['sometimes', 'string', Rule::in(['stripe', 'yoomoney'])],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'new_plan_id.notIn' => 'The chosen plan is the same as your current plan.',
        ];
    }
}

