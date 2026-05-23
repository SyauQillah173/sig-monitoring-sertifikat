<?php

namespace App\Services\Cement;

use App\Models\KategoriSemen;
use App\Models\LokasiPabrik;
use App\Models\MerekSemen;
use App\Models\SertifikatGreenLabel;
use App\Models\SertifikatSni;
use App\Models\SertifikatTkdn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CementDashboardService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);
        $selectedMerekIds = $this->selectedMerekIds($filters);
        $sniQuery = $this->sniQuery($filters, $selectedMerekIds);
        $tkdnQuery = $this->tkdnQuery($filters, $selectedMerekIds);
        $greenLabelQuery = $this->greenLabelQuery($filters, $selectedMerekIds);

        $sniCertificates = (clone $sniQuery)->get();
        $tkdnCertificates = (clone $tkdnQuery)->get();
        $greenLabelCertificates = (clone $greenLabelQuery)->get();

        return [
            'filters' => $filters,
            'kategoriTree' => KategoriSemen::query()
                ->with(['merekSemen' => fn ($query) => $query->orderBy('nama_merek')])
                ->orderBy('nama_kategori')
                ->get(),
            'options' => $this->filterOptions(),
            'summary' => $this->summary($sniCertificates, $tkdnCertificates, $greenLabelCertificates, $selectedMerekIds),
            'chart' => $this->chartData($sniCertificates, $tkdnCertificates, $greenLabelCertificates),
            'sertifikatSni' => $sniCertificates,
            'sertifikatTkdn' => $tkdnCertificates,
            'sertifikatGreenLabel' => $greenLabelCertificates,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     sni: string,
     *     lspro: string,
     *     lokasi: string,
     *     status: string,
     *     kategori: list<int>,
     *     merek: list<int>
     * }
     */
    public function normalizeFilters(array $filters): array
    {
        return [
            'sni' => (string) ($filters['sni'] ?? 'all'),
            'lspro' => (string) ($filters['lspro'] ?? 'all'),
            'lokasi' => (string) ($filters['lokasi'] ?? 'all'),
            'status' => (string) ($filters['status'] ?? 'all'),
            'kategori' => collect($filters['kategori'] ?? [])->map(fn ($id) => (int) $id)->filter()->values()->all(),
            'merek' => collect($filters['merek'] ?? [])->map(fn ($id) => (int) $id)->filter()->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<int>
     */
    public function selectedMerekIds(array $filters): array
    {
        $fromKategori = empty($filters['kategori'])
            ? collect()
            : MerekSemen::query()
                ->whereIn('kategori_semen_id', $filters['kategori'])
                ->pluck('id');

        return collect($filters['merek'])
            ->merge($fromKategori)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{sni: Collection<int, string>, lspro: Collection<int, string>, lokasi: Collection<int, string>, status: array<string, string>}
     */
    public function filterOptions(): array
    {
        return [
            'sni' => $this->distinctAcrossTables('sni'),
            'lspro' => SertifikatSni::query()->distinct()->orderBy('lspro')->pluck('lspro'),
            'lokasi' => $this->locationOptions(),
            'status' => SertifikatSni::statusOptions(),
        ];
    }

    /**
     * @param  list<int>  $selectedMerekIds
     */
    public function sniQuery(array $filters, array $selectedMerekIds): Builder
    {
        return SertifikatSni::query()
            ->with(['merekSemen.kategoriSemen'])
            ->when($selectedMerekIds !== [], fn (Builder $query) => $query->whereIn('merek_semen_id', $selectedMerekIds))
            ->when($filters['sni'] !== 'all', fn (Builder $query) => $query->where('sni', $filters['sni']))
            ->when($filters['lspro'] !== 'all', fn (Builder $query) => $query->where('lspro', $filters['lspro']))
            ->when($filters['lokasi'] !== 'all', fn (Builder $query) => $query->where('lokasi', $filters['lokasi']))
            ->filterExpiryStatus($filters['status'])
            ->orderBy('berlaku_sd')
            ->orderBy('sni');
    }

    /**
     * @param  list<int>  $selectedMerekIds
     */
    public function tkdnQuery(array $filters, array $selectedMerekIds): Builder
    {
        return SertifikatTkdn::query()
            ->with(['merekSemen.kategoriSemen'])
            ->when($selectedMerekIds !== [], fn (Builder $query) => $query->whereIn('merek_semen_id', $selectedMerekIds))
            ->when($filters['sni'] !== 'all', fn (Builder $query) => $query->where('sni', $filters['sni']))
            ->when($filters['lokasi'] !== 'all', fn (Builder $query) => $query->where('lokasi', $filters['lokasi']))
            ->filterExpiryStatus($filters['status'])
            ->orderBy('berlaku_sd')
            ->orderBy('sni');
    }

    /**
     * @param  list<int>  $selectedMerekIds
     */
    public function greenLabelQuery(array $filters, array $selectedMerekIds): Builder
    {
        return SertifikatGreenLabel::query()
            ->with(['merekSemen.kategoriSemen'])
            ->when($selectedMerekIds !== [], fn (Builder $query) => $query->whereIn('merek_semen_id', $selectedMerekIds))
            ->when($filters['sni'] !== 'all', fn (Builder $query) => $query->where('sni', $filters['sni']))
            ->when($filters['lokasi'] !== 'all', fn (Builder $query) => $query->where('lokasi', $filters['lokasi']))
            ->filterExpiryStatus($filters['status'])
            ->orderBy('berlaku_sd')
            ->orderBy('sni');
    }

    /**
     * @param  Collection<int, object>  $sniCertificates
     * @param  Collection<int, object>  $tkdnCertificates
     * @param  Collection<int, object>  $greenLabelCertificates
     * @param  list<int>  $selectedMerekIds
     * @return array{total_merek: int, total_sni: int, total_tkdn: int, total_green_label: int}
     */
    private function summary(Collection $sniCertificates, Collection $tkdnCertificates, Collection $greenLabelCertificates, array $selectedMerekIds): array
    {
        $merekIdsFromCertificates = $sniCertificates
            ->pluck('merek_semen_id')
            ->merge($tkdnCertificates->pluck('merek_semen_id'))
            ->merge($greenLabelCertificates->pluck('merek_semen_id'))
            ->unique()
            ->values();

        return [
            'total_merek' => $merekIdsFromCertificates->isNotEmpty()
                ? $merekIdsFromCertificates->count()
                : ($selectedMerekIds === [] ? MerekSemen::query()->count() : count($selectedMerekIds)),
            'total_sni' => $sniCertificates->count(),
            'total_tkdn' => $tkdnCertificates->count(),
            'total_green_label' => $greenLabelCertificates->count(),
        ];
    }

    /**
     * @param  Collection<int, object>  ...$collections
     * @return array{items: Collection<int, array{label: string, value: int, color: string, percent: float}>, gradient: string}
     */
    private function chartData(Collection ...$collections): array
    {
        $colors = ['#2f80ed', '#7c3aed', '#06b6d4', '#f97316', '#ec4899', '#84cc16', '#eab308', '#ef4444'];
        $counts = collect($collections)
            ->flatMap(fn (Collection $collection) => $collection->pluck('komoditi'))
            ->filter()
            ->countBy()
            ->sortKeys();
        $total = max($counts->sum(), 1);
        $offset = 0;

        $items = $counts->values()->isEmpty()
            ? collect([['label' => 'Belum Ada Data', 'value' => 1]])
            : $counts->map(fn (int $value, string $label) => ['label' => $label, 'value' => $value])->values();

        $items = $items->map(function (array $item, int $index) use ($colors, $total) {
            return [
                'label' => $item['label'],
                'value' => $item['value'],
                'color' => $colors[$index % count($colors)],
                'percent' => round(($item['value'] / $total) * 100, 2),
            ];
        });

        $gradientParts = $items->map(function (array $item) use (&$offset) {
            $start = $offset;
            $offset += $item['percent'];

            return "{$item['color']} {$start}% {$offset}%";
        })->implode(', ');

        return [
            'items' => $items,
            'gradient' => $gradientParts ?: '#cbd5e1 0% 100%',
        ];
    }

    private function distinctAcrossTables(string $column): Collection
    {
        return SertifikatSni::query()->pluck($column)
            ->merge(SertifikatTkdn::query()->pluck($column))
            ->merge(SertifikatGreenLabel::query()->pluck($column))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    private function locationOptions(): Collection
    {
        return LokasiPabrik::query()
            ->where('is_active', true)
            ->orderBy('nama_lokasi')
            ->pluck('nama_lokasi')
            ->merge($this->distinctAcrossTables('lokasi'))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }
}
