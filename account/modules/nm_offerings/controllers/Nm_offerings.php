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
        $data = [
            'title' => 'Offerings catalogue',
            'catalogue_json' => json_encode($catalogue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
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
        $catalogue = $this->postedCatalogue();
        if ($catalogue === null) return;
        try {
            $revision = $this->catalog->saveDraft($catalogue, get_staff_user_id());
            log_activity('Offerings catalogue draft saved [Revision: ' . $revision . ']');
            set_alert('success', 'Catalogue draft saved.');
        } catch (Throwable $error) {
            set_alert('danger', html_escape($error->getMessage()));
        }
        redirect(admin_url('nm_offerings'));
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

    private function postedCatalogue()
    {
        $json = $this->input->post('catalogue_json', false);
        $catalogue = is_string($json) ? json_decode($json, true) : null;
        if (!is_array($catalogue)) {
            set_alert('danger', 'The catalogue must be valid JSON.');
            redirect(admin_url('nm_offerings'));
            return null;
        }
        return $catalogue;
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
