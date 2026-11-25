<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
                    <div>
                        <h4 class="tw-mt-0 tw-font-semibold tw-text-lg"><?php echo html_escape($title); ?></h4>
                        <p class="text-muted">One shared catalogue for the website, recommendations, quotes and partner API.</p>
                    </div>
                    <span class="label label-info"><?php echo (int) $offering_count; ?> offerings</span>
                </div>
                <div class="panel_s"><div class="panel-body">
                    <div class="row m-b-15">
                        <div class="col-md-6"><strong>Published revision:</strong> <?php echo $published_revision === null ? 'None' : (int) $published_revision; ?></div>
                        <div class="col-md-6"><strong>Draft revision:</strong> <?php echo $draft_revision === null ? 'None' : (int) $draft_revision; ?></div>
                    </div>
                    <?php if ($can_edit) { ?>
                        <?php echo form_open(admin_url('nm_offerings/save')); ?>
                        <p class="text-muted">Edit the complete offering document. Translations, price components, classifications and relationships are validated together before the draft is saved.</p>
                        <?php echo render_textarea('catalogue_json', 'Catalogue JSON', $catalogue_json, ['rows' => 30, 'class' => 'form-control', 'spellcheck' => 'false']); ?>
                        <button type="submit" class="btn btn-primary">Save draft</button>
                        <?php echo form_close(); ?>
                    <?php } else { ?>
                        <p>You have view access only. Ask an administrator to grant catalogue edit permission.</p>
                    <?php } ?>
                    <?php if ($can_publish) { ?>
                        <hr>
                        <?php echo form_open(admin_url('nm_offerings/publish')); ?>
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
