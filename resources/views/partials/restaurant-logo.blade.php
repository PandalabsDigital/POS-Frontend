@if(! empty($restaurantLogoUrl))
    <img src="{{ $restaurantLogoUrl }}" alt="{{ $restaurantName }}" class="{{ $class ?? 'dashboard-restaurant-logo' }}">
@endif
