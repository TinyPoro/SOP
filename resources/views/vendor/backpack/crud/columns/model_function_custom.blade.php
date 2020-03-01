{{-- custom return value --}}
@php
	$value = $entry->{$column['function_name']}();

@endphp

<span>
@php
	echo str_limit(strip_tags($value), 80, "[...]");
@endphp
</span>
