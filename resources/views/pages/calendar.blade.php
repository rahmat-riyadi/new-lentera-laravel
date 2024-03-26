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
            border-radius: 15px;
        }

    </style>
    @endassets

    @script
    <script>

        const calendarEl = document.getElementById('calendar');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'id',
            headerToolbar: false,
            height: '65vh',
            events: [
                { // this object will be "parsed" into an Event Object
                    title: 'Tugas 1', // a property!
                    start: '2024-03-01', // a property!
                    end: '2024-03-02',
                    backgroundColor: '#E1F1F8',
                    textColor: '#00AED6',
                    borderColor: '#E1F1F8'
                }
            ]
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