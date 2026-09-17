<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports): View
    {
        $range = $reports->range($request->query('from'), $request->query('to'));
        $driver = DB::connection()->getDriverName();

        return view('reports.index', [
            'from' => $range['from']->toDateString(),
            'to' => $range['to']->toDateString(),
            'daily' => $reports->dailySales($range['from'], $range['to']),
            'monthly' => $reports->monthlySalesForDriver($range['from'], $range['to'], $driver),
            'orders' => $reports->orderHistory($range['from'], $range['to']),
        ]);
    }
}
