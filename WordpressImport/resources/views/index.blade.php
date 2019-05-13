@extends('layout')

@section('content')

    <form action="{{ route('addons.wordpress_import.map') }}" method="POST" enctype="multipart/form-data">
        {{ csrf_field() }}

        <div class="flexy mb-3">
            <h1>Import Wordpress XML</h1>
        </div>

        <div class="card">

            <div class="flexy">

                <div class="form-group fill">
                    <label>JSON File</label>
                    <input type="file" class="form-control" name="file">
                </div>
                <button type="submit" class="btn btn-primary btn-lg ml-16">Import</button>

            </div>

        </div>

    </form>

@endsection
