<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Article;
use App\Models\Banner;
use App\Models\Page;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class PagesController extends Controller
{
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
        return view('articles');
    }

    public function article(Request $request)
    {
        return view('article');
    }

    public function albums(Request $request)
    {
        return view('albums');
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
