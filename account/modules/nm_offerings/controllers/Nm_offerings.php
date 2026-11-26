<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Nm_offerings extends AdminController
{
    private $catalog;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('nm_offerings/nm_offerings');
        $this->load->library('nm_offerings/nm_offerings_catalog');
        $this->catalog = $this->nm_offerings_catalog;
    }

    public function index()
    {
        if (!$this->can('view')) return $this->deny();
        // Redirect links from the old query-string editor to the descriptive route.
        $legacyId = $this->input->get('id', true);
        if (is_string($legacyId) && $legacyId !== '' && preg_match('/^[a-z0-9][a-z0-9-]*$/', $legacyId)) {
            redirect(admin_url('nm-offerings/edit/' . rawurlencode($legacyId)));
            return;
        }
        if ($this->input->get('new', true) === '1') {
            redirect(admin_url('nm-offerings/create'));
            return;
        }
        try {
            $data = $this->catalogueData();
        } catch (Throwable $error) {
            show_error(html_escape($error->getMessage()), 503, 'Offerings catalogue unavailable');
            return;
        }
        $data['title'] = 'Offerings catalogue';
        $data['can_edit'] = $this->can('edit');
        $data['can_publish'] = $this->can('publish');
        $this->load->view('nm_offerings/index', $data);
    }

    public function create()
    {
        if (!$this->can('edit')) return $this->deny();
        try {
            $data = $this->catalogueData();
        } catch (Throwable $error) {
            show_error(html_escape($error->getMessage()), 503, 'Offerings catalogue unavailable');
            return;
        }
        $data['title'] = 'Create offering';
        $data['selected'] = [
            'id' => '', 'type' => 'service', 'name' => ['en' => ''], 'description' => ['en' => ''],
            'summary' => ['en' => ''], 'industries' => [], 'business_needs' => [], 'prices' => [], 'relations' => [],
            'active' => true, 'visibility' => 'catalog',
        ];
        $data['is_new'] = true;
        $data['can_edit'] = true;
        $this->load->view('nm_offerings/edit', $data);
    }

    public function edit($id = '')
    {
        if (!$this->can('edit')) return $this->deny();
        if (!is_string($id) || !preg_match('/^[a-z0-9][a-z0-9-]*$/', $id)) show_404();
        try {
            $data = $this->catalogueData();
        } catch (Throwable $error) {
            show_error(html_escape($error->getMessage()), 503, 'Offerings catalogue unavailable');
            return;
        }
        foreach ($data['offerings'] as $offering) {
            if (($offering['id'] ?? null) === $id) {
                $data['selected'] = $offering;
                break;
            }
        }
        if (!isset($data['selected'])) show_404();
        $data['title'] = 'Edit offering: ' . $id;
        $data['is_new'] = false;
        $data['can_edit'] = true;
        $this->load->view('nm_offerings/edit', $data);
    }

    public function save()
    {
        if (!$this->can('edit')) return $this->deny();
        $originalId = trim((string) $this->input->post('original_id'));
        try {
            $offering = $this->offeringFromPost($originalId);
            $revision = $this->catalog->saveOffering($offering, $originalId, get_staff_user_id());
            log_activity('Offerings catalogue draft saved [Revision: ' . $revision . ']');
            set_alert('success', 'Offering saved in catalogue draft.');
            redirect(admin_url('nm-offerings/edit/' . rawurlencode($offering['id'])));
        } catch (Throwable $error) {
            set_alert('danger', html_escape($error->getMessage()));
            redirect($originalId === '' ? admin_url('nm-offerings/create') : admin_url('nm-offerings/edit/' . rawurlencode($originalId)));
        }
    }

    public function publish()
    {
        if (!$this->can('publish')) return $this->deny();
        try {
            $revision = $this->catalog->publish(get_staff_user_id());
            log_activity('Offerings catalogue published [Revision: ' . $revision . ']');
            set_alert('success', 'Catalogue revision ' . $revision . ' is live.');
        } catch (Throwable $error) {
            set_alert('danger', html_escape($error->getMessage()));
        }
        redirect(admin_url('nm-offerings'));
    }

    private function catalogueData()
    {
        $store = $this->catalog->store();
        $draft = $store->current('draft');
        $published = $store->current('published');
        $source = $draft ?: $published;
        $offerings = $source['catalogue']['offerings'] ?? [];
        return [
            'offerings' => $offerings,
            'draft_revision' => $draft['revision'] ?? null,
            'published_revision' => $published['revision'] ?? null,
            'offering_count' => count($offerings),
            'can_publish' => $this->can('publish'),
            'source_label' => $draft ? 'Draft' : 'Published snapshot',
            'database_path' => $this->catalog->databasePath(),
        ];
    }

    private function offeringFromPost($originalId)
    {
        $offering = [];
        if ($originalId !== '') {
            foreach ($this->catalog->workingCatalogue()['catalogue']['offerings'] ?? [] as $existing) {
                if (($existing['id'] ?? null) === $originalId) { $offering = $existing; break; }
            }
            if ($offering === []) throw new RuntimeException('This offering is no longer available in the current draft.');
        }
        $id = trim((string) $this->input->post('id'));
        if ($originalId !== '' && $id !== $originalId) throw new InvalidArgumentException('An offering ID cannot be changed after creation.');
        $type = trim((string) $this->input->post('type'));
        if (!in_array($type, ['mandate', 'software', 'playbook', 'service'], true)) throw new InvalidArgumentException('Choose a valid offering type.');
        $offering['id'] = $id;
        $offering['type'] = $type;
        foreach (['name', 'description', 'summary'] as $field) {
            $english = (string) $this->input->post($field . '_en', false);
            $translations = is_array($offering[$field] ?? null) ? $offering[$field] : [];
            if ($field !== 'summary' || $english !== '') $translations['en'] = $english;
            $offering[$field] = $translations;
        }
        foreach (['category', 'practice', 'format', 'engagement', 'status', 'image', 'image_alt', 'image_source'] as $field) {
            $value = trim((string) $this->input->post($field, false));
            if ($value === '') unset($offering[$field]); else $offering[$field] = $value;
        }
        foreach (['industries', 'business_needs'] as $field) {
            $value = (string) $this->input->post($field, false);
            $offering[$field] = array_values(array_unique(array_filter(array_map('trim', preg_split('/[\\r\\n,]+/', $value) ?: []), 'strlen')));
        }
        $prices = $this->input->post('prices');
        $offering['prices'] = [];
        foreach (is_array($prices) ? $prices : [] as $price) {
            if (!is_array($price)) throw new InvalidArgumentException('Each price component must be a form row.');
            $id = trim((string) ($price['id'] ?? ''));
            if ($id === '') continue;
            $component = ['id' => $id, 'billing' => (string) ($price['billing'] ?? '')];
            foreach (['amount_mad', 'minimum_mad', 'maximum_mad', 'percentage'] as $field) {
                $value = trim((string) ($price[$field] ?? ''));
                if ($value !== '') {
                    if (!is_numeric($value)) throw new InvalidArgumentException('Price amounts must be numeric.');
                    $component[$field] = (float) $value;
                }
            }
            $unit = trim((string) ($price['unit'] ?? ''));
            if ($unit !== '') $component['unit'] = $unit;
            $label = trim((string) ($price['label_en'] ?? ''));
            if ($label !== '') $component['label'] = ['en' => $label];
            $basisJson = trim((string) ($price['basis_json'] ?? ''));
            if ($basisJson !== '') {
                $basis = json_decode($basisJson, true);
                if (!is_array($basis) || json_last_error() !== JSON_ERROR_NONE) throw new InvalidArgumentException('Price basis must be a valid JSON object.');
                $component['basis'] = $basis;
            } elseif (($component['billing'] ?? '') === 'percentage') {
                throw new InvalidArgumentException('Percentage prices require a calculation basis.');
            }
            $offering['prices'][] = $component;
        }
        $relations = $this->input->post('relations');
        $offering['relations'] = [];
        foreach (is_array($relations) ? $relations : [] as $relation) {
            if (!is_array($relation)) throw new InvalidArgumentException('Each relationship must be a form row.');
            $target = trim((string) ($relation['offering_id'] ?? ''));
            if ($target === '') continue;
            $offering['relations'][] = [
                'offering_id' => $target,
                'type' => (string) ($relation['type'] ?? ''),
                'optional' => ($relation['optional'] ?? '') === '1',
                'visibility' => (string) ($relation['visibility'] ?? 'detail_only'),
            ];
        }
        $offering['active'] = $this->input->post('active') === '1';
        $visibility = (string) $this->input->post('visibility');
        if (!in_array($visibility, ['catalog', 'detail_only', 'hidden'], true)) throw new InvalidArgumentException('Choose a valid visibility.');
        $offering['visibility'] = $visibility;
        return $offering;
    }

    private function can($capability)
    {
        return has_permission(NM_OFFERINGS_MODULE_NAME, '', $capability);
    }

    private function deny()
    {
        access_denied(NM_OFFERINGS_MODULE_NAME);
    }
}
