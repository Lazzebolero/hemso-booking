<?php

namespace Tests\Unit\Support;

use App\Support\VisitorDogSupport;
use Illuminate\Http\Request;
use Tests\TestCase;

class VisitorDogSupportTest extends TestCase
{
    public function test_date_range_from_request_swaps_inverted_dates(): void
    {
        $request = Request::create('/', 'GET', [
            'from_date' => '2026-06-10',
            'to_date' => '2026-06-01',
        ]);

        [$from, $to] = VisitorDogSupport::dateRangeFromRequest($request);

        $this->assertSame('2026-06-01', $from->toDateString());
        $this->assertSame('2026-06-10', $to->toDateString());
    }

    public function test_date_filter_query_from_request_omits_empty_values(): void
    {
        $request = Request::create('/', 'GET', [
            'from_date' => '2026-06-01',
            'to_date' => '',
        ]);

        $this->assertSame(
            ['from_date' => '2026-06-01'],
            VisitorDogSupport::dateFilterQueryFromRequest($request),
        );
    }

    public function test_back_navigation_returns_gallery_when_requested(): void
    {
        $request = Request::create('/host/visitor-dogs/1', 'GET', [
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-07',
            'return' => VisitorDogSupport::RETURN_GALLERY,
        ]);

        $backNav = VisitorDogSupport::backNavigation($request, 'host');

        $this->assertSame('Till galleriet', $backNav['label']);
        $this->assertStringContainsString('visitor-dogs/gallery', $backNav['url']);
        $this->assertStringContainsString('from_date=2026-06-01', $backNav['url']);
        $this->assertStringContainsString('to_date=2026-06-07', $backNav['url']);
    }

    public function test_link_query_for_return_includes_filter_and_return_key(): void
    {
        $request = Request::create('/', 'GET', [
            'from_date' => '2026-06-02',
            'to_date' => '2026-06-02',
        ]);

        $this->assertSame(
            [
                'from_date' => '2026-06-02',
                'to_date' => '2026-06-02',
                'return' => VisitorDogSupport::RETURN_INDEX,
            ],
            VisitorDogSupport::linkQueryForReturn($request, VisitorDogSupport::RETURN_INDEX),
        );
    }
}
