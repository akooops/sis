<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Article;
use App\Models\Banner;
use App\Models\Event;
use App\Models\Grade;
use App\Models\Page;
use App\Models\Program;
use App\Models\VisitService;
use App\Services\IndexService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

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
            'status' => 'published'
        ])->first();

        if(!$page) abort(404);

        $banners = Banner::orderBy('order')->get();
        $programs = Program::latest()->get();
        $articles = Article::latest()->where('status', 'published')->limit(6)->get();
        $albums = Album::latest()->where('status', 'published')->limit(6)->get();

        return view('index', compact('page', 'banners', 'programs', 'articles', 'albums'));
    }

    public function page(Request $request, $slug = null)
    {
        $page = Page::where([
            'slug' => $slug,
            'status' => 'published'
        ])->first();
        
        if(!$page) abort(404);

        return view('page', compact('page'));
    }

    public function visits(Request $request)
    {

        $page = Page::where([
            'slug' => 'visits',
            'status' => 'published'
        ])->first();

        if(!$page) abort(404);

        $visitServices = VisitService::orderBy('order')->latest()->get();

        return view('visits', [
            'page' => $page,
            'visitServices' => $visitServices
        ]);
    }

    public function inquiries(Request $request)
    {
        $page = Page::where([
            'slug' => 'inquiries',
            'status' => 'published'
        ])->first();

        if(!$page) abort(404);

        return view('inquiries', [
            'page' => $page
        ]);
    }

    public function contact(Request $request)
    {
        $page = Page::where([
            'slug' => 'contact',
            'status' => 'published'
        ])->first();

        if(!$page) abort(404);

        return view('contact', [
            'page' => $page
        ]);
    }

    public function articles(Request $request)
    {
        $page = Page::where([
            'slug' => 'articles',
            'status' => 'published'
        ])->first();

        if(!$page) abort(404);

        $pageNumber = $this->indexService->checkPageIfNull($request->query('page', 1));
        $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

        $articles = Article::latest()->where('status', 'published');;

        if ($search) {
            $articles->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $articles = $articles->paginate('10', ['*'], 'page', $pageNumber);

        $popularArticles = Article::inRandomOrder()->limit(6)->where('status', 'published')->whereNotIn('id', $articles->pluck('id'))->get();

        return view('articles', [
            'page' => $page,
            'articles' => $articles,
            'popularArticles' => $popularArticles,
            'pagination' => $this->indexService->handlePagination($articles)
        ]);
    }

    public function article(Request $request, $slug = null)
    {
        $article = Article::where([
            'slug' => $slug,
            'status' => 'published'
        ])->first();
        
        if(!$article) abort(404);

        $popularArticles = Article::inRandomOrder()->limit(6)->where('status', 'published')->whereNotIn('id', [$article->id])->get();

        return view('article', compact('article', 'popularArticles'));
    }

    public function albums(Request $request)
    {
        $page = Page::where([
            'slug' => 'albums',
            'status' => 'published'
        ])->first();

        if(!$page) abort(404);

        $pageNumber = $this->indexService->checkPageIfNull($request->query('page', 1));
        $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

        $albums = Album::latest()->where('status', 'published');

        if ($search) {
            $albums->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $albums = $albums->paginate('10', ['*'], 'page', $pageNumber);

        return view('albums', [
            'page' => $page,
            'albums' => $albums,
            'pagination' => $this->indexService->handlePagination($albums)
        ]);
    }

    public function album(Request $request, $slug)
    {
        $album = Album::where([
            'slug' => $slug,
            'status' => 'published'
        ])->first();
        
        if(!$album) abort(404);

        return view('album', compact('album'));
    }

    public function events(Request $request)
    {
        $page = Page::where([
            'slug' => 'events',
            'status' => 'published'
        ])->first();

        if(!$page) abort(404);

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
            'status' => 'published'
        ])->first();
        
        if(!$event) abort(404);

        return view('event', compact('event'));
    }

    public function program(Request $request, $slug)
    {
        $program = Program::where([
            'slug' => $slug        
        ])->first();
        
        if(!$program) abort(404);

        $programs = Program::get();
        return view('program', compact('program', 'programs'));
    }

    public function grade(Request $request, $slug)
    {
        $grade = Grade::where([
            'slug' => $slug        
        ])->first();
        
        if(!$grade) abort(404);

        $grades = Grade::where('program_id', $grade->program_id)->get();

        return view('grade', compact('grade', 'grades'));
    }
}
