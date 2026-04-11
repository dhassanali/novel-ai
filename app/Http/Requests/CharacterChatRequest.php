<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CharacterChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        $novel = $this->route('novel');

        return $this->user()->can('update', $novel);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => 'required|string|max:2000',
            'history' => 'sometimes|array',
            'history.*.role' => 'required|string|in:user,character',
            'history.*.content' => 'required|string',
        ];
    }
}
