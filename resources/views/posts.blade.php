@extends('layouts.master')

@section('title', 'Les Articles')
@section('description', "Explorez notre compilation d'articles variés pour découvrir des perspectives riches et informatives sur un large éventail de sujets. Avec une qualité de contenu garantie, plongez-vous dans une expérience de lecture captivante et enrichissante.")
@section('canonical', route('posts'))

@section('css')
@endsection

@section('content')
<section class="wrapper bg-secondary">
    <div class="container pt-10 pb-19 pt-md-14 pb-md-20 text-center">
        <div class="row">
            <div class="col-8 mx-auto">
                <h1 class="display-1 mb-3">
                    Nos Articles
                </h1>
                <p class="fs-16 text-gray px-2">
                    Explorez notre compilation d'articles variés pour découvrir des perspectives riches et informatives sur un large éventail de sujets. Avec une qualité de contenu garantie, plongez-vous dans une expérience de lecture captivante et enrichissante.
                </p>

                <div class="form-floating mt-4">
                    <form action="{{route('posts')}}">
                        <input id="search" type="text" name="search" value="{{request()->get('search')}}" placeholder="Rechercher ici..." class="form-control form-primary">
                    </form>                               
                </div>
            </div>
            <!-- /column -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container -->
</section>
<!-- /section -->
<section class="wrapper">
    <div class="container pb-14 pb-md-16">
        <div class="row">
            <div class="col-lg-10 mx-auto">

                <div class="blog classic-view mt-n17">                    
                    <article class="post">
                        <div class="card bg-secondary">
                            <a href="{{route('post', ['slug' => $selectedPost->slug])}}" class="rounded"> 
                                <img src="{{$selectedPost->image->fullpath}}" alt="{{$selectedPost->title}}" style="width: 100%; height: 500px; object-fit: cover"/>
                            </a>

                            <div class="card-body">
                                <div class="post-header">
                                    <div class="post-category text-line">
                                        <a class="hover link-primary" rel="category">
                                            {{$selectedPost->category->name}}
                                        </a>
                                    </div>
                                    <!-- /.post-category -->
                                    <h2 class="post-title h2 mt-1 mb-3">
                                        <a class="text-white clamp-text-2" href="{{route('post', ['slug' => $selectedPost->slug])}}">
                                            {{$selectedPost->title}}
                                        </a>
                                    </h2>
                                </div>
                                <!-- /.post-header -->
                                <div class="post-content">
                                    <p class="text-gray">
                                        {{$selectedPost->overview}}
                                    </p>
                                </div>
                                <!-- /.post-content -->
                            </div>
                            <!--/.card-body -->
                            <div class="card-footer">
                                <ul class="post-meta d-flex mb-0">
                                    <li class="post-date">
                                        <i class="uil uil-user"></i>
                                        <span>Par {{$selectedPost->user->fullname}}</span>
                                    </li>
            
                                    <li class="post-date">
                                        <i class="uil uil-calendar-alt"></i>
                                        <span>{{$selectedPost->getFormattedCreatedAt()}}</span>
                                    </li>
            
                                    <li class="post-comments">
                                        <i class="uil uil-eye fs-15"></i> 
                                        {{$selectedPost->visited}}
                                    </li>
                                </ul>
                                <!-- /.post-meta -->
                            </div>
                            <!-- /.card-footer -->
                        </div>
                        <!-- /.card -->
                    </article>
                    <!-- /.post -->
                </div>
                <!-- /.blog -->

                <div class="blog grid grid-view text-left">
                    <div class="row gx-md-8 gy-8 mb-8">
                        @foreach ($posts as $post)                           
                            <article class="item post col-md-6">
                                <div class="card bg-secondary">
                                    <a href="{{route('post', ['slug' => $post->slug])}}" class="rounded"> 
                                        <img src="{{$post->image->fullpath}}" alt="{{$post->title}}" style="width: 100%; height: 400px; object-fit: cover"/>
                                    </a>

                                    <div class="card-body">
                                        <div class="post-header">
                                            <div class="post-category text-line">
                                                <a class="hover link-primary" rel="category">
                                                    {{$post->category->name}}
                                                </a>
                                            </div>
                                            <!-- /.post-category -->
                                            <h2 class="post-title h3 mt-1 mb-3">
                                                <a class="text-white clamp-text-2" href="{{route('post', ['slug' => $post->slug])}}">
                                                    {{$post->title}}
                                                </a>
                                            </h2>
                                        </div>
                                        <!-- /.post-header -->
                                        <div class="post-content">
                                            <p class="text-gray fs-15 lh-lg post-content clamp-text-3">
                                                {{$post->overview}}
                                            </p>
                                        </div>
                                        <!-- /.post-content -->
                                    </div>
                                    <!--/.card-body -->
                                    <div class="card-footer">
                                        <ul class="post-meta d-flex mb-0">
                                            <li class="post-date">
                                                <i class="uil uil-calendar-alt"></i>
                                                <span>{{$post->getFormattedCreatedAt()}}</span>
                                            </li>
    
                                            <li class="post-comments">
                                                <i class="uil uil-eye fs-15"></i> 
                                                {{$post->visited}}
                                            </li>
                                        </ul>
                                        <!-- /.post-meta -->
                                    </div>
                                    <!-- /.card-footer -->
                                </div>
                                <!-- /.card -->
                            </article>
                            <!-- /.post -->
                        @endforeach
                    </div>
                    <!-- /.row -->
                </div>
                <!-- /.blog -->
                <nav class="d-flex" aria-label="pagination">
                    <ul class="pagination">
                        <li class="page-item {{is_null($pagination["prev_page"]) ? 'disabled' : ''}}">
                            <a class="page-link" href="{{ route('posts', array_merge(['page' => $pagination["prev_page"]], request()->only('search'))) }}" aria-label="Previous">
                            <span aria-hidden="true"><i class="uil uil-arrow-left"></i></span>
                            </a>
                        </li>

                        @foreach($pagination["pages"] as $page)
                            <li class="page-item">
                                <a class="page-link {{($page == $pagination['current_page']) ? 'active' : ''}}" href="{{ route('posts', array_merge(['page' => $page], request()->only('search'))) }}">
                                    {{$page}}
                                </a>
                            </li>
                        @endforeach

                        <li class="page-item {{is_null($pagination["next_page"]) ? 'disabled' : ''}}">
                            <a class="page-link" href="{{ route('posts', array_merge(['page' => $pagination["next_page"]], request()->only('search'))) }}" aria-label="Next">
                            <span aria-hidden="true"><i class="uil uil-arrow-right"></i></span>
                            </a>
                        </li>
                    </ul>
                    <!-- /.pagination -->
                </nav>
                <!-- /nav -->
            </div>
            <!-- /column -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container -->
</section>
<!-- /section -->
@endsection

@section('script')
@endsection