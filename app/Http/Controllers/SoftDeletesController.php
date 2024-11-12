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
    private User $userModel;
    private Role $roleModel;

    public function __construct(User $user, Role $role)
    {
        $this->middleware('auth');
        $this->userModel = $user;
        $this->roleModel = $role;
    }

    public function index(): View
    {
        $users = $this->userModel->onlyTrashed()->get();
        $roles = $this->roleModel->all();

        return view('usersmanagement.show-deleted-users', compact('users', 'roles'));
    }

    public function show(int $id): View
    {
        $user = $this->findDeletedUserOrFail($id);

        return view('usersmanagement.show-deleted-user', compact('user'));
    }

    public function restore(int $id): RedirectResponse
    {
        $this->findDeletedUserOrFail($id)->restore();

        return redirect()
            ->route('users.index')
            ->with('success', trans('usersmanagement.successRestore'));
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $this->findDeletedUserOrFail($id)->forceDelete();

        return redirect()
            ->route('users.deleted.index')
            ->with('success', trans('usersmanagement.successDestroy'));
    }

    private function findDeletedUserOrFail(int $id): User
    {
        return $this->userModel
            ->onlyTrashed()
            ->findOrFail($id);
    }
}
