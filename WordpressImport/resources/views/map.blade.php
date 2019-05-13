@extends('layout')

@section('content')

    <div class="flexy mb-3">
        <h1>Map</h1>
    </div>

    <div class="card">

        {{ dump($map) }}

    </div>

    @foreach ($map as $item)

        <div class="card">

            {{ $item['title'] }}
            <br>
            Status: {{ $item['status'] }}
            <br>
            Link: {{ $item['link'] }}
            <br>
            Post_name: {{ $item['post_name'] }}
            <br>
            Excerpt: {{ $item['excerpt'] }}
            <br>
            Pubdate: {{ $item['pubdate'] }}
            <br>
            Creator: {{ $item['creator'] }}
            <br>
            Type: {{ $item['type'] }}
            <br>
            @if ($item['attachment_url'])
                Attachment_url: {{ $item['attachment_url'] }}
                <br>
            @endif
            @if ($item['categories'])
                Categories:
                <ul>
                    @foreach ($item['categories'] as $cat)
                        <li>
                            {{ $cat }}
                        </li>
                    @endforeach
                </ul>
            @endif
            Content:
            <br>
            {{ $item['content'] }}

        </div>

    @endforeach

@endsection
