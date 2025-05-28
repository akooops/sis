<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Article;
use App\Models\Banner;
use App\Models\Page;
use App\Models\Program;
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

        $popularArticles = Article::inRandomOrder()->where('status', 'published')->whereNotIn('id', $articles->pluck('id'))->get();

        return view('articles', [
            'page' => $page,
            'articles' => $articles,
            'popularArticles' => $popularArticles,
            'pagination' => $this->indexService->handlePagination($articles)
        ]);
    }

    public function article(Request $request)
    {
        return view('article');
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

    public function album(Request $request)
    {
        return view('album');
    }

    public function events(Request $request)
    {
        return view('events');
    }

    public function event(Request $request)
    {
        return view('event');
    }

    public function program(Request $request)
    {
        return view('program');
    }

    public function grade(Request $request)
    {
        return view('grade');
    }
}
