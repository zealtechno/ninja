<?php $__env->startSection('content'); ?>
 @parent
 <!-- <?php echo $__env->make('accounts.nav', ['selected' => ACCOUNT_REPORTS, 'advanced' => true], array_except(get_defined_vars(), array('__data', '__path')))->render(); ?> -->


    <?php echo Former::open()->rules(['start_date' => 'required', 'end_date' => 'required'])->addClass('warn-on-exit'); ?>


    <div style="display:none">
    <?php echo Former::text('action'); ?>

    </div>

 <div class="row">
  <div class="col-lg-12">
        <!-- <div class="panel panel-default"> -->
            <!-- <div class="panel-heading">
                <h3 class="panel-title"><?php echo trans('texts.report_settings'); ?></h3>
            </div> -->
            <div class="panel-body" style="padding-bottom: 0px">
                <div class="row">

                    <div class="col-md-6">


               <?php echo Former::text('start_date')->data_date_format(Session::get(SESSION_DATE_PICKER_FORMAT))
                                ->addGroupClass('start_date')
                 ->append('<i class="glyphicon glyphicon-calendar" onclick="toggleDatePicker(\'start_date\')"></i>'); ?>

               <?php echo Former::text('end_date')->data_date_format(Session::get(SESSION_DATE_PICKER_FORMAT))
                                ->addGroupClass('end_date')
                 ->append('<i class="glyphicon glyphicon-calendar" onclick="toggleDatePicker(\'end_date\')"></i>'); ?>


                        <?php echo Former::actions(
                                Button::primary(trans('texts.export'))->withAttributes(array('onclick' => 'onExportClick()'))->appendIcon(Icon::create('export')),
                                Button::success(trans('texts.run'))->withAttributes(array('id' => 'submitButton','onclick' => 'onRunClick()'))->appendIcon(Icon::create('play'))
                            ); ?>


                        <?php if(!Auth::user()->hasFeature(FEATURE_REPORTS)): ?>
                        <script>
                            $(function() {
                                $('form.warn-on-exit').find('input, button').prop('disabled', true);
                            });
                        </script>
                        <?php endif; ?>


                    </div>
                    <div class="col-md-6">
                        <?php echo Former::select('report_type')->options($reportTypes, $reportType)->label(trans('texts.type')); ?>

                        <div id="dateField" style="display:<?php echo e($reportType == ENTITY_TAX_RATE ? 'block' : 'none'); ?>">
                            <?php echo Former::select('date_field')->label(trans('texts.filter'))
                                    ->addOption(trans('texts.invoice_date'), FILTER_INVOICE_DATE)
                                    ->addOption(trans('texts.payment_date'), FILTER_PAYMENT_DATE); ?>

                        </div>
                        <?php echo Former::text('client_name')->id('name')
                                ->addGroupClass('end_date'); ?>

                        <input id="client_id" type="hidden" name="client_id">
                        <!-- <div class="form-group">
                            <label for="name" class="col-lg-2 control-label required"> Client Name</label>
                            <div class="col-lg-10">
                               <?php echo Form::text('client_name', null, ['id' => 'name', 'class' => 'form-control', 'placeholder' => 'Enter client name']); ?>

                            </div>
                        </div> -->

    <?php echo Former::close(); ?>

        </div>
    </div>

 </div>
    <!-- </div>
        <div class="panel panel-default"> -->
        <div class="panel-body">
        <table class="table table-striped invoice-table save_pdf">
            <thead>
                <tr>
                    <?php foreach($columns as $column): ?>
                        <th><?php echo e(trans("texts.{$column}")); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody id="table_content">
                <?php if(count($displayData)): ?>
                    <?php foreach($displayData as $record): ?>
                        <tr>
                            <?php foreach($record as $field): ?>
                                <td><?php echo $field; ?></td>
                            <?php endforeach; ?>

                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="<?php echo e(count($columns) - 1); ?>" align="right" style="background-color: #fcfcfc; border:0px">Total Outstanding</td>
                        <td><?php echo $totalOutstanding; ?></td>
                    </tr>
                    <tr>
                        <td colspan="<?php echo e(count($columns) - 1); ?>" align="right" style="background-color: #fcfcfc; border:0px">Total Due</td>
                        <td><?php echo $due; ?></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="10" style="text-align: center"><?php echo e(trans('texts.empty_table')); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <!-- <div style="margin: 0 auto">
            <button type="button" class="btn btn-success" id="saveButton">Save Invoice <span class="glyphicon glyphicon-floppy-disk"></span></button>
            <button type="button" class="btn btn-info" id="emailButton" onclick="onEmailClick()">Email Invoice <span class="glyphicon glyphicon-send"></span></button>
        </div> -->

        <!-- <span type="button" class="btn btn-primary" onclick="onDownloadClick()" id="downloadPdfButton">Download PDF <span class="glyphicon glyphicon-download-alt"></span></span> -->

        <span type="button" class="btn btn-primary" onclick="onPdfClick()" id="downloadPdfButton">Download PDF <span class="glyphicon glyphicon-download-alt"></span></span>

        <?php echo Button::info(trans("texts.email_invoice"))->withAttributes(array('id' => 'emailButton', 'onclick' => 'onEmailClick()'))->appendIcon(Icon::create('send')); ?>


        <?php echo Form::open(['action' => ['ReportController@getReportMailPdf'],'id' => 'pdfFrom', 'class' => 'form-horizontal', 'role' => 'form', 'files' => 'true','method' =>'GET' ]); ?>


            <?php echo e(Form::hidden('action', 'pdf')); ?>

            <?php echo e(Form::hidden('start_date', null, [ 'id' => 'start_date_input'])); ?>

            <?php echo e(Form::hidden('end_date', null, [ 'id' => 'end_date_input'])); ?>

            <?php echo e(Form::hidden('client_id', null, [ 'id' => 'client_id_input'])); ?>

            <?php echo e(Form::hidden('report_type', null, [ 'id' => 'report_type_input'])); ?>

            <?php echo e(Form::hidden('date_field', null, [ 'id' => 'date_field_input'])); ?>


        <?php echo Form::close(); ?>


        <p>&nbsp;</p>


        </div>
        <!-- </div> -->

 </div>

<?php echo HTML::script( asset('js/main/pdf_save.js') ); ?>

<?php echo HTML::script( asset('js/main/client_search.js') ); ?>

<?php echo HTML::script( asset('js/main/typeahead.js') ); ?> 
 <?php echo HTML::script( asset('js/pdf/tableExport.js') ); ?>

 <?php echo HTML::script( asset('js/pdf/jquery.base64.js') ); ?>

 <?php echo HTML::script( asset('js/pdf/jspdf/libs/sprintf.js') ); ?>

 <?php echo HTML::script( asset('js/pdf/jspdf/jspdf.js') ); ?>

 <?php echo HTML::script( asset('js/pdf/jspdf/libs/base64.js') ); ?>

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
        var type = invoice.is_quote ? '<?php echo e(trans('texts.'.ENTITY_QUOTE)); ?>' : '<?php echo e(trans('texts.'.ENTITY_INVOICE)); ?>';
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
            if (val == '<?php echo e(ENTITY_TAX_RATE); ?>') {
                $('#dateField').fadeIn();
            } else {
                $('#dateField').fadeOut();
            }
        });
    })
 </script>

<?php $__env->stopSection(); ?>


<?php $__env->startSection('onReady'); ?>

 $('#start_date, #end_date').datepicker({
  autoclose: true,
  todayHighlight: true,
  keyboardNavigation: false
 });

<?php $__env->stopSection(); ?>
<?php echo $__env->make('header', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>