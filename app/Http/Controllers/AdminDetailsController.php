<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\View\View;

class AdminDetailsController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Exibe lista de rotas do sistema.
     */
    public function listRoutes(): View
    {
        $routes = collect(Route::getRoutes())
            ->map(function ($route) {
                return [
                    'method' => $route->methods()[0],
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                    'action' => $route->getActionName()
                ];
            });

        return view('pages.admin.route-details', compact('routes'));
    }

    /**
     * Exibe página de usuários ativos.
     */
    public function activeUsers(): View
    {
        $users = User::query()
            ->where('active', true)
            ->count();

        return view('pages.admin.active-users', compact('users'));
    }
}
