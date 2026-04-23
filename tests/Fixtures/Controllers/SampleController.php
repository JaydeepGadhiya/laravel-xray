<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;

class SampleController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function index(): array
    {
        return [];
    }

    public function store(Request $request): array
    {
        return [];
    }
}
