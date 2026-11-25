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
        try {
            $store = $this->catalog->store();
            $draft = $store->current('draft');
            $published = $store->current('published');
        } catch (Throwable $error) {
            show_error(html_escape($error->getMessage()), 503, 'Offerings catalogue unavailable');
            return;
        }
        $source = $draft ?: $published;
        $catalogue = $source['catalogue'] ?? ['offerings' => []];
        $selectedId = (string) $this->input->get('id');
        $isNew = $this->input->get('new') === '1';
        $selected = null;
        foreach (($catalogue['offerings'] ?? []) as $offering) {
            if (($offering['id'] ?? null) === $selectedId) {
                $selected = $offering;
                break;
            }
        }
        if ($isNew) {
            $selected = [
                'id' => 'new-offering-' . bin2hex(random_bytes(3)),
                'type' => 'service', 'name' => ['en' => 'New offering'], 'description' => ['en' => ''],
                'industries' => [], 'business_needs' => [], 'prices' => [], 'relations' => [],
                'active' => true, 'visibility' => 'catalog',
            ];
            $selectedId = '';
        }
        $data = [
            'title' => 'Offerings catalogue',
            'offerings' => $catalogue['offerings'] ?? [],
            'selected_id' => $selectedId,
            'offering_json' => $selected === null ? '' : json_encode($selected, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'is_new' => $isNew,
            'draft_revision' => $draft['revision'] ?? null,
            'published_revision' => $published['revision'] ?? null,
            'offering_count' => count($catalogue['offerings'] ?? []),
            'can_edit' => $this->can('edit'),
            'can_publish' => $this->can('publish'),
        ];
        $this->load->view('nm_offerings/index', $data);
    }

    public function save()
    {
        if (!$this->can('edit')) return $this->deny();
        $json = $this->input->post('offering_json', false);
        $offering = is_string($json) ? json_decode($json, true) : null;
        if (!is_array($offering) || !is_string($offering['id'] ?? null)) {
            set_alert('danger', 'Enter one valid offering JSON object with a stable ID.');
            redirect(admin_url('nm_offerings'));
            return;
        }
        $originalId = trim((string) $this->input->post('original_id'));
        try {
            $revision = $this->catalog->saveOffering($offering, $originalId, get_staff_user_id());
            log_activity('Offerings catalogue draft saved [Revision: ' . $revision . ']');
            set_alert('success', 'Offering saved in catalogue draft.');
            redirect(admin_url('nm_offerings?id=' . rawurlencode($offering['id'])));
        } catch (Throwable $error) {
            set_alert('danger', html_escape($error->getMessage()));
            redirect(admin_url('nm_offerings?id=' . rawurlencode($originalId) . ($originalId === '' ? '&new=1' : '')));
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
        redirect(admin_url('nm_offerings'));
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
