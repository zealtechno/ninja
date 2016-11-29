<?php $__env->startSection('head'); ?>
    @parent

    <link href="<?php echo e(asset('css/quill.snow.css')); ?>" rel="stylesheet" type="text/css"/>
    <script src="<?php echo e(asset('js/quill.min.js')); ?>" type="text/javascript"></script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
	@parent

	<style type="text/css">

	#logo {
		padding-top: 6px;
	}

	</style>

	<?php echo Former::open_for_files()
            ->addClass('warn-on-exit')
            ->autocomplete('on')
            ->rules([
                'name' => 'required',
                'website' => 'url',
            ]); ?>


	<?php echo e(Former::populate($account)); ?>


    <?php echo $__env->make('accounts.nav', ['selected' => ACCOUNT_COMPANY_DETAILS], array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>

	<div class="row">
		<div class="col-md-12">

        <div class="panel panel-default">
          <div class="panel-heading">
            <h3 class="panel-title"><?php echo trans('texts.details'); ?></h3>
          </div>
            <div class="panel-body form-padding-right">

                <?php echo Former::text('name'); ?>

                <?php echo Former::text('id_number'); ?>

                <?php echo Former::text('vat_number'); ?>

                <?php echo Former::text('website'); ?>

                <?php echo Former::text('work_email'); ?>

                <?php echo Former::text('work_phone'); ?>

                <?php echo Former::file('logo')->max(2, 'MB')->accept('image')->inlineHelp(trans('texts.logo_help')); ?>



                <?php if($account->hasLogo()): ?>
                <div class="form-group">
                    <div class="col-lg-4 col-sm-4"></div>
                    <div class="col-lg-8 col-sm-8">
                        <a href="<?php echo e($account->getLogoUrl(true)); ?>" target="_blank">
                            <?php echo HTML::image($account->getLogoUrl(true), 'Logo', ['style' => 'max-width:300px']); ?>

                        </a> &nbsp;
                        <a href="#" onclick="deleteLogo()"><?php echo e(trans('texts.remove_logo')); ?></a>
                    </div>
                </div>
                <?php endif; ?>

                <?php echo Former::select('size_id')
                        ->addOption('','')
                        ->fromQuery($sizes, 'name', 'id'); ?>


                <?php echo Former::select('industry_id')
                        ->addOption('','')
                        ->fromQuery($industries, 'name', 'id')
                        ->help('texts.industry_help'); ?>


            </div>
        </div>

        <div class="panel panel-default">
          <div class="panel-heading">
            <h3 class="panel-title"><?php echo trans('texts.address'); ?></h3>
          </div>
            <div class="panel-body form-padding-right">

            <?php echo Former::text('address1')->autocomplete('address-line1'); ?>

            <?php echo Former::text('address2')->autocomplete('address-line2'); ?>

            <?php echo Former::text('city')->autocomplete('address-level2'); ?>

            <?php echo Former::text('state')->autocomplete('address-level1'); ?>

            <?php echo Former::text('postal_code')->autocomplete('postal-code'); ?>

            <?php echo Former::select('country_id')
                    ->addOption('','')
                    ->fromQuery($countries, 'name', 'id'); ?>


            </div>
        </div>

        <div class="panel panel-default">
          <div class="panel-heading">
            <h3 class="panel-title"><?php echo trans('texts.signature'); ?></h3>
          </div>
            <div class="panel-body">

                <div class="col-md-10 col-md-offset-1">
                    <?php echo Former::textarea('email_footer')->style('display:none')->raw(); ?>

                    <div id="signatureEditor" class="form-control" style="min-height:160px" onclick="focusEditor()"></div>
                    <?php echo $__env->make('partials/quill_toolbar', ['name' => 'signature'], array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
                </div>

            </div>
        </div>
        </div>


	</div>

	<center>
        <?php echo Button::success(trans('texts.save'))->submit()->large()->appendIcon(Icon::create('floppy-disk')); ?>

	</center>

    <?php echo Former::close(); ?>


	<?php echo Form::open(['url' => 'remove_logo', 'class' => 'removeLogoForm']); ?>

	<?php echo Form::close(); ?>



	<script type="text/javascript">

        var editor = false;
        $(function() {
            $('#country_id').combobox();

            editor = new Quill('#signatureEditor', {
                modules: {
                    'toolbar': { container: '#signatureToolbar' },
                    'link-tooltip': true
                },
                theme: 'snow'
            });
            editor.setHTML($('#email_footer').val());
            editor.on('text-change', function(delta, source) {
                if (source == 'api') {
                    return;
                }
                var html = editor.getHTML();
                $('#email_footer').val(html);
                NINJA.formIsChanged = true;
            });
        });

        function focusEditor() {
            editor.focus();
        }

        function deleteLogo() {
            sweetConfirm(function() {
                $('.removeLogoForm').submit();
            });
        }

	</script>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('onReady'); ?>
    $('#name').focus();
<?php $__env->stopSection(); ?>

<?php echo $__env->make('header', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>