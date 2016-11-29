@extends('header')

@section('head')
    @parent

    @include('money_script')

    <style type="text/css">
        .input-group-addon {
            min-width: 40px;
        }
    </style>
@stop

@section('content')
    
    {!! Former::open($url)->addClass('col-md-10 col-md-offset-1 warn-on-exit')->method($method)->rules(array(
        'client' => 'required',
        'Invoice0' => 'required',        
        'amount' => 'required',     
    )) !!}

    @if ($payment)
        {!! Former::populate($payment) !!}
    @endif

    <span style="display:none">
        {!! Former::text('public_id') !!}
    </span>
    
    <input type="hidden" value="1" name="invoice_count" id="invoice_count">

    <div class="row">
        <div class="col-md-10 col-md-offset-1">

            <div class="panel panel-default">
            <div class="panel-body">

            @if (!$payment)
            {!! Former::select('client')->addOption('', '')->addGroupClass('client-select') !!}
            <?php
                for ($i = 0; $i < count($invoices); $i ++)
                {
            ?>
             <div class="row" id="<?='invoice_row'.$i?>" style="<?php if ($i != 0) echo 'display: none;'?>">
                 <div class="col-md-1"></div>
                 <div class="col-md-5">
                     {!! Former::select('Invoice'.$i)->addOption('', '')->addGroupClass('invoice-select') !!}
                 </div>
                 <div class="col-md-5">
                     {!! Former::text('Amount'.$i) !!}
                 </div>

                 <div class="col-md-1">
                     <?php
                        if ($i == 0){
                     ?>
                     <img id="add_invoice_for_payment" src="{{ URL::to('/') }}/btn_add_invoice.png" alt="add invoice" onclick="add_invoice();" style="margin-bottom:17px;" />
                     <?php
                        }
                     ?>
                 </div>
             </div>
             <?php
                }
             ?>
             
             @if (isset($paymentTypeId) && $paymentTypeId)
               {!! Former::populateField('payment_type_id', $paymentTypeId) !!}
             @endif
            @endif

            @if (!$payment || !$payment->account_gateway_id)
             {!! Former::select('payment_type_id')
                    ->addOption('','')
                    ->fromQuery($paymentTypes, 'name', 'id')
                    ->addGroupClass('payment-type-select') !!}
            @endif

            {!! Former::text('payment_date')
                        ->data_date_format(Session::get(SESSION_DATE_PICKER_FORMAT))
                        ->addGroupClass('payment_date')
                        ->append('<i class="glyphicon glyphicon-calendar"></i>') !!}
            {!! Former::text('transaction_reference') !!}

            @if (!$payment)
                {!! Former::checkbox('email_receipt')->label('&nbsp;')->text(trans('texts.email_receipt')) !!}
            @endif

            </div>
            </div>

        </div>
    </div>


    <center class="buttons">
        {!! Button::normal(trans('texts.cancel'))->appendIcon(Icon::create('remove-circle'))->asLinkTo(URL::to('/payments'))->large() !!}
        <?php
        if (count($invoices) > 0){
        ?>
        {!! Button::success(trans('texts.save'))->appendIcon(Icon::create('floppy-disk'))->submit()->large() !!}
        <?php
            }
        ?>
    </center>

    {!! Former::close() !!}

    <script type="text/javascript">

    var invoices = {!! $invoices !!};
    var clients = {!! $clients !!};

    $(function() {
        @if ($payment)
          $('#payment_date').datepicker('update', '{{ $payment->payment_date }}')
        @else
          $('#payment_date').datepicker('update', new Date());
          populateInvoiceComboboxes({{ $clientPublicId }}, {{ $invoicePublicId }});
        @endif

        $('#payment_type_id').combobox();       

        @if (!$payment && !$clientPublicId)
            $('.client-select input.form-control').focus();
        @elseif (!$payment && !$invoicePublicId)
            $('.invoice-select input.form-control').focus();
        @elseif (!$payment)
            $('#amount').focus();
        @endif

        $('.payment_date .input-group-addon').click(function() {
            toggleDatePicker('payment_date');
        });
    });

    function add_invoice()
    {        
        for (var i = 0; i < invoices.length; i ++)
        {
            if ($('#invoice_row'+i).css('display') == "none")
            {
                $('#invoice_row'+i).show();
                $('#invoice_count').val(i + 1);
                break;
            }
        }
    }
    </script>

@stop