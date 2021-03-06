@extends('poller.index')

@section('title', __('Pollers'))

@section('content')

@parent

<br />
@if( $pollers )
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">@lang('Standard Pollers')</h3>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover table-condensed">
                <tr>
                    <th>@lang('Poller Name')</th>
                    <th>@lang('Devices Polled')</th>
                    <th>@lang('Total Poll Time')</th>
                    <th>@lang('Last Run')</th>
                    <th>@lang('Actions')</th>
                </tr>
                @foreach($pollers as $poller)
                <tr class="{{ $poller['row_class'] }}" id="row_{{ $poller['id'] }}">
                    <td>{{ $poller['poller_name'] }}</td>
                    <td>{{ $poller['devices'] }}</td>
                    <td>{{ $poller['time_taken'] }} Seconds</td>
                    <td>{{ $poller['last_polled'] }}</td>
                    <td>@if( $poller['long_not_polled'] )<button type='button' class='btn btn-danger btn-sm' aria-label=@lang('Delete') data-toggle='modal' data-target='#confirm-delete' data-id='{{ $poller['id'] }}' data-pollertype='delete-poller' name='delete-poller'><i class='fa fa-trash' aria-hidden='true'></i></button>@endif</td>
                </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>
@endif

@if( $poller_cluster )
<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">@lang('Poller Cluster Health')</h3>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-condensed">
                <tr>
                    <th>@lang('Name')</th>
                    <th>@lang('Node ID')</th>
                    <th>@lang('Version')</th>
                    <th>@lang('Groups Served')</th>
                    <th>@lang('Last Checkin')</th>
                    <th>@lang('Cluster Master')</th>
                    <th>@lang('Job')</th>
                    <th>@lang('Workers')</th>
                    <th>@lang('Devices Actioned')<br><small>@lang('Last Interval')</small></th>
                    <th>@lang('Devices Pending')</th>
                    <th>@lang('Worker Seconds')<br><small>@lang('Consumed/Maximum')</small></th>
                    <th>@lang('Actions')</th>
                </tr>
                @foreach($poller_cluster as $poller)
                @foreach($poller['stats'] as $stats)
                <tr class="{{ $poller['row_class'] }}" id="row_{{ $poller['id'] }}">
                @if( $loop->first )
                    <td rowspan="{{ $poller['stats']->count() }}">{{ $poller['poller_name'] }}</td>
                    <td rowspan="{{ $poller['stats']->count() }}"@if($poller['node_id'] == '') ' class="danger"' @endif>{{ $poller['node_id'] }}</td>
                    <td rowspan="{{ $poller['stats']->count() }}">{{ $poller['poller_version'] }}</td>
                    <td rowspan="{{ $poller['stats']->count() }}">{{ $poller['poller_groups'] }}</td>
                    <td rowspan="{{ $poller['stats']->count() }}">{{ $poller['last_report'] }}</td>
                    <td rowspan="{{ $poller['stats']->count() }}">@if( $poller['master'] ) "@lang('Yes')" @else "@lang('No')" @endif</td>
                @endif
                    <td>{{ $stats['poller_type'] }}</td>
                    <td>{{ $stats['workers'] }}</td>
                    <td>{{ $stats['devices'] }}</td>
                    <td>{{ $stats['depth'] }}</td>
                    <td>{{ $stats['worker_seconds'] }} / {{ $stats['frequency'] * $stats['workers'] }}</td>
                @if( $loop->first )
                    <td rowspan="{{ $poller['stats']->count() }}">@if( $poller['long_not_polled'] )<button type='button' class='btn btn-danger btn-sm' aria-label=@lang('Delete') data-toggle='modal' data-target='#confirm-delete' data-id='{{ $poller['id'] }}' data-pollertype='delete-cluster-poller' name='delete-cluster-poller'><i class='fa fa-trash' aria-hidden='true'></i></button>@endif</td>
                @endif
                </tr>
                @endforeach
                @endforeach
            </table>
            <small>
              Worker seconds indicates the maximum polling throughput a node can achieve in perfect conditions. If the consumed is close to the maximum, consider adding more threads, or better tuning your groups.<br>
              If there are devices pending but consumed worker seconds is low, your hardware is not sufficient for the number of devices and the poller cannot reach maximum throughput.
            </small>
        </div>
    </div>
</div>
@endif

@if(auth()->user()->isAdmin())
<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="@lang('Delete')" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h5 class="modal-title" id="Delete">@lang('Confirm Delete')</h5>
            </div>
            <div class="modal-body">
                <p>@lang('Please confirm that you would like to delete this poller.')</p>
            </div>
            <div class="modal-footer">
                <form role="form" class="remove_token_form">
                    @csrf
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('Cancel')</button>
                    <button type="submit" class="btn btn-danger danger" id="poller-removal"
                            data-target="poller-removal">@lang('Delete')
                    </button>
                    <input type="hidden" name="id" id="id" value="">
                    <input type="hidden" name="pollertype" id="pollertype" value="">
                    <input type="hidden" name="confirm" id="confirm" value="yes">
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
@if(auth()->user()->isAdmin())
<script>
    $('#confirm-delete').on('show.bs.modal', function (e) {
        id = $(e.relatedTarget).data('id');
        pollertype = $(e.relatedTarget).data('pollertype');
        $("#id").val(id);
        $("#pollertype").val(pollertype);
    });

    $('#poller-removal').click('', function (e) {
        e.preventDefault();
        var id = $("#id").val();
        var pollertype = $("#pollertype").val();
        $.ajax({
            type: 'POST',
            url: 'ajax_form.php',
            data: {type: pollertype, id: id},
            success: function (result) {
                if (result.status == 0) {
                    toastr.success(result.message);
                    $("#row_" + id).remove();
                }
                else {
                    toastr.error(result.message);
                }
                $("#confirm-delete").modal('hide');
            },
            error: function () {
                toastr.error(@lang('An error occurred deleting this poller.'));
                $("#confirm-delete").modal('hide');
            }
        });
    });
</script>
@endif
@endsection
