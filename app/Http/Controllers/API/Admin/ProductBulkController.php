<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkProductImportRequest;
use App\Http\Requests\Admin\BulkProductUpdateRequest;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductBulkService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class ProductBulkController extends Controller
{
    public function __construct(private ProductBulkService $productBulkService) {}

    public function import(BulkProductImportRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        /** @var User $user */
        $user = $request->user();

        try {
            $result = $this->productBulkService->importFromCsv($request->file('file'), $user);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'result' => $result,
            'message' => 'CSV içe aktarma tamamlandı.',
        ]);
    }

    public function update(BulkProductUpdateRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        /** @var User $user */
        $user = $request->user();

        try {
            $result = $this->productBulkService->updateFromCsv($request->file('file'), $user);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'result' => $result,
            'message' => 'Toplu güncelleme tamamlandı.',
        ]);
    }

    public function template(string $type): Response
    {
        $this->authorize('viewAny', Product::class);

        $content = match ($type) {
            'import' => $this->productBulkService->importTemplate(),
            'update' => $this->productBulkService->updateTemplate(),
            default => abort(404),
        };

        $filename = match ($type) {
            'import' => 'product-import-template.csv',
            'update' => 'product-update-template.csv',
            default => 'template.csv',
        };

        return response("\xEF\xBB\xBF".$content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
