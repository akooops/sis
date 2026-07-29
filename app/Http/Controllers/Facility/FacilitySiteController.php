<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\Article;
use App\Models\Event;
use App\Models\Page;
use App\Services\IndexService;
use Illuminate\Http\Request;

class FacilitySiteController extends Controller
{
    protected $indexService;

    public function __construct(IndexService $indexService)
    {
        $this->indexService = $indexService;
    }

    public function home(Request $request)
    {
        $facility = $request->attributes->get('facility');

        $articles = $facility->articles()->latest()->where('status', 'published')->limit(3)->get();
        $albums = $facility->albums()->latest()->where('status', 'published')->limit(3)->get();
        $events = $facility->events()->latest()->where('status', 'published')->limit(3)->get();

        return view('facility.home', compact('facility', 'articles', 'albums', 'events'));
    }

    public function articles(Request $request)
    {
        $facility = $request->attributes->get('facility');

        $pageNumber = $this->indexService->checkPageIfNull($request->query('page', 1));
        $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

        $articles = $facility->articles()->latest()->where('status', 'published');

        if ($search) {
            $articles->where(function ($query) use ($search) {
                $query->where('id', $search)
                    ->orWhere('name', 'like', '%'.$search.'%');
            });
        }

        $articles = $articles->paginate(9, ['*'], 'page', $pageNumber);

        return view('facility.articles', [
            'facility' => $facility,
            'articles' => $articles,
            'pagination' => $this->indexService->handlePagination($articles),
        ]);
    }

    public function article(Request $request, $slug = null)
    {
        $facility = $request->attributes->get('facility');

        $article = $facility->articles()
            ->where(['slug' => $slug, 'status' => 'published'])
            ->first();

        if (! $article) {
            abort(404);
        }

        $otherArticles = $facility->articles()
            ->where('status', 'published')
            ->whereNotIn('id', [$article->id])
            ->latest()
            ->limit(3)
            ->get();

        return view('facility.article', compact('facility', 'article', 'otherArticles'));
    }

    public function albums(Request $request)
    {
        $facility = $request->attributes->get('facility');

        $pageNumber = $this->indexService->checkPageIfNull($request->query('page', 1));

        $albums = $facility->albums()->latest()->where('status', 'published')
            ->paginate(9, ['*'], 'page', $pageNumber);

        return view('facility.albums', [
            'facility' => $facility,
            'albums' => $albums,
            'pagination' => $this->indexService->handlePagination($albums),
        ]);
    }

    public function album(Request $request, $slug = null)
    {
        $facility = $request->attributes->get('facility');

        $album = $facility->albums()
            ->where(['slug' => $slug, 'status' => 'published'])
            ->first();

        if (! $album) {
            abort(404);
        }

        return view('facility.album', compact('facility', 'album'));
    }

    public function events(Request $request)
    {
        $facility = $request->attributes->get('facility');

        $events = $facility->events()->latest()->where('status', 'published')->get();

        return view('facility.events', compact('facility', 'events'));
    }

    public function event(Request $request, $slug = null)
    {
        $facility = $request->attributes->get('facility');

        $event = $facility->events()
            ->where(['slug' => $slug, 'status' => 'published'])
            ->first();

        if (! $event) {
            abort(404);
        }

        return view('facility.event', compact('facility', 'event'));
    }

    public function contact(Request $request)
    {
        $facility = $request->attributes->get('facility');

        return view('facility.contact', compact('facility'));
    }

    public function reserve(Request $request)
    {
        $facility = $request->attributes->get('facility');

        $timeSlots = $facility->upcomingTimeSlots()->orderBy('starts_at')->get();

        return view('facility.reserve', compact('facility', 'timeSlots'));
    }

    public function page(Request $request, $slug = null)
    {
        $facility = $request->attributes->get('facility');

        $page = $facility->pages()
            ->where(['slug' => $slug, 'status' => 'published'])
            ->first();

        if (! $page) {
            abort(404);
        }

        return view('facility.page', compact('facility', 'page'));
    }
}
