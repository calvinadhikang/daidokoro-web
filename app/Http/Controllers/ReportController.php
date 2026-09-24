<?php

namespace App\Http\Controllers;

use App\Http\Requests\MenuReportRequest;
use App\Http\Requests\SalesReportRequest;
use App\Services\MenuReportService;
use App\Services\SalesReportService;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(
        private SalesReportService $salesReports,
        private MenuReportService $menuReports,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/reports/index');
    }

    public function sales(SalesReportRequest $request): Response
    {
        return Inertia::render('admin/reports/sales', $this->salesReports->build($request->validated()));
    }

    public function menus(MenuReportRequest $request): Response
    {
        return Inertia::render('admin/reports/menus', $this->menuReports->build($request->validated()));
    }
}
