<?php

namespace App\Http\Controllers;

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
        return view('index');
    }

    public function page(Request $request)
    {
        return view('page');
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

    public function grades(Request $request)
    {
        return view('grades');
    }

    public function grade(Request $request)
    {
        return view('grade');
    }
}
