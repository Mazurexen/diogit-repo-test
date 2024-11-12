<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SoftDeletesController extends Controller
{
    /**
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @return View
     */
    public function index(): View
    {
        return view('usersmanagement.show-deleted-users', [
            'users' => User::onlyTrashed()->get(),
            'roles' => Role::all()
        ]);
    }

    /**
     * @param int $id
     * @return View
     */
    public function show(int $id): View
    {
        return view('usersmanagement.show-deleted-user', [
            'user' => $this->findDeletedUserOrFail($id)
        ]);
    }

    /**
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $this->findDeletedUserOrFail($id)->restore();

        return redirect('/users/')
            ->with('success', trans('usersmanagement.successRestore'));
    }

    /**
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        $this->findDeletedUserOrFail($id)->forceDelete();

        return redirect('/users/deleted/')
            ->with('success', trans('usersmanagement.successDestroy'));
    }

    /**
     * @param int $id
     * @return User
     */
    private function findDeletedUserOrFail(int $id): User
    {
        return User::onlyTrashed()
            ->where('id', $id)
            ->firstOrFail();
    }
}
