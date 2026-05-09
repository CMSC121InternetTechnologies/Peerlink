<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\TutoringRequest;
use App\Models\TutoringSession;
use App\Policies\RequestPolicy;
use App\Policies\SessionPolicy;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Laravel auto-discovers Policies named "{Model}Policy" — our class
        // names (RequestPolicy / SessionPolicy) don't match (TutoringRequest /
        // TutoringSession) so we wire them up manually here.
        Gate::policy(TutoringRequest::class, RequestPolicy::class);
        Gate::policy(TutoringSession::class, SessionPolicy::class);

        // Stop JsonResource collections from wrapping themselves in `{data: [...]}`.
        // The frontend expects bare arrays (allTutors.forEach, notifications.map,
        // etc.) — without this, TutorResource::collection() returns
        // {tutors: {data: [...]}} which crashes the JS with "T.map is not a
        // function" the moment any code tries to iterate the value.
        JsonResource::withoutWrapping();
    }
}
