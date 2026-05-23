<?php

namespace App\Services\Reports;

use App\Enums\CertificateStatus;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CertificateMonitoringReportService
{
    /**
     * @param  array{date_from: string|null, date_to: string|null, category_id: int|null, product_id: int|null, status: string}  $filters
     */
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->resultsQuery($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array{date_from: string|null, date_to: string|null, category_id: int|null, product_id: int|null, status: string}  $filters
     * @return Collection<int, Certificate>
     */
    public function get(array $filters): Collection
    {
        return $this->resultsQuery($filters)->get();
    }

    /**
     * @return array{categories: Collection<int, Category>, products: Collection<int, Product>, statuses: array<string, string>}
     */
    public function filterOptions(): array
    {
        return [
            'categories' => Category::query()->orderBy('name')->get(),
            'products' => Product::query()->with('category')->orderBy('name')->get(),
            'statuses' => Certificate::monitoringFilterOptions(),
        ];
    }

    /**
     * @param  array{date_from: string|null, date_to: string|null, category_id: int|null, product_id: int|null, status: string}  $filters
     * @return array{total: int, active: int, expiring_soon: int, expired: int}
     */
    public function summary(array $filters): array
    {
        $query = $this->filteredQuery($filters);

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->filterMonitoringStatus(CertificateStatus::Active->value)->count(),
            'expiring_soon' => (clone $query)->filterMonitoringStatus(CertificateStatus::ExpiringSoon->value)->count(),
            'expired' => (clone $query)->filterMonitoringStatus(CertificateStatus::Expired->value)->count(),
        ];
    }

    /**
     * @param  array{date_from: string|null, date_to: string|null, category_id: int|null, product_id: int|null, status: string}  $filters
     */
    private function resultsQuery(array $filters): Builder
    {
        return $this->filteredQuery($filters)
            ->with(['product.category', 'certificateType', 'issuer'])
            ->orderBy('expires_at')
            ->orderBy('certificate_number');
    }

    /**
     * @param  array{date_from: string|null, date_to: string|null, category_id: int|null, product_id: int|null, status: string}  $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        return Certificate::query()
            ->when(
                $filters['date_from'],
                fn (Builder $query, string $dateFrom) => $query->whereDate('expires_at', '>=', $dateFrom),
            )
            ->when(
                $filters['date_to'],
                fn (Builder $query, string $dateTo) => $query->whereDate('expires_at', '<=', $dateTo),
            )
            ->when(
                $filters['category_id'],
                fn (Builder $query, int $categoryId) => $query->whereHas(
                    'product',
                    fn (Builder $productQuery) => $productQuery->where('category_id', $categoryId),
                ),
            )
            ->when(
                $filters['product_id'],
                fn (Builder $query, int $productId) => $query->where('product_id', $productId),
            )
            ->filterMonitoringStatus($filters['status']);
    }
}
