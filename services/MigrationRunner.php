<?php
require_once(__DIR__ . '/../config/database.php');

/**
 * Applies pending .sql files from database/migrations to the current database
 * and records what ran in `schema_migrations`.
 *
 * Files are applied in filename order, so keep the NNN_ prefix.
 *
 * IMPORTANT: MySQL/MariaDB implicitly commit on DDL, so a migration that fails
 * halfway cannot be rolled back. The runner stops at the first error and reports
 * the failing statement; take a backup before running on production.
 */
class MigrationRunner
{
    /**
     * MySQL errors that mean "this statement's change is already in place".
     *
     * A migration is a description of the desired schema, so re-running one
     * against a database that already has the column/table/index is a no-op,
     * not a failure. Without this, adopting the runner on a live database
     * where someone had already added a column by hand meant the migration
     * aborted and every later statement in the file was skipped.
     *
     * Deliberately excludes 1062 (duplicate entry): that is data, not schema,
     * and silently swallowing it would hide real problems. Seed rows should
     * use INSERT IGNORE or ON DUPLICATE KEY UPDATE instead.
     */
    private const ALREADY_APPLIED_ERRNOS = [
        1007, // database exists
        1050, // table already exists
        1060, // duplicate column name
        1061, // duplicate key name
        1091, // can't DROP; column/key does not exist (already dropped)
        1826, // duplicate foreign key constraint name
    ];

    private $conn;
    private string $dir;

    public function __construct($conn = null)
    {
        if ($conn instanceof mysqli) {
            $this->conn = $conn;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }

        $this->dir = __DIR__ . '/../database/migrations';
    }

    public function ensureTable(): void
    {
        $this->conn->query(
            "CREATE TABLE IF NOT EXISTS `schema_migrations` (
                `id`           int(11)      NOT NULL AUTO_INCREMENT,
                `migration`    varchar(255) NOT NULL,
                `checksum`     char(40)     NOT NULL COMMENT 'sha1 of the .sql file when applied',
                `statements`   int(11)      NOT NULL DEFAULT 0,
                `execution_ms` int(11)      NOT NULL DEFAULT 0,
                `baselined`    tinyint(1)   NOT NULL DEFAULT 0 COMMENT '1 = marked applied without running',
                `applied_by`   varchar(120) DEFAULT NULL,
                `applied_at`   timestamp    NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_migration` (`migration`)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }

    /** @return array<string, array> keyed by migration filename */
    public function getApplied(): array
    {
        $this->ensureTable();

        $applied = [];
        $result = $this->conn->query("SELECT * FROM `schema_migrations` ORDER BY `migration` ASC");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $applied[$row['migration']] = $row;
            }
        }

        return $applied;
    }

    /** @return string[] migration filenames, in apply order */
    public function getAvailable(): array
    {
        if (!is_dir($this->dir)) {
            return [];
        }

        $files = glob($this->dir . '/*.sql') ?: [];
        $names = array_map('basename', $files);
        sort($names, SORT_NATURAL);

        return $names;
    }

    /**
     * Combined view for the admin screen.
     * Each row: name, applied, applied_at, baselined, checksum_changed, size, statements.
     */
    public function getStatus(): array
    {
        $applied = $this->getApplied();
        $rows = [];

        foreach ($this->getAvailable() as $name) {
            $path = $this->dir . '/' . $name;
            $sql = (string) @file_get_contents($path);
            $checksum = sha1($sql);
            $record = $applied[$name] ?? null;

            $rows[] = [
                'name'             => $name,
                'applied'          => $record !== null,
                'applied_at'       => $record['applied_at'] ?? null,
                'applied_by'       => $record['applied_by'] ?? null,
                'baselined'        => $record ? (bool) $record['baselined'] : false,
                'checksum_changed' => $record ? ($record['checksum'] !== $checksum) : false,
                'size'             => strlen($sql),
                'statements'       => count($this->splitStatements($sql)),
            ];
        }

        // A migration recorded in the DB whose file is gone still matters — surface it.
        foreach ($applied as $name => $record) {
            if (!in_array($name, array_column($rows, 'name'), true)) {
                $rows[] = [
                    'name'             => $name,
                    'applied'          => true,
                    'applied_at'       => $record['applied_at'],
                    'applied_by'       => $record['applied_by'],
                    'baselined'        => (bool) $record['baselined'],
                    'checksum_changed' => false,
                    'size'             => null,
                    'statements'       => (int) $record['statements'],
                    'missing_file'     => true,
                ];
            }
        }

        return $rows;
    }

    public function getPending(): array
    {
        $applied = $this->getApplied();

        return array_values(array_filter(
            $this->getAvailable(),
            fn($name) => !isset($applied[$name])
        ));
    }

    /**
     * Run every pending migration in order. Stops at the first failure.
     * @return array{ok: bool, log: array, error: ?string}
     */
    public function runPending(?string $appliedBy = null): array
    {
        $this->ensureTable();

        $log = [];
        foreach ($this->getPending() as $name) {
            $result = $this->runOne($name, $appliedBy);
            $log[] = $result;

            if (!$result['ok']) {
                return ['ok' => false, 'log' => $log, 'error' => $result['error']];
            }
        }

        return ['ok' => true, 'log' => $log, 'error' => null];
    }

    public function runOne(string $name, ?string $appliedBy = null): array
    {
        $path = $this->dir . '/' . basename($name);

        if (!is_readable($path)) {
            return ['migration' => $name, 'ok' => false, 'statements' => 0, 'ms' => 0,
                    'error' => 'File not found or unreadable'];
        }

        $sql = (string) file_get_contents($path);
        $statements = $this->splitStatements($sql);

        $start = microtime(true);
        $executed = 0;
        $skipped = 0;

        foreach ($statements as $index => $statement) {
            $result = $this->conn->query($statement);

            if ($result === false) {
                // Already in place (column/table/index exists)? Treat as done.
                if (in_array($this->conn->errno, self::ALREADY_APPLIED_ERRNOS, true)) {
                    $skipped++;
                    continue;
                }

                return [
                    'migration'  => $name,
                    'ok'         => false,
                    'statements' => $executed,
                    'skipped'    => $skipped,
                    'ms'         => (int) round((microtime(true) - $start) * 1000),
                    'error'      => sprintf(
                        'Statement %d of %d failed: %s — %s',
                        $index + 1,
                        count($statements),
                        $this->conn->error,
                        $this->summarize($statement)
                    ),
                ];
            }

            // Free any result set (e.g. a SELECT inside a migration) so the next
            // statement does not hit "commands out of sync".
            if ($result instanceof mysqli_result) {
                $result->free();
            }

            $executed++;
        }

        $ms = (int) round((microtime(true) - $start) * 1000);

        // Nothing left to do means the schema already matched the file — record
        // it as baselined so the admin screen shows "auto applied" rather than
        // implying work was performed.
        $alreadyPresent = ($executed === 0 && $skipped > 0);
        $this->record($name, sha1($sql), $executed, $ms, $alreadyPresent, $appliedBy);

        return [
            'migration'  => $name,
            'ok'         => true,
            'statements' => $executed,
            'skipped'    => $skipped,
            'ms'         => $ms,
            'error'      => null,
        ];
    }

    /**
     * Record migrations as applied WITHOUT running them. Use when adopting this
     * runner on a database where the migrations were already applied by hand —
     * re-running them would repeat destructive steps such as DROP TABLE.
     */
    public function baseline(array $names, ?string $appliedBy = null): int
    {
        $this->ensureTable();

        $count = 0;
        foreach ($names as $name) {
            $path = $this->dir . '/' . basename($name);
            if (!is_readable($path)) {
                continue;
            }

            $sql = (string) file_get_contents($path);
            $this->record($name, sha1($sql), count($this->splitStatements($sql)), 0, true, $appliedBy);
            $count++;
        }

        return $count;
    }

    private function record(string $name, string $checksum, int $statements, int $ms, bool $baselined, ?string $appliedBy): void
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO `schema_migrations` (migration, checksum, statements, execution_ms, baselined, applied_by)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE checksum = VALUES(checksum), statements = VALUES(statements),
                                     execution_ms = VALUES(execution_ms), baselined = VALUES(baselined),
                                     applied_by = VALUES(applied_by), applied_at = CURRENT_TIMESTAMP"
        );
        if (!$stmt) {
            return;
        }

        $baselinedInt = $baselined ? 1 : 0;
        $stmt->bind_param('ssiiis', $name, $checksum, $statements, $ms, $baselinedInt, $appliedBy);
        $stmt->execute();
        $stmt->close();
    }

    private function summarize(string $statement): string
    {
        $flat = preg_replace('/\s+/', ' ', trim($statement));

        return strlen($flat) > 160 ? substr($flat, 0, 157) . '...' : $flat;
    }

    /**
     * Split a .sql file into individual statements.
     *
     * Handles -- and # line comments, /* *​/ block comments, quoted strings and
     * backtick identifiers (so a ; inside them does not split), and DELIMITER.
     *
     * @return string[]
     */
    public function splitStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $delimiter = ';';
        $length = strlen($sql);
        $i = 0;

        while ($i < $length) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            // Line comments — only when they start a token, so "a--b" in a string is safe.
            if (($char === '-' && $next === '-' && (($i + 2 >= $length) || preg_match('/\s/', $sql[$i + 2]))) || $char === '#') {
                while ($i < $length && $sql[$i] !== "\n") {
                    $i++;
                }
                continue;
            }

            // Block comments. /*! ... */ are MySQL conditional hints — keep those.
            if ($char === '/' && $next === '*') {
                $isHint = ($i + 2 < $length) && $sql[$i + 2] === '!';
                $end = strpos($sql, '*/', $i + 2);
                $end = $end === false ? $length : $end + 2;
                if ($isHint) {
                    $buffer .= substr($sql, $i, $end - $i);
                }
                $i = $end;
                continue;
            }

            // Quoted strings and quoted identifiers.
            if ($char === "'" || $char === '"' || $char === '`') {
                $quote = $char;
                $buffer .= $char;
                $i++;
                while ($i < $length) {
                    $c = $sql[$i];
                    if ($c === '\\' && $quote !== '`' && $i + 1 < $length) {
                        $buffer .= $c . $sql[$i + 1];
                        $i += 2;
                        continue;
                    }
                    $buffer .= $c;
                    $i++;
                    if ($c === $quote) {
                        // A doubled quote is an escaped quote, not the end.
                        if ($i < $length && $sql[$i] === $quote) {
                            $buffer .= $sql[$i];
                            $i++;
                            continue;
                        }
                        break;
                    }
                }
                continue;
            }

            // DELIMITER directive (client-side, not sent to the server).
            if (($char === 'D' || $char === 'd') && trim($buffer) === ''
                && preg_match('/^DELIMITER[ \t]+(\S+)[ \t]*\r?\n?/i', substr($sql, $i), $m)) {
                $delimiter = $m[1];
                $i += strlen($m[0]);
                $buffer = '';
                continue;
            }

            // End of statement.
            if (substr($sql, $i, strlen($delimiter)) === $delimiter) {
                if (trim($buffer) !== '') {
                    $statements[] = trim($buffer);
                }
                $buffer = '';
                $i += strlen($delimiter);
                continue;
            }

            $buffer .= $char;
            $i++;
        }

        if (trim($buffer) !== '') {
            $statements[] = trim($buffer);
        }

        return $statements;
    }
}
