<?php
require_once __DIR__ . '/../models/SecurityModel.php';

class SecurityController
{
    private $model;

    public function __construct()
    {
        $this->model = new SecurityModel();
    }

    public function getAllSections()
    {
        return $this->model->getAll();
    }

    /** Public API payload — active sections only. */
    public function getSectionsApi()
    {
        try {
            return [
                'success' => true,
                'data'    => $this->model->getActive(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    public function addSection($data)
    {
        try {
            $heading = trim($data['heading'] ?? '');
            if ($heading === '') {
                throw new Exception('Heading is required');
            }

            $ok = $this->model->create([
                'section_key' => $this->model->makeUniqueKey($heading),
                'heading'     => $heading,
                'content'     => $data['content'] ?? '',
                'sort_order'  => (int) ($data['sort_order'] ?? 0),
                'status'      => ($data['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
            ]);

            return [
                'success' => $ok,
                'message' => $ok ? 'Section added.' : 'Could not add the section.',
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateSection($id, $data)
    {
        try {
            $heading = trim($data['heading'] ?? '');
            if ($heading === '') {
                throw new Exception('Heading is required');
            }

            $ok = $this->model->update($id, [
                'heading'    => $heading,
                // Rich text from CKEditor — stored as HTML, deliberately not stripped.
                'content'    => $data['content'] ?? '',
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'status'     => ($data['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
            ]);

            return [
                'success' => $ok,
                'message' => $ok ? 'Section updated.' : 'Could not update the section.',
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deleteSection($id)
    {
        $ok = $this->model->delete($id);

        return [
            'success' => $ok,
            'message' => $ok ? 'Section deleted.' : 'Could not delete the section.',
        ];
    }
}
