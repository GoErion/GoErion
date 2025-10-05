<div>
    <div class="flex items-center justify-center">
        <img src="{{ $captcha }}" alt="" class="mb-2 border rounded-lg focus:ring-pink-500 focus:border-pink-500">
    </div>
        <div class="">
            <label for="captcha" class="block mb-2 font-bold">{{__('Captcha')}}</label>
            <input type="text" name="captcha" id="captcha" placeholder="Enter Captcha" class="bg-transparent border rounded-lg focus:ring-pink-500 focus:border-pink-500 @error('captcha') border-red-500 @enderror block w-full p-2.5 dark:placeholder-slate-400"/>
            @error('captcha')
            <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>
</div>
