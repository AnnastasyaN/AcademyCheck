<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use Illuminate\Http\JsonResponse;

class DocumentTypeController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->success(
            DocumentType::query()
                ->where('is_active', true)
                ->orderBy('label')
                ->get(['id', 'name', 'label', 'description']),
        );
    }
}
