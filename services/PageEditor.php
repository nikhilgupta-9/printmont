<?php
/**
 * The engine behind the page editor.
 *
 * Every website page keeps its content in a different shape: page_sections
 * has page_key/section_type/title/content/extra, about_us calls the same
 * things section_title and section_content, help_articles has its own
 * columns again, and some tables mark a row live with is_active = 1 while
 * others use status = 'active'.
 *
 * This class hides all of that. A schema describes a page in editor terms
 * and names a `source` — a table plus a map from the four canonical fields
 * (title, content, extra, image_path) to that table's real columns. Rows
 * come back normalised, so the editor UI and the schema field definitions
 * never change when a new table is added.
 *
 * A source may be declared per page and overridden per part, which is how
 * one screen can edit three tables at once (Help Center) or one table with
 * several section types (Become a Seller).
 */
class PageEditor
{
    /** page_sections, the shape most pages use. */
    const DEFAULT_SOURCE = [
        'table'   => 'page_sections',
        'key'     => 'page_key',
        'columns' => [
            'id'         => 'id',
            'title'      => 'title',
            'content'    => 'content',
            'extra'      => 'extra',
            'image_path' => 'image_path',
            'type'       => 'section_type',
            'order'      => 'display_order',
            'active'     => 'is_active',
        ],
        'active_on'  => 1,
        'active_off' => 0,
    ];

    private $db;
    private $schemas;

    public function __construct($db, array $schemas)
    {
        $this->db = $db;
        $this->schemas = $schemas;
    }

    public function page(string $key): ?array
    {
        return $this->schemas[$key] ?? null;
    }

    /** The part a request names, or null if the address is not real. */
    public function partAt(array $schema, string $bandKey, int $index): ?array
    {
        foreach ($schema['bands'] as $band) {
            if ($band['key'] === $bandKey) {
                return $band['parts'][$index] ?? null;
            }
        }
        return null;
    }

    /**
     * Where one part's rows live. Page source over the default, part source
     * over that, so a schema only states what differs.
     */
    public function source(array $schema, array $part, string $pageKey): array
    {
        $source = self::DEFAULT_SOURCE;

        foreach ([$schema['source'] ?? [], $part['source'] ?? []] as $override) {
            $columns = array_merge($source['columns'], $override['columns'] ?? []);
            $source = array_merge($source, $override);
            $source['columns'] = $columns;
        }

        // Only page_sections-shaped tables hold several pages at once.
        if (!array_key_exists('key_value', $source)) {
            $source['key_value'] = $pageKey;
        }

        return $source;
    }

    /** Bucket name the browser uses for one part's rows. */
    public static function bucket(string $bandKey, int $index): string
    {
        return $bandKey . '.' . $index;
    }

    /** Every row on the page, grouped by the part that owns it. */
    public function rows(array $schema, string $pageKey): array
    {
        $out = [];
        foreach ($schema['bands'] as $band) {
            foreach ($band['parts'] as $index => $part) {
                $out[self::bucket($band['key'], $index)] =
                    $this->fetchPart($schema, $band, $part, $pageKey);
            }
        }
        return $out;
    }

    /**
     * Rows belonging to one part, normalised to the canonical field names.
     * A list never swallows a row that a pinned single already owns.
     */
    public function fetchPart(array $schema, array $band, array $part, string $pageKey): array
    {
        $source = $this->source($schema, $part, $pageKey);
        $col = $source['columns'];

        $select = [];
        foreach (['id', 'title', 'content', 'extra', 'image_path', 'order', 'active'] as $field) {
            $name = $field === 'order' ? 'display_order' : ($field === 'active' ? 'is_active' : $field);
            $select[] = $col[$field]
                ? '`' . $col[$field] . '` AS `' . $name . '`'
                : "'' AS `" . $name . '`';
        }

        $where = [];
        $types = '';
        $args = [];

        if (!empty($source['key'])) {
            $where[] = '`' . $source['key'] . '` = ?';
            $types .= 's';
            $args[] = $source['key_value'];
        }
        if (!empty($col['type']) && !empty($part['type'])) {
            $where[] = '`' . $col['type'] . '` = ?';
            $types .= 's';
            $args[] = $part['type'];
        }
        if (!empty($part['match']['extra']) && !empty($col['extra'])) {
            $where[] = '`' . $col['extra'] . '` = ?';
            $types .= 's';
            $args[] = $part['match']['extra'];
        } else {
            foreach ($this->claimedExtras($band, $part) as $claimed) {
                $where[] = '`' . $col['extra'] . '` <> ?';
                $types .= 's';
                $args[] = $claimed;
            }
        }

        $sql = 'SELECT ' . implode(', ', $select) . ' FROM `' . $source['table'] . '`'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY ' . ($col['order'] ? '`' . $col['order'] . '` ASC, ' : '') . '`' . $col['id'] . '` ASC';

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if ($args) {
            $stmt->bind_param($types, ...$args);
        }
        $stmt->execute();

        $rows = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int) $row['id'];
            $row['display_order'] = (int) $row['display_order'];
            $row['is_active'] = $this->isOn($source, $row['is_active']) ? 1 : 0;
            $rows[] = $row;
        }
        $stmt->close();

        return $rows;
    }

    /**
     * Extra values that pinned single parts in the same band already own,
     * so an unpinned list of the same type leaves them alone.
     */
    private function claimedExtras(array $band, array $part): array
    {
        if (empty($part['type'])) {
            return [];
        }

        $claimed = [];
        foreach ($band['parts'] as $sibling) {
            if (($sibling['type'] ?? null) === $part['type'] && !empty($sibling['match']['extra'])) {
                $claimed[] = $sibling['match']['extra'];
            }
        }
        return $claimed;
    }

    private function isOn(array $source, $value): bool
    {
        return (string) $value === (string) $source['active_on'];
    }

    /** One row by id, scoped to the part that claims to own it. */
    public function row(array $schema, array $part, string $pageKey, int $id): ?array
    {
        $source = $this->source($schema, $part, $pageKey);
        $col = $source['columns'];

        $where = ['`' . $col['id'] . '` = ?'];
        $types = 'i';
        $args = [$id];

        if (!empty($source['key'])) {
            $where[] = '`' . $source['key'] . '` = ?';
            $types .= 's';
            $args[] = $source['key_value'];
        }

        $stmt = $this->db->prepare(
            'SELECT * FROM `' . $source['table'] . '` WHERE ' . implode(' AND ', $where) . ' LIMIT 1'
        );
        $stmt->bind_param($types, ...$args);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    /** The same row, renamed to the canonical fields the browser knows. */
    public function normalise(array $source, array $row): array
    {
        $col = $source['columns'];
        return [
            'id'            => (int) $row[$col['id']],
            'title'         => $col['title'] ? (string) $row[$col['title']] : '',
            'content'       => $col['content'] ? (string) $row[$col['content']] : '',
            'extra'         => $col['extra'] ? (string) $row[$col['extra']] : '',
            'image_path'    => $col['image_path'] ? (string) $row[$col['image_path']] : '',
            'display_order' => $col['order'] ? (int) $row[$col['order']] : 0,
            'is_active'     => $this->isOn($source, $row[$col['active']] ?? $source['active_on']) ? 1 : 0,
        ];
    }

    /**
     * Insert or update. `$values` is keyed by canonical field name; anything
     * the source cannot store is dropped rather than guessed at.
     */
    public function save(array $schema, array $part, string $pageKey, int $id, array $values, bool $active): int
    {
        $source = $this->source($schema, $part, $pageKey);
        $col = $source['columns'];

        $set = [];
        $types = '';
        $args = [];

        foreach (['title', 'content', 'extra', 'image_path'] as $field) {
            if (!$col[$field] || !array_key_exists($field, $values)) {
                continue;
            }
            $set[$col[$field]] = 's';
            $types .= 's';
            $args[] = (string) $values[$field];
        }

        if ($col['active']) {
            $set[$col['active']] = 's';
            $types .= 's';
            $args[] = (string) ($active ? $source['active_on'] : $source['active_off']);
        }

        if ($id > 0) {
            $sql = 'UPDATE `' . $source['table'] . '` SET '
                . implode(', ', array_map(fn($c) => '`' . $c . '` = ?', array_keys($set)))
                . ' WHERE `' . $col['id'] . '` = ?';
            $types .= 'i';
            $args[] = $id;

            if (!empty($source['key'])) {
                $sql .= ' AND `' . $source['key'] . '` = ?';
                $types .= 's';
                $args[] = $source['key_value'];
            }

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$args);
            if (!$stmt->execute()) {
                throw new Exception('The section could not be saved.');
            }
            $stmt->close();
            return $id;
        }

        // New rows carry the part's own type and key, and go to the end.
        $columns = array_keys($set);

        if (!empty($col['type']) && !empty($part['type'])) {
            $columns[] = $col['type'];
            $types .= 's';
            $args[] = $part['type'];
        }
        if (!empty($source['key'])) {
            $columns[] = $source['key'];
            $types .= 's';
            $args[] = $source['key_value'];
        }
        if (!empty($col['order'])) {
            $columns[] = $col['order'];
            $types .= 'i';
            $args[] = $this->nextOrder($source, $part);
        }
        foreach ($source['defaults'] ?? [] as $column => $value) {
            $columns[] = $column;
            $types .= 's';
            $args[] = (string) $value;
        }

        // Columns the table insists on but the editor should not ask for —
        // a slug, for instance, which is just the title in another form.
        foreach ($source['derived'] ?? [] as $column => $from) {
            $columns[] = $column;
            $types .= 's';
            $args[] = self::slug($values[$from] ?? '');
        }

        $stmt = $this->db->prepare(
            'INSERT INTO `' . $source['table'] . '` (`' . implode('`, `', $columns) . '`) VALUES ('
            . rtrim(str_repeat('?, ', count($columns)), ', ') . ')'
        );
        $stmt->bind_param($types, ...$args);
        if (!$stmt->execute()) {
            throw new Exception('The section could not be added.');
        }
        $newId = $this->db->insert_id;
        $stmt->close();

        return $newId;
    }

    /** "Getting Started" -> "getting-started", with the clock as a tiebreak. */
    public static function slug(string $text): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $text), '-'));
        return $slug !== '' ? substr($slug, 0, 180) : 'item-' . time();
    }

    private function nextOrder(array $source, array $part): int
    {
        $col = $source['columns'];
        $where = [];
        $types = '';
        $args = [];

        if (!empty($source['key'])) {
            $where[] = '`' . $source['key'] . '` = ?';
            $types .= 's';
            $args[] = $source['key_value'];
        }
        if (!empty($col['type']) && !empty($part['type'])) {
            $where[] = '`' . $col['type'] . '` = ?';
            $types .= 's';
            $args[] = $part['type'];
        }

        $stmt = $this->db->prepare(
            'SELECT COALESCE(MAX(`' . $col['order'] . '`), 0) + 1 AS n FROM `' . $source['table'] . '`'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        );
        if ($args) {
            $stmt->bind_param($types, ...$args);
        }
        $stmt->execute();
        $next = (int) ($stmt->get_result()->fetch_assoc()['n'] ?? 1);
        $stmt->close();

        return $next;
    }

    public function delete(array $schema, array $part, string $pageKey, int $id): void
    {
        $source = $this->source($schema, $part, $pageKey);
        $col = $source['columns'];

        $sql = 'DELETE FROM `' . $source['table'] . '` WHERE `' . $col['id'] . '` = ?';
        $types = 'i';
        $args = [$id];

        if (!empty($source['key'])) {
            $sql .= ' AND `' . $source['key'] . '` = ?';
            $types .= 's';
            $args[] = $source['key_value'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$args);
        if (!$stmt->execute()) {
            throw new Exception('The section could not be deleted.');
        }
        $stmt->close();
    }

    public function toggle(array $schema, array $part, string $pageKey, int $id, bool $active): void
    {
        $source = $this->source($schema, $part, $pageKey);
        $col = $source['columns'];

        if (!$col['active']) {
            throw new Exception('This section cannot be hidden.');
        }

        $sql = 'UPDATE `' . $source['table'] . '` SET `' . $col['active'] . '` = ? WHERE `' . $col['id'] . '` = ?';
        $types = 'si';
        $args = [(string) ($active ? $source['active_on'] : $source['active_off']), $id];

        if (!empty($source['key'])) {
            $sql .= ' AND `' . $source['key'] . '` = ?';
            $types .= 's';
            $args[] = $source['key_value'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$args);
        $stmt->execute();
        $stmt->close();
    }

    public function reorder(array $schema, array $part, string $pageKey, array $ids): void
    {
        $source = $this->source($schema, $part, $pageKey);
        $col = $source['columns'];

        if (!$col['order']) {
            throw new Exception('These sections cannot be reordered.');
        }

        $sql = 'UPDATE `' . $source['table'] . '` SET `' . $col['order'] . '` = ? WHERE `' . $col['id'] . '` = ?';
        if (!empty($source['key'])) {
            $sql .= ' AND `' . $source['key'] . '` = ?';
        }

        $stmt = $this->db->prepare($sql);
        $position = 1;

        foreach ($ids as $rawId) {
            $id = (int) $rawId;
            if ($id <= 0) {
                continue;
            }
            if (!empty($source['key'])) {
                $stmt->bind_param('iis', $position, $id, $source['key_value']);
            } else {
                $stmt->bind_param('ii', $position, $id);
            }
            $stmt->execute();
            $position++;
        }

        $stmt->close();
    }

    /** Options for a select field, read from whatever table it names. */
    public function options(array $field): array
    {
        $spec = $field['options'] ?? null;
        if (!$spec || empty($spec['table'])) {
            return [];
        }

        $sql = 'SELECT `' . $spec['value'] . '` AS v, `' . $spec['label'] . '` AS l FROM `' . $spec['table'] . '`'
            . (!empty($spec['where']) ? ' WHERE ' . $spec['where'] : '')
            . ' ORDER BY `' . ($spec['order'] ?? $spec['label']) . '` ASC';

        $out = [];
        if ($result = $this->db->query($sql)) {
            while ($row = $result->fetch_assoc()) {
                $out[] = ['value' => (string) $row['v'], 'label' => (string) $row['l']];
            }
        }
        return $out;
    }

    /** Fill every select field on the page with its current options. */
    public function withOptions(array $schema): array
    {
        foreach ($schema['bands'] as $b => $band) {
            foreach ($band['parts'] as $p => $part) {
                foreach ($part['fields'] as $f => $field) {
                    if (($field['input'] ?? '') === 'select' && !empty($field['options']['table'])) {
                        $schema['bands'][$b]['parts'][$p]['fields'][$f]['choices'] = $this->options($field);
                    }
                }
            }
        }
        return $schema;
    }
}
