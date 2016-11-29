<table class="table table-striped data-table <?php echo e($class = str_random(8)); ?>">
    <colgroup>
        <?php for($i = 0; $i < count($columns); $i++): ?>
        <col class="con<?php echo e($i); ?>" />
        <?php endfor; ?>
    </colgroup>
    <thead>
    <tr>
        <?php foreach($columns as $i => $c): ?>
        <th align="center" valign="middle" class="head<?php echo e($i); ?>"
            <?php if($c == 'checkbox'): ?>
                style="width:20px"
            <?php endif; ?>
        >
            <?php if($c == 'checkbox' && $hasCheckboxes = true): ?>
                <input type="checkbox" class="selectAll"/>
            <?php else: ?>
                <?php echo e($c); ?>

            <?php endif; ?>
        </th>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <?php foreach($data as $d): ?>
    <tr>
        <?php foreach($d as $dd): ?>
        <td><?php echo e($dd); ?></td>
        <?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<script type="text/javascript">
    <?php if(isset($values['entityType'])): ?>
            window.load_<?php echo e($values['entityType']); ?> = function load_<?php echo e($values['entityType']); ?>() {
                load_<?php echo e($class); ?>();
            }
    <?php else: ?>
        jQuery(document).ready(function(){
            load_<?php echo e($class); ?>();
        });
    <?php endif; ?>

    function refreshDatatable() {
        window.dataTable.api().ajax.reload();
    }

    function load_<?php echo e($class); ?>() {
        window.dataTable = jQuery('.<?php echo e($class); ?>').dataTable({
            "fnRowCallback": function(row, data) {
                if (data[0].indexOf('ENTITY_DELETED') > 0) {
                    $(row).addClass('entityDeleted');
                }
                if (data[0].indexOf('ENTITY_ARCHIVED') > 0) {
                    $(row).addClass('entityArchived');
                }
            },
            "bAutoWidth": false,
            <?php if(isset($hasCheckboxes) && $hasCheckboxes): ?>
            'aaSorting': [['1', 'asc']],
            // Disable sorting on the first column
            "aoColumnDefs": [
                {
                    'bSortable': false,
                    'aTargets': [ 0, <?php echo e(count($columns) - 1); ?> ]
                },
                {
                    'sClass': 'right',
                    'aTargets': <?php echo e(isset($values['rightAlign']) ? json_encode($values['rightAlign']) : '[]'); ?>

                }
            ],
            <?php endif; ?>
            <?php foreach($options as $k => $o): ?>
            <?php echo json_encode($k); ?>: <?php echo json_encode($o); ?>,
            <?php endforeach; ?>
            <?php foreach($callbacks as $k => $o): ?>
            <?php echo json_encode($k); ?>: <?php echo $o; ?>,
            <?php endforeach; ?>
            "fnDrawCallback": function(oSettings) {
                if (window.onDatatableReady) {
                    window.onDatatableReady();
                }
            }
        });
    }
</script>