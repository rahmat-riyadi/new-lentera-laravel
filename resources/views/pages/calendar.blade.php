<?php

use function Livewire\Volt\{state, mount, on, updated};
use App\Models\Course;
use App\Models\Quiz;
use App\Models\Assignment;
use App\Models\Attendance;

state(['events']);

mount(function(){

    $time = time();
    $courseids = Course::
    whereIn('mdl_course.id', function($q) use ($time){
        $q->select('e.courseid')
        ->from('mdl_enrol as e')
        ->join('mdl_user_enrolments as ue', function ($join) {
            $join->on('ue.enrolid', '=', 'e.id')
                ->where('ue.userid', '=', auth()->user()->id);
        })
        ->join('mdl_course as c', 'c.id', '=', 'e.courseid')
        ->where('ue.status', '=', '0')
        ->where('e.status', '=', '0')
        ->where('ue.timestart', '<=', $time)
        ->where(function ($query) use ($time) {
            $query->where('ue.timeend', '=', 0)
                    ->orWhere('ue.timeend', '>', $time);
        });
    })
    ->pluck('id');

    Log::info($courseids);

    $quiz = Quiz::whereIn('course_id', $courseids)
    ->join('moodle402.mdl_course as c', 'c.id', '=', 'quizzes.course_id')
    ->whereNotNull('activity_remember')
    ->select(
        'quizzes.id',
        'quizzes.name as title',
        'quizzes.activity_remember',
    )
    ->get();

    $quiz = $quiz->map(function($e){
        $e->type = 'quiz';
        $e->start = \Carbon\Carbon::parse($e->activity_remember)->format('Y-m-d');
        $e->end = \Carbon\Carbon::parse($e->activity_remember)->format('Y-m-d');
        $e->backgroundColor = '#E9D6E8';
        $e->textColor = '#93328E';
        $e->borderColor = '#E9D6E8';
        return $e;
    });

    $assignment = Assignment::whereIn('course_id', $courseids)
    ->join('moodle402.mdl_course as c', 'c.id', '=', 'assignments.course_id')
    ->whereNotNull('activity_remember')
    ->select(
        'assignments.id',
        'assignments.name as title',
        'assignments.activity_remember',
    )
    ->get();

    $assignment = $assignment->map(function($e){
        $e->type = 'assignment';
        $e->backgroundColor = '#E1F1F8';
        $e->textColor = '#00AED6';
        $e->borderColor = '#E1F1F8';
        $e->start = \Carbon\Carbon::parse($e->activity_remember)->format('Y-m-d');
        $e->end = \Carbon\Carbon::parse($e->activity_remember)->format('Y-m-d');
        return $e;
    });

    $this->events = collect($assignment->merge($quiz))->sortBy('due_date');

});

?>

<x-layouts.app>
    @volt
    <div class="overflow-y-auto h-full pb-3" >
        <div class="p-8">
            <div wire:ignore class="bg-white course-page-header p-8 rounded-lg">
                <div class="flex items-center mb-5">
                    <p class="mr-auto text-xl font-semibold" >{{ \Carbon\Carbon::now()->translatedFormat('F Y') }}</p>
                    <div class="flex items-center" >
                        <x-button id="today-button" >
                            Hari Ini
                        </x-button>
                        <button class="rotate-180 mr-3 ml-4" id="prev-button" >
                            <img class="w-9" src="{{ asset('assets/icons/arrow-calendar.svg') }}" alt="">
                        </button>
                        <button id="next-button" >
                            <img class="w-9" src="{{ asset('assets/icons/arrow-calendar.svg') }}" alt="">
                        </button>
                    </div>
                </div>
                <div id='calendar'></div>
            </div>
        </div>
    </div>

    @assets
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <style>
        .fc-col-header {
            background-color: #F9FAFB;
        }

        .fc-col-header .fc-col-header-cell-cushion {
            font-size: 14px;
            padding: 16px 0;
            font-weight: 600;
        }

        td.fc-day {
            text-align: center !important;
        }

        .fc-event {
            border-radius: 6px;
            font-weight: 500;
        }

    </style>
    @endassets

    @script
    <script>

        const events = {{ Js::from($events) }}

        console.log(events)

        const calendarEl = document.getElementById('calendar');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'id',
            headerToolbar: false,
            height: '65vh',
            events: events
        });
        calendar.render();

        document.getElementById('today-button').addEventListener('click', function() {
            calendar.today();
        });

        document.getElementById('prev-button').addEventListener('click', function() {
            calendar.prev(); // call method
        });

        document.getElementById('next-button').addEventListener('click', function() {
            calendar.next(); // call method
        });

    </script>
    @endscript

    @endvolt
</x-layouts.app>