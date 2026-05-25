<?php

namespace App\Http\Controllers;

use App\Models\BusinessAmendment;
use App\Models\BusinessRegistration;
use App\Models\RiitReturn;
use App\Models\TinRegistration;
use App\Services\AiReportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function registrationIndividual(Request $request): View
    {
        return $this->reportView($request, 'individual_registration');
    }

    public function registrationBusiness(Request $request): View
    {
        return $this->reportView($request, 'business_registration');
    }

    public function amendmentsIndividual(Request $request): View
    {
        return $this->reportView($request, 'individual_amendment');
    }

    public function amendmentsBusiness(Request $request): View
    {
        return $this->reportView($request, 'business_amendment');
    }

    public function amendmentsGraduation(): View
    {
        abort(404);
    }

    public function aiBuilder(Request $request): View
    {
        return view('reports.ai-builder', [
            'filters' => [
                'scope' => $request->get('scope', 'all'),
                'date_from' => $request->get('date_from', now()->subDays(30)->toDateString()),
                'date_to' => $request->get('date_to', now()->toDateString()),
                'prompt' => $request->get('prompt', 'Generate an executive summary with key trends, bottlenecks, risks, and recommended actions.'),
            ],
            'result' => null,
            'context' => null,
            'aiConfigured' => config('services.ai_reports.enabled') && config('services.ai_reports.api_key'),
        ]);
    }

    public function generateAiReport(Request $request, AiReportService $aiReportService): View
    {
        $validated = $request->validate([
            'scope' => 'required|in:all,individual_registration,business_registration,individual_amendment,business_amendment,riit_returns',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'prompt' => 'required|string|max:1200',
        ]);

        $context = $this->aiReportContext($validated);
        $result = $aiReportService->generate($validated['prompt'], $context);

        return view('reports.ai-builder', [
            'filters' => $validated,
            'result' => $result,
            'context' => $context,
            'aiConfigured' => config('services.ai_reports.enabled') && config('services.ai_reports.api_key'),
        ]);
    }

    public function exportRegistration(Request $request): StreamedResponse
    {
        $type = $request->get('type') === 'business' ? 'business_registration' : 'individual_registration';

        return $this->exportReport($request, $type);
    }

    public function exportAmendments(Request $request): StreamedResponse
    {
        $type = $request->get('type') === 'business' ? 'business_amendment' : 'individual_amendment';

        return $this->exportReport($request, $type);
    }

    private function reportView(Request $request, string $type): View
    {
        $config = $this->reportConfig($type);
        $query = $this->reportQuery($type, $request);
        $summaryRows = (clone $query)->get();
        $rows = (clone $query)->latest('created_at')->paginate(25)->withQueryString();

        return view('reports.detailed', [
            'title' => $config['title'],
            'description' => $config['description'],
            'type' => $type,
            'exportUrl' => $config['exportUrl'],
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to', 'distribution']),
            'stats' => $this->statsFromRows($summaryRows, $type),
            'charts' => $this->chartDataFromRows($summaryRows, $type),
            'rows' => $rows,
            'columns' => $config['columns'],
        ]);
    }

    private function exportReport(Request $request, string $type): StreamedResponse
    {
        $config = $this->reportConfig($type);
        $rows = $this->reportQuery($type, $request)->latest('created_at')->get();
        $filename = str_replace(' ', '_', strtolower($config['title'])) . '_' . now()->format('Ymd_His') . '.csv';

        return response()->stream(function () use ($rows, $config, $type) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, array_column($config['columns'], 'label'));

            foreach ($rows as $row) {
                fputcsv($file, array_map(fn ($column) => $this->reportValue($row, $column['key'], $type), $config['columns']));
            }

            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    private function aiReportContext(array $filters): array
    {
        $scope = $filters['scope'];
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        $sections = [];
        foreach ($this->aiReportScopes($scope) as $type) {
            $sections[$type] = $this->aiReportSection($type, $dateFrom, $dateTo);
        }

        return [
            'generated_at' => now()->toDateTimeString(),
            'filters' => [
                'scope' => $scope,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'sections' => $sections,
        ];
    }

    private function aiReportScopes(string $scope): array
    {
        if ($scope !== 'all') {
            return [$scope];
        }

        return [
            'individual_registration',
            'business_registration',
            'individual_amendment',
            'business_amendment',
            'riit_returns',
        ];
    }

    private function aiReportSection(string $type, ?string $dateFrom, ?string $dateTo): array
    {
        if ($type === 'riit_returns') {
            $query = RiitReturn::query();
            $this->applyDateRange($query, $dateFrom, $dateTo);
            $rows = $query->get();

            return [
                'title' => 'Resident Individual Income Tax Returns',
                'total' => $rows->count(),
                'by_status' => $rows->countBy('status')->all(),
                'by_return_type' => $rows->countBy('return_type')->all(),
                'tax_due_total' => round((float) $rows->sum('tax_due'), 2),
                'attachments_total' => (int) $rows->sum(fn ($row) => $row->attachments()->count()),
                'daily_trend' => $this->dailyTrend($rows),
            ];
        }

        $query = $this->baseReportQuery($type);
        $this->applyDateRange($query, $dateFrom, $dateTo);
        $rows = $query->get();

        return [
            'title' => $this->reportConfig($type)['title'],
            'total' => $rows->count(),
            'status_summary' => $this->statsFromRows($rows, $type),
            'daily_trend' => $this->dailyTrend($rows),
            'distribution' => $this->chartDataFromRows($rows, $type)['distribution']->all(),
        ];
    }

    private function baseReportQuery(string $type): Builder
    {
        return match ($type) {
            'business_registration' => BusinessRegistration::query(),
            'individual_amendment' => TinRegistration::query()->whereIn('registration_type', ['AMND', 'AMEND']),
            'business_amendment' => BusinessAmendment::query()->with('registration'),
            default => TinRegistration::query()->where(function ($q) {
                $q->whereNull('registration_type')
                    ->orWhere('registration_type', '')
                    ->orWhere('registration_type', 'NEW');
            }),
        };
    }

    private function applyDateRange(Builder $query, ?string $dateFrom, ?string $dateTo): void
    {
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
    }

    private function dailyTrend(Collection $rows): array
    {
        return $rows
            ->groupBy(fn ($row) => $row->created_at?->format('Y-m-d') ?: 'Unknown')
            ->map->count()
            ->sortKeys()
            ->all();
    }

    private function reportQuery(string $type, Request $request): Builder
    {
        $query = match ($type) {
            'business_registration' => BusinessRegistration::query(),
            'individual_amendment' => TinRegistration::query()->whereIn('registration_type', ['AMND', 'AMEND']),
            'business_amendment' => BusinessAmendment::query()->with('registration'),
            default => TinRegistration::query()->where(function ($q) {
                $q->whereNull('registration_type')
                    ->orWhere('registration_type', '')
                    ->orWhere('registration_type', 'NEW');
            }),
        };

        $this->applyReportFilters($query, $request, $type);

        return $query;
    }

    private function applyReportFilters(Builder $query, Request $request, string $type): void
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search, $type) {
                if ($type === 'business_registration') {
                    $q->where('reference_number', 'like', "%{$search}%")
                        ->orWhere('new_tin', 'like', "%{$search}%")
                        ->orWhere('old_tin', 'like', "%{$search}%")
                        ->orWhere('legal_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                    return;
                }

                if ($type === 'business_amendment') {
                    $q->where('reference_number', 'like', "%{$search}%")
                        ->orWhere('tin', 'like', "%{$search}%")
                        ->orWhere('amendment_tin', 'like', "%{$search}%")
                        ->orWhere('document_locator', 'like', "%{$search}%")
                        ->orWhereHas('registration', function ($registrationQuery) use ($search) {
                            $registrationQuery->where('legal_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                    return;
                }

                $q->where('ref', 'like', "%{$search}%")
                    ->orWhere('tin', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")
                    ->orWhere('forenames', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $status = $request->status;
            if (in_array($type, ['business_registration', 'business_amendment'], true)) {
                $query->where('status', $this->businessStatusFromDisplay($status));
            } else {
                $query->where('status', strtoupper($status));
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('distribution')) {
            $this->applyDistributionFilter($query, $request->distribution, $type);
        }
    }

    private function applyDistributionFilter(Builder $query, string $distribution, string $type): void
    {
        if ($type === 'business_registration') {
            $query->where(function ($q) use ($distribution) {
                $q->where('business_type_display', $distribution)
                    ->orWhere('business_type', $distribution);
            });
            return;
        }

        if ($type === 'business_amendment') {
            $key = strtolower(str_replace(' ', '_', $distribution));
            $query->whereJsonContains('amended_sections', $key);
            return;
        }

        if ($type === 'individual_amendment') {
            if ($distribution === 'With Amendment Notes') {
                $query->whereNotNull('amendment_notes')->where('amendment_notes', '<>', '');
                return;
            }

            if ($distribution === 'Without Amendment Notes') {
                $query->where(function ($q) {
                    $q->whereNull('amendment_notes')->orWhere('amendment_notes', '');
                });
            }
            return;
        }

        $query->where(function ($q) use ($distribution) {
            if ($distribution === 'Unspecified') {
                $q->whereNull('country_of_citizenship')->orWhere('country_of_citizenship', '');
                return;
            }

            $q->where('country_of_citizenship', $distribution);
        });
    }

    private function statsFromRows(Collection $rows, string $type): array
    {
        $statuses = $rows->map(fn ($row) => $this->displayStatus($row->status, $type));

        return [
            'total' => $rows->count(),
            'pending' => $statuses->filter(fn ($status) => $status === 'PENDING')->count(),
            'under_review' => $statuses->filter(fn ($status) => $status === 'UNDER_REVIEW')->count(),
            'approved' => $statuses->filter(fn ($status) => $status === 'APPROVED')->count(),
            'rejected' => $statuses->filter(fn ($status) => $status === 'REJECTED')->count(),
            'submitted_today' => $rows->filter(fn ($row) => $row->created_at?->isToday())->count(),
        ];
    }

    private function chartDataFromRows(Collection $rows, string $type): array
    {
        $statusCounts = collect(['PENDING', 'UNDER_REVIEW', 'APPROVED', 'REJECTED'])
            ->mapWithKeys(fn ($status) => [$status => $rows->filter(fn ($row) => $this->displayStatus($row->status, $type) === $status)->count()]);

        $trendLabels = collect(range(13, 0))->map(fn ($daysAgo) => now()->subDays($daysAgo)->format('Y-m-d'));
        $trend = $trendLabels->map(fn ($date) => [
            'date' => $date,
            'label' => \Carbon\Carbon::parse($date)->format('d M'),
            'count' => $rows->filter(fn ($row) => $row->created_at?->format('Y-m-d') === $date)->count(),
        ]);

        $distribution = match ($type) {
            'business_registration' => $rows
                ->groupBy(fn ($row) => $row->business_type_display ?: $row->business_type ?: 'Unspecified')
                ->map->count()
                ->sortDesc()
                ->take(8),
            'business_amendment' => $this->businessAmendmentSectionDistribution($rows),
            'individual_amendment' => $rows
                ->groupBy(fn ($row) => $row->amendment_notes ? 'With Amendment Notes' : 'Without Amendment Notes')
                ->map->count(),
            default => $rows
                ->groupBy(fn ($row) => $row->country_of_citizenship ?: 'Unspecified')
                ->map->count()
                ->sortDesc()
                ->take(8),
        };

        return [
            'status' => $statusCounts,
            'trend' => $trend,
            'distribution' => $distribution,
            'distributionTitle' => match ($type) {
                'business_registration' => 'Business Types',
                'business_amendment' => 'Amended Sections',
                'individual_amendment' => 'Amendment Notes Coverage',
                default => 'Citizenship Distribution',
            },
        ];
    }

    private function businessAmendmentSectionDistribution(Collection $rows): Collection
    {
        $sections = collect();

        foreach ($rows as $row) {
            foreach (($row->amended_sections ?? []) as $section) {
                $sections->push(ucwords(str_replace('_', ' ', $section)));
            }
        }

        return $sections->countBy()->sortDesc()->take(8);
    }

    private function reportConfig(string $type): array
    {
        $baseColumns = [
            ['key' => 'reference', 'label' => 'Reference'],
            ['key' => 'tin', 'label' => 'TIN'],
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'submitted_at', 'label' => 'Submitted Date'],
            ['key' => 'reviewed_at', 'label' => 'Reviewed Date'],
            ['key' => 'review_notes', 'label' => 'Review Notes'],
        ];

        return match ($type) {
            'business_registration' => [
                'title' => 'Business Registration Report',
                'description' => 'Detailed reporting for business TIN registration submissions.',
                'exportUrl' => route('reports.registration.export', ['type' => 'business']),
                'columns' => array_merge($baseColumns, [
                    ['key' => 'business_type', 'label' => 'Business Type'],
                    ['key' => 'application_type', 'label' => 'Application Type'],
                ]),
            ],
            'individual_amendment' => [
                'title' => 'Individual Amendment Report',
                'description' => 'Detailed reporting for individual amendment submissions stored under individual TIN records.',
                'exportUrl' => route('reports.amendments.export', ['type' => 'individual']),
                'columns' => array_merge($baseColumns, [
                    ['key' => 'amendment_notes', 'label' => 'Amendment Notes'],
                ]),
            ],
            'business_amendment' => [
                'title' => 'Business Amendment Report',
                'description' => 'Detailed reporting for business amendment submissions and amended sections.',
                'exportUrl' => route('reports.amendments.export', ['type' => 'business']),
                'columns' => array_merge($baseColumns, [
                    ['key' => 'amended_sections', 'label' => 'Amended Sections'],
                    ['key' => 'amendment_type', 'label' => 'Amendment Type'],
                ]),
            ],
            default => [
                'title' => 'Individual Registration Report',
                'description' => 'Detailed reporting for new individual TIN registration submissions.',
                'exportUrl' => route('reports.registration.export', ['type' => 'individual']),
                'columns' => $baseColumns,
            ],
        };
    }

    public function reportValue($row, string $key, string $type): string
    {
        return match ($key) {
            'reference' => (string) ($row->ref ?? $row->reference_number ?? 'N/A'),
            'tin' => (string) ($row->tin ?? $row->new_tin ?? $row->old_tin ?? $row->amendment_tin ?? 'N/A'),
            'name' => $this->reportName($row, $type),
            'email' => $this->reportEmail($row, $type),
            'status' => $this->displayStatus($row->status, $type),
            'submitted_at' => (string) ($row->created_at?->format('Y-m-d H:i:s') ?? 'N/A'),
            'reviewed_at' => (string) ($row->reviewed_at?->format('Y-m-d H:i:s') ?? 'N/A'),
            'review_notes' => (string) ($row->review_notes ?? $row->remarks ?? $row->rejection_reason ?? 'N/A'),
            'business_type' => (string) ($row->business_type_display ?? $row->business_type ?? 'N/A'),
            'application_type' => (string) ($row->application_type ?? 'N/A'),
            'amendment_notes' => (string) ($row->amendment_notes ?? 'N/A'),
            'amended_sections' => (string) ($row->amended_sections_display ?? 'N/A'),
            'amendment_type' => (string) ($row->amendment_type ?? 'N/A'),
            default => 'N/A',
        };
    }

    private function reportName($row, string $type): string
    {
        if ($type === 'business_registration') {
            return (string) ($row->display_name ?? 'N/A');
        }

        if ($type === 'business_amendment') {
            return (string) (
                $row->registration?->display_name
                ?? data_get($row->amendment_data, 'business_details.legal_name')
                ?? data_get($row->amendment_data, 'legal_name')
                ?? 'Business Amendment'
            );
        }

        return trim(($row->title ? $row->title . ' ' : '') . ($row->forenames ?? '') . ' ' . ($row->surname ?? '')) ?: 'N/A';
    }

    private function reportEmail($row, string $type): string
    {
        if ($type === 'business_amendment') {
            return (string) (
                $row->registration?->email
                ?? data_get($row->amendment_data, 'contact_info.email')
                ?? data_get($row->amendment_data, 'email')
                ?? 'N/A'
            );
        }

        return (string) ($row->email ?? 'N/A');
    }

    private function displayStatus(?string $status, string $type): string
    {
        if (in_array($type, ['business_registration', 'business_amendment'], true)) {
            return match ($status) {
                'submitted' => 'PENDING',
                'under_review' => 'UNDER_REVIEW',
                'approved' => 'APPROVED',
                'rejected' => 'REJECTED',
                default => strtoupper((string) $status),
            };
        }

        return strtoupper((string) $status);
    }

    private function businessStatusFromDisplay(string $status): string
    {
        return match (strtoupper($status)) {
            'PENDING' => 'submitted',
            'UNDER_REVIEW' => 'under_review',
            'APPROVED' => 'approved',
            'REJECTED' => 'rejected',
            default => strtolower($status),
        };
    }
}
