{{-- select_from_array column --}}
@php
	$values = data_get($entry, $column['name']);
@endphp

<span>
    <select class="form-control" id="{{$entry->getKey()}}">
		@foreach($column['options'] as $option => $value)
			@if($values == $option)
				<option value="{{$option}}" selected>{{$value}}</option>
			@else
				<option value="{{$option}}">{{$value}}</option>
			@endif
		@endforeach
    </select>
</span>
