<button id="export-excel" class="btn btn-xs btn-primary"><i class="fa fa-file-excel-o"></i> Export</button>

@push('crud_list_scripts')
    <script>
        $('#export-excel').click(function() {
            let url = "{{ url($crud->route.'/export') }}" + "?" + window.location.href.slice(window.location.href.indexOf('?') + 1);

            let win = window.open(url, '_blank');
            win.focus();
        })
    </script>
@endpush
