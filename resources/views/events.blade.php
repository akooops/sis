@extends('layouts.master')

@section('title', 'Les Évènements')
@section('description', 'Chez IEC, nous organisons divers événements de haute qualité, à travers lesquels nous visons à répondre au mieux aux besoins de notre communauté et à fournir une acquisition habile et complète des connaissances pour tous les passionnés du Génie Industriel !')
@section('canonical', route('events'))

@section('css')
@endsection

@section('content')
<section class="wrapper bg-secondary">
    <div class="container py-12 text-center">
        <div class="row">
            <div class="col-8 mx-auto">
                <h1 class="display-1 mb-3">
                    Nos Évènements
                </h1>
                <p class="fs-16 text-gray px-2">
                    Chez IEC, nous organisons divers événements de haute qualité, à travers lesquels nous visons à répondre au mieux aux besoins de notre communauté et à fournir une acquisition habile et complète des connaissances pour tous les passionnés du Génie Industriel !
                </p>
            </div>
            <!-- /column -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container -->
</section>
<!-- /section -->

<section class="wrapper">
    <div class="container py-12">
        @foreach ($events as $key => $event)
            <div class="row gx-lg-4 gx-xl-8 gy-12 align-items-center {{ $key % 2 == 0 ? '' : 'flex-row-reverse' }}">
                <div class="col-lg-5 position-relative">
                    <div class="img-mask mask-2 px-8">
                        <img src="{{$event->image->fullpath}}" alt="{{$event->name}}" class="bg-white" style="height: 400px; object-fit: contain"/>
                    </div>
                </div>
                <!--/column -->
                <div class="col-lg-7">
                    <h3 class="display-4 mb-5 text-primary">
                        {{$event->name}}
                    </h3>
                    <p class="mb-7" style="word-wrap: break-word">
                        {{$event->description}}
                    </p>

                    @if(count($event->editions) != 0)
                        <h4 class="fs-16 text-uppercase text-line text-primary mb-3">Éditions</h4>
                    @endif

                    <div class="row counter-wrapper gy-6">
                        @foreach ($event->editions as $key => $edition)
                            <div class="col-12 col-md-2 col-lg-4">
                                <div class="card">
                                    <a href="{{route('edition', ['slug' => $edition->slug])}}">
                                        <div class="card-body bg-secondary" style="border-radius: 8px; border: 0">
                                            <h5>
                                                {{$edition->name}}
                                            </h5>
                                        </div>
                                        <!--/.card-body -->
                                    </a>
                                </div>
                                <!--/.card -->
                            </div>
                        @endforeach
                    </div>
                    <!--/.row -->
                </div>
                <!--/column -->
            </div>
            <!--/.row -->

            @if($key != count($events) - 1)
                <hr>
            @endif
            
        @endforeach
    </div>
    <!-- /.container -->
</section>
<!-- /section -->
@endsection

@section('script')
@endsection