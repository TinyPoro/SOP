{{-- custom return value --}}
@php
	$value = $entry->{$column['function_name']}();

@endphp

<span>
@php
	echo $value;
@endphp
</span>
