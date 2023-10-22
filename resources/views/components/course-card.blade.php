<div class="flex flex-col transition-all" >
    <img src="{{ asset('assets/icons/course-header.svg') }}" alt="">
    <div class="p-3 pt-2" >
      <div class="flex justify-between items-center" >
        <p class="text-grey-500 text-sm" >{{ $studyProgram }}</p>
        <button class="hover:bg-grey-200 rounded" >
          <x-icons.more-svg class="fill-[#374957]" />
        </button>
      </div>
      <a href="/course/{{ $course_id ??  "" }}" class="body-2 font-semibold mb-[14px] -mt-[2px] hover:text-secodary cursor-pointer" >{{ $course_title ?? '' }}</a>
      <div class="flex justify-between items-center mb-1" >
        <p class="text-[11px] font-normal">Progres Kelas</p>
        <p class="text-[12px] font-semibold">{{ $progress ?? 0 }}%</p>
      </div>
      <div class="h-2 rounded-full bg-grey-300 overflow-hidden" >
        <div class="h-full bg-grey-500 rounded-full -translate-x-1/2">
        </div>
      </div>
    </div>
</div>