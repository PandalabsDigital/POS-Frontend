@extends('layouts.app')

@section('title', 'Add category')

@section('content')
<h1 class="h3 mb-4">Add category</h1>
<form method="POST" action="{{ route('categories.store') }}" class="stat-card" style="max-width: 520px">
    @csrf
    @include('categories._form')
    <button class="btn btn-accent">Save</button>
</form>
@endsection
