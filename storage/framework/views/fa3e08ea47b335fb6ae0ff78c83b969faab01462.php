<?php $__env->startSection('head'); ?>
	@parent

    <?php echo $__env->make('money_script', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

	<script src="<?php echo asset('js/Chart.min.js'); ?>" type="text/javascript"></script>
    <script src="<?php echo e(asset('js/daterangepicker.min.js')); ?>" type="text/javascript"></script>
    <link href="<?php echo e(asset('css/daterangepicker.css')); ?>" rel="stylesheet" type="text/css"/>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<script type="text/javascript">

    <?php if(Auth::user()->hasPermission('view_all')): ?>
        function loadChart(data) {
            var ctx = document.getElementById('chart-canvas').getContext('2d');

            if (window.myChart) {
                window.myChart.config.data = data;
                window.myChart.config.options.scales.xAxes[0].time.unit = chartGropuBy.toLowerCase();
                window.myChart.config.options.scales.xAxes[0].time.round = chartGropuBy.toLowerCase();
                window.myChart.update();
            } else {
                $('#progress-div').hide();
                $('#chart-canvas').fadeIn();
                window.myChart = new Chart(ctx, {
                    type: 'line',
                    data: data,
                    options: {
                        tooltips: {
                            mode: 'x-axis',
                            titleFontSize: 15,
                            titleMarginBottom: 12,
                            bodyFontSize: 15,
                            bodySpacing: 10,
                            callbacks: {
                                title: function(item) {
                                    return moment(item[0].xLabel).format("<?php echo e($account->getMomentDateFormat()); ?>");
                                },
                                label: function(item, data) {
                                    if (item.datasetIndex == 0) {
                                        var label = " <?php echo e(trans('texts.invoices')); ?>: ";
                                    } else if (item.datasetIndex == 1) {
                                        var label = " <?php echo e(trans('texts.payments')); ?>: ";
                                    } else if (item.datasetIndex == 2) {
                                        var label = " <?php echo e(trans('texts.expenses')); ?>: ";
                                    }

                                    return label + formatMoney(item.yLabel, chartCurrencyId, account.country_id);
                                }
                            }
                        },
                        title: {
                            display: false,
                            fontSize: 18,
                            text: '<?php echo e(trans('texts.total_revenue')); ?>'
                        },
                        scales: {
                            xAxes: [{
                                type: 'time',
                                time: {
                                    unit: 'day',
                                    round: 'day',
                                },
                                gridLines: {
                                    display: false,
                                },
                            }],
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true,
                                    callback: function(label, index, labels) {
                                        return formatMoney(label, chartCurrencyId, account.country_id);
                                    }
                                },
                            }]
                        }
                    }
                });
            }
        }

        var account = <?php echo $account; ?>;
        var chartStartDate = moment().subtract(29, 'days');
        var chartEndDate = moment();
        var chartGropuBy = 'day';
        var chartCurrencyId = <?php echo e($account->getCurrencyId()); ?>;

        $(function() {

            // Initialize date range selector

            function cb(start, end) {
                $('#reportrange span').html(start.format('<?php echo e($account->getMomentDateFormat()); ?>') + ' - ' + end.format('<?php echo e($account->getMomentDateFormat()); ?>'));
                chartStartDate = start;
                chartEndDate = end;
                loadData();
            }

            $('#reportrange').daterangepicker({
                locale: {
                    "format": "<?php echo e($account->getMomentDateFormat()); ?>",
                },
                startDate: chartStartDate,
                endDate: chartEndDate,
                linkedCalendars: false,
                ranges: {
                   'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                   'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                   'This Month': [moment().startOf('month'), moment().endOf('month')],
                   'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            }, cb);

            cb(chartStartDate, chartEndDate);

            $("#currency-btn-group > .btn").click(function(){
                $(this).addClass("active").siblings().removeClass("active");
                chartCurrencyId = currencyMap[$(this).text()].id;
                loadData();
            });

            $("#group-btn-group > .btn").click(function(){
                $(this).addClass("active").siblings().removeClass("active");
                chartGropuBy = $(this).text();
                loadData();
            });

            function loadData() {
                var includeExpenses = "<?php echo e(count($expenses) ? 'true' : 'false'); ?>";
                var url = "<?php echo url('/dashboard_chart_data'); ?>/" + chartGropuBy + '/' + chartStartDate.format('YYYY-MM-DD') + '/' + chartEndDate.format('YYYY-MM-DD') + '/' + chartCurrencyId + '/' + includeExpenses;
                $.get(url, function(response) {
                    response = JSON.parse(response);
                    loadChart(response.data);

                    var totals = response.totals;
                    $('.revenue-div').text(formatMoney(totals.revenue, chartCurrencyId, account.country_id));
                    $('.outstanding-div').text(formatMoney(totals.balance, chartCurrencyId, account.country_id));
                    $('.expenses-div').text(formatMoney(totals.expenses, chartCurrencyId, account.country_id));
                    $('.average-div').text(formatMoney(totals.average, chartCurrencyId, account.country_id));

                    $('.currency').hide();
                    $('.currency_' + chartCurrencyId).show();
                })
            }

        });
    <?php else: ?>
        $(function() {
            $('.currency').show();
        })
    <?php endif; ?>

</script>

<div class="row">
    <div class="col-md-2">
        <ol class="breadcrumb"><li class='active'><?php echo e(trans('texts.dashboard')); ?></li></ol>
    </div>
    <?php if(count($tasks)): ?>
        <div class="col-md-2" style="padding-top:6px">
            <?php foreach($tasks as $task): ?>
                <?php echo Button::primary($task->present()->titledName)->small()->asLinkTo($task->present()->url); ?>

            <?php endforeach; ?>
        </div>
        <div class="col-md-8">
    <?php else: ?>
        <div class="col-md-10">
    <?php endif; ?>
        <?php if(Auth::user()->hasPermission('view_all')): ?>
        <div class="pull-right">
            <?php if(count($currencies) > 1): ?>
            <div id="currency-btn-group" class="btn-group" role="group" style="border: 1px solid #ccc;">
              <?php foreach($currencies as $key => $val): ?>
                <button type="button" class="btn btn-normal <?php echo e(array_values($currencies)[0] == $val ? 'active' : ''); ?>"
                    style="font-weight:normal !important;background-color:white"><?php echo e($val); ?></button>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div id="group-btn-group" class="btn-group" role="group" style="border: 1px solid #ccc; margin-left:18px">
              <button type="button" class="btn btn-normal active" style="font-weight:normal !important;background-color:white"><?php echo e(trans('texts.day')); ?></button>
              <button type="button" class="btn btn-normal" style="font-weight:normal !important;background-color:white"><?php echo e(trans('texts.week')); ?></button>
              <button type="button" class="btn btn-normal" style="font-weight:normal !important;background-color:white"><?php echo e(trans('texts.month')); ?></button>
            </div>
            <div id="reportrange" class="pull-right" style="background: #fff; cursor: pointer; padding: 9px 14px; border: 1px solid #ccc; margin-top: 0px; margin-left:18px">
                <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>&nbsp;
                <span></span> <b class="caret"></b>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-body">
                <div style="overflow:hidden">
                    <div class="in-thin">
                        <?php echo e(trans('texts.total_revenue')); ?>

                    </div>
                    <div class="revenue-div in-bold pull-right" style="color:#337ab7">
                    </div>
                    <div class="in-bold">
                        <?php if(count($paidToDate)): ?>
                            <?php foreach($paidToDate as $item): ?>
                                <div class="currency currency_<?php echo e($item->currency_id ?: $account->getCurrencyId()); ?>" style="display:none">
                                    <?php echo e(Utils::formatMoney($item->value, $item->currency_id)); ?>

                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="currency currency_<?php echo e($account->getCurrencyId()); ?>" style="display:none">
                                <?php echo e(Utils::formatMoney(0)); ?>

                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-body">
                <div style="overflow:hidden">
                    <?php if(count($expenses)): ?>
                        <div class="in-thin">
                            <?php echo e(trans('texts.total_expenses')); ?>

                        </div>
                        <div class="expenses-div in-bold pull-right" style="color:#337ab7">
                        </div>
                        <div class="in-bold">
                            <?php foreach($expenses as $item): ?>
                                <div class="currency currency_<?php echo e($item->currency_id ?: $account->getCurrencyId()); ?>" style="display:none">
                                    <?php echo e(Utils::formatMoney($item->value, $item->currency_id)); ?><br/>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="in-thin">
                            <?php echo e(trans('texts.average_invoice')); ?>

                        </div>
                        <div class="average-div in-bold pull-right" style="color:#337ab7">
                        </div>
                        <div class="in-bold">
                            <?php if(count($averageInvoice)): ?>
                                <?php foreach($averageInvoice as $item): ?>
                                    <div class="currency currency_<?php echo e($item->currency_id ?: $account->getCurrencyId()); ?>" style="display:none">
                                        <?php echo e(Utils::formatMoney($item->invoice_avg, $item->currency_id)); ?><br/>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="currency currency_<?php echo e($account->getCurrencyId()); ?>" style="display:none">
                                    <?php echo e(Utils::formatMoney(0)); ?>

                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-body">
                <div style="overflow:hidden">
                    <div class="in-thin">
                        <?php echo e(trans('texts.outstanding')); ?>

                    </div>
                    <div class="outstanding-div in-bold pull-right" style="color:#337ab7">
                    </div>
                    <div class="in-bold">
                        <?php if(count($balances)): ?>
                            <?php foreach($balances as $item): ?>
                                <div class="currency currency_<?php echo e($item->currency_id ?: $account->getCurrencyId()); ?>" style="display:none">
                                    <?php echo e(Utils::formatMoney($item->value, $item->currency_id)); ?><br/>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="currency currency_<?php echo e($account->getCurrencyId()); ?>" style="display:none">
                                <?php echo e(Utils::formatMoney(0)); ?>

                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if(Auth::user()->hasPermission('view_all')): ?>
<div class="row">
    <div class="col-md-12">
        <div id="progress-div" class="progress">
            <div class="progress-bar progress-bar-striped active" role="progressbar"
                aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%"></div>
        </div>
        <canvas id="chart-canvas" height="70px" style="background-color:white;padding:20px;display:none"></canvas>
    </div>
</div>
<p>&nbsp;</p>
<?php endif; ?>

<div class="row">
    <!--<div class="col-md-6">
        <div class="panel panel-default dashboard" style="height:320px">
            <div class="panel-heading" style="background-color:#286090 !important">
                <h3 class="panel-title in-bold-white">
                    <i class="glyphicon glyphicon-exclamation-sign"></i> <?php echo e(trans('texts.activity')); ?>

                    <?php if($invoicesSent): ?>
                        <div class="pull-right" style="font-size:14px;padding-top:4px">
                            <?php echo e(trans_choice('texts.invoices_sent', $invoicesSent)); ?>

                        </div>
                    <?php endif; ?>
                </h3>
            </div>
            <ul class="panel-body list-group" style="height:276px;overflow-y:auto;">
                <?php foreach($activities as $activity): ?>
                <li class="list-group-item">
                    <span style="color:#888;font-style:italic"><?php echo e(Utils::timestampToDateString(strtotime($activity->created_at))); ?>:</span>
                    <?php echo $activity->getMessage(); ?>

                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>//-->

    <div class="col-md-6">
        <div class="panel panel-default dashboard" style="height:320px;">
            <div class="panel-heading" style="margin:0; background-color: #f5f5f5 !important;">
                <h3 class="panel-title" style="color: black !important">
                    <?php if(count($expenses) && count($averageInvoice)): ?>
                        <div class="pull-right" style="font-size:14px;padding-top:4px;font-weight:bold">
                            <?php foreach($averageInvoice as $item): ?>
                                <span class="currency currency_<?php echo e($item->currency_id ?: $account->getCurrencyId()); ?>" style="display:none">
                                    <?php echo e(trans('texts.average_invoice')); ?>

                                    <?php echo e(Utils::formatMoney($item->invoice_avg, $item->currency_id)); ?> |
                                </span>
                            <?php endforeach; ?>
                            <span class="average-div" style="color:#337ab7"/>
                        </div>
                    <?php endif; ?>
                    <i class="glyphicon glyphicon-ok-sign"></i> <?php echo e(trans('texts.recent_payments')); ?>

                </h3>
            </div>
            <div class="panel-body" style="height:274px;overflow-y:auto;">
                <table class="table table-striped">
                    <thead>
                        <th><?php echo e(trans('texts.invoice_number_short')); ?></th>
                        <th><?php echo e(trans('texts.client')); ?></th>
                        <th><?php echo e(trans('texts.payment_date')); ?></th>
                        <th><?php echo e(trans('texts.amount')); ?></th>
                    </thead>
                    <tbody>
                        <?php foreach($payments as $payment): ?>
                        <tr>
                            <td><?php echo \App\Models\Invoice::calcLink($payment); ?></td>
                            <?php if (app('Illuminate\Contracts\Auth\Access\Gate')->check('viewByOwner', [ENTITY_CLIENT, $payment->client_user_id])): ?>
                                <td><?php echo link_to('/clients/'.$payment->client_public_id, trim($payment->client_name) ?: (trim($payment->first_name . ' ' . $payment->last_name) ?: $payment->email)); ?></td>
                            <?php else: ?>
                                <td><?php echo e(trim($payment->client_name) ?: (trim($payment->first_name . ' ' . $payment->last_name) ?: $payment->email)); ?></td>
                            <?php endif; ?>
                            <td><?php echo e(Utils::fromSqlDate($payment->payment_date)); ?></td>
                            <td><?php echo e(Utils::formatMoney($payment->amount, $payment->currency_id ?: ($account->currency_id ?: DEFAULT_CURRENCY))); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="panel panel-default dashboard" style="height:320px;">
            <div class="panel-heading" style="margin:0; background-color: #f5f5f5 !important;">
                <h3 class="panel-title" style="color: black !important">
                    <i class="glyphicon glyphicon-time"></i> <?php echo e(trans('texts.upcoming_invoices')); ?>

                </h3>
            </div>
            <div class="panel-body" style="height:274px;overflow-y:auto;">
                <table class="table table-striped">
                    <thead>
                        <th><?php echo e(trans('texts.invoice_number_short')); ?></th>
                        <th><?php echo e(trans('texts.client')); ?></th>
                        <th><?php echo e(trans('texts.due_date')); ?></th>
                        <th><?php echo e(trans('texts.balance_due')); ?></th>
                    </thead>
                    <tbody>
                        <?php foreach($upcoming as $invoice): ?>
                            <?php if($invoice->invoice_type_id == INVOICE_TYPE_STANDARD): ?>
                                <tr>
                                    <td><?php echo \App\Models\Invoice::calcLink($invoice); ?></td>
                                    <?php if (app('Illuminate\Contracts\Auth\Access\Gate')->check('viewByOwner', [ENTITY_CLIENT, $invoice->client_user_id])): ?>
                                        <td><?php echo link_to('/clients/'.$invoice->client_public_id, trim($invoice->client_name) ?: (trim($invoice->first_name . ' ' . $invoice->last_name) ?: $invoice->email)); ?></td>
                                    <?php else: ?>
                                        <td><?php echo e(trim($invoice->client_name) ?: (trim($invoice->first_name . ' ' . $invoice->last_name) ?: $invoice->email)); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo e(Utils::fromSqlDate($invoice->due_date)); ?></td>
                                    <td><?php echo e(Utils::formatMoney($invoice->balance, $invoice->currency_id ?: ($account->currency_id ?: DEFAULT_CURRENCY))); ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
    <div class="row">
    <div class="col-md-6">
        <div class="panel panel-default dashboard" style="height:320px">
            <div class="panel-heading" style="background-color:#777 !important">
                <h3 class="panel-title in-bold-white">
                    <i class="glyphicon glyphicon-time"></i> <?php echo e(trans('texts.invoices_past_due')); ?>

                </h3>
            </div>
            <div class="panel-body" style="height:274px;overflow-y:auto;">
                <table class="table table-striped">
                    <thead>
                        <th><?php echo e(trans('texts.invoice_number_short')); ?></th>
                        <th><?php echo e(trans('texts.client')); ?></th>
                        <th><?php echo e(trans('texts.due_date')); ?></th>
                        <th><?php echo e(trans('texts.balance_due')); ?></th>
                    </thead>
                    <tbody>
                        <?php foreach($pastDue as $invoice): ?>
                            <?php if($invoice->invoice_type_id == INVOICE_TYPE_STANDARD): ?>
                                <tr>
                                    <td><?php echo \App\Models\Invoice::calcLink($invoice); ?></td>
                                    <?php if (app('Illuminate\Contracts\Auth\Access\Gate')->check('viewByOwner', [ENTITY_CLIENT, $invoice->client_user_id])): ?>
                                        <td><?php echo link_to('/clients/'.$invoice->client_public_id, trim($invoice->client_name) ?: (trim($invoice->first_name . ' ' . $invoice->last_name) ?: $invoice->email)); ?></td>
                                    <?php else: ?>
                                        <td><?php echo e(trim($invoice->client_name) ?: (trim($invoice->first_name . ' ' . $invoice->last_name) ?: $invoice->email)); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo e(Utils::fromSqlDate($invoice->due_date)); ?></td>
                                    <td><?php echo e(Utils::formatMoney($invoice->balance, $invoice->currency_id ?: ($account->currency_id ?: DEFAULT_CURRENCY))); ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if($hasQuotes): ?>
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default dashboard" style="height:320px;">
                <div class="panel-heading" style="margin:0; background-color: #f5f5f5 !important;">
                    <h3 class="panel-title" style="color: black !important">
                        <i class="glyphicon glyphicon-time"></i> <?php echo e(trans('texts.upcoming_quotes')); ?>

                    </h3>
                </div>
                <div class="panel-body" style="height:274px;overflow-y:auto;">
                    <table class="table table-striped">
                        <thead>
                            <th><?php echo e(trans('texts.quote_number_short')); ?></th>
                            <th><?php echo e(trans('texts.client')); ?></th>
                            <th><?php echo e(trans('texts.valid_until')); ?></th>
                            <th><?php echo e(trans('texts.amount')); ?></th>
                        </thead>
                        <tbody>
                            <?php foreach($upcoming as $invoice): ?>
                                <?php if($invoice->invoice_type_id == INVOICE_TYPE_QUOTE): ?>
                                    <tr>
                                        <td><?php echo \App\Models\Invoice::calcLink($invoice); ?></td>
                                        <td><?php echo link_to('/clients/'.$invoice->client_public_id, trim($invoice->client_name) ?: (trim($invoice->first_name . ' ' . $invoice->last_name) ?: $invoice->email)); ?></td>
                                        <td><?php echo e(Utils::fromSqlDate($invoice->due_date)); ?></td>
                                        <td><?php echo e(Utils::formatMoney($invoice->balance, $invoice->currency_id ?: ($account->currency_id ?: DEFAULT_CURRENCY))); ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel panel-default dashboard" style="height:320px">
                <div class="panel-heading" style="background-color:#777 !important">
                    <h3 class="panel-title in-bold-white">
                        <i class="glyphicon glyphicon-time"></i> <?php echo e(trans('texts.expired_quotes')); ?>

                    </h3>
                </div>
                <div class="panel-body" style="height:274px;overflow-y:auto;">
                    <table class="table table-striped">
                        <thead>
                            <th><?php echo e(trans('texts.quote_number_short')); ?></th>
                            <th><?php echo e(trans('texts.client')); ?></th>
                            <th><?php echo e(trans('texts.valid_until')); ?></th>
                            <th><?php echo e(trans('texts.amount')); ?></th>
                        </thead>
                        <tbody>
                            <?php foreach($pastDue as $invoice): ?>
                                <?php if($invoice->invoice_type_id == INVOICE_TYPE_QUOTE): ?>
                                    <tr>
                                        <td><?php echo \App\Models\Invoice::calcLink($invoice); ?></td>
                                        <td><?php echo link_to('/clients/'.$invoice->client_public_id, trim($invoice->client_name) ?: (trim($invoice->first_name . ' ' . $invoice->last_name) ?: $invoice->email)); ?></td>
                                        <td><?php echo e(Utils::fromSqlDate($invoice->due_date)); ?></td>
                                        <td><?php echo e(Utils::formatMoney($invoice->balance, $invoice->currency_id ?: ($account->currency_id ?: DEFAULT_CURRENCY))); ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script type="text/javascript">
    $(function() {
        $('.normalDropDown:not(.dropdown-toggle)').click(function() {
            window.location = '<?php echo e(URL::to('invoices/create')); ?>';
        });
    });
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('header', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>