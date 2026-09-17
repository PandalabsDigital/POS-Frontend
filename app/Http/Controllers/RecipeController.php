<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class RecipeController extends Controller
{
    public function index(): View
    {
        $courses = collect(config('recipes.courses'))
            ->map(fn (array $course, string $key) => [
                'key' => $key,
                'label' => $course['label'],
                'icon' => $course['icon'],
                'query' => $course['query'],
            ])
            ->values();

        $cuisines = collect(config('recipes.cuisines'))->map(function (array $cuisine) use ($courses) {
            $links = [];

            foreach ($courses as $course) {
                $links[$course['key']] = 'https://www.youtube.com/results?search_query='.rawurlencode(
                    $cuisine['name'].' '.$course['query']
                );
            }

            return [
                'name' => $cuisine['name'],
                'flag' => $cuisine['flag'],
                'links' => $links,
            ];
        });

        $fastFood = collect(config('recipes.fast_food'))->map(fn (array $item) => [
            'name' => $item['name'],
            'flag' => $item['flag'],
            'url' => 'https://www.youtube.com/results?search_query='.rawurlencode($item['query']),
        ]);

        return view('recipes.index', [
            'courses' => $courses,
            'cuisines' => $cuisines,
            'fastFood' => $fastFood,
            'defaultCourse' => $courses->first(),
        ]);
    }
}
