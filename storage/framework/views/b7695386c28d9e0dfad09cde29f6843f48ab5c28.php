<div class="col-lg-3 col-md-6">

    <?php echo Former::select("{$section}_select")
            ->placeholder(trans("texts.{$fields}"))
            ->options($account->getAllInvoiceFields()[$fields])
            ->onchange("addField('{$section}')")
            ->raw(); ?>


    <table class="field-list">
    <tbody data-bind="sortable: { data: <?php echo e($section); ?>, as: 'field', afterMove: onDragged }">
        <tr>
            <td>
                <i class="fa fa-close" data-bind="click: $root.<?php echo e(Utils::toCamelCase('remove' . ucwords($section))); ?>"></i>
                <span data-bind="text: window.field_map[field]"></span>
                <i class="fa fa-bars"></i>
            </td>
        </tr>
    </tbody>
    </table>


</div>
