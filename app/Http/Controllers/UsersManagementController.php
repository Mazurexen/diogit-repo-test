<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Traits\CaptureIpTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Auth;

class UsersManagementController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->middleware('auth');
        $this->userService = $userService;
    }

    public function index()
    {
        $users = config('usersmanagement.enablePagination')
            ? User::paginate(config('usersmanagement.paginateListSize'))
            : User::all();

        return view('usersmanagement.show-users', [
            'users' => $users,
            'roles' => Role::all()
        ]);
    }

    public function create()
    {
        return view('usersmanagement.create-user', [
            'roles' => Role::all()
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $this->userService->create($request->validated());
        return redirect('users')->with('success', trans('usersmanagement.createSuccess'));
    }

    public function show(User $user)
    {
        return view('usersmanagement.show-user', compact('user'));
    }

    public function edit(User $user)
    {
        $currentRole = $user->roles->first();
        
        return view('usersmanagement.edit-user', [
            'user' => $user,
            'roles' => Role::all(),
            'currentRole' => $currentRole
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->userService->update($user, $request->validated());
        return back()->with('success', trans('usersmanagement.updateSuccess'));
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', trans('usersmanagement.deleteSelfError'));
        }

        $user->deleted_ip_address = (new CaptureIpTrait)->getClientIp();
        $user->save();
        $user->delete();

        return redirect('users')->with('success', trans('usersmanagement.deleteSuccess'));
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'user_search_box' => 'required|string|max:255'
        ]);

        $results = User::where('id', 'like', $validated['user_search_box'].'%')
            ->orWhere('name', 'like', $validated['user_search_box'].'%')
            ->orWhere('email', 'like', $validated['user_search_box'].'%')
            ->with('roles')
            ->get();

        return response()->json($results, Response::HTTP_OK);
    }
}
