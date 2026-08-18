<?php
require_once(__DIR__ . '/../config/database.php');

/**
 * Sections of the storefront Security page.
 *
 * Mirrors PolicyModel: a stable text key per section so the frontend and any
 * deep links do not depend on auto-increment ids.
 */
class SecurityModel
{
    private $db;
    private $table = 'security_sections';

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /** All sections, admin ordering. */
    public function getAll()
    {
        $result = $this->db->query(
            "SELECT * FROM {$this->table} ORDER BY sort_order ASC, id ASC"
        );

        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /** Active sections only — what the public API serves. */
    public function getActive()
    {
        $result = $this->db->query(
            "SELECT id, section_key, heading, content, sort_order
             FROM {$this->table}
             WHERE status = 'active'
             ORDER BY sort_order ASC, id ASC"
        );

        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    public function getByKey($key)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE section_key = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('s', $key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} (section_key, heading, content, sort_order, status)
             VALUES (?, ?, ?, ?, ?)"
        );
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            'sssis',
            $data['section_key'],
            $data['heading'],
            $data['content'],
            $data['sort_order'],
            $data['status']
        );
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET heading = ?, content = ?, sort_order = ?, status = ?
             WHERE id = ?"
        );
        if (!$stmt) {
            return false;
        }

        $id = (int) $id;
        $stmt->bind_param(
            'ssisi',
            $data['heading'],
            $data['content'],
            $data['sort_order'],
            $data['status'],
            $id
        );
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        if (!$stmt) {
            return false;
        }

        $id = (int) $id;
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    /**
     * Turn a heading into a unique key. Only used when creating a section,
     * since changing a key later would break anything linking to it.
     */
    public function makeUniqueKey($heading)
    {
        $base = strtolower(trim($heading));
        $base = preg_replace('/[^a-z0-9]+/', '-', $base);
        $base = trim($base, '-');
        $base = $base === '' ? 'section' : substr($base, 0, 50);

        $key = $base;
        $n = 2;
        while ($this->getByKey($key) !== null) {
            $key = substr($base, 0, 50 - strlen((string) $n) - 1) . '-' . $n;
            $n++;
        }

        return $key;
    }
}
