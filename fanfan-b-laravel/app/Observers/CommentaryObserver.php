<?php

namespace App\Observers;

use App\Models\data\Commentary;

class CommentaryObserver
{
    /**
     * Handle the Commentary "created" event.
     *
     * @param  \App\Models\data\Commentary  $commentary
     * @return void
     */
    public function created(Commentary $commentary)
    {
        logger('created');
        //
    }

    /**
     * Handle the Commentary "updated" event.
     *
     * @param  \App\Models\data\Commentary  $commentary
     * @return void
     */
    public function updated(Commentary $commentary)
    {
        logger('updated');
        //
    }

    /**
     * Handle the Commentary "deleted" event.
     *
     * @param  \App\Models\Commentary  $commentary
     * @return void
     */
    public function deleted(Commentary $commentary)
    {
        //
    }

    /**
     * Handle the Commentary "restored" event.
     *
     * @param  \App\Models\data\Commentary  $commentary
     * @return void
     */
    public function restored(Commentary $commentary)
    {
        //
    }

    /**
     * Handle the Commentary "force deleted" event.
     *
     * @param  \App\Models\data\Commentary  $commentary
     * @return void
     */
    public function forceDeleted(Commentary $commentary)
    {
        //
    }
}
