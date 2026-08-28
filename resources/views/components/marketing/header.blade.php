<header class="fixed inset-x-0 top-0 z-50 border-b border-slate-900 px-16 mx-8">
    <nav aria-label="Global" class="flex items-center justify-between p-2 lg:px-8">
      <div class="flex lg:flex-1">
        <a href="{{ route('marketing') }}" class="-m-1.5">
          <img src="{{ asset(config('marketing.logo')) }}" alt="" class="h-26 w-auto not-dark:hidden" />
        </a>
      </div>
      <div class="flex lg:hidden">
        <button type="button" command="show-modal" commandfor="mobile-menu" class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-700 dark:text-gray-200">
          <span class="sr-only">Open main menu</span>
        </button>
      </div>
      <div class="hidden lg:flex lg:gap-x-12">
        <a href="#" class="text-sm/6 font-semibold text-slate-900 dark:text-slate-300">Product</a>
        <a href="#" class="text-sm/6 font-semibold text-slate-900 dark:text-slate-300">Pricing</a>
        <a href="#" class="text-sm/6 font-semibold text-slate-900 dark:text-slate-300">About Us</a>
        <a href="#" class="text-sm/6 font-semibold text-slate-900 dark:text-slate-300">Partners</a>
        <a href="#" class="text-sm/6 font-semibold text-slate-900 dark:text-slate-300">Customer</a>
      </div>
      <div class="hidden lg:flex lg:flex-1 lg:justify-end space-x-3">
        <a href="{{ route('login') }}" class="text-sm/6 font-semibold border border-pink-800 text-slate-900 dark:text-slate-300 py-2 px-8 rounded-lg">Sign In</a>
        <a href="{{  route('register') }}" class="text-sm/6 font-semibold bg-pink-800 text-slate-900 dark:text-slate-300 py-2 px-4 rounded-lg">Get Started</a>
      </div>
    </nav>
    <el-dialog>
      <dialog id="mobile-menu" class="backdrop:bg-transparent lg:hidden">
        <div tabindex="0" class="fixed inset-0 focus:outline-none">
          <el-dialog-panel class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-white p-6 sm:max-w-sm sm:ring-1 sm:ring-gray-900/10 dark:bg-gray-900 dark:sm:ring-gray-100/10">
            <div class="flex items-center justify-between">
              <a href="#" class="-m-1.5">
                <img src="{{ asset(config('marketing.logo')) }}" alt="" class="h-8 w-auto not-dark:hidden" />
              </a>
              <button type="button" command="close" commandfor="mobile-menu" class="-m-2.5 rounded-md p-2.5 text-gray-700 dark:text-gray-200">
                <span class="sr-only">Close menu</span>
              </button>
            </div>
            <div class="mt-6 flow-root">
              <div class="-my-6 divide-y divide-gray-500/10 dark:divide-white/10">
                <div class="space-y-2 py-6">
                  <a href="#" class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5">Product</a>
                  <a href="#" class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5">Pricing</a>
                  <a href="#" class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5">About Us</a>
                  <a href="#" class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5">Partners</a>
                  <a href="#" class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5">Customer</a>
                </div>
                <div class="py-6">
                  <a href="#" class="-mx-3 block rounded-lg px-3 py-2.5 text-base/7 font-semibold text-gray-900 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5">Log in</a>
                </div>
              </div>
            </div>
          </el-dialog-panel>
        </div>
      </dialog>
    </el-dialog>
  </header>