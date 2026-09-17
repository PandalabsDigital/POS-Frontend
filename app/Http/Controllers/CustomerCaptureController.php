<?php

namespace App\Http\Controllers;

use App\Services\CustomerCaptureAnalytics;
use App\Services\CustomerCaptureSettings;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerCaptureController extends Controller
{
    public function index(Request $request, ReportService $reports, CustomerCaptureAnalytics $analytics): View
    {
        abort_unless(CustomerCaptureSettings::current()['enable_analytics'], 404);

        $range = $reports->range($request->query('from'), $request->query('to'));

        return view('customers.capture', [
            'from' => $range['from']->toDateString(),
            'to' => $range['to']->toDateString(),
            'summary' => $analytics->summary($range['from'], $range['to']),
            'staff' => $analytics->byStaff($range['from'], $range['to']),
        ]);
    }
}
