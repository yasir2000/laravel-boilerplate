<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function __construct(
        private CompanyService $companyService
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('can:view,company')->only(['show']);
        $this->middleware('can:update,company')->only(['update']);
        $this->middleware('can:delete,company')->only(['destroy']);
    }

    /**
     * Display a listing of companies.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Company::class);

        $companies = $this->companyService->getCompanies($request->all());

        return $this->successResponse($companies, 'Companies retrieved successfully');
    }

    /**
     * Store a newly created company.
     */
    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $this->authorize('create', Company::class);

        $company = $this->companyService->createCompany($request->validated());

        return $this->successResponse($company, 'Company created successfully', 201);
    }

    /**
     * Display the specified company.
     */
    public function show(Company $company): JsonResponse
    {
        $company->load(['users', 'projects']);

        return $this->successResponse($company, 'Company retrieved successfully');
    }

    /**
     * Update the specified company.
     */
    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $company = $this->companyService->updateCompany($company, $request->validated());

        return $this->successResponse($company, 'Company updated successfully');
    }

    /**
     * Remove the specified company.
     */
    public function destroy(Company $company): JsonResponse
    {
        $this->companyService->deleteCompany($company);

        return $this->successResponse(null, 'Company deleted successfully');
    }

    /**
     * Get company statistics.
     */
    public function statistics(Company $company): JsonResponse
    {
        $this->authorize('view', $company);

        $statistics = $this->companyService->getCompanyStatistics($company);

        return $this->successResponse($statistics, 'Company statistics retrieved successfully');
    }
}
