@extends("shared.base", ["title" => "Dashboard"])

@section("styles")
<style>
    .stat-card {
        transition: transform 0.2s ease-in-out;
        cursor: pointer;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .pending-badge {
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.6; }
        100% { opacity: 1; }
    }
    .table-row-clickable {
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .table-row-clickable:hover {
        background-color: rgba(59, 118, 225, 0.05);
    }
</style>
@endsection

@section("content")
    <div class="wrapper">
        @include("shared.partials.topbar") 
        @include("shared.partials.sidenav")

        <div class="content-page">
            <div class="container-fluid">
                @include("shared.partials.page-title", ["subtitle" => "Dashboards", "title" => "Dashboard"])

                <!-- Statistics Cards -->
                <div class="row row-cols-xxl-4 row-cols-md-2 row-cols-1 g-3 mb-4">
                    <div class="col">
                        <div class="card stat-card" onclick="window.location.href='{{ route('admin.registrations.index', ['status' => 'pending']) }}'">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="avatar fs-60 avatar-img-size flex-shrink-0">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24">
                                            <i data-lucide="file-plus"></i>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <h3 class="mb-2 fw-bold">
                                            <span class="counter" data-target="{{ $stats['new_applications'] ?? 0 }}">
                                                {{ number_format($stats['new_applications'] ?? 0) }}
                                            </span>
                                        </h3>
                                        <p class="mb-0 text-muted">New Applications</p>
                                        <small class="text-muted">
                                            <i class="mdi mdi-calendar"></i> 
                                            Today: {{ $stats['today_applications'] ?? 0 }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col">
                        <div class="card stat-card" onclick="window.location.href='{{ route('admin.registrations.index', ['status' => 'approved']) }}'">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="avatar fs-60 avatar-img-size flex-shrink-0">
                                        <span class="avatar-title bg-success-subtle text-success rounded-circle fs-24">
                                            <i data-lucide="check-circle"></i>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <h3 class="mb-2 fw-bold">
                                            <span class="counter" data-target="{{ $stats['approved_applications'] ?? 0 }}">
                                                {{ number_format($stats['approved_applications'] ?? 0) }}
                                            </span>
                                        </h3>
                                        <p class="mb-0 text-muted">Approved Applications</p>
                                        <small class="text-success">
                                            <i class="mdi mdi-trending-up"></i> 
                                            {{ $stats['this_month_applications'] ?? 0 }} this month
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col">
                        <div class="card stat-card" onclick="window.location.href='{{ route('admin.registrations.index', ['status' => 'rejected']) }}'">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="avatar fs-60 avatar-img-size flex-shrink-0">
                                        <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-24">
                                            <i data-lucide="x-circle"></i>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <h3 class="mb-2 fw-bold">
                                            <span class="counter" data-target="{{ $stats['rejected_applications'] ?? 0 }}">
                                                {{ number_format($stats['rejected_applications'] ?? 0) }}
                                            </span>
                                        </h3>
                                        <p class="mb-0 text-muted">Rejected Applications</p>
                                        <small class="text-muted">
                                            <i class="mdi mdi-percent"></i> 
                                            {{ $stats['total_applications'] > 0 ? round(($stats['rejected_applications'] / $stats['total_applications']) * 100, 1) : 0 }}% of total
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col">
                        <div class="card stat-card" onclick="window.location.href='{{ route('admin.registrations.index') }}'">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="avatar fs-60 avatar-img-size flex-shrink-0">
                                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-24">
                                            <i data-lucide="briefcase"></i>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <h3 class="mb-2 fw-bold">
                                            <span class="counter" data-target="{{ $stats['total_applications'] ?? 0 }}">
                                                {{ number_format($stats['total_applications'] ?? 0) }}
                                            </span>
                                        </h3>
                                        <p class="mb-0 text-muted">Total Applications</p>
                                        <small class="text-muted">
                                            <i class="mdi mdi-chart-line"></i> 
                                            {{ $stats['this_week_applications'] ?? 0 }} this week
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Tasks Alert (if any) -->
                @if(isset($pending_tasks) && $pending_tasks->count() > 0)
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-warning border-0 shadow-sm">
                            <div class="d-flex align-items-center">
                                <i data-lucide="bell" class="me-3 fs-24"></i>
                                <div>
                                    <h5 class="alert-heading mb-1">Pending Actions</h5>
                                    <p class="mb-0">You have pending tasks requiring your attention:</p>
                                    <ul class="mt-2 mb-0">
                                        @foreach($pending_tasks as $task)
                                        <li>
                                            <a href="{{ $task['url'] }}" class="alert-link">
                                                {{ $task['title'] }}: {{ $task['count'] }} pending
                                            </a>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Charts Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-0">
                                <div class="row g-0">
                                    <div class="col-xxl-3 col-xl-6 order-xl-1 order-xxl-0">
                                        <div class="p-4 border-end border-dashed">
                                            <h4 class="card-title mb-1">Application Distribution</h4>
                                            <p class="text-muted fs-xs mb-4">
                                                Total applications by status
                                            </p>
                                            <div class="row mt-4">
                                                <div class="col-lg-12">
                                                    <div style="height: 280px">
                                                        <canvas id="status-pie-chart" 
                                                                data-labels='@json($pie_chart_data["status"]["labels"])'
                                                                data-data='@json($pie_chart_data["status"]["data"])'
                                                                data-colors='@json($pie_chart_data["status"]["colors"])'>
                                                        </canvas>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-3 text-center">
                                                <div class="d-flex justify-content-center gap-3">
                                                    <div>
                                                        <span class="badge bg-warning-subtle text-warning">⬤ Pending</span>
                                                        <strong class="ms-1">{{ $pie_chart_data['status']['data'][0] ?? 0 }}</strong>
                                                    </div>
                                                    <div>
                                                        <span class="badge bg-success-subtle text-success">⬤ Approved</span>
                                                        <strong class="ms-1">{{ $pie_chart_data['status']['data'][1] ?? 0 }}</strong>
                                                    </div>
                                                    <div>
                                                        <span class="badge bg-danger-subtle text-danger">⬤ Rejected</span>
                                                        <strong class="ms-1">{{ $pie_chart_data['status']['data'][2] ?? 0 }}</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <hr class="d-xxl-none border-light m-0" />
                                    </div>
                                    <div class="col-xxl-4 col-xl-6">
                                        <div class="p-4 border-end border-dashed">
                                            <h4 class="card-title mb-1">Application Type Distribution</h4>
                                            <p class="text-muted fs-xs mb-4">
                                                Individual vs Business applications
                                            </p>
                                            <div class="row mt-4">
                                                <div class="col-lg-12">
                                                    <div style="height: 280px">
                                                        <canvas id="type-pie-chart" 
                                                                data-labels='@json($pie_chart_data["type"]["labels"])'
                                                                data-data='@json($pie_chart_data["type"]["data"])'
                                                                data-colors='@json($pie_chart_data["type"]["colors"])'>
                                                        </canvas>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-3 text-center">
                                                <div class="d-flex justify-content-center gap-3">
                                                    <div>
                                                        <span class="badge bg-primary-subtle text-primary">⬤ Individual</span>
                                                        <strong class="ms-1">{{ $pie_chart_data['type']['data'][0] ?? 0 }}</strong>
                                                    </div>
                                                    <div>
                                                        <span class="badge bg-orange-subtle text-orange">⬤ Business</span>
                                                        <strong class="ms-1">{{ $pie_chart_data['type']['data'][1] ?? 0 }}</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xxl-5 order-xl-3 order-xxl-1">
                                        <div class="px-4 py-4">
                                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                                <h4 class="card-title mb-0">Applications Analytics (Last 12 Months)</h4>
                                                <div class="d-flex gap-2">
                                                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshDashboard()">
                                                        <i data-lucide="refresh-cw"></i> Refresh
                                                    </button>
                                                    <a class="btn btn-sm btn-primary" href="{{ route('admin.registrations.export') }}">
                                                        <i data-lucide="download"></i> Export Report
                                                    </a>
                                                </div>
                                            </div>
                                            <div dir="ltr">
                                                <div class="mt-3" style="height: 350px">
                                                    <canvas id="sales-analytics-chart"
                                                            data-labels='@json($chart_data["labels"])'
                                                            data-total='@json($chart_data["total"])'
                                                            data-approved='@json($chart_data["approved"])'
                                                            data-rejected='@json($chart_data["rejected"])'>
                                                    </canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Applications Tables -->
                <div class="row g-4">
                    <div class="col-xxl-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent d-flex justify-content-between align-items-center border-dashed">
                                <h4 class="card-title mb-0">
                                    <i data-lucide="user"></i> Recent Individual Applications
                                </h4>
                                <div class="d-flex gap-2">
                                    <a class="btn btn-sm btn-primary" href="{{ route('admin.registrations.export', ['type' => 'individual']) }}">
                                        <i data-lucide="download"></i> Export
                                    </a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-centered table-custom table-sm table-nowrap table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Ref No</th>
                                                <th>Applicant Name</th>
                                                <th>Business Type</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($recent_individual_applications ?? [] as $app)
                                            <tr class="table-row-clickable" onclick="window.location.href='{{ route('admin.registrations.show', $app['id']) }}'">
                                                <td>
                                                    <span class="fw-medium">{{ $app['reference_no'] }}</span>
                                                </td>
                                                <td>{{ $app['name'] }}</td>
                                                <td>{{ $app['business_type'] }}</td>
                                                <td>{!! $app['status_badge'] !!}</td>
                                                <td>
                                                    <small>{{ $app['date_human'] }}</small>
                                                    <br>
                                                    <small class="text-muted">{{ $app['date'] }}</small>
                                                </td>
                                                <td onclick="event.stopPropagation()">
                                                    <a href="{{ route('admin.registrations.show', $app['id']) }}" 
                                                       class="btn btn-sm btn-soft-primary">
                                                        <i data-lucide="eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <i data-lucide="inbox" class="mb-2"></i>
                                                    <p class="mb-0">No individual applications found</p>
                                                </td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="text-center">
                                    <a href="{{ route('admin.registrations.index', ['type' => 'individual']) }}" class="text-muted">
                                        View All Individual Applications <i data-lucide="arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xxl-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent d-flex justify-content-between align-items-center border-dashed">
                                <h4 class="card-title mb-0">
                                    <i data-lucide="building"></i> Recent Business Applications
                                </h4>
                                <div class="d-flex gap-2">
                                    <a class="btn btn-sm btn-primary" href="{{ route('admin.registrations.export', ['type' => 'business']) }}">
                                        <i data-lucide="download"></i> Export
                                    </a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-centered table-custom table-sm table-nowrap table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Ref No</th>
                                                <th>Business Name</th>
                                                <th>Registration No</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($recent_business_applications ?? [] as $app)
                                            <tr class="table-row-clickable" onclick="window.location.href='{{ route('admin.registrations.show', $app['id']) }}'">
                                                <td>
                                                    <span class="fw-medium">{{ $app['reference_no'] }}</span>
                                                </td>
                                                <td>{{ $app['business_name'] }}</td>
                                                <td>{{ $app['registration_number'] ?? 'N/A' }}</td>
                                                <td>{!! $app['status_badge'] !!}</td>
                                                <td>
                                                    <small>{{ $app['date_human'] }}</small>
                                                    <br>
                                                    <small class="text-muted">{{ $app['date'] }}</small>
                                                </td>
                                                <td onclick="event.stopPropagation()">
                                                    <a href="{{ route('admin.registrations.show', $app['id']) }}" 
                                                       class="btn btn-sm btn-soft-primary">
                                                        <i data-lucide="eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <i data-lucide="inbox" class="mb-2"></i>
                                                    <p class="mb-0">No business applications found</p>
                                                </td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="text-center">
                                    <a href="{{ route('admin.registrations.index', ['type' => 'business']) }}" class="text-muted">
                                        View All Business Applications <i data-lucide="arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @include("shared.partials.footer")
        </div>
    </div>

    @include("shared.partials.customizer") 
    @include("shared.partials.footer-scripts")
@endsection

@section("scripts")
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let statusChart, typeChart, lineChart;

        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            initializeCounters();
            initializeAutoRefresh();
            loadRecentActivity();
        });

        function initializeCharts() {
            // Status Pie Chart
            const statusCanvas = document.getElementById('status-pie-chart');
            if (statusCanvas) {
                const labels = JSON.parse(statusCanvas.dataset.labels || '[]');
                const data = JSON.parse(statusCanvas.dataset.data || '[]');
                const colors = JSON.parse(statusCanvas.dataset.colors || '[]');
                
                statusChart = new Chart(statusCanvas, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: colors,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        const total = data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Type Pie Chart
            const typeCanvas = document.getElementById('type-pie-chart');
            if (typeCanvas) {
                const labels = JSON.parse(typeCanvas.dataset.labels || '[]');
                const data = JSON.parse(typeCanvas.dataset.data || '[]');
                const colors = JSON.parse(typeCanvas.dataset.colors || '[]');
                
                typeChart = new Chart(typeCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: colors,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        const total = data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Line Chart
            const lineCanvas = document.getElementById('sales-analytics-chart');
            if (lineCanvas) {
                const labels = JSON.parse(lineCanvas.dataset.labels || '[]');
                const total = JSON.parse(lineCanvas.dataset.total || '[]');
                const approved = JSON.parse(lineCanvas.dataset.approved || '[]');
                const rejected = JSON.parse(lineCanvas.dataset.rejected || '[]');
                
                lineChart = new Chart(lineCanvas, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Total Applications',
                                data: total,
                                borderColor: '#3b76e1',
                                backgroundColor: 'rgba(59, 118, 225, 0.1)',
                                tension: 0.4,
                                fill: true,
                                borderWidth: 2
                            },
                            {
                                label: 'Approved',
                                data: approved,
                                borderColor: '#2ab57d',
                                backgroundColor: 'rgba(42, 181, 125, 0.1)',
                                tension: 0.4,
                                fill: true,
                                borderWidth: 2
                            },
                            {
                                label: 'Rejected',
                                data: rejected,
                                borderColor: '#fd625e',
                                backgroundColor: 'rgba(253, 98, 94, 0.1)',
                                tension: 0.4,
                                fill: true,
                                borderWidth: 2
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        let value = context.parsed.y;
                                        return `${label}: ${value.toLocaleString()}`;
                                    }
                                }
                            },
                            legend: {
                                position: 'bottom'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }
        }

        function initializeCounters() {
            const counters = document.querySelectorAll('.counter');
            counters.forEach(counter => {
                const target = parseInt(counter.dataset.target);
                if (target > 0) {
                    animateValue(counter, 0, target, 1000);
                }
            });
        }

        function animateValue(element, start, end, duration) {
            const range = end - start;
            const increment = range / (duration / 16);
            let current = start;
            const timer = setInterval(() => {
                current += increment;
                if (current >= end) {
                    clearInterval(timer);
                    current = end;
                }
                element.textContent = Math.floor(current).toLocaleString();
            }, 16);
        }

        function refreshDashboard() {
            const refreshBtn = document.querySelector('[onclick="refreshDashboard()"]');
            const originalHtml = refreshBtn.innerHTML;
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = '<i data-lucide="loader"></i> Refreshing...';
            lucide.createIcons();
            
            fetch('{{ route("dashboard.refresh") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                setTimeout(() => {
                    location.reload();
                }, 1000);
            });
        }

        function initializeAutoRefresh() {
            // Auto-refresh data every 5 minutes
            setInterval(() => {
                fetch('{{ route("dashboard.stats") }}', {
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateStats(data.data);
                    }
                })
                .catch(error => console.error('Auto-refresh error:', error));
            }, 300000);
        }

        function updateStats(stats) {
            const statElements = {
                new_applications: document.querySelector('.counter[data-target]'),
                approved_applications: document.querySelectorAll('.counter')[1],
                rejected_applications: document.querySelectorAll('.counter')[2],
                total_applications: document.querySelectorAll('.counter')[3]
            };
            
            if (stats.new_applications !== undefined && statElements.new_applications) {
                const current = parseInt(statElements.new_applications.textContent.replace(/,/g, '')) || 0;
                animateValue(statElements.new_applications, current, stats.new_applications, 500);
            }
        }

function loadRecentActivity() {
    // Function disabled - route not defined yet
    console.log('Recent activity loading disabled');
}

        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
    @vite(["resources/js/pages/custom-table.js"])
    @vite(["resources/js/pages/dashboard-ecommerce.js"])
@endsection