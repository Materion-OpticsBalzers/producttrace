<?php

namespace App\Http\Livewire\Data;

use App\Models\User;
use Livewire\Component;

class UserManager extends Component
{
    public $search = '';

    public function toggleAdmin($userId) {
        $user = User::find($userId);

        $user->update([
            'is_admin' => (int)!$user->is_admin
        ]);
    }

    public function removeSession($userId) {
        User::destroy($userId);
    }

    public function render()
    {
        if($this->search != '') {
            $users = User::where('name', 'like', "%$this->search%")->orderBy('personnel_number', 'asc')->get();
        } else {
            $users = User::orderBy('personnel_number', 'asc')->get();
        }

        return view('livewire.data.user-manager', compact('users'));
    }
}
