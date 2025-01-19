<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CourseCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            'shortname',
            'fullname',
            'category',
            // 'timeaccess',
            // 'component',
            'visible',
            // 'showactivitydates',
            // 'showcompletionconditions',
            // 'pdfexportfont',
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
        ->limit(2)
        ->get();

        $categories = CourseCategory::whereIn('id', $recent_course->pluck('category'))->get();
        $completionRate = DB::connection('moodle_mysql')->table('mdl_course as c')
        ->select([
            'c.id as courseid',
            'c.fullname',
            DB::raw('COUNT(DISTINCT cmc.id) as completed_activities'),
            DB::raw('COUNT(DISTINCT cm.id) as total_activities'),
            DB::raw('ROUND((COUNT(DISTINCT cmc.id) * 100.0) / COUNT(DISTINCT cm.id), 2) as completion_percentage')
        ])
        ->join('mdl_course_modules as cm', 'cm.course', '=', 'c.id')
        ->leftJoin('mdl_course_modules_completion as cmc', function($join) use ($request) {
            $join->on('cmc.coursemoduleid', '=', 'cm.id')
                ->where('cmc.userid', '=', $request->user()->id)
                ->where('cmc.completionstate', '=', 1);
        })
        ->where('cm.completion', '>', 0)
        ->whereIn('c.id', $recent_course->pluck('id')->toArray())
        ->groupBy(['c.id', 'c.fullname'])
        ->get();

        foreach($recent_course as $r){
            $c = $categories->firstWhere('id', $r->category);
            $cr = $completionRate->firstWhere('courseid',  $r->id);
            $r->category_name = $c->name ?? '';
            $r->percentage = (int)$cr->completion_percentage;
        }

        $courses = $this->getInProgressCourse($request);

        foreach($courses as $c){
            $cat = $categories->firstWhere('id', $c->category);
            $cr = $completionRate->firstWhere('courseid',  $c->id);
            $c->category_name = $cat->name ?? '';
            $c->percentage = $cr ? (int)$cr->completion_percentage : 0;
        }

        $event = $this->getDashboardEvents(
            $request->user()->id,
            $courses->pluck('id')->toArray()
        );

        foreach($event as $e){
            $e->formatted_date = Carbon::parse($e->timestart)->setTimezone('Asia/Makassar')->translatedFormat('d F Y');
            $e->formatted_time = Carbon::parse($e->timestart)->setTimezone('Asia/Makassar')->translatedFormat('H:i');
        }

        return response()->json([
            'message' => 'get dashboard data success',
            'data' => [
                'recent_course' => $recent_course,
                'courses' => $courses,
                'dashboard_event' => $event
            ]
        ]);

    }

    public function getAllCourse($request){
        return DB::table('mdl_course as c')
        ->joinSub(
            DB::table('mdl_enrol as e')
                ->select('e.courseid')
                ->distinct()
                ->join('mdl_user_enrolments as ue', function ($join) use ($request) {
                    $join->on('ue.enrolid', '=', 'e.id')
                         ->where('ue.userid', '=', $request->user()->id)
                         ->where('ue.status', '=', 0)
                         ->where('e.status', '=', 0)
                         ->where('ue.timestart', '<=', time())
                         ->where(function ($query) {
                             $query->where('ue.timeend', '=', 0)
                                   ->orWhere('ue.timeend', '>', time());
                         });
                }),
            'en',
            'en.courseid',
            '=',
            'c.id'
        )
        ->leftJoin('mdl_context as ctx', function ($join) {
            $join->on('ctx.instanceid', '=', 'c.id')
                 ->where('ctx.contextlevel', '=', 50);
        })
        ->where('c.id', '<>', 1)
        ->orderByDesc('c.visible')
        ->orderBy('c.fullname', 'asc')
        ->select([
            'c.id',
            'c.category',
            'c.sortorder',
            'c.shortname',
            'c.fullname',
            'c.idnumber',
            'c.startdate',
            'c.visible',
            // 'c.groupmode',
            // 'c.groupmodeforce',
            'c.cacherev',
            // 'c.showactivitydates',
            // 'c.showcompletionconditions',
            'c.summary',
            'c.summaryformat',
            'c.enddate',
            // 'c.pdfexportfont',
            'ctx.id as ctxid',
            'ctx.path as ctxpath',
            'ctx.depth as ctxdepth',
            'ctx.contextlevel as ctxlevel',
            'ctx.instanceid as ctxinstance',
            'ctx.locked as ctxlocked',
        ])
        ->get();
    }

    public function getInProgressCourse($request){
        return DB::connection('moodle_mysql')->table('mdl_course as c')
        ->joinSub(
            DB::connection('moodle_mysql')->table('mdl_enrol as e')
                ->distinct()
                ->select('e.courseid')
                ->join('mdl_user_enrolments as ue', function ($join) use ($request) {
                    $join->on('ue.enrolid', '=', 'e.id')
                         ->where('ue.userid', '=', $request->user()->id)
                         ->where('ue.status', '=', 0)
                         ->where('e.status', '=', 0)
                         ->where('ue.timestart', '<=', time())
                         ->where(function ($query) {
                             $query->where('ue.timeend', '=', 0)
                                   ->orWhere('ue.timeend', '>', time());
                         });
                }),
            'en',
            'en.courseid',
            '=',
            'c.id'
        )
        ->leftJoin('mdl_context as ctx', function ($join) {
            $join->on('ctx.instanceid', '=', 'c.id')
                 ->where('ctx.contextlevel', '=', 50);
        })
        ->where('c.id', '<>', 1)
        ->orderByDesc('c.visible')
        ->orderBy('c.fullname', 'asc')
        ->select([
            'c.id',
            'c.category',
            'c.sortorder',
            'c.shortname',
            'c.fullname',
            'c.idnumber',
            'c.startdate',
            'c.visible',
            'c.groupmode',
            'c.groupmodeforce',
            'c.cacherev',
            // 'c.showactivitydates',
            // 'c.showcompletionconditions',
            'c.summary',
            'c.summaryformat',
            'c.enddate',
            // 'c.pdfexportfont',
            'ctx.id as ctxid',
            'ctx.path as ctxpath',
            'ctx.depth as ctxdepth',
            'ctx.contextlevel as ctxlevel',
            'ctx.instanceid as ctxinstance',
            'ctx.locked as ctxlocked',
        ])
        ->get();
    }

    function calculateCompletion($moduleCompletions) {
        // Hitung total modul yang memerlukan completion
        $totalModules = $moduleCompletions->count();
        
        // Hitung modul yang sudah complete
        $completedModules = $moduleCompletions->filter(function($module) {
            return isset($module->completionstate) && $module->completionstate == 1;
        })->count();
        
        // Hitung persentase
        $percentage = $totalModules > 0 
            ? round(($completedModules / $totalModules) * 100, 2)
            : 0;
            
        return [
            'total' => $totalModules,
            'completed' => $completedModules,
            'percentage' => $percentage
        ];
    }

    public function getDashboardEvents($userId, $courses = [],$limit = 10)
    {
        return DB::connection('moodle_mysql')->table('mdl_event as e')
            ->select([
                'e.*',
                'c.fullname as coursefullname',
                'c.shortname as courseshortname',
                'cs.name as sectionname',
                'cs.section as sectionnumber',
            ])
            ->leftJoin('mdl_modules as m', 'e.modulename', '=', 'm.name')
            ->leftJoin('mdl_course as c', 'c.id', '=', 'e.courseid')
            ->leftJoin('mdl_course_modules as cm', function($join){
                $join->on('cm.instance', '=', 'e.instance')
                ->where('cm.module', '=', 'm.id')
                ->where('cm.course', '=', 'c.id');
            })
            ->leftJoin('mdl_course_sections as cs', 'cs.id', '=', 'cm.section')
            ->where('e.visible', 1)
            ->where(function($query) use ($userId, $courses) {
                $query->where(function($q) use ($userId) {
                    // Personal events
                    $q->where('e.userid', $userId)
                    ->where('e.courseid', 0)
                    ->where('e.groupid', 0)
                    ->where('e.categoryid', 0);
                })->orWhere(function($q) use ($courses) {
                    // Course events
                    $q->where('e.groupid', 0)
                    ->whereIn('e.courseid', [ ...$courses, 1])
                    ->where('e.categoryid', 0);
                })->orWhere(function($q) {
                    // Category events
                $q->where('e.groupid', 0)
                  ->where('e.courseid', 0)
                  ->where('e.categoryid', 2);
            });
        })
        ->where(function($query) {
            $currentTime = time();
            $query->where('e.timestart', '>=', $currentTime)
                  ->orWhere(DB::raw('e.timestart + e.timeduration'), '>', $currentTime);
        })
        ->where(function($query) {
            $query->where('m.visible', 1)
                  ->orWhereNull('m.visible');
        })
        ->orderBy(DB::raw('COALESCE(e.timesort, e.timestart)'))
        ->orderBy('e.id')
        ->limit($limit)
        ->get();
    }

}
