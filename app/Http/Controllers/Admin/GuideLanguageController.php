<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class GuideLanguageController extends Controller
{
    public function index(): View
    {
        if (! Schema::hasTable('guide_language')) {
            return view('admin.guide-languages.setup-required');
        }

        $languages = Language::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $guides = User::query()
            ->whereHas('roles', function ($query) {
                $query->where('slug', Roles::GUIDE);
            })
            ->with('guideLanguages')
            ->orderBy('name')
            ->get()
            ->map(fn (User $guide) => $this->sortGuideLanguages($guide));

        return view('admin.guide-languages.index', compact('guides', 'languages'));
    }

    private function sortGuideLanguages(User $guide): User
    {
        /** @var Collection<int, Language> $guideLanguages */
        $guideLanguages = $guide->guideLanguages
            ->sortBy([
                ['sort_order', 'asc'],
                ['name', 'asc'],
            ])
            ->values();

        $guide->setRelation('guideLanguages', $guideLanguages);

        return $guide;
    }
}
