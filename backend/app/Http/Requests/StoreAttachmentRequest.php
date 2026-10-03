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

 //Seluruh file yang diunggah harus memiliki salah satu dari ekstensi berikut: jpg, jpeg, png, webp, pdf, doc, docx, xlsx, txt, mp4, webm. Ukuran maksimum file yang diizinkan adalah 20480 KB (20 MB).