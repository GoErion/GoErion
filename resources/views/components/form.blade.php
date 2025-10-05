@props([
    'action'=>'',
    'method'=>'post'
])
    <form {{ $attributes }} action="{{$action}}" method="{{ strtolower($method) === 'get' ? 'get' : 'post' }}">
        @csrf
        @if(!in_array(strtolower($method),['get','post']))
            @method($method)  {{--e.g: PUT,PATCH,DELETE--}}
        @endif
        {{ $slot }}
    </form>
