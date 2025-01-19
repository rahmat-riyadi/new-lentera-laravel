<?php 

use function Livewire\Volt\{state, rules, updating};    
use Illuminate\Support\Facades\Http;
use App\Models\User;


state(['username', 'password', 'invalidate']);

rules(['username' => 'required', 'password' => 'required'])->messages([
    'username.required' => 'username harus diisi',
    'password.required' => 'password harus diisi'
]);

updating([
    'username' => fn() => $this->invalidate = false,
    'password' => fn() => $this->invalidate = false
]);

$submit = function (){

    $this->validate();
    
    $response = Http::get(env('MOODLE_URL').'/login/token.php', [
        'username' => $this->username,
        'password' => $this->password,
        'service' => 'new-lentera-service',
    ]);

    

    if(!$response->ok()){
        $this->invalidate = true;
    }

    if(!empty($response->json()['error'])){
        $this->invalidate = true;
    }

    $userid = DB::connection('moodle_mysql')->table('mdl_external_tokens')
    ->where('token', $response->json()['token'])
    ->first('userid');

    $user = User::find($userid->userid);

    Auth::login($user);

    $this->redirect('/');

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login | Lentera</title>
    @include('partials.fontface')
    @vite('resources/css/app.css')
</head>
<body>
    @volt
    <div class="h-screen overflow-hidden relative flex " >
        <div 
            class="
                w-full
                md:w-[56%]
                relative
                bg-cover
                transition-all
            "
            style="background-image: url('{{ asset('/assets/images/login_bg.png') }}');"
        >  
            <img src="{{ asset('assets/images/login_logo.svg') }}" class="w-32 mx-auto md:ml-10 pt-10" alt="">
            <div class="absolute hidden bottom-0 pl-10 pb-10 pr-28 md:block" >
                <h2 class="headline-2 text-secodary" >Selamat Datang di lentera</h2>
                <p class="text-[16px] leading-[30px] text-grey-300" >Lentera (Learning Center Area) merupakan ruang atau tempat pembelajaran mahasiswa UIN Alauddin secara online atau biasa disebut e-learning.</p>
            </div>
        </div>
        <div
            class="
                grow
                flex
                justify-center
                items-center
                absolute
                inset-y-0
                inset-x-4
                md:static
                transition-all
            "
        >
            <div 
                class="
                    bg-white
                    w-full
                    p-6
                    md:p-0
                    md:w-8/12
                    rounded-xl
                "
            >
                <p class="font-semibold text-2xl mb-2 text-center md:text-left" >Login</p>
                <p class="
                    body-2 
                    w-64
                    font-semibold
                    text-grey-600 
                    text-center
                    leading-5 
                    mx-auto
                    mb-6 
                    md:font-medium
                    md:mb-3 
                    md:w-full 
                    md:text-left
                    "
                >
                    Silahkan masukkan NIP/NIDN dan password anda
                </p>
                <form wire:submit="submit" >
                    <div>
                        <label for="username">
                            <span class="block label text-gray-600 text-[12px] mb-1" >Username</span>
                            <div class="text-field flex" >
                                <img src="{{ asset('assets/icons/Peserta.svg') }}" class="mr-2 w-5"  alt=''>
                                <input autocomplete="off" wire:model.live="username" id="username" placeholder="Masukkan NIP/NIDN"  class="text-field-base grow peer">
                            </div>
                            @error('username')
                            <span class="text-error text-xs " >{{ $message }}</span> 
                            @enderror
                        </label>
                        <div class="h-[16px]"></div>
                        <label for="pass">
                            <span class="block label text-gray-600 text-[12px] mb-1" >Password</span>
                            <div class="text-field flex" >
                                <img src="{{ asset('assets/icons/pass.svg') }}" class="mr-2 w-5"  alt=''>
                                <input id="pass" wire:model.live="password" autocomplete="off" placeholder="Masukkan Password" class="text-field-base grow peer">
                            </div>
                            @error('password')
                            <span class="text-error text-xs " >{{ $message }}</span> 
                            @enderror
                        </label>
                        <div class="flex items-center mt-[13px] justify-between">
                            <label for="remember" class="flex items-center" >
                                <input type="checkbox" class="checkbox w-[18px] h-[18px]" id="remember">
                                <span class="text-xs ml-2" >Simpan username</span>
                            </label>
                            <a href="/" class="text-primary text-xs font-medium" >Lupa Password?</a>
                        </div>
                        <button type="submit" class="mt-8 btn-primary large w-full btn-large flex justify-center relative" >
                            <div wire:loading.class.remove="opacity-0" wire:loading.class="opacity-100" wire:target="submit" class="absolute left-1/2 -translate-x-1/2 top-1/2 -translate-y-1/2 opacity-0" >
                                {{-- <x-loading/> --}}
                            </div>
                            <span class="opacity-100 transition-all" wire:loading.class="opacity-0" wire:loading.class.remove="opacity-100" >Masuk</span>
                        </button>
                        <p class="text-sm mt-6" >Anda kesulitan login? <a href="/" class="text-primary underline" >Hubungi Kami</a></p>
                    </div>
                </form>
            </div>
        </div>
        <div class="alert mb-4 rounded-lg bg-red-100 px-6 py-4 text-sm text-red-500 absolute z-50 top-8 right-1/2 translate-x-1/2 transition-all duration-200 {{ $invalidate ? '-translate-y-0' : '-translate-y-[200px]'}} " >
            Username atau Password salah
        </div>
    </div>

    @script
    <script>

        $wire.on('logged_in', ([ res ]) => {
            const json = JSON.parse(res);
            window.localStorage.setItem('ws_token', json.token);
            window.location.href = '/';
        })

    </script>
    @endscript

    @endvolt
</body>
</html>