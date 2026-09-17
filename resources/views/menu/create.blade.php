@extends('layouts.app')

@section('title', 'Add menu item')

@section('content')
<h1 class="h3 mb-4">Add menu item</h1>
<form method="POST" action="{{ route('menu-items.store') }}" enctype="multipart/form-data" class="stat-card">
    @csrf
    @include('menu._form')
    <button class="btn btn-accent">Save</button>
</form>
@endsection
