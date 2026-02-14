<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\AchievementCategory;
use App\Models\Album;
use App\Models\Article;
use App\Models\Banner;
use App\Models\Calendar;
use App\Models\Event;
use App\Models\Form;
use App\Models\Grade;
use App\Models\JobPosting;
use App\Models\Newsletter;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Program;
use App\Models\VisitService;
use App\Services\IndexService;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    protected $indexService;

    public function __construct(IndexService $indexService)
    {
        $this->indexService = $indexService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $page = Page::where([
            'slug' => 'home',
            'status' => 'published',
        ])->first();

        if (! $page) {
            abort(404);
        }

        $banners = Banner::orderBy('order')->get();
        $programs = getPrograms();
        $articles = Article::latest()->where('status', 'published')->limit(6)->get();
        $albums = Album::latest()->where('status', 'published')->limit(6)->get();
        $achievements = Achievement::with('category')
            ->where('status', 'published')
            ->orderBy('achievement_date', 'desc')
            ->limit(6)
            ->get();
        $partners = Partner::latest()->get();

        return view('index', compact('page', 'banners', 'programs', 'articles', 'albums', 'achievements', 'partners'));
    }

    public function page(Request $request, $slug = null)
    {
        $page = Page::where([
            'slug' => $slug,
            'status' => 'published',
        ])->first();

        if (! $page) {
            abort(404);
        }

        return view('page', compact('page'));
    }

    public function visits(Request $request)
    {

        $page = Page::where([
            'slug' => 'visits',
            'status' => 'published',
        ])->first();

        if (! $page) {
            abort(404);
        }

        $visitServices = VisitService::orderBy('order')->latest()->get();

        return view('visits', [
            'page' => $page,
            'visitServices' => $visitServices,
        ]);
    }

    public function inquiries(Request $request)
    {
        $page = Page::where([
            'slug' => 'inquiries',
            'status' => 'published',
        ])->first();

        if (! $page) {
            abort(404);
        }

        return view('inquiries', [
            'page' => $page,
        ]);
    }

    public function contact(Request $request)
    {
        $page = Page::where([
            'slug' => 'contact',
            'status' => 'published',
        ])->first();

        if (! $page) {
            abort(404);
        }

        return view('contact', [
            'page' => $page,
        ]);
    }

    public function jobs(Request $request)
    {
        $page = Page::where([
            'slug' => 'jobs',
            'status' => 'published',
        ])->first();

        if (! $page) {
            abort(404);
        }

        $pageNumber = $this->indexService->checkPageIfNull($request->query('page', 1));
        $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

        $jobs = JobPosting::latest()
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('application_deadline')
                    ->orWhere('application_deadline', '>', now());
            });

        if ($search) {
            $jobs->where(function ($query) use ($search) {
                $query->where('id', $search)
                    ->orWhere('name', 'like', '%'.$search.'%');
            });
        }

        $jobs = $jobs->paginate('10', ['*'], 'page', $pageNumber);

        return view('jobs', [
            'page' => $page,
            'jobs' => $jobs,
            'pagination' => $this->indexService->handlePagination($jobs),
        ]);
    }

    public function job(Request $request, $slug = null)
    {
        $job = JobPosting::where([
            'slug' => $slug,
            'status' => 'published',
        ])->first();

        if (! $job) {
            abort(404);
        }

        return view('job', compact('job'));
    }

    public function articles(Request $request)
    {
        $page = Page::where([
            'slug' => 'articles',
            'status' => 'published',
        ])->first();

        if (! $page) {
            abort(404);
        }

        $pageNumber = $this->indexService->checkPageIfNull($request->query('page', 1));
        $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

        $articles = Article::latest()->where('status', 'published');

        if ($search) {
            $articles->where(function ($query) use ($search) {
                $query->where('id', $search)
                    ->orWhere('name', 'like', '%'.$search.'%');
            });
        }

        $articles = $articles->paginate('10', ['*'], 'page', $pageNumber);

        $popularArticles = Article::inRandomOrder()->limit(6)->where('status', 'published')->whereNotIn('id', $articles->pluck('id'))->get();

        return view('articles', [
            'page' => $page,
            'articles' => $articles,
            'popularArticles' => $popularArticles,
            'pagination' => $this->indexService->handlePagination($articles),
        ]);
    }

    public function article(Request $request, $slug = null)
    {
        $article = Article::where([
            'slug' => $slug,
            'status' => 'published',
        ])->first();

        if (! $article) {
            abort(404);
        }

        $popularArticles = Article::inRandomOrder()->limit(6)->where('status', 'published')->whereNotIn('id', [$article->id])->get();

        return view('article', compact('article', 'popularArticles'));
    }

    public function albums(Request $request)
    {
        $page = Page::where([
            'slug' => 'albums',
            'status' => 'published',
        ])->first();

        if (! $page) {
            abort(404);
        }

        $pageNumber = $this->indexService->checkPageIfNull($request->query('page', 1));
        $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

        $albums = Album::latest()->where('status', 'published');

        if ($search) {
            $albums->where(function ($query) use ($search) {
                $query->where('id', $search)
                    ->orWhere('name', 'like', '%'.$search.'%');
            });
        }

        $albums = $albums->paginate('10', ['*'], 'page', $pageNumber);

        return view('albums', [
            'page' => $page,
            'albums' => $albums,
            'pagination' => $this->indexService->handlePagination($albums),
        ]);
    }

    public function album(Request $request, $slug)
    {
        $album = Album::where([
            'slug' => $slug,
            'status' => 'published',
        ])->first();

        if (! $album) {
            abort(404);
        }

        return view('album', compact('album'));
    }

    public function events(Request $request)
    {
        $page = Page::where([
            'slug' => 'events',
            'status' => 'published',
        ])->first();

        if (! $page) {
            abort(404);
        }

        $events = Event::latest()->where('status', 'published')->get();

        return view('events', [
            'page' => $page,
            'events' => $events,
        ]);
    }

    public function event(Request $request, $slug)
    {
        $event = Event::where([
            'slug' => $slug,
            'status' => 'published',
        ])->first();

        if (! $event) {
            abort(404);
        }

        return view('event', compact('event'));
    }

    public function program(Request $request, $slug)
    {
        $program = Program::where([
            'slug' => $slug,
        ])->first();

        if (! $program) {
            abort(404);
        }

        $programs = Program::get();

        return view('program', compact('program', 'programs'));
    }

    public function grade(Request $request, $slug)
    {
        $grade = Grade::where([
            'slug' => $slug,
        ])->first();

        if (! $grade) {
            abort(404);
        }

        $grades = Grade::where('program_id', $grade->program_id)->get();

        return view('grade', compact('grade', 'grades'));
    }

    public function forms(Request $request)
    {
        $page = Page::where([
            'slug' => 'forms',
            'status' => 'published',
        ])->first();

        if (! $page) {
            abort(404);
        }

        $forms = Form::get();

        return view('forms', [
            'page' => $page,
            'forms' => $forms,
        ]);
    }

    public function newsletters(Request $request)
    {
        $page = Page::where([
            'slug' => 'newsletters',
            'status' => 'published',
        ])->first();

        if (! $page) {
            abort(404);
        }

        $newsletters = Newsletter::get();

        return view('newsletters', [
            'page' => $page,
            'newsletters' => $newsletters,
        ]);
    }

    public function guidelines(Request $request)
    {
        $page = Page::where([
            'slug' => 'guidelines',
            'status' => 'published',
        ])->first();

        if (! $page) {
            abort(404);
        }

        $grades = Grade::with('files')->orderBy('order', 'asc')->get();

        return view('guidelines', [
            'page' => $page,
            'grades' => $grades,
        ]);
    }

    public function calendars(Request $request)
    {
        $page = Page::where([
            'slug' => 'calendars',
            'status' => 'published',
        ])->first();

        if (! $page) {
            abort(404);
        }

        $calendars = Calendar::get();

        return view('calendars', [
            'page' => $page,
            'calendars' => $calendars,
        ]);
    }

    public function achievements(Request $request)
    {
        $page = Page::where([
            'slug' => 'achievements',
            'status' => 'published',
        ])->first();

        if (! $page) {
            abort(404);
        }

        $categories = AchievementCategory::all();
        $achievementsQuery = Achievement::where('status', 'published')->orderBy('achievement_date', 'desc');

        $category = $this->indexService->checkIfEmpty($request->query('category'));
        $year = $this->indexService->checkIfEmpty($request->query('year'));
        $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

        // Apply filters
        if ($category) {
            $achievementsQuery->whereHas('category', function ($query) use ($category) {
                $query->where('slug', $category);
            });
        }

        if ($year) {
            $achievementsQuery->whereYear('achievement_date', $year);
        }

        if ($search) {
            $achievementsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhereHas('category', function ($q) use ($search) {
                        $q->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        $achievements = $achievementsQuery->get();

        // Group achievements by year
        $achievementsByYear = $achievements->groupBy(function ($achievement) {
            return \Carbon\Carbon::parse($achievement->achievement_date)->format('Y');
        })->sortKeysDesc();

        $years = Achievement::selectRaw('YEAR(achievement_date) as year')
            ->where('status', 'published')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return view('achievements', [
            'page' => $page,
            'categories' => $categories,
            'achievements' => $achievements,
            'achievementsByYear' => $achievementsByYear,
            'years' => $years,
        ]);
    }
}
