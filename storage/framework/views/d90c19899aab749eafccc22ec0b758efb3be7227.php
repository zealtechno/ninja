<?php if(!Utils::isPro() && isset($advanced) && $advanced): ?>
<div class="alert alert-warning" style="font-size:larger;">
<center>
    <?php echo trans('texts.pro_plan_advanced_settings', ['link'=>link_to('/settings/account_management?upgrade=true', trans('texts.pro_plan_remove_logo_link'))]); ?>

</center>
</div>
<?php endif; ?>

<div class="row">

    <div class="col-md-3">
        <?php foreach([
            BASIC_SETTINGS => \App\Models\Account::$basicSettings,
            ADVANCED_SETTINGS => \App\Models\Account::$advancedSettings,
        ] as $type => $settings): ?>
            <div class="panel panel-default">
                <div class="panel-heading" style="color:white">
                    <?php echo e(trans("texts.{$type}")); ?>

                    <?php if($type === ADVANCED_SETTINGS && !Utils::isPro()): ?>
                        <sup><?php echo e(strtoupper(trans('texts.pro'))); ?></sup>
                    <?php endif; ?>
                </div>
                <div class="list-group">
                    <?php foreach($settings as $section): ?>
                        <a href="<?php echo e(URL::to("settings/{$section}")); ?>" class="list-group-item <?php echo e($selected === $section ? 'selected' : ''); ?>"
                            style="width:100%;text-align:left"><?php echo e(trans("texts.{$section}")); ?></a>
                    <?php endforeach; ?>
                    <?php if($type === ADVANCED_SETTINGS && !Utils::isNinjaProd()): ?>
                        <a href="<?php echo e(URL::to("settings/system_settings")); ?>" class="list-group-item <?php echo e($selected === 'system_settings' ? 'selected' : ''); ?>"
                            style="width:100%;text-align:left"><?php echo e(trans("texts.system_settings")); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="col-md-9">
