{{-- Lägg in detta i layouts.app, gärna före @yield('content') eller i layoutens CSS-del --}}

<style>
    .stats-filter-card,
    .stats-card,
    .chart-card,
    .table-card {
        background: #ffffff;
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        border: 1px solid #eef2f7;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
    }

    .stats-card {
        padding: 1.25rem;
        position: relative;
        overflow: hidden;
    }

    .stats-card::after {
        content: '';
        position: absolute;
        right: -24px;
        top: -24px;
        width: 90px;
        height: 90px;
        border-radius: 999px;
        background: rgba(59, 130, 246, 0.08);
    }

    .stats-card.accent-men::after { background: rgba(59, 130, 246, 0.12); }
    .stats-card.accent-women::after { background: rgba(236, 72, 153, 0.12); }
    .stats-card.accent-youth::after { background: rgba(234, 179, 8, 0.14); }
    .stats-card.accent-children::after { background: rgba(34, 197, 94, 0.14); }

    .stats-label {
        font-size: 0.9rem;
        color: #64748b;
        margin-bottom: 0.5rem;
    }

    .stats-value {
        font-size: 2rem;
        font-weight: 700;
        color: #0f172a;
    }

    .chart-card,
    .table-card {
        min-height: 100%;
    }

    @media (max-width: 992px) {
        .stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 576px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
