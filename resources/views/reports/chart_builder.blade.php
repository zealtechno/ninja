@extends('header')

@section('content')
 @parent
 <!-- @include('accounts.nav', ['selected' => ACCOUNT_REPORTS, 'advanced' => true]) -->


    {!! Former::open()->rules(['start_date' => 'required', 'end_date' => 'required'])->addClass('warn-on-exit') !!}

    <div style="display:none">
    {!! Former::text('action') !!}
    </div>

 <div class="row">
  <div class="col-lg-12">
        <!-- <div class="panel panel-default"> -->
            <!-- <div class="panel-heading">
                <h3 class="panel-title">{!! trans('texts.report_settings') !!}</h3>
            </div> -->
            <div class="panel-body" style="padding-bottom: 0px">
                <div class="row">

                    <div class="col-md-6">


               {!! Former::text('start_date')->data_date_format(Session::get(SESSION_DATE_PICKER_FORMAT))
                                ->addGroupClass('start_date')
                 ->append('<i class="glyphicon glyphicon-calendar" onclick="toggleDatePicker(\'start_date\')"></i>') !!}
               {!! Former::text('end_date')->data_date_format(Session::get(SESSION_DATE_PICKER_FORMAT))
                                ->addGroupClass('end_date')
                 ->append('<i class="glyphicon glyphicon-calendar" onclick="toggleDatePicker(\'end_date\')"></i>') !!}

                        {!! Former::actions(
                                Button::primary(trans('texts.export'))->withAttributes(array('onclick' => 'onExportClick()'))->appendIcon(Icon::create('export')),
                                Button::success(trans('texts.run'))->withAttributes(array('id' => 'submitButton','onclick' => 'onRunClick()'))->appendIcon(Icon::create('play'))
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
                        {!! Former::text('client_name')->id('name')
                                ->addGroupClass('end_date') !!}
                        <input id="client_id" type="hidden" name="client_id">
                        <!-- <div class="form-group">
                            <label for="name" class="col-lg-2 control-label required"> Client Name</label>
                            <div class="col-lg-10">
                               {!! Form::text('client_name', null, ['id' => 'name', 'class' => 'form-control', 'placeholder' => 'Enter client name']) !!}
                            </div>
                        </div> -->

    {!! Former::close() !!}
        </div>
    </div>

 </div>
    <!-- </div>
        <div class="panel panel-default"> -->
        <div class="panel-body">
        <table class="table table-striped invoice-table save_pdf">
            <thead>
                <tr>
                    @foreach ($columns as $column)
                        <th>{{ trans("texts.{$column}") }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody id="table_content">
                @if (count($displayData))
                    @foreach ($displayData as $record)
                        <tr>
                            @foreach ($record as $field)
                                <td>{!! $field !!}</td>
                            @endforeach

                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="{{count($columns) - 1}}" align="right" style="background-color: #fcfcfc; border:0px">Total Outstanding</td>
                        <td>{!! $totalOutstanding !!}</td>
                    </tr>
                    <tr>
                        <td colspan="{{count($columns) - 1}}" align="right" style="background-color: #fcfcfc; border:0px">Total Due</td>
                        <td>{!! $due !!}</td>
                    </tr>
                @else
                    <tr>
                        <td colspan="10" style="text-align: center">{{ trans('texts.empty_table') }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
        <!-- <div style="margin: 0 auto">
            <button type="button" class="btn btn-success" id="saveButton">Save Invoice <span class="glyphicon glyphicon-floppy-disk"></span></button>
            <button type="button" class="btn btn-info" id="emailButton" onclick="onEmailClick()">Email Invoice <span class="glyphicon glyphicon-send"></span></button>
        </div> -->

        <!-- <span type="button" class="btn btn-primary" onclick="onDownloadClick()" id="downloadPdfButton">Download PDF <span class="glyphicon glyphicon-download-alt"></span></span> -->

        <span type="button" class="btn btn-primary" onclick="onPdfClick()" id="downloadPdfButton">Download PDF <span class="glyphicon glyphicon-download-alt"></span></span>

        {!! Button::info(trans("texts.email_invoice"))->withAttributes(array('id' => 'emailButton', 'onclick' => 'onEmailClick()'))->appendIcon(Icon::create('send')) !!}

        {!! Form::open(['action' => ['ReportController@getReportMailPdf'],'id' => 'pdfFrom', 'class' => 'form-horizontal', 'role' => 'form', 'files' => 'true','method' =>'GET' ]) !!}

            {{ Form::hidden('action', 'pdf') }}
            {{ Form::hidden('start_date', null, [ 'id' => 'start_date_input']) }}
            {{ Form::hidden('end_date', null, [ 'id' => 'end_date_input']) }}
            {{ Form::hidden('client_id', null, [ 'id' => 'client_id_input']) }}
            {{ Form::hidden('report_type', null, [ 'id' => 'report_type_input']) }}
            {{ Form::hidden('date_field', null, [ 'id' => 'date_field_input']) }}

        {!! Form::close() !!}

        <p>&nbsp;</p>


        </div>
        <!-- </div> -->

 </div>

{!! HTML::script( asset('js/main/pdf_save.js') ) !!}
{!! HTML::script( asset('js/main/client_search.js') ) !!}
{!! HTML::script( asset('js/main/typeahead.js') ) !!} 
 {!! HTML::script( asset('js/pdf/tableExport.js') ) !!}
 {!! HTML::script( asset('js/pdf/jquery.base64.js') ) !!}
 {!! HTML::script( asset('js/pdf/jspdf/libs/sprintf.js') ) !!}
 {!! HTML::script( asset('js/pdf/jspdf/jspdf.js') ) !!}
 {!! HTML::script( asset('js/pdf/jspdf/libs/base64.js') ) !!}
 <script src="https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js" type="text/javascript"> </script>
 <script src="https://cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js" type="text/javascript"> </script>
 <script src="//cdn.datatables.net/buttons/1.2.2/js/buttons.flash.min.js" type="text/javascript"> </script>
 <script src="//cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js" type="text/javascript"> </script>
 <script src="//cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js" type="text/javascript"> </script>
 <script src="//cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js" type="text/javascript"> </script>
 <script src="//cdn.datatables.net/buttons/1.2.2/js/buttons.html5.min.js" type="text/javascript"> </script>
 <script src="//cdn.datatables.net/buttons/1.2.2/js/buttons.print.min.js" type="text/javascript"> </script>

 <link href="https://cdn.datatables.net/1.10.12/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css"/>
 <link href="https://cdn.datatables.net/buttons/1.2.2/css/buttons.dataTables.min.css" rel="stylesheet" type="text/css"/>

 <script type="text/javascript">
    function onPdfClick() {
        let start_date = $('#start_date').val();
        let end_date = $('#end_date').val();
        let client_id = $('#client_id').val();
        let report_type = 'ENTITY_INVOICE';
        let date_field = 'FILTER_INVOICE_DATE';
        $('#start_date_input').val(start_date);
        $('#end_date_input').val(end_date);
        $('#client_id_input').val(client_id);
        $('#report_type_input').val(report_type);
        $('#date_field_input').val(date_field);
        $('#pdfFrom').submit();
    }
    function onEmailClick() {
        let start_date = $('#start_date').val();
        let end_date = $('#end_date').val();
        let client_id = $('#client_id').val();
        let report_type = 'ENTITY_INVOICE';
        let date_field = 'FILTER_INVOICE_DATE';
        let action = 'mail';
        $.ajax({
            url:'report-mail-pdf',
            method:'get',
            data:{start_date, end_date, report_type, date_field, client_id, action},
            success:function(data){
                // $('#table_content').html(data);
            }
        })
    }
    
    function onDownloadClick() {
        trackEvent('/activity', '/download_pdf');
        var invoice = createInvoiceModel();
        var design  = getDesignJavascript();
        if (!design) return;
        var doc = generatePDF(invoice, design, true);
        var type = invoice.is_quote ? '{{ trans('texts.'.ENTITY_QUOTE) }}' : '{{ trans('texts.'.ENTITY_INVOICE) }}';
        doc.save(type +'-' + $('#invoice_number').val() + '.pdf');
    }
    
    function onRunClick() {
        let start_date = $('#start_date').val();
        let end_date = $('#end_date').val();
        let client_id = $('#client_id').val();
        let report_type = 'ENTITY_INVOICE';
        let date_field = 'FILTER_INVOICE_DATE';
        $.ajax({
            url:'firstreport',
            method:'get',
            data:{start_date, end_date, report_type, date_field, client_id},
            success:function(data){
                $('#table_content').html(data);
            }
        })
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