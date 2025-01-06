<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request){

        $recent_course = DB::connection('moodle_mysql')->table('mdl_course as c')
        ->select([
            'c.id',
            'idnumber',
            'summary',
            'summaryformat',
            'startdate',
            'enddate',
            'category',
            'shortname',
            'fullname',
            'timeaccess',
            'component',
            'visible',
            'showactivitydates',
            'showcompletionconditions',
            'pdfexportfont',
            'ctx.id as ctxid',
            'ctx.path as ctxpath',
            'ctx.depth as ctxdepth',
            'ctx.contextlevel as ctxlevel',
            'ctx.instanceid as ctxinstance',
            'ctx.locked as ctxlocked',
        ])
        ->join('mdl_context as ctx', function ($join) {
            $join->on('ctx.instanceid', '=', 'c.id')
                 ->where('ctx.contextlevel', '=', '50');
        })
        ->join('mdl_user_lastaccess as ul', 'ul.courseid', '=', 'c.id')
        ->leftJoin('mdl_favourite as fav', function ($join) {
            $join->on('fav.itemid', '=', 'ul.courseid')
                 ->where('fav.component', '=', 'core_course')
                 ->where('fav.itemtype', '=', 'courses')
                 ->where('fav.userid', '=', 7);
        })
        ->leftJoin('mdl_enrol as eg', function ($join) {
            $join->on('eg.courseid', '=', 'c.id')
                 ->where('eg.status', '=', '0')
                 ->where('eg.enrol', '=', 'guest');
        })
        ->where('ul.userid', '=', 7)
        ->where('c.visible', '=', 1)
        ->where(function ($query) use ($request) {
            $query->whereNotNull('eg.id')
                  ->orWhereExists(function ($subquery) use ($request) {
                      $subquery->select('e.id')
                          ->from('mdl_enrol as e')
                          ->join('mdl_user_enrolments as ue', 'ue.enrolid', '=', 'e.id')
                          ->whereColumn('e.courseid', 'c.id')
                          ->where('e.status', '=', '0')
                          ->where('ue.status', '=', '0')
                          ->where('ue.userid', '=', $request->user()->id)
                          ->where('ue.timestart', '<', now()->timestamp)
                          ->where(function ($query) {
                              $query->where('ue.timeend', '=', 0)
                                    ->orWhere('ue.timeend', '>', now()->timestamp);
                          });
                  });
        })
        ->orderBy('timeaccess', 'desc')
        ->limit(2);

        return response()->json([
            'message' => 'get dashboard data success',
            'data' => [
                'recent_course' => $recent_course
            ]
        ]);

    }
}
