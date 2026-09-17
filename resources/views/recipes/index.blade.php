@extends('layouts.app')

@section('title', 'Recipes')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-1">Recipes</h1>
    <p class="text-muted mb-0">Pick a course or fast food style, then open YouTube recipes.</p>
</div>

<div class="course-tabs mb-4" role="tablist" aria-label="Recipe types">
    @foreach($courses as $index => $course)
        <button type="button"
            class="course-tab {{ $index === 0 ? 'active' : '' }}"
            data-panel="cuisines"
            data-course="{{ $course['key'] }}"
            aria-pressed="{{ $index === 0 ? 'true' : 'false' }}">
            <i class="bi {{ $course['icon'] }}" aria-hidden="true"></i>
            {{ $course['label'] }}
        </button>
    @endforeach
    <button type="button"
        class="course-tab"
        data-panel="fast-food"
        data-course="fast_food"
        aria-pressed="false">
        <i class="bi bi-shop" aria-hidden="true"></i>
        Fast food
    </button>
</div>

<div id="cuisine-recipes">
    <h2 class="h5 mb-3">Cuisines</h2>
    <div class="row g-3">
        @foreach($cuisines as $cuisine)
            <div class="col-6 col-md-4 col-xl-3">
                <a class="recipe-card"
                    href="{{ $cuisine['links'][$defaultCourse['key']] }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    data-links='@json($cuisine['links'])'>
                    <span class="recipe-flag" aria-hidden="true">{{ $cuisine['flag'] }}</span>
                    <span class="fw-semibold">{{ $cuisine['name'] }}</span>
                    <small class="recipe-course-label">{{ $defaultCourse['label'] }} on YouTube</small>
                    <span class="recipe-yt"><i class="bi bi-youtube" aria-hidden="true"></i> Watch</span>
                </a>
            </div>
        @endforeach
    </div>
</div>

<div id="fast-food-recipes" class="d-none">
    <h2 class="h5 mb-3">Fast food</h2>
    <div class="row g-3">
        @foreach($fastFood as $item)
            <div class="col-6 col-md-4 col-xl-3">
                <a class="recipe-card" href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer">
                    <span class="recipe-flag" aria-hidden="true">{{ $item['flag'] }}</span>
                    <span class="fw-semibold">{{ $item['name'] }}</span>
                    <small>{{ $item['name'] }} recipes on YouTube</small>
                    <span class="recipe-yt"><i class="bi bi-youtube" aria-hidden="true"></i> Watch</span>
                </a>
            </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
    const tabs = document.querySelectorAll('.course-tab');
    const cards = document.querySelectorAll('#cuisine-recipes .recipe-card');
    const labels = document.querySelectorAll('.recipe-course-label');
    const cuisinePanel = document.getElementById('cuisine-recipes');
    const fastFoodPanel = document.getElementById('fast-food-recipes');

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const course = tab.dataset.course;
            const panel = tab.dataset.panel;
            tabs.forEach((el) => {
                el.classList.toggle('active', el === tab);
                el.setAttribute('aria-pressed', el === tab ? 'true' : 'false');
            });
            cuisinePanel.classList.toggle('d-none', panel !== 'cuisines');
            fastFoodPanel.classList.toggle('d-none', panel !== 'fast-food');
            if (panel !== 'cuisines') {
                return;
            }
            cards.forEach((card, index) => {
                const links = JSON.parse(card.dataset.links);
                card.href = links[course];
                if (labels[index]) {
                    labels[index].textContent = `${tab.textContent.trim()} on YouTube`;
                }
            });
        });
    });
</script>
@endpush
