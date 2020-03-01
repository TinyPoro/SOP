{{-- select_from_array column --}}
@php
	$values = data_get($entry, $column['name']);
@endphp

<span>
    <select class="form-control" id="update-status-{{$entry->getKey()}}" data-id="{{$entry->getKey()}}">
		@foreach($column['options'] as $option => $value)
			@if($values == $option)
				<option value="{{$option}}" selected>{{$value}}</option>
			@else
				<option value="{{$option}}">{{$value}}</option>
			@endif
		@endforeach
    </select>
</span>

<script>
    $("#update-status-{{$entry->getKey()}}").change(function() {
        let id = $(this).data("id")
        let val = $(this).val()

        let url = '{{route('order.update_status', ['id' => ":id"])}}'
        url = url.replace(":id", id)

        let data = {
            status: val
		}

        $.ajax({
            method: 'PUT',
            url: url,
            data: data,
            success: function(result){
                console.log(result);

                setTimeout(function() {
                    window.location.reload();
                }, 500);
            },
            error: function (jqXHR) {
                console.log(jqXHR.responseText);
            }
        });
    })
</script>
