<?php $__env->startSection('head'); ?>
	@parent

		<?php echo $__env->make('money_script', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

		<?php foreach($invoice->client->account->getFontFolders() as $font): ?>
        <script src="<?php echo e(asset('js/vfs_fonts/'.$font.'.js')); ?>" type="text/javascript"></script>
    	<?php endforeach; ?>
        <script src="<?php echo e(asset('pdf.built.js')); ?>?no_cache=<?php echo e(NINJA_VERSION); ?>" type="text/javascript"></script>

		<style type="text/css">
			body {
				background-color: #f8f8f8;
			}

            .dropdown-menu li a{
                overflow:hidden;
                margin-top:5px;
                margin-bottom:5px;
            }
		</style>

    <?php if(!empty($transactionToken) && $accountGateway->gateway_id == GATEWAY_BRAINTREE): ?>
        <div id="paypal-container"></div>
        <script type="text/javascript" src="https://js.braintreegateway.com/js/braintree-2.23.0.min.js"></script>
        <script type="text/javascript" >
            $(function() {
                var paypalLink = $('.dropdown-menu a[href$="paypal"]'),
                    paypalUrl = paypalLink.attr('href'),
                    checkout;
                paypalLink.parent().attr('id', 'paypal-container');
                braintree.setup("<?php echo e($transactionToken); ?>", "custom", {
                    onReady: function (integration) {
                        checkout = integration;
                        $('.dropdown-menu a[href$="#braintree_paypal"]').each(function(){
                            var el=$(this);
                            el.attr('href', el.attr('href').replace('#braintree_paypal','?device_data='+encodeURIComponent(integration.deviceData)))
                        })
                    },
                    paypal: {
                        container: "paypal-container",
                        singleUse: false,
                        enableShippingAddress: false,
                        enableBillingAddress: false,
                        headless: true,
                        locale: "<?php echo e($invoice->client->language ? $invoice->client->language->locale : $invoice->account->language->locale); ?>"
                    },
                    dataCollector: {
                        paypal: true
                    },
                    onPaymentMethodReceived: function (obj) {
                        window.location.href = paypalUrl.replace('#braintree_paypal', '') + '/' + encodeURIComponent(obj.nonce) + "?device_data=" + encodeURIComponent(JSON.stringify(obj.details));
                    }
                });
                paypalLink.click(function(e){
                    e.preventDefault();
                    checkout.paypal.initAuthFlow();
                })
            });
        </script>
    <?php elseif(!empty($enableWePayACH)): ?>
        <script type="text/javascript" src="https://static.wepay.com/js/tokenization.v2.js"></script>
        <script type="text/javascript">
            $(function() {
                var achLink = $('.dropdown-menu a[href$="/bank_transfer"]'),
                    achUrl = achLink.attr('href');
                WePay.set_endpoint('<?php echo e(WEPAY_ENVIRONMENT); ?>');
                achLink.click(function(e) {
                    e.preventDefault();

                    $('#wepay-error').remove();
                    var email = <?php echo json_encode($contact->email); ?> || prompt('<?php echo e(trans('texts.ach_email_prompt')); ?>');
                    if(!email)return;

                    WePay.bank_account.create({
                        'client_id': '<?php echo e(WEPAY_CLIENT_ID); ?>',
                        'email':email
                    }, function(data){
                        dataObj = JSON.parse(data);
                        if(dataObj.bank_account_id) {
                            window.location.href = achLink.attr('href') + '/' + dataObj.bank_account_id + "?details=" + encodeURIComponent(data);
                        } else if(dataObj.error) {
                            $('#wepay-error').remove();
                            achLink.closest('.container').prepend($('<div id="wepay-error" style="margin-top:20px" class="alert alert-danger"></div>').text(dataObj.error_description));
                        }
                    });
                });
            });
        </script>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

	<div class="container">

        <?php if(!empty($partialView)): ?>
            <?php echo $__env->make($partialView, array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
        <?php else: ?>
            <div class="pull-right" style="text-align:right">
            <?php if($invoice->isQuote()): ?>
                <?php echo Button::normal(trans('texts.download_pdf'))->withAttributes(['onclick' => 'onDownloadClick()'])->large(); ?>&nbsp;&nbsp;
                <?php if($showApprove): ?>
                    <?php echo Button::success(trans('texts.approve'))->asLinkTo(URL::to('/approve/' . $invitation->invitation_key))->large(); ?>

                <?php endif; ?>
    		<?php elseif($invoice->client->account->isGatewayConfigured() && !$invoice->isPaid() && !$invoice->is_recurring): ?>
                <?php echo Button::normal(trans('texts.download_pdf'))->withAttributes(['onclick' => 'onDownloadClick()'])->large(); ?>&nbsp;&nbsp;
                <?php if(count($paymentTypes) > 1): ?>
                    <?php echo DropdownButton::success(trans('texts.pay_now'))->withContents($paymentTypes)->large(); ?>

                <?php else: ?>
                    <a href='<?php echo $paymentURL; ?>' class="btn btn-success btn-lg"><?php echo e(trans('texts.pay_now')); ?></a>
                <?php endif; ?>
    		<?php else: ?>
    			<?php echo Button::normal(trans('texts.download_pdf'))->withAttributes(['onclick' => 'onDownloadClick()'])->large(); ?>

                <?php if($account->isNinjaAccount()): ?>
                    <?php echo Button::primary(trans('texts.return_to_app'))->asLinkTo(URL::to('/settings/account_management'))->large(); ?>

                <?php endif; ?>
    		<?php endif; ?>
    		</div>
        <?php endif; ?>

        <div class="pull-left">
            <?php if(!empty($documentsZipURL)): ?>
                <?php echo Button::normal(trans('texts.download_documents', array('size'=>Form::human_filesize($documentsZipSize))))->asLinkTo($documentsZipURL)->large(); ?>

            <?php endif; ?>
        </div>

		<div class="clearfix"></div><p>&nbsp;</p>
        <?php if($account->isPro() && $invoice->hasDocuments()): ?>
            <div class="invoice-documents">
            <h3><?php echo e(trans('texts.documents_header')); ?></h3>
            <ul>
            <?php foreach($invoice->documents as $document): ?>
                <li><a target="_blank" href="<?php echo e($document->getClientUrl($invitation)); ?>"><?php echo e($document->name); ?> (<?php echo e(Form::human_filesize($document->size)); ?>)</a></li>
            <?php endforeach; ?>
            <?php foreach($invoice->expenses as $expense): ?>
                <?php foreach($expense->documents as $document): ?>
                    <li><a target="_blank" href="<?php echo e($document->getClientUrl($invitation)); ?>"><?php echo e($document->name); ?> (<?php echo e(Form::human_filesize($document->size)); ?>)</a></li>
                <?php endforeach; ?>
            <?php endforeach; ?>
            </ul>
            </div>
        <?php endif; ?>

        <?php if($account->hasFeature(FEATURE_DOCUMENTS) && $account->invoice_embed_documents): ?>
            <?php foreach($invoice->documents as $document): ?>
                <?php if($document->isPDFEmbeddable()): ?>
                    <script src="<?php echo e($document->getClientVFSJSUrl()); ?>" type="text/javascript" async></script>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php foreach($invoice->expenses as $expense): ?>
                <?php foreach($expense->documents as $document): ?>
                    <?php if($document->isPDFEmbeddable()): ?>
                        <script src="<?php echo e($document->getClientVFSJSUrl()); ?>" type="text/javascript" async></script>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
		<script type="text/javascript">

			window.invoice = <?php echo $invoice->toJson(); ?>;
			invoice.features = {
                customize_invoice_design:<?php echo e($invoice->client->account->hasFeature(FEATURE_CUSTOMIZE_INVOICE_DESIGN) ? 'true' : 'false'); ?>,
                remove_created_by:<?php echo e($invoice->client->account->hasFeature(FEATURE_REMOVE_CREATED_BY) ? 'true' : 'false'); ?>,
                invoice_settings:<?php echo e($invoice->client->account->hasFeature(FEATURE_INVOICE_SETTINGS) ? 'true' : 'false'); ?>

            };
			invoice.is_quote = <?php echo e($invoice->isQuote() ? 'true' : 'false'); ?>;
			invoice.contact = <?php echo $contact->toJson(); ?>;

			function getPDFString(cb) {
    	  	    return generatePDF(invoice, invoice.invoice_design.javascript, true, cb);
			}

            if (window.hasOwnProperty('pjsc_meta')) {
                window['pjsc_meta'].remainingTasks++;
            }

			$(function() {
                <?php if(Input::has('phantomjs')): ?>
                    doc = getPDFString();
                    doc.getDataUrl(function(pdfString) {
                        document.write(pdfString);
                        document.close();

                        if (window.hasOwnProperty('pjsc_meta')) {
                            window['pjsc_meta'].remainingTasks--;
                        }
                    });
                <?php else: ?>
                    refreshPDF();
                <?php endif; ?>
			});

			function onDownloadClick() {
				var doc = generatePDF(invoice, invoice.invoice_design.javascript, true);
                var fileName = invoice.is_quote ? invoiceLabels.quote : invoiceLabels.invoice;
				doc.save(fileName + '-' + invoice.invoice_number + '.pdf');
			}

		</script>

		<?php echo $__env->make('invoices.pdf', ['account' => $invoice->client->account, 'viewPDF' => true], array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

		<p>&nbsp;</p>

	</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('public.header', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>