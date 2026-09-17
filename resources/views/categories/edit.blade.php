@extends('layouts.app')

@section('title', 'Edit category')

@section('content')
<h1 class="h3 mb-4">Edit category</h1>
<form method="POST" action="{{ route('categories.update', $category) }}" class="stat-card" style="max-width: 520px">
    @csrf @method('PUT')
    @include('categories._form', ['category' => $category])
    <button class="btn btn-accent">Update</button>
</form>
@endsection
