<?php $__env->startSection('head'); ?>
    @parent

    <?php if($client->hasAddress()): ?>
        <style>
          #map {
            width: 100%;
            height: 200px;
            border-width: 1px;
            border-style: solid;
            border-color: #ddd;
          }
        </style>

        <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo e(env('GOOGLE_MAPS_API_KEY')); ?>"></script>
    <?php endif; ?>
<?php $__env->stopSection(); ?>


<?php $__env->startSection('content'); ?>

    <div class="row">
        <div class="col-md-7">
            <div>
                <span style="font-size:28px"><?php echo e($client->getDisplayName()); ?></span>
                <?php if($client->trashed()): ?>
                    &nbsp;&nbsp;<?php echo $client->present()->status; ?>

                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-5">
            <div class="pull-right">
                <?php echo Former::open('clients/bulk')->addClass('mainForm'); ?>

                <div style="display:none">
                    <?php echo Former::text('action'); ?>

                    <?php echo Former::text('public_id')->value($client->public_id); ?>

                </div>
 <!--
                <img src="<?php echo e(URL::to('/')); ?>/btn_report.png" onclick="alert('report');" width="101px" height="40px" />
				
				-->
		
                <?php if($gatewayLink): ?>
                    <?php echo Button::normal(trans('texts.view_in_gateway', ['gateway'=>$gatewayName]))
                            ->asLinkTo($gatewayLink)
                            ->withAttributes(['target' => '_blank']); ?>

                <?php endif; ?>

                <?php if($client->trashed()): ?>
                    <?php if (app('Illuminate\Contracts\Auth\Access\Gate')->check('edit', $client)): ?>
                        <?php echo Button::primary(trans('texts.restore_client'))->withAttributes(['onclick' => 'onRestoreClick()']); ?>

                    <?php endif; ?>
                <?php else: ?>
                    <?php if (app('Illuminate\Contracts\Auth\Access\Gate')->check('edit', $client)): ?>
                    <?php echo DropdownButton::normal(trans('texts.edit_client'))
                        ->withAttributes(['class'=>'normalDropDown'])
                        ->withContents([
                          ['label' => trans('texts.archive_client'), 'url' => "javascript:onArchiveClick()"],
                          ['label' => trans('texts.delete_client'), 'url' => "javascript:onDeleteClick()"],
                        ]
                      )->split(); ?>

                    <?php endif; ?>
                    <?php if (app('Illuminate\Contracts\Auth\Access\Gate')->check('create', ENTITY_INVOICE)): ?>
                        <?php echo DropdownButton::primary(trans('texts.new_invoice'))
                                ->withAttributes(['class'=>'primaryDropDown'])
                                ->withContents($actionLinks)->split(); ?>

                    <?php endif; ?>
                <?php endif; ?>
              <?php echo Former::close(); ?>


            </div>
        </div>
    </div>

	<?php if($client->last_login > 0): ?>
	<h3 style="margin-top:0px"><small>
		<?php echo e(trans('texts.last_logged_in')); ?> <?php echo e(Utils::timestampToDateTimeString(strtotime($client->last_login))); ?>

	</small></h3>
	<?php endif; ?>
    <br/>

    <div class="panel panel-default">
    <div class="panel-body">
	<div class="row">

		<div class="col-md-3">
			<h3><?php echo e(trans('texts.details')); ?></h3>
            <?php if($client->id_number): ?>
                <p><i class="fa fa-id-number" style="width: 20px"></i><?php echo e(trans('texts.id_number').': '.$client->id_number); ?></p>
            <?php endif; ?>
            <?php if($client->vat_number): ?>
		  	   <p><i class="fa fa-vat-number" style="width: 20px"></i><?php echo e(trans('texts.vat_number').': '.$client->vat_number); ?></p>
            <?php endif; ?>

            <?php if($client->address1): ?>
                <?php echo e($client->address1); ?><br/>
            <?php endif; ?>
            <?php if($client->address2): ?>
                <?php echo e($client->address2); ?><br/>
            <?php endif; ?>
            <?php if($client->getCityState()): ?>
                <?php echo e($client->getCityState()); ?><br/>
            <?php endif; ?>
            <?php if($client->country): ?>
                <?php echo e($client->country->name); ?><br/>
            <?php endif; ?>

            <?php if($client->account->custom_client_label1 && $client->custom_value1): ?>
                <?php echo e($client->account->custom_client_label1 . ': ' . $client->custom_value1); ?><br/>
            <?php endif; ?>
            <?php if($client->account->custom_client_label2 && $client->custom_value2): ?>
                <?php echo e($client->account->custom_client_label2 . ': ' . $client->custom_value2); ?><br/>
            <?php endif; ?>

            <?php if($client->work_phone): ?>
                <i class="fa fa-phone" style="width: 20px"></i><?php echo e($client->work_phone); ?>

            <?php endif; ?>

            <?php if($client->private_notes): ?>
                <p><i><?php echo e($client->private_notes); ?></i></p>
            <?php endif; ?>

  	        <?php if($client->client_industry): ?>
                <?php echo e($client->client_industry->name); ?><br/>
            <?php endif; ?>
            <?php if($client->client_size): ?>
                <?php echo e($client->client_size->name); ?><br/>
            <?php endif; ?>

		  	<?php if($client->website): ?>
		  	   <p><?php echo Utils::formatWebsite($client->website); ?></p>
            <?php endif; ?>

            <?php if($client->language): ?>
                <p><i class="fa fa-language" style="width: 20px"></i><?php echo e($client->language->name); ?></p>
            <?php endif; ?>

		  	<p><?php echo e($client->payment_terms ? trans('texts.payment_terms') . ": Net " . $client->payment_terms : ''); ?></p>
		</div>

		<div class="col-md-3">
			<h3><?php echo e(trans('texts.contacts')); ?></h3>
		  	<?php foreach($client->contacts as $contact): ?>
                <?php if($contact->first_name || $contact->last_name): ?>
                    <b><?php echo e($contact->first_name.' '.$contact->last_name); ?></b><br/>
                <?php endif; ?>
                <?php if($contact->email): ?>
                    <i class="fa fa-envelope" style="width: 20px"></i><?php echo HTML::mailto($contact->email, $contact->email); ?><br/>
                <?php endif; ?>
                <?php if($contact->phone): ?>
                    <i class="fa fa-phone" style="width: 20px"></i><?php echo e($contact->phone); ?><br/>
                <?php endif; ?>
                <?php if(Auth::user()->confirmed && $client->account->enable_client_portal): ?>
                    <i class="fa fa-dashboard" style="width: 20px"></i><a href="<?php echo e($contact->link); ?>" target="_blank"><?php echo e(trans('texts.view_client_portal')); ?></a><br/>
                <?php endif; ?>
		  	<?php endforeach; ?>
		</div>

		<div class="col-md-4">
			<h3><?php echo e(trans('texts.standing')); ?>

			<table class="table" style="width:100%">
				<tr>
					<td><small><?php echo e(trans('texts.paid_to_date')); ?></small></td>
					<td style="text-align: right"><?php echo e(Utils::formatMoney($client->paid_to_date, $client->getCurrencyId())); ?></td>
				</tr>
				<tr>
					<td><small><?php echo e(trans('texts.balance')); ?></small></td>
					<td style="text-align: right"><?php echo e(Utils::formatMoney($client->balance, $client->getCurrencyId())); ?></td>
				</tr>
				<?php if($credit > 0): ?>
				<tr>
					<td><small><?php echo e(trans('texts.credit')); ?></small></td>
					<td style="text-align: right"><?php echo e(Utils::formatMoney($credit, $client->getCurrencyId())); ?></td>
				</tr>
				<?php endif; ?>
			</table>
			</h3>
		</div>
	</div>
    </div>
    </div>

    <!--
    <?php if($client->hasAddress()): ?>
        <div id="map"></div>
        <br/>
    <?php endif; ?>
    //-->

	<ul class="nav nav-tabs nav-justified">
		<!--<?php echo Form::tab_link('#activity', trans('texts.activity'), true); ?>//-->
        <?php if($hasTasks): ?>
            <?php echo Form::tab_link('#tasks', trans('texts.tasks')); ?>

        <?php endif; ?>
		<?php if($hasQuotes && Utils::isPro()): ?>
			<?php echo Form::tab_link('#quotes', trans('texts.quotes')); ?>

		<?php endif; ?>
		<?php echo Form::tab_link('#invoices', trans('texts.invoices')); ?>

		<?php echo Form::tab_link('#payments', trans('texts.payments')); ?>

        <!--
		<?php echo Form::tab_link('#credits', trans('texts.credits')); ?>

        //-->
	</ul>

	<div class="tab-content">
    <!--
        <div class="tab-pane active" id="activity">

			<?php echo Datatable::table()
		    	->addColumn(
		    		trans('texts.date'),
		    		trans('texts.message'),
		    		trans('texts.balance'),
		    		trans('texts.adjustment'))
		    	->setUrl(url('api/activities/'. $client->public_id))
                ->setCustomValues('entityType', 'activity')
		    	->setOptions('sPaginationType', 'bootstrap')
		    	->setOptions('bFilter', false)
		    	->setOptions('aaSorting', [['0', 'desc']])
		    	->render('datatable'); ?>


        </div>
    //-->
    <?php if($hasTasks): ?>
        <div class="tab-pane" id="tasks">

            <?php echo Datatable::table()
                ->addColumn(
                    trans('texts.date'),
                    trans('texts.duration'),
                    trans('texts.description'),
                    trans('texts.status'))
                ->setUrl(url('api/tasks/'. $client->public_id))
                ->setCustomValues('entityType', 'tasks')
                ->setOptions('sPaginationType', 'bootstrap')
                ->setOptions('bFilter', false)
                ->setOptions('aaSorting', [['0', 'desc']])
                ->render('datatable'); ?>


        </div>
    <?php endif; ?>


    <?php if(Utils::hasFeature(FEATURE_QUOTES) && $hasQuotes): ?>
        <div class="tab-pane" id="quotes">

			<?php echo Datatable::table()
		    	->addColumn(
	    			trans('texts.quote_number'),
	    			trans('texts.quote_date'),
	    			trans('texts.total'),
	    			trans('texts.valid_until'),
	    			trans('texts.status'))
		    	->setUrl(url('api/quotes/'. $client->public_id))
                ->setCustomValues('entityType', 'quotes')
		    	->setOptions('sPaginationType', 'bootstrap')
		    	->setOptions('bFilter', false)
		    	->setOptions('aaSorting', [['0', 'desc']])
		    	->render('datatable'); ?>


        </div>
    <?php endif; ?>

		<div class="tab-pane" id="invoices">

			<?php if($hasRecurringInvoices): ?>
				<?php echo Datatable::table()
			    	->addColumn(
			    		trans('texts.frequency_id'),
			    		trans('texts.start_date'),
			    		trans('texts.end_date'),
			    		trans('texts.invoice_total'))
			    	->setUrl(url('api/recurring_invoices/' . $client->public_id))
                    ->setCustomValues('entityType', 'recurring_invoices')
			    	->setOptions('sPaginationType', 'bootstrap')
			    	->setOptions('bFilter', false)
			    	->setOptions('aaSorting', [['0', 'asc']])
			    	->render('datatable'); ?>

			<?php endif; ?>

			<?php echo Datatable::table()
		    	->addColumn(
		    			trans('texts.invoice_number'),
		    			trans('texts.invoice_date'),
		    			trans('texts.invoice_total'),
		    			trans('texts.balance_due'),
		    			trans('texts.due_date'),
		    			trans('texts.status'))
		    	->setUrl(url('api/invoices/' . $client->public_id))
                ->setCustomValues('entityType', 'invoices')
		    	->setOptions('sPaginationType', 'bootstrap')
		    	->setOptions('bFilter', false)
		    	->setOptions('aaSorting', [['0', 'desc']])
		    	->render('datatable'); ?>


        </div>
        <div class="tab-pane" id="payments">

	    	<?php echo Datatable::table()
						->addColumn(
			    			trans('texts.invoice'),
			    			trans('texts.transaction_reference'),
			    			trans('texts.method'),
                            trans('texts.source'),
			    			trans('texts.payment_amount'),
			    			trans('texts.payment_date'),
                            trans('texts.status'))
				->setUrl(url('api/payments/' . $client->public_id))
                ->setCustomValues('entityType', 'payments')
				->setOptions('sPaginationType', 'bootstrap')
				->setOptions('bFilter', false)
				->setOptions('aaSorting', [['0', 'desc']])
				->render('datatable'); ?>


        </div>
        <!--
        <div class="tab-pane" id="credits">

	    	<?php echo Datatable::table()
						->addColumn(
								trans('texts.credit_amount'),
								trans('texts.credit_balance'),
								trans('texts.credit_date'),
								trans('texts.private_notes'))
				->setUrl(url('api/credits/' . $client->public_id))
                ->setCustomValues('entityType', 'credits')
				->setOptions('sPaginationType', 'bootstrap')
				->setOptions('bFilter', false)
				->setOptions('aaSorting', [['0', 'asc']])
				->render('datatable'); ?>


        </div>
        //-->
    </div>

	<script type="text/javascript">

    var loadedTabs = {};

	$(function() {
		$('.normalDropDown:not(.dropdown-toggle)').click(function() {
			window.location = '<?php echo e(URL::to('clients/' . $client->public_id . '/edit')); ?>';
		});
		$('.primaryDropDown:not(.dropdown-toggle)').click(function() {
			window.location = '<?php echo e(URL::to('invoices/create/' . $client->public_id )); ?>';
		});

        // load datatable data when tab is shown and remember last tab selected
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
          var target = $(e.target).attr("href") // activated tab
          target = target.substring(1);
          localStorage.setItem('client_tab', target);
          if (!loadedTabs.hasOwnProperty(target)) {
            loadedTabs[target] = true;
            window['load_' + target]();
            if (target == 'invoices' && window.hasOwnProperty('load_recurring_invoices')) {
                window['load_recurring_invoices']();
            }
          }
        });
        var tab = localStorage.getItem('client_tab') || '';
        var selector = '.nav-tabs a[href="#' + tab.replace('#', '') + '"]';
        if (tab && tab != 'activity' && $(selector).length) {
            $(selector).tab('show');
        } else {
            window['load_activity']();
        }
	});

	function onArchiveClick() {
		$('#action').val('archive');
		$('.mainForm').submit();
	}

	function onRestoreClick() {
		$('#action').val('restore');
		$('.mainForm').submit();
	}

	function onDeleteClick() {
		sweetConfirm(function() {
			$('#action').val('delete');
			$('.mainForm').submit();
		});
	}

    <?php if($client->hasAddress()): ?>
        function initialize() {
            var mapCanvas = document.getElementById('map');
            var mapOptions = {
                zoom: <?php echo e(DEFAULT_MAP_ZOOM); ?>,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                zoomControl: true,
            };

            var map = new google.maps.Map(mapCanvas, mapOptions)
            var address = "<?php echo e("{$client->address1} {$client->address2} {$client->city} {$client->state} {$client->postal_code} " . ($client->country ? $client->country->name : '')); ?>";

            geocoder = new google.maps.Geocoder();
            geocoder.geocode( { 'address': address}, function(results, status) {
                if (status == google.maps.GeocoderStatus.OK) {
                  if (status != google.maps.GeocoderStatus.ZERO_RESULTS) {
                    var result = results[0];
                    map.setCenter(result.geometry.location);

                    var infowindow = new google.maps.InfoWindow(
                        { content: '<b>'+result.formatted_address+'</b>',
                        size: new google.maps.Size(150, 50)
                    });

                    var marker = new google.maps.Marker({
                        position: result.geometry.location,
                        map: map,
                        title:address,
                    });
                    google.maps.event.addListener(marker, 'click', function() {
                        infowindow.open(map, marker);
                    });
                } else {
                    $('#map').hide();
                }
            } else {
              $('#map').hide();
          }
      });
    }

    google.maps.event.addDomListener(window, 'load', initialize);
    <?php endif; ?>

	</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('header', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>