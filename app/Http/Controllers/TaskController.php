<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TaskController extends Controller
{
    /**
     * @throws \DateInvalidTimeZoneException
     */
    public function __invoke(Request $request)
    {
        $tz = new \DateTimeZone(auth()->user()->timezone ?? config('app.timezone'));
        $thisMorning = now($tz)->setHour(00)->setMinute(00)->setSecond(00)->subSeconds($tz->getOffset(now()));
        $tonight = now($tz)->setHour(23)->setMinute(59)->setSecond(59)->subSeconds($tz->getOffset(now()));
        $query = DB::table('tasks')->where('user_id', auth()->user()->id)
            ->where(function (Builder $query) use ($thisMorning, $tonight) {
                $query->where('scheduled_at', '>=', $thisMorning)
                    ->where('scheduled_at', '<=', $tonight);
            })
            ->orWhere(function (Builder $query) use ($thisMorning, $tonight) {
                $query->where('scheduled_at', '<', $thisMorning)
                    ->where('completed_at', null);
            })
            ->orderBy('created_at');
        $tasks = $query->get();

        $todaysTasks = DB::table('tasks')->where('user_id', auth()->user()->id)
            ->where(function (Builder $query) use ($thisMorning, $tonight) {
                $query->where('scheduled_at', '>=', $thisMorning)
                    ->where('scheduled_at', '<=', $tonight);
            })->get();
        $lateTasks = DB::table('tasks')->where('user_id', auth()->user()->id)
            ->where(function (Builder $query) use ($thisMorning, $tonight) {
                $query->where('scheduled_at', '<', $thisMorning)
                    ->where('completed_at', null);
            })->get();

        return Inertia::render('Tasks', ['tasks' => $tasks, 'todaysTasks' => $todaysTasks, 'lateTasks' => $lateTasks]);
    }
}
