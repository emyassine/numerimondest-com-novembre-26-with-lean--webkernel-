<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$offering = is_array($selected ?? null) ? $selected : [];
$value = static function ($field, $default = '') use ($offering) {
    return html_escape((string) ($offering[$field] ?? $default));
};
$english = static function ($field) use ($offering) {
    $translations = $offering[$field] ?? [];
    return html_escape(is_array($translations) ? (string) ($translations['en'] ?? '') : '');
};
$lines = static function ($field) use ($offering) {
    return html_escape(implode("\n", is_array($offering[$field] ?? null) ? $offering[$field] : []));
};
$prices = is_array($offering['prices'] ?? null) ? $offering['prices'] : [];
$relations = is_array($offering['relations'] ?? null) ? $offering['relations'] : [];
?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
                    <div>
                        <h4 class="tw-mt-0 tw-font-semibold tw-text-lg"><?php echo html_escape($title); ?></h4>
                        <p class="text-muted">Edit the shared SQLite catalogue. Saving creates a draft; publishing makes it live on the website.</p>
                    </div>
                    <a class="btn btn-default" href="<?php echo admin_url('nm-offerings'); ?>">Back to offerings</a>
                </div>
                <?php echo form_open(admin_url('nm-offerings/save')); ?>
                <?php echo form_hidden('original_id', $is_new ? '' : ($offering['id'] ?? '')); ?>
                <div class="panel_s"><div class="panel-body">
                    <h4>Offering details</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <?php echo render_input('id', 'Stable URL ID (lowercase, hyphens)', $value('id'), 'text', ['required' => true, 'pattern' => '[a-z0-9][a-z0-9-]*', 'readonly' => !$is_new]); ?>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group"><label for="type">Offering type</label>
                                <select class="form-control" id="type" name="type" required>
                                    <?php foreach (['mandate', 'software', 'playbook', 'service'] as $type) { ?>
                                        <option value="<?php echo $type; ?>" <?php echo ($offering['type'] ?? 'service') === $type ? 'selected' : ''; ?>><?php echo ucfirst($type); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <?php echo render_input('name_en', 'Name (English)', $english('name'), 'text', ['required' => true]); ?>
                    <?php echo render_textarea('summary_en', 'Short summary (English)', $english('summary'), ['rows' => 3]); ?>
                    <?php echo render_textarea('description_en', 'Description (English)', $english('description'), ['rows' => 7, 'required' => true]); ?>
                    <div class="row">
                        <?php foreach (['category' => 'Category', 'practice' => 'Practice', 'format' => 'Format', 'engagement' => 'Engagement', 'status' => 'Status'] as $field => $label) { ?>
                            <div class="col-md-4"><?php echo render_input($field, $label, $value($field), 'text'); ?></div>
                        <?php } ?>
                    </div>
                    <div class="row">
                        <div class="col-md-6"><?php echo render_textarea('industries', 'Industries (one ID per line)', $lines('industries'), ['rows' => 6]); ?></div>
                        <div class="col-md-6"><?php echo render_textarea('business_needs', 'Business needs (one ID per line)', $lines('business_needs'), ['rows' => 6]); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group"><label for="visibility">Visibility</label>
                                <select class="form-control" name="visibility" id="visibility">
                                    <?php foreach (['catalog' => 'Public catalogue', 'detail_only' => 'Detail only', 'hidden' => 'Hidden'] as $key => $label) { ?>
                                        <option value="<?php echo $key; ?>" <?php echo ($offering['visibility'] ?? 'catalog') === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group"><label for="active">Status</label>
                                <select class="form-control" name="active" id="active">
                                    <option value="1" <?php echo !empty($offering['active']) ? 'selected' : ''; ?>>Active</option>
                                    <option value="0" <?php echo empty($offering['active']) ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div></div>
                <div class="panel_s"><div class="panel-body">
                    <h4>Pricing and related offerings</h4>
                    <p class="text-muted">Add or remove price components and relationships. Leave an unused row empty.</p>
                    <h5>Price components</h5>
                    <div id="price-rows">
                        <?php foreach (array_merge($prices, [null]) as $index => $price) { $price = is_array($price) ? $price : []; $priceLabel = $price['label'] ?? []; ?>
                            <div class="panel_s price-row"><div class="panel-body">
                                <div class="row">
                                    <?php foreach (['id' => 'Component ID', 'amount_mad' => 'Amount (MAD)', 'minimum_mad' => 'Minimum (MAD)', 'maximum_mad' => 'Maximum (MAD)', 'percentage' => 'Percentage', 'unit' => 'Unit ID'] as $field => $label) { ?>
                                        <div class="col-md-4"><div class="form-group"><label><?php echo $label; ?></label><input class="form-control" name="prices[<?php echo $index; ?>][<?php echo $field; ?>]" value="<?php echo html_escape((string) ($price[$field] ?? '')); ?>" type="<?php echo $field === 'id' || $field === 'unit' ? 'text' : 'number'; ?>" <?php echo $field !== 'id' && $field !== 'unit' ? 'step="any" min="0"' : ''; ?>></div></div>
                                    <?php } ?>
                                    <div class="col-md-4"><div class="form-group"><label>Billing</label><select class="form-control" name="prices[<?php echo $index; ?>][billing]"><?php foreach (['one_time', 'monthly', 'quarterly', 'annual', 'percentage'] as $billing) { ?><option value="<?php echo $billing; ?>" <?php echo ($price['billing'] ?? '') === $billing ? 'selected' : ''; ?>><?php echo ucwords(str_replace('_', ' ', $billing)); ?></option><?php } ?></select></div></div>
                                    <div class="col-md-4"><div class="form-group"><label>Label (English)</label><input class="form-control" name="prices[<?php echo $index; ?>][label_en]" value="<?php echo html_escape(is_array($priceLabel) ? (string) ($priceLabel['en'] ?? '') : ''); ?>"></div></div>
                                    <div class="col-md-8"><div class="form-group"><label>Calculation basis (JSON object, percentage pricing only)</label><input class="form-control" name="prices[<?php echo $index; ?>][basis_json]" value="<?php echo html_escape(isset($price['basis']) && is_array($price['basis']) ? json_encode($price['basis'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : ''); ?>" placeholder='{"field":"..."}'></div></div>
                                    <div class="col-md-12"><button class="btn btn-default remove-row" type="button">Remove price</button></div>
                                </div>
                            </div></div>
                        <?php } ?>
                    </div>
                    <button class="btn btn-default" type="button" id="add-price">Add price component</button>
                    <hr>
                    <h5>Relationships</h5>
                    <div id="relation-rows">
                        <?php foreach (array_merge($relations, [null]) as $index => $relation) { $relation = is_array($relation) ? $relation : []; ?>
                            <div class="panel_s relation-row"><div class="panel-body"><div class="row">
                                <div class="col-md-3"><div class="form-group"><label>Offering ID</label><input class="form-control" name="relations[<?php echo $index; ?>][offering_id]" value="<?php echo html_escape((string) ($relation['offering_id'] ?? '')); ?>" placeholder="related-offering"></div></div>
                                <div class="col-md-3"><div class="form-group"><label>Relationship</label><select class="form-control" name="relations[<?php echo $index; ?>][type]"><?php foreach (['complementary', 'extension', 'maintenance', 'follow_up', 'gateway'] as $type) { ?><option value="<?php echo $type; ?>" <?php echo ($relation['type'] ?? '') === $type ? 'selected' : ''; ?>><?php echo ucwords(str_replace('_', ' ', $type)); ?></option><?php } ?></select></div></div>
                                <div class="col-md-3"><div class="form-group"><label>Visibility</label><select class="form-control" name="relations[<?php echo $index; ?>][visibility]"><?php foreach (['catalog', 'detail_only', 'hidden'] as $visibility) { ?><option value="<?php echo $visibility; ?>" <?php echo ($relation['visibility'] ?? 'detail_only') === $visibility ? 'selected' : ''; ?>><?php echo ucwords(str_replace('_', ' ', $visibility)); ?></option><?php } ?></select></div></div>
                                <div class="col-md-2"><div class="form-group"><label>Optional</label><select class="form-control" name="relations[<?php echo $index; ?>][optional]"><option value="1" <?php echo ($relation['optional'] ?? true) ? 'selected' : ''; ?>>Yes</option><option value="0" <?php echo isset($relation['optional']) && !$relation['optional'] ? 'selected' : ''; ?>>No</option></select></div></div>
                                <div class="col-md-1"><button class="btn btn-default remove-row" type="button">×</button></div>
                            </div></div></div>
                        <?php } ?>
                    </div>
                    <button class="btn btn-default" type="button" id="add-relation">Add relationship</button>
                </div></div>
                <div class="panel_s"><div class="panel-body">
                    <h4>Media</h4>
                    <div class="row">
                        <div class="col-md-4"><?php echo render_input('image', 'Image path or URL', $value('image'), 'text'); ?></div>
                        <div class="col-md-4"><?php echo render_input('image_alt', 'Image alternative text', $value('image_alt'), 'text'); ?></div>
                        <div class="col-md-4"><?php echo render_input('image_source', 'Image source', $value('image_source'), 'text'); ?></div>
                    </div>
                    <button type="submit" class="btn btn-primary">Save draft</button>
                    <a class="btn btn-default" href="<?php echo admin_url('nm-offerings'); ?>">Cancel</a>
                </div></div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    function addRow(containerId, rowClass, buttonId) {
        var container = document.getElementById(containerId);
        var template = container.querySelector('.' + rowClass);
        var button = document.getElementById(buttonId);
        button.addEventListener('click', function () {
            var clone = template.cloneNode(true);
            clone.querySelectorAll('input').forEach(function (input) { input.value = ''; });
            clone.querySelectorAll('select').forEach(function (select) { select.selectedIndex = 0; });
            var index = container.querySelectorAll('.' + rowClass).length;
            clone.querySelectorAll('[name]').forEach(function (field) {
                field.name = field.name.replace(/\[\d+\]/, '[' + index + ']');
            });
            container.appendChild(clone);
        });
    }
    addRow('price-rows', 'price-row', 'add-price');
    addRow('relation-rows', 'relation-row', 'add-relation');
    document.addEventListener('click', function (event) {
        if (event.target.classList.contains('remove-row')) {
            var rows = event.target.closest('.price-row, .relation-row').parentElement;
            if (rows.children.length > 1) event.target.closest('.price-row, .relation-row').remove();
        }
    });
})();
</script>
<?php init_tail(); ?>
