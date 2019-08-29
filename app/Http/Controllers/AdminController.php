<?php

namespace App\Http\Controllers;

use App\Models\User\User;
use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use App\Helpers\StringHelper;
use App\Http\Resources\Account\User\UserShort as UserResource;

class AdminController extends Controller
{
    /**
     * Instantiate a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! $request->user()->admin) {
                return redirect(null, 403)->route('login');
            }

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function index()
    {
        return view('admin.index')
            ->withUsers(UserResource::collection(User::all()));
    }

    /**
     * Get list of users.
     *
     * @param Request $request
     * @return array
     */
    public function users(Request $request)
    {
        $perPage = $request->has('perPage') ? $request->get('perPage') : config('monica.number_of_contacts_pagination');

        $users = User::where('id', '>', '0');

        $queryString = StringHelper::buildQuery([
            'first_name',
            'last_name',
            'email',
        ], $request->get('search') ? $request->get('search') : '');

        $users->whereRaw('('.$queryString.')');
        $users->orderByRaw('created_at asc');
        $users = $users->paginate($perPage);

        $userList = collect();
        foreach ($users as $user) {
            $userList->push([
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'is_admin' => (bool) $user->admin,
                'is_policy_compliant' => $user->isPolicyCompliant(),
                'account' => [
                    'id' => $user->account->id,
                    'has_access_to_paid_version_for_free' => $user->account->has_access_to_paid_version_for_free,
                ],
                'created_at' => DateHelper::getTimestamp($user->created_at),
                'updated_at' => DateHelper::getTimestamp($user->updated_at),
            ]);
        }

        return [
            'total' => $users->total(),
            'entries' => UserResource::collection($users),
        ];
    }

    /**
     * Get one user.
     *
     * @param Request $request
     * @return array
     */
    public function user(Request $request, User $user)
    {
        return [
            'entry' => new UserResource($user),
        ];
    }

    /**
     * Switch admin user.
     *
     * @param Request $request
     * @return array
     */
    public function userAdminToggle(Request $request, User $user)
    {
        $user->admin = ! $user->admin;
        $user->save();

        return [
            'entry' => new UserResource($user),
        ];
    }
}