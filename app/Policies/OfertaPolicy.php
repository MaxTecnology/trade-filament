<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Oferta;
use Illuminate\Auth\Access\HandlesAuthorization;

class OfertaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_oferta');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Oferta $oferta): bool
    {
        return $user->can('view_oferta');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_oferta');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Oferta $oferta): bool
    {
        return $user->can('update_oferta');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Oferta $oferta): bool
    {
        return $user->can('delete_oferta');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_oferta');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Oferta $oferta): bool
    {
        return $user->can('force_delete_oferta');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_oferta');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Oferta $oferta): bool
    {
        return $user->can('restore_oferta');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_oferta');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Oferta $oferta): bool
    {
        return $user->can('replicate_oferta');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_oferta');
    }
}
