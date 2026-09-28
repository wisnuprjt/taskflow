<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    public const MIMES = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xlsx', 'txt', 'mp4', 'webm'];

    public const MAX_KB = 20480;

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:'.implode(',', self::MIMES), 'max:'.self::MAX_KB],
        ];
    }
}
