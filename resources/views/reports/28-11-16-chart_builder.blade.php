@extends('header')

@section('content')
	@parent
	@include('accounts.nav', ['selected' => ACCOUNT_REPORTS, 'advanced' => true])


    {!! Former::open()->rules(['start_date' => 'required', 'end_date' => 'required'])->addClass('warn-on-exit') !!}

    <div style="display:none">
    {!! Former::text('action') !!}
    </div>

    {!! Former::populateField('start_date', $startDate) !!}
    {!! Former::populateField('end_date', $endDate) !!}

	<div class="row">
		<div class="col-lg-12">
            <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">{!! trans('texts.report_settings') !!}</h3>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">

            			{!! Former::text('start_date')->data_date_format(Session::get(SESSION_DATE_PICKER_FORMAT))
                                ->addGroupClass('start_date')
            					->append('<i class="glyphicon glyphicon-calendar" onclick="toggleDatePicker(\'start_date\')"></i>') !!}
            			{!! Former::text('end_date')->data_date_format(Session::get(SESSION_DATE_PICKER_FORMAT))
                                ->addGroupClass('end_date')
            					->append('<i class="glyphicon glyphicon-calendar" onclick="toggleDatePicker(\'end_date\')"></i>') !!}

                        <p>&nbsp;</p>
                        {!! Former::actions(
                                Button::primary(trans('texts.export'))->withAttributes(array('onclick' => 'onExportClick()'))->appendIcon(Icon::create('export')),
                                Button::success(trans('texts.run'))->withAttributes(array('id' => 'submitButton'))->submit()->appendIcon(Icon::create('play'))
                            ) !!}

                        @if (!Auth::user()->hasFeature(FEATURE_REPORTS))
                        <script>
                            $(function() {
                                $('form.warn-on-exit').find('input, button').prop('disabled', true);
                            });
                        </script>
                        @endif


                    </div>
                    <div class="col-md-6">
                        {!! Former::select('report_type')->options($reportTypes, $reportType)->label(trans('texts.type')) !!}
                        <div id="dateField" style="display:{{ $reportType == ENTITY_TAX_RATE ? 'block' : 'none' }}">
                            {!! Former::select('date_field')->label(trans('texts.filter'))
                                    ->addOption(trans('texts.invoice_date'), FILTER_INVOICE_DATE)
                                    ->addOption(trans('texts.payment_date'), FILTER_PAYMENT_DATE) !!}
                        </div>

			 {!! Former::close() !!}
        </div>
        </div>

	</div>
    </div>
        <div class="panel panel-default">
        <div class="panel-body">
        <table class="table table-striped invoice-table">
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th>{{ trans("texts.{$column}") }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @if (count($displayData))
                @foreach ($displayData as $record)
                    <tr>
                        @foreach ($record as $field)
                            <td>{!! $field !!}</td>
                        @endforeach
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="10" style="text-align: center">{{ trans('texts.empty_table') }}</td>
                </tr>
            @endif
        </tbody>
        </table>

        <p>&nbsp;</p>

        @if (count(array_values($reportTotals)))
        <table class="table table-striped invoice-table">
        <thead>
            <tr>
                <th>{{ trans("texts.totals") }}</th>
                @foreach (array_values($reportTotals)[0] as $key => $val)
                    <th>{{ trans("texts.{$key}") }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($reportTotals as $currencyId => $val)
                <tr>
                    <td>{!! Utils::getFromCache($currencyId, 'currencies')->name !!}</td>
                    @foreach ($val as $id => $field)
                        <td>{!! Utils::formatMoney($field, $currencyId) !!}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
        </table>
        @endif

        </div>
        </div>

	</div>

	<script type="text/javascript">

    function onExportClick() {
        $('#action').val('export');
        $('#submitButton').click();
        $('#action').val('');
    }

    $(function() {
        $('.start_date .input-group-addon').click(function() {
            toggleDatePicker('start_date');
        });
        $('.end_date .input-group-addon').click(function() {
            toggleDatePicker('end_date');
        });

        $('#report_type').change(function() {
            var val = $('#report_type').val();
            if (val == '{{ ENTITY_TAX_RATE }}') {
                $('#dateField').fadeIn();
            } else {
                $('#dateField').fadeOut();
            }
        });
    })


	</script>

@stop


@section('onReady')

	$('#start_date, #end_date').datepicker({
		autoclose: true,
		todayHighlight: true,
		keyboardNavigation: false
	});

@stop
