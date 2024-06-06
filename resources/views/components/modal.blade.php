<div 
    class="fixed bg-transparent z-[1001] inset-0 flex" 
    x-transition:enter="transition-all duration-300 ease-in-out "
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-all duration-300 ease-in-out "
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-show="{{ $show }}"
>
    <div class="m-auto bg-white w-[320px] md:min-w-[550px] rounded-lg overflow-hidden transition-all duration-300  ease-in-out p-0" >
        <div class="flex items-center justify-between px-6 py-4 border-b border-grey-300" >
            <span class="font-semibold text-lg" >{{ $title ?? '' }}</span>
            <button type="button" @click="{{ $onClose ?? '' }}" >
                <svg class="fill-grey-500" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M4.4443 1.6853C6.0888 0.58649 8.02219 0 10 0C12.6513 0.00286757 15.1932 1.05736 17.0679 2.9321C18.9427 4.80684 19.9971 7.34872 20 10C20 11.9778 19.4135 13.9112 18.3147 15.5557C17.2159 17.2002 15.6541 18.4819 13.8268 19.2388C11.9996 19.9957 9.98892 20.1937 8.0491 19.8079C6.10929 19.422 4.32746 18.4696 2.92894 17.0711C1.53041 15.6725 0.578004 13.8907 0.192152 11.9509C-0.193701 10.0111 0.00433286 8.00043 0.761209 6.17317C1.51809 4.34591 2.79981 2.78412 4.4443 1.6853ZM5.37025 16.9289C6.74066 17.8446 8.35183 18.3333 10 18.3333C12.2094 18.3309 14.3276 17.4522 15.8899 15.8899C17.4522 14.3276 18.3309 12.2094 18.3333 10C18.3333 8.35183 17.8446 6.74066 16.9289 5.37025C16.0132 3.99984 14.7118 2.93174 13.189 2.301C11.6663 1.67027 9.99076 1.50525 8.37426 1.82679C6.75775 2.14833 5.27289 2.94201 4.10745 4.10745C2.94201 5.27288 2.14834 6.75774 1.82679 8.37425C1.50525 9.99076 1.67028 11.6663 2.30101 13.189C2.93174 14.7118 3.99984 16.0132 5.37025 16.9289ZM12.7441 6.42294C12.965 6.42294 13.177 6.5107 13.3332 6.66692C13.4895 6.8232 13.5772 7.03512 13.5772 7.25609C13.5772 7.47706 13.4895 7.68898 13.3332 7.84526L11.1782 10.0003L13.3332 12.1553C13.485 12.3124 13.569 12.5229 13.5671 12.7414C13.5652 12.9599 13.4776 13.1689 13.3231 13.3234C13.1686 13.4779 12.9596 13.5656 12.7411 13.5675C12.5226 13.5694 12.3121 13.4854 12.1549 13.3336L9.99991 11.1786L7.84491 13.3336C7.68774 13.4854 7.47724 13.5694 7.25875 13.5675C7.04025 13.5656 6.83124 13.4779 6.67673 13.3234C6.52223 13.1689 6.43459 12.9599 6.43269 12.7414C6.43079 12.5229 6.51478 12.3124 6.66658 12.1553L8.82158 10.0003L6.66658 7.84526C6.51478 7.68809 6.43079 7.47759 6.43269 7.25909C6.43459 7.04059 6.52223 6.83158 6.67673 6.67707C6.83124 6.52257 7.04025 6.43493 7.25875 6.43303C7.47724 6.43113 7.68774 6.51513 7.84491 6.66692L9.99991 8.82192L12.1549 6.66692C12.3112 6.5107 12.5231 6.42294 12.7441 6.42294Z"/>
                </svg>
            </button>
        </div>
        <div class="px-6 py-6" >
            {{ $slot }}
        </div>
        {{ $footer ?? '' }}
    </div>
</div>

<div 
    class="inset-0 bg-black opacity-50 z-[1000] fixed" 
    x-transition:enter="transition-all duration-300 ease-in-out "
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-all duration-300 ease-in-out "
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-show="{{ $show }}"
>
</div>