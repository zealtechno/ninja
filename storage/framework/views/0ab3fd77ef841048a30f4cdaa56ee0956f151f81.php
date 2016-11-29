<?php $__env->startSection('head'); ?>
	@parent

    <?php echo $__env->make('money_script', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

    <?php foreach($account->getFontFolders() as $font): ?>
        <script src="<?php echo e(asset('js/vfs_fonts/'.$font.'.js')); ?>" type="text/javascript"></script>
    <?php endforeach; ?>
	<script src="<?php echo e(asset('pdf.built.js')); ?>?no_cache=<?php echo e(NINJA_VERSION); ?>" type="text/javascript"></script>
    <script src="<?php echo e(asset('js/lightbox.min.js')); ?>" type="text/javascript"></script>
    <link href="<?php echo e(asset('css/lightbox.css')); ?>" rel="stylesheet" type="text/css"/>

    <style type="text/css">
        /* the value is auto set so we're removing the bold formatting */
        label.control-label[for=invoice_number] {
            font-weight: normal !important;
        }

        select.tax-select {
            width: 50%;
            float: left;
        }

        #scrollable-dropdown-menu .tt-menu {
            max-height: 150px;
            overflow-y: auto;
            overflow-x: hidden;
        }
    </style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php if($errors->first('invoice_items')): ?>
        <div class="alert alert-danger"><?php echo e(trans($errors->first('invoice_items'))); ?></div>
    <?php endif; ?>

    <?php if($invoice->id): ?>
		<ol class="breadcrumb">
            <?php if($invoice->is_recurring): ?>
             <li><?php echo link_to('invoices', trans('texts.recurring_invoice')); ?></li>
            <?php else: ?>
			 <li><?php echo link_to(($entityType == ENTITY_QUOTE ? 'quotes' : 'invoices'), trans('texts.' . ($entityType == ENTITY_QUOTE ? 'quotes' : 'invoices'))); ?></li>
			 <li class="active"><?php echo e($invoice->invoice_number); ?></li>
            <?php endif; ?>
		</ol>
	<?php endif; ?>

	<?php echo Former::open($url)
            ->method($method)
            ->addClass('warn-on-exit main-form')
            ->autocomplete('off')
            ->onsubmit('return onFormSubmit(event)')
            ->rules(array(
        		'client' => 'required',
                'invoice_number' => 'required',
        		'product_key' => 'max:255'
        	)); ?>


    <?php echo $__env->make('partials.autocomplete_fix', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

	<input type="submit" style="display:none" name="submitButton" id="submitButton">

	<div data-bind="with: invoice">
    <div class="panel panel-default">
    <div class="panel-body">

    <div class="row" style="min-height:195px" onkeypress="formEnterClick(event)">
    	<div class="col-md-4" id="col_1">

    		<?php if($invoice->id || $data): ?>
				<div class="form-group">
					<label for="client" class="control-label col-lg-4 col-sm-4"><?php echo e(trans('texts.client')); ?></label>
					<div class="col-lg-8 col-sm-8">
                        <h4>
                            <span data-bind="text: getClientDisplayName(ko.toJS(client()))"></span>
                            <?php if($invoice->client->is_deleted): ?>
                                &nbsp;&nbsp;<div class="label label-danger"><?php echo e(trans('texts.deleted')); ?></div>
                            <?php endif; ?>
                        </h4>

                        <?php if (app('Illuminate\Contracts\Auth\Access\Gate')->check('view', $invoice->client)): ?>
                            <?php if (app('Illuminate\Contracts\Auth\Access\Gate')->check('edit', $invoice->client)): ?>
                                <a id="editClientLink" class="pointer" data-bind="click: $root.showClientForm"><?php echo e(trans('texts.edit_client')); ?></a> |
                            <?php endif; ?>
                            <?php echo link_to('/clients/'.$invoice->client->public_id, trans('texts.view_client'), ['target' => '_blank']); ?>

                        <?php endif; ?>
					</div>
				</div>
				<div style="display:none">
    		<?php endif; ?>

            <?php echo Former::select('client')->addOption('', '')->data_bind("dropdown: client")->addClass('client-input')->addGroupClass('client_select closer-row'); ?>


			<div class="form-group" style="margin-bottom: 8px">
				<div class="col-lg-8 col-sm-8 col-lg-offset-4 col-sm-offset-4">
					<a id="createClientLink" class="pointer" data-bind="click: $root.showClientForm, html: $root.clientLinkText"></a>
                    <span data-bind="visible: $root.invoice().client().public_id() > 0" style="display:none">|
                        <a data-bind="attr: {href: '<?php echo e(url('/clients')); ?>/' + $root.invoice().client().public_id()}" target="_blank"><?php echo e(trans('texts.view_client')); ?></a>
                    </span>
				</div>
			</div>

			<?php if($invoice->id || $data): ?>
				</div>
			<?php endif; ?>

			<div data-bind="with: client" class="invoice-contact">
				<div style="display:none" class="form-group" data-bind="visible: contacts().length > 0 &amp;&amp; (contacts()[0].email() || contacts()[0].first_name()), foreach: contacts">
					<div class="col-lg-8 col-lg-offset-4 col-sm-offset-4">
						<label class="checkbox" data-bind="attr: {for: $index() + '_check'}" onclick="refreshPDF(true)">
                            <input type="hidden" value="0" data-bind="attr: {name: 'client[contacts][' + $index() + '][send_invoice]'}">
							<input type="checkbox" value="1" data-bind="checked: send_invoice, attr: {id: $index() + '_check', name: 'client[contacts][' + $index() + '][send_invoice]'}">
							<span data-bind="html: email.display"></span>
                        </label>
                        <?php if( ! $invoice->is_deleted && ! $invoice->client->is_deleted): ?>
                        <?php if($invoice->email_send_flag =='no'): ?>
                        <span data-bind="visible: !$root.invoice().is_recurring()">
                            <span data-bind="html: $data.view_as_recipient"></span>&nbsp;&nbsp;
                            <?php if(Utils::isConfirmed()): ?>
                            <span style="vertical-align:text-top;color:red" class="fa fa-exclamation-triangle"
                                    data-bind="visible: $data.email_error, tooltip: {title: $data.email_error}"></span>
                            
                            <?php endif; ?>
                        </span>
                        <?php endif; ?>
                        <?php endif; ?>
					</div>
				</div>
			</div>

		</div>
		<div class="col-md-4" id="col_2">
			<div data-bind="visible: !is_recurring()">
				<?php echo Former::text('invoice_date')->data_bind("datePicker: invoice_date, valueUpdate: 'afterkeydown'")->label(trans("texts.{$entityType}_date"))
							->data_date_format(Session::get(SESSION_DATE_PICKER_FORMAT, DEFAULT_DATE_PICKER_FORMAT))->appendIcon('calendar')->addGroupClass('invoice_date'); ?>

				<?php echo Former::text('due_date')->data_bind("datePicker: due_date, valueUpdate: 'afterkeydown'")->label(trans("texts.{$entityType}_due_date"))
							->data_date_format(Session::get(SESSION_DATE_PICKER_FORMAT, DEFAULT_DATE_PICKER_FORMAT))->appendIcon('calendar')->addGroupClass('due_date'); ?>


                <?php echo Former::text('partial')->data_bind("value: partial, valueUpdate: 'afterkeydown'")->onchange('onPartialChange()')
                            ->rel('tooltip')->data_toggle('tooltip')->data_placement('bottom')->title(trans('texts.partial_value')); ?>

			</div>
            <?php if($entityType == ENTITY_INVOICE): ?>
			<div data-bind="visible: is_recurring" style="display: none">
				<?php echo Former::select('frequency_id')->options($frequencies)->data_bind("value: frequency_id")
                        ->appendIcon('question-sign')->addGroupClass('frequency_id')->onchange('onFrequencyChange()'); ?>

				<?php echo Former::text('start_date')->data_bind("datePicker: start_date, valueUpdate: 'afterkeydown'")
							->data_date_format(Session::get(SESSION_DATE_PICKER_FORMAT, DEFAULT_DATE_PICKER_FORMAT))->appendIcon('calendar')->addGroupClass('start_date'); ?>

				<?php echo Former::text('end_date')->data_bind("datePicker: end_date, valueUpdate: 'afterkeydown'")
							->data_date_format(Session::get(SESSION_DATE_PICKER_FORMAT, DEFAULT_DATE_PICKER_FORMAT))->appendIcon('calendar')->addGroupClass('end_date'); ?>

                <?php echo Former::select('recurring_due_date')->label(trans('texts.due_date'))->options($recurringDueDates)->data_bind("value: recurring_due_date")->appendIcon('question-sign')->addGroupClass('recurring_due_date'); ?>

			</div>
            <?php endif; ?>

            <?php if($account->showCustomField('custom_invoice_text_label1', $invoice)): ?>
                <?php echo Former::text('custom_text_value1')->label($account->custom_invoice_text_label1)->data_bind("value: custom_text_value1, valueUpdate: 'afterkeydown'"); ?>

            <?php endif; ?>
		</div>

		<div class="col-md-4" id="col_2">
            <span data-bind="visible: !is_recurring()">
            <?php echo Former::text('invoice_number')
                        ->label(trans("texts.{$entityType}_number_short"))
                        ->onchange('checkInvoiceNumber()')
                        ->addGroupClass('invoice-number')
                        ->data_bind("value: invoice_number, valueUpdate: 'afterkeydown'"); ?>

            </span>
            <?php if($account->getTokenGatewayId()): ?>
            <span data-bind="visible: is_recurring()" style="display: none">
                <div data-bind="visible: !(auto_bill() == <?php echo e(AUTO_BILL_OPT_IN); ?> &amp;&amp; client_enable_auto_bill()) &amp;&amp; !(auto_bill() == <?php echo e(AUTO_BILL_OPT_OUT); ?> &amp;&amp; !client_enable_auto_bill())" style="display: none">
                <?php echo Former::select('auto_bill')
                        ->data_bind("value: auto_bill, valueUpdate: 'afterkeydown', event:{change:function(){if(auto_bill()==".AUTO_BILL_OPT_IN.")client_enable_auto_bill(0);if(auto_bill()==".AUTO_BILL_OPT_OUT.")client_enable_auto_bill(1)}}")
                        ->options([
                            AUTO_BILL_OFF => trans('texts.off'),
                            AUTO_BILL_OPT_IN => trans('texts.opt_in'),
                            AUTO_BILL_OPT_OUT => trans('texts.opt_out'),
                            AUTO_BILL_ALWAYS => trans('texts.always'),
                        ]); ?>

                </div>
                <input type="hidden" name="client_enable_auto_bill" data-bind="attr: { value: client_enable_auto_bill() }" />
                <div class="form-group" data-bind="visible: auto_bill() == <?php echo e(AUTO_BILL_OPT_IN); ?> &amp;&amp; client_enable_auto_bill()">
                    <div class="col-sm-4 control-label"><?php echo e(trans('texts.auto_bill')); ?></div>
                    <div class="col-sm-8" style="padding-top:10px;padding-bottom:9px">
                        <?php echo e(trans('texts.opted_in')); ?> - <a href="#" data-bind="click:function(){client_enable_auto_bill(false)}">(<?php echo e(trans('texts.disable')); ?>)</a>
                    </div>
                </div>
                <div class="form-group" data-bind="visible: auto_bill() == <?php echo e(AUTO_BILL_OPT_OUT); ?> &amp;&amp; !client_enable_auto_bill()">
                    <div class="col-sm-4 control-label"><?php echo e(trans('texts.auto_bill')); ?></div>
                    <div class="col-sm-8" style="padding-top:10px;padding-bottom:9px">
                        <?php echo e(trans('texts.opted_out')); ?> - <a href="#" data-bind="click:function(){client_enable_auto_bill(true)}">(<?php echo e(trans('texts.enable')); ?>)</a>
                    </div>
                </div>
            </span>
            <?php endif; ?>
			<?php echo Former::text('po_number')->label(trans('texts.po_number_short'))->data_bind("value: po_number, valueUpdate: 'afterkeydown'"); ?>

			<?php echo Former::text('discount')->data_bind("value: discount, valueUpdate: 'afterkeydown'")
					->addGroupClass('discount-group')->type('number')->min('0')->step('any')->append(
						Former::select('is_amount_discount')->addOption(trans('texts.discount_percent'), '0')
						->addOption(trans('texts.discount_amount'), '1')->data_bind("value: is_amount_discount")->raw()
			); ?>


            <?php if($account->showCustomField('custom_invoice_text_label2', $invoice)): ?>
                <?php echo Former::text('custom_text_value2')->label($account->custom_invoice_text_label2)->data_bind("value: custom_text_value2, valueUpdate: 'afterkeydown'"); ?>

            <?php endif; ?>

            <?php if($entityType == ENTITY_INVOICE): ?>
            <div class="form-group" style="margin-bottom: 8px">
                <div class="col-lg-8 col-sm-8 col-sm-offset-4" style="padding-top: 10px">
                	<?php if($invoice->recurring_invoice): ?>
                        <?php echo trans('texts.created_by_invoice', ['invoice' => link_to('/invoices/'.$invoice->recurring_invoice->public_id, trans('texts.recurring_invoice'))]); ?>

    				<?php elseif($invoice->id): ?>
                        <span class="smaller">
                        <?php if(isset($lastSent) && $lastSent): ?>
                            <?php echo trans('texts.last_sent_on', ['date' => link_to('/invoices/'.$lastSent->public_id, $invoice->last_sent_date, ['id' => 'lastSent'])]); ?> <br/>
                        <?php endif; ?>
                        <?php if($invoice->is_recurring && $invoice->getNextSendDate()): ?>
                           <?php echo trans('texts.next_send_on', ['date' => '<span data-bind="tooltip: {title: \''.$invoice->getPrettySchedule().'\', html: true}">'.$account->formatDate($invoice->getNextSendDate()).
                                '<span class="glyphicon glyphicon-info-sign" style="padding-left:10px;color:#B1B5BA"></span></span>']); ?>

                            <?php if($invoice->getDueDate()): ?>
                                <br>
                                <?php echo trans('texts.next_due_on', ['date' => '<span>'.$account->formatDate($invoice->getDueDate($invoice->getNextSendDate())).'</span>']); ?>

                            <?php endif; ?>
                        <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
		</div>
	</div>

	<div class="table-responsive" style="padding-top:4px">
	<table class="table invoice-table">
		<thead>
			<tr>
				<th style="min-width:32px;" class="hide-border"></th>
				<th style="min-width:160px"><?php echo e($invoiceLabels['item']); ?></th>
				<th style="width:100%"><?php echo e($invoiceLabels['description']); ?></th>
                <?php if($account->showCustomField('custom_invoice_item_label1')): ?>
                    <th style="min-width:120px"><?php echo e($account->custom_invoice_item_label1); ?></th>
                <?php endif; ?>
                <?php if($account->showCustomField('custom_invoice_item_label2')): ?>
                    <th style="min-width:120px"><?php echo e($account->custom_invoice_item_label2); ?></th>
                <?php endif; ?>
				<th style="min-width:120px" data-bind="text: costLabel"><?php echo e($invoiceLabels['unit_cost']); ?></th>
				<th style="<?php echo e($account->hide_quantity ? 'display:none' : 'min-width:120px'); ?>" data-bind="text: qtyLabel"><?php echo e($invoiceLabels['quantity']); ?></th>
				<th style="min-width:<?php echo e($account->enable_second_tax_rate ? 180 : 120); ?>px;display:none;" data-bind="visible: $root.invoice_item_taxes.show"><?php echo e(trans('texts.tax')); ?></th>
				<th style="min-width:120px;"><?php echo e(trans('texts.line_total')); ?></th>
				<th style="min-width:32px;" class="hide-border"></th>
			</tr>
		</thead>
		<tbody data-bind="sortable: { data: invoice_items, afterMove: onDragged }">
			<tr data-bind="event: { mouseover: showActions, mouseout: hideActions }" class="sortable-row">
                            
				<td class="hide-border td-icon">
					<i style="display:none" data-bind="visible: actionsVisible() &amp;&amp;
                        $parent.invoice_items().length > 1" class="fa fa-sort"></i>
				</td>
                                
				<td>
                    <div id="scrollable-dropdown-menu">
                        <input id="product_key" type="text" data-bind="productTypeahead: product_key, items: $root.products, key: 'product_key', valueUpdate: 'afterkeydown', attr: {name: 'invoice_items[' + $index() + '][product_key]'}" class="form-control invoice-item handled"/>
                    </div>
				</td>
				<td>
					<textarea data-bind="value: wrapped_notes, valueUpdate: 'afterkeydown', attr: {name: 'invoice_items[' + $index() + '][notes]'}"
                        rows="1" cols="60" style="resize: vertical" class="form-control word-wrap"></textarea>
                        <input type="text" data-bind="value: task_public_id, attr: {name: 'invoice_items[' + $index() + '][task_public_id]'}" style="display: none"/>
						<input type="text" data-bind="value: expense_public_id, attr: {name: 'invoice_items[' + $index() + '][expense_public_id]'}" style="display: none"/>
				</td>
                <?php if($account->showCustomField('custom_invoice_item_label1')): ?>
                    <td>
                        <input data-bind="value: custom_value1, valueUpdate: 'afterkeydown', attr: {name: 'invoice_items[' + $index() + '][custom_value1]'}" class="form-control invoice-item"/>
                    </td>
                <?php endif; ?>
                <?php if($account->showCustomField('custom_invoice_item_label2')): ?>
                    <td>
                        <input data-bind="value: custom_value2, valueUpdate: 'afterkeydown', attr: {name: 'invoice_items[' + $index() + '][custom_value2]'}" class="form-control invoice-item"/>
                    </td>
                <?php endif; ?>
				<td>
					<input data-bind="value: prettyCost, valueUpdate: 'afterkeydown', attr: {name: 'invoice_items[' + $index() + '][cost]'}"
                        style="text-align: right" class="form-control invoice-item"/>
				</td>
				<td style="<?php echo e($account->hide_quantity ? 'display:none' : ''); ?>">
					<input data-bind="value: prettyQty, valueUpdate: 'afterkeydown', attr: {name: 'invoice_items[' + $index() + '][qty]'}"
                        style="text-align: right" class="form-control invoice-item" name="quantity"/>
				</td>
				<td style="display:none;" data-bind="visible: $root.invoice_item_taxes.show">
                    <?php echo Former::select('')
                            ->addOption('', '')
                            ->options($taxRateOptions)
                            ->data_bind('value: tax1')
                            ->addClass($account->enable_second_tax_rate ? 'tax-select' : '')
                            ->raw(); ?>

                    <input type="text" data-bind="value: tax_name1, attr: {name: 'invoice_items[' + $index() + '][tax_name1]'}" style="display:none">
                    <input type="text" data-bind="value: tax_rate1, attr: {name: 'invoice_items[' + $index() + '][tax_rate1]'}" style="display:none">
                    <div data-bind="visible: $root.invoice().account.enable_second_tax_rate == '1'">
                        <?php echo Former::select('')
                                ->addOption('', '')
                                ->options($taxRateOptions)
                                ->data_bind('value: tax2')
                                ->addClass('tax-select')
                                ->raw(); ?>

                    </div>
                    <input type="text" data-bind="value: tax_name2, attr: {name: 'invoice_items[' + $index() + '][tax_name2]'}" style="display:none">
                    <input type="text" data-bind="value: tax_rate2, attr: {name: 'invoice_items[' + $index() + '][tax_rate2]'}" style="display:none">
				</td>
				<td style="text-align:right;padding-top:9px !important" nowrap>
					<div class="line-total" data-bind="text: totals.total"></div>
				</td>
                                <?php if($invoice->email_send_flag=='no'): ?>
				<td style="cursor:pointer" class="hide-border td-icon">
                    <i style="padding-left:2px" data-bind="click: $parent.removeItem, visible: actionsVisible() &amp;&amp;
                    $index() < ($parent.invoice_items().length - 1) &amp;&amp;
                    $parent.invoice_items().length > 1" class="fa fa-minus-circle redlink" title="Remove item"/>
				</td>
                                <?php endif; ?>
			</tr>
		</tbody>


		<tfoot>
			<tr>
				<td class="hide-border"/>
				<td class="hide-border" colspan="<?php echo e(2 + ($account->showCustomField('custom_invoice_item_label1') ? 1 : 0) + ($account->showCustomField('custom_invoice_item_label2') ? 1 : 0)); ?>" rowspan="6" style="vertical-align:top">
					<br/>
                    <div role="tabpanel">

                      <ul class="nav nav-tabs" role="tablist" style="border: none">
                        <li role="presentation" class="active"><a href="#notes" aria-controls="notes" role="tab" data-toggle="tab"><?php echo e(trans('texts.note_to_client')); ?></a></li>
                        <li role="presentation"><a href="#terms" aria-controls="terms" role="tab" data-toggle="tab"><?php echo e(trans("texts.terms")); ?></a></li>
                        <li role="presentation"><a href="#footer" aria-controls="footer" role="tab" data-toggle="tab"><?php echo e(trans("texts.footer")); ?></a></li>
                        <?php if($account->hasFeature(FEATURE_DOCUMENTS)): ?>
                            <li role="presentation"><a href="#attached-documents" aria-controls="attached-documents" role="tab" data-toggle="tab">
                                <?php echo e(trans("texts.invoice_documents")); ?>

                                <?php if(count($invoice->documents)): ?>
                                    (<?php echo e(count($invoice->documents)); ?>)
                                <?php endif; ?>
                            </a></li>
                        <?php endif; ?>
                    </ul>

                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane active" id="notes" style="padding-bottom:44px">
                            <?php echo Former::textarea('public_notes')->data_bind("value: wrapped_notes, valueUpdate: 'afterkeydown'")
                            ->label(null)->style('resize: none; width: 500px;')->rows(4); ?>

                        </div>
                        <div role="tabpanel" class="tab-pane" id="terms">
                            <?php echo Former::textarea('terms')->data_bind("value:wrapped_terms, placeholder: terms_placeholder, valueUpdate: 'afterkeydown'")
                            ->label(false)->style('resize: none; width: 500px')->rows(4)
                            ->help('<div class="checkbox">
                                        <label>
                                            <input name="set_default_terms" type="checkbox" style="width: 24px" data-bind="checked: set_default_terms"/>'.trans('texts.save_as_default_terms').'
                                        </label>
                                        <div class="pull-right" data-bind="visible: showResetTerms()">
                                            <a href="#" onclick="return resetTerms()" title="'. trans('texts.reset_terms_help') .'">' . trans("texts.reset_terms") . '</a>
                                        </div>
                                    </div>'); ?>

                        </div>
                        <div role="tabpanel" class="tab-pane" id="footer">
                            <?php echo Former::textarea('invoice_footer')->data_bind("value:wrapped_footer, placeholder: footer_placeholder, valueUpdate: 'afterkeydown'")
                            ->label(false)->style('resize: none; width: 500px')->rows(4)
                            ->help('<div class="checkbox">
                                        <label>
                                            <input name="set_default_footer" type="checkbox" style="width: 24px" data-bind="checked: set_default_footer"/>'.trans('texts.save_as_default_footer').'
                                        </label>
                                        <div class="pull-right" data-bind="visible: showResetFooter()">
                                            <a href="#" onclick="return resetFooter()" title="'. trans('texts.reset_footer_help') .'">' . trans("texts.reset_footer") . '</a>
                                        </div>
                                    </div>'); ?>

                        </div>
                        <?php if($account->hasFeature(FEATURE_DOCUMENTS)): ?>
                        <div role="tabpanel" class="tab-pane" id="attached-documents" style="position:relative;z-index:9">
                            <div id="document-upload">
                                <div class="dropzone">
                                    <div data-bind="foreach: documents">
                                        <input type="hidden" name="document_ids[]" data-bind="value: public_id"/>
                                    </div>
                                </div>
                                <?php if($invoice->hasExpenseDocuments()): ?>
                                    <h4><?php echo e(trans('texts.documents_from_expenses')); ?></h4>
                                    <?php foreach($invoice->expenses as $expense): ?>
                                        <?php foreach($expense->documents as $document): ?>
                                            <div><?php echo e($document->name); ?></div>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

				</td>
				<td class="hide-border" style="display:none" data-bind="visible: $root.invoice_item_taxes.show"/>
				<td colspan="<?php echo e($account->hide_quantity ? 1 : 2); ?>"><?php echo e(trans('texts.subtotal')); ?></td>
				<td style="text-align: right"><span data-bind="text: totals.subtotal"/></td>
			</tr>

			<tr style="display:none" data-bind="visible: discount() != 0">
				<td class="hide-border" colspan="3"/>
				<td style="display:none" class="hide-border" data-bind="visible: $root.invoice_item_taxes.show"/>
				<td colspan="<?php echo e($account->hide_quantity ? 1 : 2); ?>"><?php echo e(trans('texts.discount')); ?></td>
				<td style="text-align: right"><span data-bind="text: totals.discounted"/></td>
			</tr>

            <?php if($account->showCustomField('custom_invoice_label1', $invoice) && $account->custom_invoice_taxes1): ?>
				<tr>
					<td class="hide-border" colspan="3"/>
					<td style="display:none" class="hide-border" data-bind="visible: $root.invoice_item_taxes.show"/>
					<td colspan="<?php echo e($account->hide_quantity ? 1 : 2); ?>"><?php echo e($account->custom_invoice_label1); ?></td>
					<td style="text-align: right;padding-right: 28px" colspan="2"><input name="custom_value1" class="form-control" data-bind="value: custom_value1, valueUpdate: 'afterkeydown'"/></td>
				</tr>
			<?php endif; ?>

            <?php if($account->showCustomField('custom_invoice_label2', $invoice) && $account->custom_invoice_taxes2): ?>
				<tr>
					<td class="hide-border" colspan="3"/>
					<td style="display:none" class="hide-border" data-bind="visible: $root.invoice_item_taxes.show"/>
					<td colspan="<?php echo e($account->hide_quantity ? 1 : 2); ?>"><?php echo e($account->custom_invoice_label2); ?></td>
					<td style="text-align: right;padding-right: 28px" colspan="2"><input name="custom_value2" class="form-control" data-bind="value: custom_value2, valueUpdate: 'afterkeydown'"/></td>
				</tr>
			<?php endif; ?>

            <tr style="display:none" data-bind="visible: $root.invoice_item_taxes.show &amp;&amp; totals.hasItemTaxes">
                <td class="hide-border" colspan="4"/>
                <?php if(!$account->hide_quantity): ?>
                    <td><?php echo e(trans('texts.tax')); ?></td>
                <?php endif; ?>
                <td style="min-width:120px"><span data-bind="html: totals.itemTaxRates"/></td>
                <td style="text-align: right"><span data-bind="html: totals.itemTaxAmounts"/></td>
            </tr>

			<tr style="display:none" data-bind="visible: $root.invoice_taxes.show">
				<td class="hide-border" colspan="3"/>
				<td style="display:none" class="hide-border" data-bind="visible: $root.invoice_item_taxes.show"/>
				<?php if(!$account->hide_quantity): ?>
					<td><?php echo e(trans('texts.tax')); ?></td>
				<?php endif; ?>
				<td style="min-width:120px">
                    <?php echo Former::select('')
                            ->id('taxRateSelect1')
                            ->addOption('', '')
                            ->options($taxRateOptions)
                            ->addClass($account->enable_second_tax_rate ? 'tax-select' : '')
                            ->data_bind('value: tax1')
                            ->raw(); ?>

                    <input type="text" name="tax_name1" data-bind="value: tax_name1" style="display:none">
                    <input type="text" name="tax_rate1" data-bind="value: tax_rate1" style="display:none">
                    <div data-bind="visible: $root.invoice().account.enable_second_tax_rate == '1'">
                    <?php echo Former::select('')
                            ->addOption('', '')
                            ->options($taxRateOptions)
                            ->addClass('tax-select')
                            ->data_bind('value: tax2')
                            ->raw(); ?>

                    </div>
                    <input type="text" name="tax_name2" data-bind="value: tax_name2" style="display:none">
                    <input type="text" name="tax_rate2" data-bind="value: tax_rate2" style="display:none">
                </td>
				<td style="text-align: right"><span data-bind="text: totals.taxAmount"/></td>
			</tr>

            <?php if($account->showCustomField('custom_invoice_label1', $invoice) && !$account->custom_invoice_taxes1): ?>
				<tr>
					<td class="hide-border" colspan="3"/>
					<td style="display:none" class="hide-border" data-bind="visible: $root.invoice_item_taxes.show"/>
					<td colspan="<?php echo e($account->hide_quantity ? 1 : 2); ?>"><?php echo e($account->custom_invoice_label1); ?></td>
					<td style="text-align: right;padding-right: 28px" colspan="2"><input name="custom_value1" class="form-control" data-bind="value: custom_value1, valueUpdate: 'afterkeydown'"/></td>
				</tr>
			<?php endif; ?>

            <?php if($account->showCustomField('custom_invoice_label2', $invoice) && !$account->custom_invoice_taxes2): ?>
				<tr>
					<td class="hide-border" colspan="3"/>
					<td style="display:none" class="hide-border" data-bind="visible: $root.invoice_item_taxes.show"/>
					<td colspan="<?php echo e($account->hide_quantity ? 1 : 2); ?>"><?php echo e($account->custom_invoice_label2); ?></td>
					<td style="text-align: right;padding-right: 28px" colspan="2"><input name="custom_value2" class="form-control" data-bind="value: custom_value2, valueUpdate: 'afterkeydown'"/></td>
				</tr>
			<?php endif; ?>

			<?php if(!$account->hide_paid_to_date): ?>
				<tr>
					<td class="hide-border" colspan="3"/>
					<td style="display:none" class="hide-border" data-bind="visible: $root.invoice_item_taxes.show"/>
					<td colspan="<?php echo e($account->hide_quantity ? 1 : 2); ?>"><?php echo e(trans('texts.paid_to_date')); ?></td>
					<td style="text-align: right" data-bind="text: totals.paidToDate"></td>
				</tr>
			<?php endif; ?>

			<tr data-bind="style: { 'font-weight': partial() ? 'normal' : 'bold', 'font-size': partial() ? '1em' : '1.05em' }">
				<td class="hide-border" colspan="3"/>
				<td class="hide-border" style="display:none" data-bind="visible: $root.invoice_item_taxes.show"/>
				<td class="hide-border" data-bind="css: {'hide-border': !partial()}" colspan="<?php echo e($account->hide_quantity ? 1 : 2); ?>"><?php echo e($entityType == ENTITY_INVOICE ? $invoiceLabels['balance_due'] : trans('texts.total')); ?></td>
				<td class="hide-border" data-bind="css: {'hide-border': !partial()}" style="text-align: right"><span data-bind="text: totals.total"></span></td>
			</tr>

			<tr style="font-size:1.05em; display:none; font-weight:bold" data-bind="visible: partial">
				<td class="hide-border" colspan="3"/>
				<td class="hide-border" style="display:none" data-bind="visible: $root.invoice_item_taxes.show"/>
				<td class="hide-border" colspan="<?php echo e($account->hide_quantity ? 1 : 2); ?>"><?php echo e($invoiceLabels['partial_due']); ?></td>
				<td class="hide-border" style="text-align: right"><span data-bind="text: totals.partial"></span></td>
			</tr>

		</tfoot>


	</table>
	</div>
    </div>
    </div>

	<p>&nbsp;</p>
        <div class="form-actions" id="btn">

		<div style="display:none;">
			<?php echo Former::populateField('entityType', $entityType); ?>


			<?php echo Former::text('entityType'); ?>

			<?php echo Former::text('action'); ?>

            <?php echo Former::text('public_id')->data_bind('value: public_id'); ?>

            <?php echo Former::text('is_recurring')->data_bind('value: is_recurring'); ?>

            <?php echo Former::text('is_quote')->data_bind('value: is_quote'); ?>

            <?php echo Former::text('has_tasks')->data_bind('value: has_tasks'); ?>

            <?php echo Former::text('data')->data_bind('value: ko.mapping.toJSON(model)'); ?>

			<?php echo Former::text('has_expenses')->data_bind('value: has_expenses'); ?>

            <?php echo Former::text('pdfupload'); ?>

		</div>

		<?php if(!Utils::hasFeature(FEATURE_MORE_INVOICE_DESIGNS) || \App\Models\InvoiceDesign::count() == COUNT_FREE_DESIGNS_SELF_HOST): ?>
			<?php echo Former::select('invoice_design_id')->style('display:inline;width:150px;background-color:white !important')->raw()->fromQuery($invoiceDesigns, 'name', 'id')->data_bind("value: invoice_design_id")->addOption(trans('texts.more_designs') . '...', '-1'); ?>

		<?php else: ?>
			<?php echo Former::select('invoice_design_id')->style('display:inline;width:150px;background-color:white !important')->raw()->fromQuery($invoiceDesigns, 'name', 'id')->data_bind("value: invoice_design_id"); ?>

		<?php endif; ?>

        <?php if( $invoice->exists && $invoice->id && ! $invoice->is_recurring): ?>
		    <?php echo Button::primary(trans('texts.download_pdf'))
                    ->withAttributes(['onclick' => 'onDownloadClick()', 'id' => 'downloadPdfButton'])
                    ->appendIcon(Icon::create('download-alt')); ?>

        <?php endif; ?>

        <?php if($invoice->isClientTrashed()): ?>
            <!-- do nothing -->
        <?php elseif($invoice->trashed()): ?>
            <?php echo Button::success(trans('texts.restore'))->withAttributes(['onclick' => 'submitBulkAction("restore")'])->appendIcon(Icon::create('cloud-download')); ?>

		<?php elseif(!$invoice->trashed()): ?>
			<?php echo Button::success(trans("texts.save_{$entityType}"))->withAttributes(array('id' => 'saveButton', 'onclick' => 'onSaveClick()'))->appendIcon(Icon::create('floppy-disk')); ?>

		    <?php echo Button::info(trans("texts.email_{$entityType}"))->withAttributes(array('id' => 'emailButton', 'onclick' => 'onEmailClick()'))->appendIcon(Icon::create('send')); ?>

            <?php if($invoice->id): ?>
                <?php echo DropdownButton::normal(trans('texts.more_actions'))
                      ->withContents($actions)
                      ->dropup(); ?>

            <?php endif; ?>
		<?php endif; ?>

	</div>
	<p>&nbsp;</p>

	<?php echo $__env->make('invoices.pdf', ['account' => Auth::user()->account], array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

	<?php if(!Auth::user()->account->isPro()): ?>
		<div style="font-size:larger">
			<?php echo trans('texts.pro_plan_remove_logo', ['link'=>link_to('/settings/account_management?upgrade=true', trans('texts.pro_plan_remove_logo_link'))]); ?>

		</div>
	<?php endif; ?>

	<div class="modal fade" id="clientModal" tabindex="-1" role="dialog" aria-labelledby="clientModalLabel" aria-hidden="true">
	  <div class="modal-dialog" data-bind="css: {'large-dialog': $root.showMore}">
	    <div class="modal-content" style="background-color: #f8f8f8">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
	        <h4 class="modal-title" id="clientModalLabel"><?php echo e(trans('texts.client')); ?></h4>
	      </div>

       <div class="container" style="width: 100%; padding-bottom: 0px !important">
       <div class="panel panel-default">
        <div class="panel-body">

        <div class="row" data-bind="with: client" onkeypress="clientModalEnterClick(event)">
            <div style="margin-left:0px;margin-right:0px" data-bind="css: {'col-md-6': $root.showMore}">

                <?php echo Former::hidden('client_public_id')->data_bind("value: public_id, valueUpdate: 'afterkeydown',
                            attr: {name: 'client[public_id]'}"); ?>

                <?php echo Former::text('client[name]')
                    ->data_bind("value: name, valueUpdate: 'afterkeydown', attr { placeholder: name.placeholder }")
                    ->label('client_name'); ?>


                <span data-bind="visible: $root.showMore">

                    <?php echo Former::text('client[id_number]')
                            ->label('id_number')
                            ->data_bind("value: id_number, valueUpdate: 'afterkeydown'"); ?>

                    <?php echo Former::text('client[vat_number]')
                            ->label('vat_number')
                            ->data_bind("value: vat_number, valueUpdate: 'afterkeydown'"); ?>


                    <?php echo Former::text('client[website]')
                            ->label('website')
                            ->data_bind("value: website, valueUpdate: 'afterkeydown'"); ?>

                    <?php echo Former::text('client[work_phone]')
                            ->label('work_phone')
                            ->data_bind("value: work_phone, valueUpdate: 'afterkeydown'"); ?>


                </span>

                <?php if(Auth::user()->hasFeature(FEATURE_INVOICE_SETTINGS)): ?>
                    <?php if($account->custom_client_label1): ?>
                        <?php echo Former::text('client[custom_value1]')
                            ->label($account->custom_client_label1)
                            ->data_bind("value: custom_value1, valueUpdate: 'afterkeydown'"); ?>

                    <?php endif; ?>
                    <?php if($account->custom_client_label2): ?>
                        <?php echo Former::text('client[custom_value2]')
                            ->label($account->custom_client_label2)
                            ->data_bind("value: custom_value2, valueUpdate: 'afterkeydown'"); ?>

                    <?php endif; ?>
                <?php endif; ?>

                <span data-bind="visible: $root.showMore">
                    &nbsp;

                    <?php echo Former::text('client[address1]')
                            ->label(trans('texts.address1'))
                            ->data_bind("value: address1, valueUpdate: 'afterkeydown'"); ?>

                    <?php echo Former::text('client[address2]')
                            ->label(trans('texts.address2'))
                            ->data_bind("value: address2, valueUpdate: 'afterkeydown'"); ?>

                    <?php echo Former::text('client[city]')
                            ->label(trans('texts.city'))
                            ->data_bind("value: city, valueUpdate: 'afterkeydown'"); ?>

                    <?php echo Former::text('client[state]')
                            ->label(trans('texts.state'))
                            ->data_bind("value: state, valueUpdate: 'afterkeydown'"); ?>

                    <?php echo Former::text('client[postal_code]')
                            ->label(trans('texts.postal_code'))
                            ->data_bind("value: postal_code, valueUpdate: 'afterkeydown'"); ?>

                    <?php echo Former::select('client[country_id]')
                            ->label(trans('texts.country_id'))
                            ->addOption('','')->addGroupClass('country_select')
                            ->fromQuery(Cache::get('countries'), 'name', 'id')->data_bind("dropdown: country_id"); ?>

                </span>

            </div>
            <div style="margin-left:0px;margin-right:0px" data-bind="css: {'col-md-6': $root.showMore}">

                <div data-bind='template: { foreach: contacts,
                                        beforeRemove: hideContact,
                                        afterAdd: showContact }'>

                    <?php echo Former::hidden('public_id')->data_bind("value: public_id, valueUpdate: 'afterkeydown',
                            attr: {name: 'client[contacts][' + \$index() + '][public_id]'}"); ?>

                    <?php echo Former::text('first_name')->data_bind("value: first_name, valueUpdate: 'afterkeydown',
                            attr: {name: 'client[contacts][' + \$index() + '][first_name]'}"); ?>

                    <?php echo Former::text('last_name')->data_bind("value: last_name, valueUpdate: 'afterkeydown',
                            attr: {name: 'client[contacts][' + \$index() + '][last_name]'}"); ?>

                    <?php echo Former::text('email')->data_bind("value: email, valueUpdate: 'afterkeydown',
                            attr: {name: 'client[contacts][' + \$index() + '][email]', id:'email'+\$index()}")
                            ->addClass('client-email'); ?>

                    <?php echo Former::text('phone')->data_bind("value: phone, valueUpdate: 'afterkeydown',
                            attr: {name: 'client[contacts][' + \$index() + '][phone]'}"); ?>

                    <?php if($account->hasFeature(FEATURE_CLIENT_PORTAL_PASSWORD) && $account->enable_portal_password): ?>
                        <?php echo Former::password('password')->data_bind("value: (typeof password=='function'?password():null)?'-%unchanged%-':'', valueUpdate: 'afterkeydown',
                            attr: {name: 'client[contacts][' + \$index() + '][password]'}"); ?>

                    <?php endif; ?>
                    <div class="form-group">
                        <div class="col-lg-8 col-lg-offset-4">
                            <span class="redlink bold" data-bind="visible: $parent.contacts().length > 1">
                                <?php echo link_to('#', trans('texts.remove_contact').' -', array('data-bind'=>'click: $parent.removeContact')); ?>

                            </span>
                            <span data-bind="visible: $index() === ($parent.contacts().length - 1)" class="pull-right greenlink bold">
                                <?php echo link_to('#', trans('texts.add_contact').' +', array('data-bind'=>'click: $parent.addContact')); ?>

                            </span>
                        </div>
                    </div>
                </div>

                <span data-bind="visible: $root.showMore">
                    &nbsp;
                </span>

                <?php echo Former::select('client[currency_id]')->addOption('','')
                        ->placeholder($account->currency ? $account->currency->name : '')
                        ->label(trans('texts.currency_id'))
                        ->data_bind('value: currency_id')
                        ->fromQuery($currencies, 'name', 'id'); ?>


                <span data-bind="visible: $root.showMore">
                <?php echo Former::select('client[language_id]')->addOption('','')
                        ->placeholder($account->language ? $account->language->name : '')
                        ->label(trans('texts.language_id'))
                        ->data_bind('value: language_id')
                        ->fromQuery($languages, 'name', 'id'); ?>

                <?php echo Former::select('client[payment_terms]')->addOption('','')->data_bind('value: payment_terms')
                        ->fromQuery($paymentTerms, 'name', 'num_days')
                        ->label(trans('texts.payment_terms'))
                        ->help(trans('texts.payment_terms_help')); ?>

                <?php echo Former::select('client[size_id]')->addOption('','')->data_bind('value: size_id')
                        ->label(trans('texts.size_id'))
                        ->fromQuery($sizes, 'name', 'id'); ?>

                <?php echo Former::select('client[industry_id]')->addOption('','')->data_bind('value: industry_id')
                        ->label(trans('texts.industry_id'))
                        ->fromQuery($industries, 'name', 'id'); ?>

                <?php echo Former::textarea('client_private_notes')
                        ->label(trans('texts.private_notes'))
                        ->data_bind("value: private_notes, attr:{ name: 'client[private_notes]'}"); ?>

                </span>
            </div>
            </div>
        </div>
        </div>
        </div>

         <div class="modal-footer" style="margin-top: 0px; padding-top:0px;">
            <span class="error-block" id="emailError" style="display:none;float:left;font-weight:bold"><?php echo e(trans('texts.provide_name_or_email')); ?></span><span>&nbsp;</span>
            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo e(trans('texts.cancel')); ?></button>
            <button type="button" class="btn btn-default" data-bind="click: $root.showMoreFields, text: $root.showMore() ? '<?php echo e(trans('texts.less_fields')); ?>' : '<?php echo e(trans('texts.more_fields')); ?>'"></button>
            <button id="clientDoneButton" type="button" class="btn btn-primary" data-bind="click: $root.clientFormComplete"><?php echo e(trans('texts.done')); ?></button>
         </div>

        </div>
      </div>
    </div>

	<div class="modal fade" id="recurringModal" tabindex="-1" role="dialog" aria-labelledby="recurringModalLabel" aria-hidden="true">
	  <div class="modal-dialog" style="min-width:150px">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
	        <h4 class="modal-title" id="recurringModalLabel"><?php echo e(trans('texts.recurring_invoices')); ?></h4>
	      </div>

	    <div style="background-color: #fff; padding-left: 16px; padding-right: 16px">
	    	&nbsp; <?php echo isset($recurringHelp) ? $recurringHelp : ''; ?> &nbsp;
		</div>

	     <div class="modal-footer" style="margin-top: 0px">
	      	<button type="button" class="btn btn-primary" data-dismiss="modal"><?php echo e(trans('texts.close')); ?></button>
	     </div>

	    </div>
	  </div>
	</div>

    <div class="modal fade" id="recurringDueDateModal" tabindex="-1" role="dialog" aria-labelledby="recurringDueDateModalLabel" aria-hidden="true">
	  <div class="modal-dialog" style="min-width:150px">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
	        <h4 class="modal-title" id="recurringDueDateModalLabel"><?php echo e(trans('texts.recurring_due_dates')); ?></h4>
	      </div>

	    <div style="background-color: #fff; padding-left: 16px; padding-right: 16px">
	    	&nbsp; <?php echo isset($recurringDueDateHelp) ? $recurringDueDateHelp : ''; ?> &nbsp;
		</div>

	     <div class="modal-footer" style="margin-top: 0px">
	      	<button type="button" class="btn btn-primary" data-dismiss="modal"><?php echo e(trans('texts.close')); ?></button>
	     </div>

	    </div>
	  </div>
	</div>

	<?php echo Former::close(); ?>


    <?php echo Former::open("{$entityType}s/bulk")->addClass('bulkForm'); ?>

    <?php echo Former::populateField('bulk_public_id', $invoice->public_id); ?>

    <span style="display:none">
    <?php echo Former::text('bulk_public_id'); ?>

    <?php echo Former::text('bulk_action'); ?>

    </span>
    <?php echo Former::close(); ?>


    </div>

    <?php echo $__env->make('invoices.knockout', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

	<script type="text/javascript">
    var sval=$.trim($(".alert-hide").html());
    sval = '<?php echo $invoice->email_send_flag;?>';
    //alert(sval);
    if(sval=='yes'){
       $("input").prop('disabled', true);
       $('input[type="text"], textarea').attr('readonly','readonly');
       $("select").attr("disabled", "disabled");
       
       $("#downloadPdfButton").attr("disabled","disabled");
       $("#saveButton").attr("disabled","disabled");
       $("#emailButton").attr("disabled","disabled");
       $(".dropdown-toggle").attr("disabled","disabled");
       $("a").removeAttr("href");
       $("#editClientLink").attr("data-bind","");
       $("<span>").attr("data-bind","");
    }
    Dropzone.autoDiscover = false;

    var products = <?php echo $products; ?>;
    var clients = <?php echo $clients; ?>;
    var account = <?php echo Auth::user()->account; ?>;
    var dropzone;

    var clientMap = {};
    var $clientSelect = $('select#client');
    var invoiceDesigns = <?php echo $invoiceDesigns; ?>;
    var invoiceFonts = <?php echo $invoiceFonts; ?>;

	$(function() {
        // create client dictionary
        for (var i=0; i<clients.length; i++) {
            var client = clients[i];
            var clientName = getClientDisplayName(client);
            if (!clientName) {
                continue;
            }
            for (var j=0; j<client.contacts.length; j++) {
                var contact = client.contacts[j];
                var contactName = getContactDisplayName(contact);
                if (contact.is_primary === '1') {
                    contact.send_invoice = true;
                }
                if (clientName != contactName) {
                    $clientSelect.append(new Option(contactName, client.public_id));
                }
            }
            clientMap[client.public_id] = client;
            $clientSelect.append(new Option(clientName, client.public_id));
        }

        <?php if($data): ?>
            // this means we failed so we'll reload the previous state
            window.model = new ViewModel(<?php echo $data; ?>);
        <?php else: ?>
            // otherwise create blank model
            window.model = new ViewModel();

            var invoice = <?php echo $invoice; ?>;
            ko.mapping.fromJS(invoice, model.invoice().mapping, model.invoice);
            model.invoice().is_recurring(<?php echo e($invoice->is_recurring ? '1' : '0'); ?>);
            model.invoice().start_date_orig(model.invoice().start_date());

            <?php if($invoice->id): ?>
                var invitationContactIds = <?php echo json_encode($invitationContactIds); ?>;
                var client = clientMap[invoice.client.public_id];
                if (client) { // in case it's deleted
                    for (var i=0; i<client.contacts.length; i++) {
                        var contact = client.contacts[i];
                        contact.send_invoice = invitationContactIds.indexOf(contact.public_id) >= 0;
                    }
                }
                model.invoice().addItem(); // add blank item
            <?php else: ?>
                model.invoice().custom_taxes1(<?php echo e($account->custom_invoice_taxes1 ? 'true' : 'false'); ?>);
                model.invoice().custom_taxes2(<?php echo e($account->custom_invoice_taxes2 ? 'true' : 'false'); ?>);
                // set the default account tax rate
                <?php if($account->invoice_taxes && ! empty($defaultTax)): ?>
                    var defaultTax = <?php echo $defaultTax->toJson(); ?>;
                    model.invoice().tax_rate1(defaultTax.rate);
                    model.invoice().tax_name1(defaultTax.name);
                <?php endif; ?>
            <?php endif; ?>

            <?php if(isset($tasks) && $tasks): ?>
                // move the blank invoice line item to the end
                var blank = model.invoice().invoice_items.pop();
                var tasks = <?php echo $tasks; ?>;

                for (var i=0; i<tasks.length; i++) {
                    var task = tasks[i];
                    var item = model.invoice().addItem();
                    item.notes(task.description);
                    item.qty(task.duration);
                    item.task_public_id(task.publicId);
                }
                model.invoice().invoice_items.push(blank);
                model.invoice().has_tasks(true);
            <?php endif; ?>

            <?php if(isset($expenses) && $expenses): ?>
                model.expense_currency_id(<?php echo e(isset($expenseCurrencyId) ? $expenseCurrencyId : 0); ?>);

                // move the blank invoice line item to the end
                var blank = model.invoice().invoice_items.pop();
                var expenses = <?php echo $expenses; ?>


                for (var i=0; i<expenses.length; i++) {
                    var expense = expenses[i];
                    var item = model.invoice().addItem();
                    item.product_key(expense.expense_category ? expense.expense_category.name : '');
                    item.notes(expense.public_notes);
                    item.qty(1);
                    item.expense_public_id(expense.public_id);
					item.cost(expense.converted_amount);
                    item.tax_rate1(expense.tax_rate1);
                    item.tax_name1(expense.tax_name1);
                    item.tax_rate2(expense.tax_rate2);
                    item.tax_name2(expense.tax_name2);
                }
                model.invoice().invoice_items.push(blank);
                model.invoice().has_expenses(true);
            <?php endif; ?>

        <?php endif; ?>

        // display blank instead of '0'
        if (!NINJA.parseFloat(model.invoice().discount())) model.invoice().discount('');
        if (!NINJA.parseFloat(model.invoice().partial())) model.invoice().partial('');
        if (!model.invoice().custom_value1()) model.invoice().custom_value1('');
        if (!model.invoice().custom_value2()) model.invoice().custom_value2('');

        ko.applyBindings(model);
        onItemChange();


		$('#country_id').combobox().on('change', function(e) {
			var countryId = $('input[name=country_id]').val();
            var country = _.findWhere(countries, {id: countryId});
			if (country) {
                model.invoice().client().country = country;
                model.invoice().client().country_id(countryId);
            } else {
				model.invoice().client().country = false;
				model.invoice().client().country_id(0);
			}
		});

		$('[rel=tooltip]').tooltip({'trigger':'manual'});

		$('#invoice_date, #due_date, #start_date, #end_date, #last_sent_date').datepicker();

		<?php if($invoice->client && !$invoice->id): ?>
			$('input[name=client]').val(<?php echo e($invoice->client->public_id); ?>);
		<?php endif; ?>

		var $input = $('select#client');
		$input.combobox().on('change', function(e) {
            var oldId = model.invoice().client().public_id();
            var clientId = parseInt($('input[name=client]').val(), 10) || 0;
            if (clientId > 0) {
                var selected = clientMap[clientId];
				model.loadClient(selected);
                // we enable searching by contact but the selection must be the client
                $('.client-input').val(getClientDisplayName(selected));
                // if there's an invoice number pattern we'll apply it now
                setInvoiceNumber(selected);
                refreshPDF(true);
			} else if (oldId) {
				model.loadClient($.parseJSON(ko.toJSON(new ClientModel())));
				model.invoice().client().country = false;
                refreshPDF(true);
			}
		});

		// If no clients exists show the client form when clicking on the client select input
		if (clients.length === 0) {
			$('.client_select input.form-control').on('click', function() {
				model.showClientForm();
			});
		}

		$('#invoice_footer, #terms, #public_notes, #invoice_number, #invoice_date, #due_date, #start_date, #po_number, #discount, #currency_id, #invoice_design_id, #recurring, #is_amount_discount, #partial, #custom_text_value1, #custom_text_value2').change(function() {
            $('#downloadPdfButton').attr('disabled', true);
			setTimeout(function() {
				refreshPDF(true);
			}, 1);
		});

        $('.frequency_id .input-group-addon').click(function() {
            showLearnMore();
        });

        $('.recurring_due_date .input-group-addon').click(function() {
            showRecurringDueDateLearnMore();
        });

        var fields = ['invoice_date', 'due_date', 'start_date', 'end_date', 'last_sent_date'];
        for (var i=0; i<fields.length; i++) {
            var field = fields[i];
            (function (_field) {
                $('.' + _field + ' .input-group-addon').click(function() {
                    toggleDatePicker(_field);
                });
            })(field);
        }

        if (model.invoice().client().public_id() || <?php echo e($invoice->id || count($clients) == 0 ? '1' : '0'); ?>) {
            // do nothing
        } else {
            $('.client_select input.form-control').focus();
        }

		$('#clientModal').on('shown.bs.modal', function () {
            $('#client\\[name\\]').focus();
		}).on('hidden.bs.modal', function () {
			if (model.clientBackup) {
				model.loadClient(model.clientBackup);
				refreshPDF(true);
			}
		})

		$('#relatedActions > button:first').click(function() {
			onPaymentClick();
		});

		$('label.radio').addClass('radio-inline');

		<?php if($invoice->client->id): ?>
			$input.trigger('change');
		<?php else: ?>
			refreshPDF(true);
		<?php endif; ?>

		var client = model.invoice().client();
		setComboboxValue($('.client_select'),
			client.public_id(),
			client.name.display());

        <?php if(isset($tasks) && $tasks): ?>
            NINJA.formIsChanged = true;
        <?php endif; ?>

        <?php if(isset($expenses) && $expenses): ?>
            NINJA.formIsChanged = true;
        <?php endif; ?>

        applyComboboxListeners();

        <?php if(Auth::user()->account->hasFeature(FEATURE_DOCUMENTS)): ?>
        $('.main-form').submit(function(){
            if($('#document-upload .dropzone .fallback input').val())$(this).attr('enctype', 'multipart/form-data')
            else $(this).removeAttr('enctype')
        })

        // Initialize document upload
        window.dropzone = false;
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if (window.dropzone) {
                return;
            }

            var target = $(e.target).attr('href') // activated tab
            if (target != '#attached-documents') {
                return;
            }

            window.dropzone = new Dropzone('#document-upload .dropzone', {
                url:<?php echo json_encode(url('documents')); ?>,
                params:{
                    _token:"<?php echo e(Session::getToken()); ?>"
                },
                acceptedFiles:<?php echo json_encode(implode(',',\App\Models\Document::$allowedMimes)); ?>,
                addRemoveLinks:true,
                dictRemoveFileConfirmation:"<?php echo e(trans('texts.are_you_sure')); ?>",
                <?php foreach(['default_message', 'fallback_message', 'fallback_text', 'file_too_big', 'invalid_file_type', 'response_error', 'cancel_upload', 'cancel_upload_confirmation', 'remove_file'] as $key): ?>
                    "dict<?php echo e(Utils::toClassCase($key)); ?>":"<?php echo e(trans('texts.dropzone_'.$key)); ?>",
                <?php endforeach; ?>
                maxFilesize:<?php echo e(floatval(MAX_DOCUMENT_SIZE/1000)); ?>,
            });
            if(dropzone instanceof Dropzone){
                dropzone.on("addedfile",handleDocumentAdded);
                dropzone.on("removedfile",handleDocumentRemoved);
                dropzone.on("success",handleDocumentUploaded);
                dropzone.on("canceled",handleDocumentCanceled);
                dropzone.on("error",handleDocumentError);
                for (var i=0; i<model.invoice().documents().length; i++) {
                    var document = model.invoice().documents()[i];
                    var mockFile = {
                        name:document.name(),
                        size:document.size(),
                        type:document.type(),
                        public_id:document.public_id(),
                        status:Dropzone.SUCCESS,
                        accepted:true,
                        url:document.url(),
                        mock:true,
                        index:i
                    };

                    dropzone.emit('addedfile', mockFile);
                    dropzone.emit('complete', mockFile);
                    if(document.preview_url()){
                        dropzone.emit('thumbnail', mockFile, document.preview_url());
                    }
                    else if(document.type()=='jpeg' || document.type()=='png' || document.type()=='svg'){
                        dropzone.emit('thumbnail', mockFile, document.url());
                    }
                    dropzone.files.push(mockFile);
                }
            }
        });
        <?php endif; ?>
	});

    function onFrequencyChange(){
        var currentName = $('#frequency_id').find('option:selected').text()
        var currentDueDateNumber = $('#recurring_due_date').find('option:selected').attr('data-num');
        var optionClass = currentName && currentName.toLowerCase().indexOf('week') > -1 ? 'weekly' :  'monthly';
        var replacementOption = $('#recurring_due_date option[data-num=' + currentDueDateNumber + '].' + optionClass);

        $('#recurring_due_date option').hide();
        $('#recurring_due_date option.' + optionClass).show();

        // Switch to an equivalent option
        if(replacementOption.length){
            replacementOption.attr('selected','selected');
        }
        else{
            $('#recurring_due_date').val('');
        }
    }

	function applyComboboxListeners() {
        var selectorStr = '.invoice-table input, .invoice-table textarea';
		$(selectorStr).off('change').on('change', function(event) {
            if ($(event.target).hasClass('handled')) {
                return;
            }
            $('#downloadPdfButton').attr('disabled', true);
            onItemChange();
            refreshPDF(true);
		});

        var selectorStr = '.invoice-table select';
        $(selectorStr).off('blur').on('blur', function(event) {
            onItemChange();
            refreshPDF(true);
        });

        $('textarea').on('keyup focus', function(e) {
            while($(this).outerHeight() < this.scrollHeight + parseFloat($(this).css("borderTopWidth")) + parseFloat($(this).css("borderBottomWidth"))) {
                $(this).height($(this).height()+1);
            };
        });
	}

	function createInvoiceModel() {
        var model = ko.toJS(window.model);
        if(!model)return;
		var invoice = model.invoice;
		invoice.features = {
            customize_invoice_design:<?php echo e(Auth::user()->hasFeature(FEATURE_CUSTOMIZE_INVOICE_DESIGN) ? 'true' : 'false'); ?>,
            remove_created_by:<?php echo e(Auth::user()->hasFeature(FEATURE_REMOVE_CREATED_BY) ? 'true' : 'false'); ?>,
            invoice_settings:<?php echo e(Auth::user()->hasFeature(FEATURE_INVOICE_SETTINGS) ? 'true' : 'false'); ?>

        };
		invoice.is_quote = <?php echo e($entityType == ENTITY_QUOTE ? 'true' : 'false'); ?>;
		invoice.contact = _.findWhere(invoice.client.contacts, {send_invoice: true});

        if (invoice.is_recurring) {
            invoice.invoice_number = "<?php echo e(trans('texts.assigned_when_sent')); ?>";
            if (invoice.start_date) {
                invoice.invoice_date = invoice.start_date;
            }
        }

        <?php if(!$invoice->id): ?>
            if (!invoice.terms) {
                invoice.terms = account['<?php echo e($entityType); ?>_terms'];
            }
            if (!invoice.invoice_footer) {
                invoice.invoice_footer = account['invoice_footer'];
            }
        <?php endif; ?>

		<?php if($account->hasLogo()): ?>
			invoice.image = "<?php echo e(Form::image_data($account->getLogoRaw(), true)); ?>";
			invoice.imageWidth = <?php echo e($account->getLogoWidth()); ?>;
			invoice.imageHeight = <?php echo e($account->getLogoHeight()); ?>;
		<?php endif; ?>

        //invoiceLabels.item = invoice.has_tasks ? invoiceLabels.date : invoiceLabels.item_orig;
        invoiceLabels.quantity = invoice.has_tasks ? invoiceLabels.hours : invoiceLabels.quantity_orig;
        invoiceLabels.unit_cost = invoice.has_tasks ? invoiceLabels.rate : invoiceLabels.unit_cost_orig;

        return invoice;
	}

    window.generatedPDF = false;
	function getPDFString(cb, force) {
        <?php if(!$account->live_preview): ?>
            if (window.generatedPDF) {
                return;
            }
        <?php endif; ?>
        var invoice = createInvoiceModel();
		var design  = getDesignJavascript();
		if (!design) return;
        generatePDF(invoice, design, force, cb);
        window.generatedPDF = true;
	}

	function getDesignJavascript() {
		var id = $('#invoice_design_id').val();
		if (id == '-1') {
			showMoreDesigns();
			model.invoice().invoice_design_id(1);
			return invoiceDesigns[0].javascript;
		} else {
            var design = _.find(invoiceDesigns, function(design){ return design.id == id});
            return design ? design.javascript : '';
		}
	}

    function resetTerms() {
        sweetConfirm(function() {
            model.invoice().terms(model.invoice().default_terms());
            refreshPDF();
        });

        return false;
    }

    function resetFooter() {
        sweetConfirm(function() {
            model.invoice().invoice_footer(model.invoice().default_footer());
            refreshPDF();
        });

        return false;
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

	function onEmailClick() {
        if (!NINJA.isRegistered) {
            swal("<?php echo trans('texts.registration_required'); ?>");
            return;
        }

        var clientId = parseInt($('input[name=client]').val(), 10) || 0;
        if (clientId == 0 ) {
            swal("<?php echo trans('texts.no_client_selected'); ?>");
            return;
        }

        if (!isContactSelected()) {
            swal("<?php echo trans('texts.no_contact_selected'); ?>");
            return;
        }

        if (!isEmailValid()) {
            swal("<?php echo trans('texts.provide_email'); ?>");
            return;
        }

		sweetConfirm(function() {
            var accountLanguageId = parseInt(<?php echo e($account->language_id ?: '0'); ?>);
            var clientLanguageId = parseInt(model.invoice().client().language_id()) || 0;
            var attachPDF = <?php echo e($account->attachPDF() ? 'true' : 'false'); ?>;

            // if they aren't attaching the pdf no need to generate it
            if ( ! attachPDF) {
                submitAction('email');
            // if the client's language is different then we can't use the browser version of the PDF
            } else if (clientLanguageId && clientLanguageId != accountLanguageId) {
                submitAction('email');
            } else {
                preparePdfData('email');
            }
		}, getSendToEmails());
	}

	function onSaveClick() {
		if (model.invoice().is_recurring()) {
            // warn invoice will be emailed when saving new recurring invoice
            if (<?php echo e($invoice->exists ? 'false' : 'true'); ?>) {
                var text = getSendToEmails() + '\n' + "<?php echo trans("texts.confirm_recurring_timing"); ?>";
                var title = "<?php echo trans("texts.confirm_recurring_email_$entityType"); ?>";
                sweetConfirm(function() {
                    submitAction('');
                }, text, title);
                return;
            // warn invoice will be emailed again if start date is changed
            } else if (model.invoice().start_date() != model.invoice().start_date_orig()) {
                var text = "<?php echo trans("texts.original_start_date"); ?>: " + model.invoice().start_date_orig() + '\n'
                            + "<?php echo trans("texts.new_start_date"); ?>: " + model.invoice().start_date();
                var title = "<?php echo trans("texts.warn_start_date_changed"); ?>";
                sweetConfirm(function() {
                    submitAction('');
                }, text, title);
                return;
            }
        }

        <?php if(!empty($autoBillChangeWarning)): ?>
            var text = "<?php echo trans('texts.warn_change_auto_bill'); ?>";
            sweetConfirm(function() {
                submitAction('');
            }, text);
            return;
        <?php endif; ?>

        submitAction('');
    }

    function getSendToEmails() {
        var client = model.invoice().client();
        var parts = [];

        for (var i=0; i<client.contacts().length; i++) {
            var contact = client.contacts()[i];
            if (contact.send_invoice()) {
                parts.push(contact.displayName());
            }
        }

        return parts.join('\n');
    }

    function preparePdfData(action) {
        var invoice = createInvoiceModel();
        var design = getDesignJavascript();
        if (!design) return;

        doc = generatePDF(invoice, design, true);
        doc.getDataUrl( function(pdfString){
            $('#pdfupload').val(pdfString);
            submitAction(action);
        });
    }

	function submitAction(value) {
		$('#action').val(value);
		$('#submitButton').click();
	}

    function onFormSubmit(event) {
        if (window.countUploadingDocuments > 0) {
            swal("<?php echo trans('texts.wait_for_upload'); ?>");
            return false;
        }

        // check invoice number is unique
        if ($('.invoice-number').hasClass('has-error')) {
            return false;
        }

        if (!isSaveValid()) {
            model.showClientForm();
            return false;
        }

        // check currency matches for expenses
        var expenseCurrencyId = model.expense_currency_id();
        var clientCurrencyId = model.invoice().client().currency_id() || <?php echo e($account->getCurrencyId()); ?>;
        if (expenseCurrencyId && expenseCurrencyId != clientCurrencyId) {
            swal("<?php echo trans('texts.expense_error_mismatch_currencies'); ?>");
            return false;
        }

        onPartialChange(true);

        return true;
    }

    function submitBulkAction(value) {
        $('#bulk_action').val(value);
        $('.bulkForm').submit();
    }

	function isSaveValid() {
		var isValid = false;
		for (var i=0; i<model.invoice().client().contacts().length; i++) {
			var contact = model.invoice().client().contacts()[i];
			if (isValidEmailAddress(contact.email()) || contact.first_name()) {
				isValid = true;
			} else {
				isValid = false;
				break;
			}
		}
		return isValid;
	}

    function isContactSelected() {
		var sendTo = false;
		var client = model.invoice().client();
		for (var i=0; i<client.contacts().length; i++) {
			var contact = client.contacts()[i];
            if ( ! contact.send_invoice()) {
                continue;
            }
			if (isValidEmailAddress(contact.email())) {
				sendTo = true;
			}
		}
		return sendTo;

    }

	function isEmailValid() {
		var isValid = true;
		var client = model.invoice().client();
		for (var i=0; i<client.contacts().length; i++) {
			var contact = client.contacts()[i];
            if ( ! contact.send_invoice()) {
                continue;
            }
			if (isValidEmailAddress(contact.email())) {
				isValid = true;
			} else {
				isValid = false;
				break;
			}
		}
		return isValid;
	}

	function onMarkClick() {
		submitBulkAction('markSent');
	}

	function onCloneClick() {
		submitAction('clone');
	}

	function onConvertClick() {
		submitAction('convert');
	}

    <?php if($invoice->id): ?>
    	function onPaymentClick() {
            <?php if(!empty($autoBillChangeWarning)): ?>
                sweetConfirm(function() {
                    window.location = '<?php echo e(URL::to('payments/create/' . $invoice->client->public_id . '/' . $invoice->public_id )); ?>';
                }, "<?php echo trans('texts.warn_change_auto_bill'); ?>");
            <?php else: ?>
                window.location = '<?php echo e(URL::to('payments/create/' . $invoice->client->public_id . '/' . $invoice->public_id )); ?>';
            <?php endif; ?>
    	}

    	function onCreditClick() {
    		window.location = '<?php echo e(URL::to('credits/create/' . $invoice->client->public_id . '/' . $invoice->public_id )); ?>';
    	}
    <?php endif; ?>

	function onArchiveClick() {
		submitBulkAction('archive');
	}

	function onDeleteClick() {
        sweetConfirm(function() {
            submitBulkAction('delete');
        });
	}

	function formEnterClick(event) {
		if (event.keyCode === 13){
			if (event.target.type == 'textarea') {
				return;
			}
			event.preventDefault();

            <?php if($invoice->trashed()): ?>
                return;
            <?php endif; ?>
			submitAction('');
			return false;
		}
	}

	function clientModalEnterClick(event) {
		if (event.keyCode === 13){
			event.preventDefault();
            model.clientFormComplete();
            return false;
        }
	}

	function onItemChange()
	{
		var hasEmpty = false;
		for(var i=0; i<model.invoice().invoice_items().length; i++) {
			var item = model.invoice().invoice_items()[i];
			if (item.isEmpty()) {
				hasEmpty = true;
			}
		}

		if (!hasEmpty) {
			model.invoice().addItem();
		}

        //NINJA.formIsChanged = true;
	}

    function onPartialChange(silent)
    {
        var val = NINJA.parseFloat($('#partial').val());
        var oldVal = val;
        val = Math.max(Math.min(val, model.invoice().totals.rawTotal()), 0);
        model.invoice().partial(val || '');

        if (!silent && val != oldVal) {
            $('#partial').tooltip('show');
            setTimeout(function() {
                $('#partial').tooltip('hide');
            }, 5000);
        }
    }

    function onRecurringEnabled()
    {
        if ($('#recurring').prop('checked')) {
            $('#emailButton').attr('disabled', true);
            model.invoice().partial('');
        } else {
            $('#emailButton').removeAttr('disabled');
        }
    }

    function showLearnMore() {
        $('#recurringModal').modal('show');
    }

    function showRecurringDueDateLearnMore() {
        $('#recurringDueDateModal').modal('show');
    }

    function setInvoiceNumber(client) {
        <?php if($invoice->id || !$account->hasClientNumberPattern($invoice)): ?>
            return;
        <?php endif; ?>
        var number = '<?php echo e($account->getNumberPattern($invoice)); ?>';
        number = number.replace('{$custom1}', client.custom_value1 ? client.custom_value1 : '');
        number = number.replace('{$custom2}', client.custom_value2 ? client.custom_value1 : '');
        model.invoice().invoice_number(number);
    }

    window.countUploadingDocuments = 0;

    function handleDocumentAdded(file){
        // open document when clicked
        if (file.url) {
            file.previewElement.addEventListener("click", function() {
                window.open(file.url, '_blank');
            });
        }
        if(file.mock)return;
        file.index = model.invoice().documents().length;
        model.invoice().addDocument({name:file.name, size:file.size, type:file.type});
        window.countUploadingDocuments++;
    }

    function handleDocumentRemoved(file){
        model.invoice().removeDocument(file.public_id);
        refreshPDF(true);
        $.ajax({
            url: '<?php echo e('/documents/'); ?>' + file.public_id,
            type: 'DELETE',
            success: function(result) {
                // Do something with the result
            }
        });
    }

    function handleDocumentUploaded(file, response){
        window.countUploadingDocuments--;
        file.public_id = response.document.public_id
        model.invoice().documents()[file.index].update(response.document);
        <?php if($account->invoice_embed_documents): ?>
            refreshPDF(true);
        <?php endif; ?>
        if(response.document.preview_url){
            dropzone.emit('thumbnail', file, response.document.preview_url);
        }
    }

    function handleDocumentCanceled() {
        window.countUploadingDocuments--;
    }

    function handleDocumentError() {
        window.countUploadingDocuments--;
    }

	</script>
    <?php if($account->hasFeature(FEATURE_DOCUMENTS) && $account->invoice_embed_documents): ?>
        <?php foreach($invoice->documents as $document): ?>
            <?php if($document->isPDFEmbeddable()): ?>
                <script src="<?php echo e($document->getVFSJSUrl()); ?>" type="text/javascript" async></script>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php foreach($invoice->expenses as $expense): ?>
            <?php foreach($expense->documents as $document): ?>
                <?php if($document->isPDFEmbeddable()): ?>
                    <script src="<?php echo e($document->getVFSJSUrl()); ?>" type="text/javascript" async></script>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
    <?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('header', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>