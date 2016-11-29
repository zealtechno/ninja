<?php $__env->startSection('head'); ?>
	@parent

    <?php echo $__env->make('money_script', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
    <?php foreach($account->getFontFolders() as $font): ?>
        <script src="<?php echo e(asset('js/vfs_fonts/'.$font.'.js')); ?>" type="text/javascript"></script>
    <?php endforeach; ?>
    <script src="<?php echo e(asset('pdf.built.js')); ?>?no_cache=<?php echo e(NINJA_VERSION); ?>" type="text/javascript"></script>
    <script src="<?php echo e(asset('js/lightbox.min.js')); ?>" type="text/javascript"></script>
    <link href="<?php echo e(asset('css/lightbox.css')); ?>" rel="stylesheet" type="text/css"/>


<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
	@parent
    <?php echo $__env->make('accounts.nav', ['selected' => ACCOUNT_INVOICE_DESIGN, 'advanced' => true], array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
    <?php echo $__env->make('accounts.partials.invoice_fields', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

  <script>
    var invoiceDesigns = <?php echo $invoiceDesigns; ?>;
    var invoiceFonts = <?php echo $invoiceFonts; ?>;
    var invoice = <?php echo json_encode($invoice); ?>;

    function getDesignJavascript() {
      var id = $('#invoice_design_id').val();
      if (id == '-1') {
        showMoreDesigns();
        $('#invoice_design_id').val(1);
        return invoiceDesigns[0].javascript;
      } else {
        var design = _.find(invoiceDesigns, function(design){ return design.id == id});
        return design ? design.javascript : '';
      }
    }

    function loadFont(fontId){
      var fontFolder = '';
      $.each(window.invoiceFonts, function(i, font){
        if(font.id==fontId)fontFolder=font.folder;
      });
      if(!window.ninjaFontVfs[fontFolder]){
        window.loadingFonts = true;
        jQuery.getScript(<?php echo json_encode(asset('js/vfs_fonts/%s.js')); ?>.replace('%s', fontFolder), function(){window.loadingFonts=false;ninjaLoadFontVfs();refreshPDF()})
      }
    }

    function getPDFString(cb) {
      invoice.features = {
          customize_invoice_design:<?php echo e(Auth::user()->hasFeature(FEATURE_CUSTOMIZE_INVOICE_DESIGN) ? 'true' : 'false'); ?>,
          remove_created_by:<?php echo e(Auth::user()->hasFeature(FEATURE_REMOVE_CREATED_BY) ? 'true' : 'false'); ?>,
          invoice_settings:<?php echo e(Auth::user()->hasFeature(FEATURE_INVOICE_SETTINGS) ? 'true' : 'false'); ?>

      };
      invoice.account.hide_quantity = $('#hide_quantity').is(":checked");
      invoice.account.invoice_embed_documents = $('#invoice_embed_documents').is(":checked");
      invoice.account.hide_paid_to_date = $('#hide_paid_to_date').is(":checked");
      invoice.invoice_design_id = $('#invoice_design_id').val();
      invoice.account.page_size = $('#page_size option:selected').text();
      invoice.account.invoice_fields = ko.mapping.toJSON(model);

      NINJA.primaryColor = $('#primary_color').val();
      NINJA.secondaryColor = $('#secondary_color').val();
      NINJA.fontSize = parseInt($('#font_size').val());
      NINJA.headerFont = $('#header_font_id option:selected').text();
      NINJA.bodyFont = $('#body_font_id option:selected').text();

      var fields = [
          'item',
          'description',
          'unit_cost',
          'quantity',
          'line_total',
          'terms',
          'balance_due',
          'partial_due'
      ];
      invoiceLabels.old = {};
      for (var i=0; i<fields.length; i++) {
        var field = fields[i];
        var val = $('#labels_' + field).val();
        if (invoiceLabels.old.hasOwnProperty(field)) {
            invoiceLabels.old[field] = invoiceLabels[field];
        }
        if (val) {
            invoiceLabels[field] = val;
        }
      }

      generatePDF(invoice, getDesignJavascript(), true, cb);
    }

    $(function() {
      var options = {
        preferredFormat: 'hex',
        disabled: <?php echo Auth::user()->hasFeature(FEATURE_CUSTOMIZE_INVOICE_DESIGN) ? 'false' : 'true'; ?>,
        showInitial: false,
        showInput: true,
        allowEmpty: true,
        clickoutFiresChange: true,
      };

      $('#primary_color').spectrum(options);
      $('#secondary_color').spectrum(options);
      $('#header_font_id').change(function(){loadFont($('#header_font_id').val())});
      $('#body_font_id').change(function(){loadFont($('#body_font_id').val())});

      refreshPDF();
    });

  </script>


  <div class="row">
    <div class="col-md-12">

      <?php echo Former::open()->addClass('warn-on-exit')->onchange('if(!window.loadingFonts)refreshPDF()'); ?>


      <?php echo Former::populateField('invoice_design_id', $account->invoice_design_id); ?>

      <?php echo Former::populateField('body_font_id', $account->getBodyFontId()); ?>

      <?php echo Former::populateField('header_font_id', $account->getHeaderFontId()); ?>

      <?php echo Former::populateField('live_preview', intval($account->live_preview)); ?>

      <?php echo Former::populateField('font_size', $account->font_size); ?>

      <?php echo Former::populateField('page_size', $account->page_size); ?>

      <?php echo Former::populateField('invoice_embed_documents', intval($account->invoice_embed_documents)); ?>

      <?php echo Former::populateField('primary_color', $account->primary_color); ?>

      <?php echo Former::populateField('secondary_color', $account->secondary_color); ?>

      <?php echo Former::populateField('hide_quantity', intval($account->hide_quantity)); ?>

      <?php echo Former::populateField('hide_paid_to_date', intval($account->hide_paid_to_date)); ?>

      <?php echo Former::populateField('all_pages_header', intval($account->all_pages_header)); ?>

      <?php echo Former::populateField('all_pages_footer', intval($account->all_pages_footer)); ?>


          <?php foreach($invoiceLabels as $field => $value): ?>
          <?php echo Former::populateField("labels_{$field}", $value); ?>

        <?php endforeach; ?>

        <div style="display:none">
            <?php echo Former::text('invoice_fields_json')->data_bind('value: ko.mapping.toJSON(model)'); ?>

		</div>


    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><?php echo trans('texts.invoice_design'); ?></h3>
      </div>

        <div class="panel-body">
            <div role="tabpanel">
                <ul class="nav nav-tabs" role="tablist" style="border: none">
                    <li role="presentation" class="active"><a href="#general_settings" aria-controls="general_settings" role="tab" data-toggle="tab"><?php echo e(trans('texts.general_settings')); ?></a></li>
                    <li role="presentation"><a href="#invoice_labels" aria-controls="invoice_labels" role="tab" data-toggle="tab"><?php echo e(trans('texts.invoice_labels')); ?></a></li>
                    <li role="presentation"><a href="#invoice_fields" aria-controls="invoice_fields" role="tab" data-toggle="tab"><?php echo e(trans('texts.invoice_fields')); ?></a></li>
                    <li role="presentation"><a href="#invoice_options" aria-controls="invoice_options" role="tab" data-toggle="tab"><?php echo e(trans('texts.invoice_options')); ?></a></li>
                    <li role="presentation"><a href="#header_footer" aria-controls="header_footer" role="tab" data-toggle="tab"><?php echo e(trans('texts.header_footer')); ?></a></li>
                </ul>
            </div>
            <div class="tab-content">
                <div role="tabpanel" class="tab-pane active" id="general_settings">
                    <div class="panel-body">

                      <div class="row">
                        <div class="col-md-6">

                          <?php if(!Utils::hasFeature(FEATURE_MORE_INVOICE_DESIGNS) || \App\Models\InvoiceDesign::count() == COUNT_FREE_DESIGNS_SELF_HOST): ?>
                            <?php echo Former::select('invoice_design_id')
                                    ->fromQuery($invoiceDesigns, 'name', 'id')
                                    ->addOption(trans('texts.more_designs') . '...', '-1'); ?>

                          <?php else: ?>
                            <?php echo Former::select('invoice_design_id')
                                    ->fromQuery($invoiceDesigns, 'name', 'id'); ?>

                          <?php endif; ?>
                          <?php echo Former::select('body_font_id')
                                  ->fromQuery($invoiceFonts, 'name', 'id'); ?>

                          <?php echo Former::select('header_font_id')
                                  ->fromQuery($invoiceFonts, 'name', 'id'); ?>


                          <?php echo Former::checkbox('live_preview')->text(trans('texts.enable')); ?>


                        </div>
                        <div class="col-md-6">

                        <?php echo e(Former::setOption('TwitterBootstrap3.labelWidths.large', 6)); ?>

                        <?php echo e(Former::setOption('TwitterBootstrap3.labelWidths.small', 6)); ?>


                          <?php echo Former::select('page_size')
                                  ->options($pageSizes); ?>


                          <?php echo Former::text('font_size')
                                ->type('number')
                                ->min('0')
                                ->step('1'); ?>


                          <?php echo Former::text('primary_color'); ?>

                          <?php echo Former::text('secondary_color'); ?>



                        <?php echo e(Former::setOption('TwitterBootstrap3.labelWidths.large', 4)); ?>

                        <?php echo e(Former::setOption('TwitterBootstrap3.labelWidths.small', 4)); ?>


                        </div>
                      </div>

                      <div class="help-block">
                        <?php echo e(trans('texts.color_font_help')); ?>

                      </div>

                    </div>
                </div>
                <div role="tabpanel" class="tab-pane" id="invoice_labels">
                    <div class="panel-body">

                      <div class="row">
                        <div class="col-md-6">
                              <?php echo Former::text('labels_item')->label(trans('texts.item')); ?>

                              <?php echo Former::text('labels_description')->label(trans('texts.description')); ?>

                              <?php echo Former::text('labels_unit_cost')->label(trans('texts.unit_cost')); ?>

                              <?php echo Former::text('labels_quantity')->label(trans('texts.quantity')); ?>

							  <?php echo Former::text('labels_line_total')->label(trans('texts.line_total')); ?>

							  <?php echo Former::text('labels_terms')->label(trans('texts.terms')); ?>

                        </div>
                        <div class="col-md-6">
                              <?php echo Former::text('labels_subtotal')->label(trans('texts.subtotal')); ?>

							  <?php echo Former::text('labels_discount')->label(trans('texts.discount')); ?>

							  <?php echo Former::text('labels_paid_to_date')->label(trans('texts.paid_to_date')); ?>

							  <?php echo Former::text('labels_balance_due')->label(trans('texts.balance_due')); ?>

							  <?php echo Former::text('labels_partial_due')->label(trans('texts.partial_due')); ?>

                              <?php echo Former::text('labels_tax')->label(trans('texts.tax')); ?>

                        </div>
                      </div>

                    </div>
                </div>
                <div role="tabpanel" class="tab-pane" id="invoice_fields">
                    <div class="panel-body">
                      <div class="row">
                          <?php echo $__env->make('accounts.partials.invoice_fields_selector', ['section' => 'invoice_fields', 'fields' => INVOICE_FIELDS_INVOICE], array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
                          <?php echo $__env->make('accounts.partials.invoice_fields_selector', ['section' => 'client_fields', 'fields' => INVOICE_FIELDS_CLIENT], array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
                          <?php echo $__env->make('accounts.partials.invoice_fields_selector', ['section' => 'account_fields1', 'fields' => INVOICE_FIELDS_ACCOUNT], array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
                          <?php echo $__env->make('accounts.partials.invoice_fields_selector', ['section' => 'account_fields2', 'fields' => INVOICE_FIELDS_ACCOUNT], array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
                      </div>
                      <div class="row">
                          <div class="pull-right" style="padding-top:18px;padding-right:14px">
                              <?php echo Button::normal(trans('texts.reset'))
                                    ->withAttributes(['onclick' => 'sweetConfirm(function() {
                                        resetFields();
                                    })'])
                                    ->small(); ?>

                          </div>
                      </div>
                    </div>
                </div>
                <div role="tabpanel" class="tab-pane" id="invoice_options">
                    <div class="panel-body">

                      <?php echo Former::checkbox('hide_quantity')->text(trans('texts.hide_quantity_help')); ?>

                      <?php echo Former::checkbox('hide_paid_to_date')->text(trans('texts.hide_paid_to_date_help')); ?>

                      <?php echo Former::checkbox('invoice_embed_documents')->text(trans('texts.invoice_embed_documents_help')); ?>


                    </div>
                </div>
                <div role="tabpanel" class="tab-pane" id="header_footer">
                    <div class="panel-body">

                    <?php echo Former::inline_radios('all_pages_header')
                            ->label(trans('texts.all_pages_header'))
                            ->radios([
                                trans('texts.first_page') => ['value' => 0, 'name' => 'all_pages_header'],
                                trans('texts.all_pages') => ['value' => 1, 'name' => 'all_pages_header'],
                            ])->check($account->all_pages_header); ?>


                    <?php echo Former::inline_radios('all_pages_footer')
                            ->label(trans('texts.all_pages_footer'))
                            ->radios([
                                trans('texts.last_page') => ['value' => 0, 'name' => 'all_pages_footer'],
                                trans('texts.all_pages') => ['value' => 1, 'name' => 'all_pages_footer'],
                            ])->check($account->all_pages_footer); ?>


                    </div>
                </div>
            </div>
        </div>
    </div>


    <br/>
    <?php echo Former::actions(
            Button::primary(trans('texts.customize'))
                ->appendIcon(Icon::create('edit'))
                ->asLinkTo(URL::to('/settings/customize_design'))
                ->large(),
            Auth::user()->hasFeature(FEATURE_CUSTOMIZE_INVOICE_DESIGN) ?
                Button::success(trans('texts.save'))
                    ->submit()->large()
                    ->appendIcon(Icon::create('floppy-disk'))
                    ->withAttributes(['class' => 'save-button']) :
                false
        ); ?>

    <br/>

      <?php echo Former::close(); ?>


    </div>
  </div>


      <?php echo $__env->make('invoices.pdf', ['account' => Auth::user()->account, 'pdfHeight' => 800], array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>


<?php $__env->stopSection(); ?>

<?php echo $__env->make('header', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>