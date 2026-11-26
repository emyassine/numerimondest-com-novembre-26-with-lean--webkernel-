<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
                    <div>
                        <h4 class="tw-mt-0 tw-font-semibold tw-text-lg">
                        	<?php echo html_escape($title); ?>
                        </h4>
                        <p class="text-muted">One shared catalogue for the website, recommendations, quotes and partner API.</p>
                    </div>
                    <span class="label label-info"><?php echo (int) $offering_count; ?> offerings</span>
                </div>
                <div class="panel_s"><div class="panel-body">
                    <p class="text-muted">SQLite: <code><?php echo html_escape($database_path); ?></code> · Current working data: <?php echo html_escape($source_label); ?></p>
                    <div class="row m-b-15">
                        <div class="col-md-6"><strong>Published revision:</strong> <?php echo $published_revision === null ? 'None' : (int) $published_revision; ?></div>
                        <div class="col-md-6"><strong>Draft revision:</strong> <?php echo $draft_revision === null ? 'None' : (int) $draft_revision; ?></div>
                    </div>
                    <?php if ($can_edit) { ?>
                        <div class="tw-mb-4"><a class="btn btn-primary" href="<?php echo admin_url('nm-offerings/create'); ?>"><i class="fa fa-plus"></i> New offering</a></div>
                    <?php } ?>
                    <div class="panel_s"><div class="panel-body panel-table-full">
                        <table class="table dt-table">
                            <thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Visibility</th><th>Status</th><?php if ($can_edit) { ?><th>Action</th><?php } ?></tr></thead>
                            <tbody>
                            <?php foreach ($offerings as $offering) { ?>
                                <tr>
                                    <td><?php echo html_escape($offering['id'] ?? ''); ?></td>
                                    <td><?php echo html_escape($offering['name']['en'] ?? $offering['id'] ?? ''); ?></td>
                                    <td><?php echo html_escape($offering['type'] ?? ''); ?></td>
                                    <td><?php echo html_escape($offering['visibility'] ?? 'catalog'); ?></td>
                                    <td><?php echo !empty($offering['active']) ? 'Active' : 'Inactive'; ?></td>
                                    <?php if ($can_edit) { ?><td><a href="<?php echo admin_url('nm-offerings/edit/' . rawurlencode($offering['id'] ?? '')); ?>">Edit</a></td><?php } ?>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div></div>
                    <?php if (!$can_edit) { ?>
                        <p>You have view access only. Ask an administrator to grant catalogue edit permission.</p>
                    <?php } ?>
                    <?php if ($can_publish) { ?>
                        <hr>
                        <?php echo form_open(admin_url('nm-offerings/publish')); ?>
                        <p>Publishing replaces the live snapshot used by the website and partner API. The previous publication remains in revision history.</p>
                        <button type="submit" class="btn btn-success" <?php echo $draft_revision === null ? 'disabled' : ''; ?>>Publish saved draft</button>
                        <?php echo form_close(); ?>
                    <?php } ?>
                </div></div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
