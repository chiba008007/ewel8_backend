<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExamLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        // バリデーション（安全のため推奨）
        $validated = [
            'testparts_id' => ['required', 'integer', 'exists:testparts,id'], // exists:testparts,idでtestparts.idと比べている
            'tokenExam' => ['required', 'string'],
            'code' => ['required', 'string'],
            'status' => ['required', 'integer', 'in:1,2'],
        ];

        return $validated;
    }

    public function messages(): array
    {
        return [
            'testparts_id.required' => 'testparts_idは必須です。',
            'testparts_id.integer' => 'testparts_idは数値で指定してください。',
            'testparts_id.exists' => '指定されたtestparts_idは存在しません。',
            'tokenExam.required' => 'tokenExamは必須です。',
            'code.required' => 'codeは必須です。',
            'status.required' => 'statusは必須です。',
            'status.integer' => 'statusは数値で指定してください。',
            'status.in' => 'statusは1または2で指定してください。',
        ];
    }

}
