<?php

$pdo = new PDO(
    'mysql:host=10.1.12.162;dbname=middata;charset=utf8mb4',
    'root',
    getenv('MIDDATA_DB_PASSWORD') ?: '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);

$queries = [
    'person_types' => 'SELECT rylx, COUNT(*) AS total FROM t_cx_zzqxryxx GROUP BY rylx ORDER BY rylx',
    'account_flags' => 'SELECT zhbz, COUNT(*) AS total FROM t_cx_zzqxryxx WHERE rylx = 1 GROUP BY zhbz ORDER BY zhbz',
    'teacher_quality' => <<<'SQL'
        SELECT
            COUNT(*) AS total,
            SUM(xgh IS NULL OR TRIM(xgh) = '') AS missing_xgh,
            COUNT(DISTINCT NULLIF(TRIM(xgh), '')) AS distinct_xgh,
            SUM(xm IS NULL OR TRIM(xm) = '') AS missing_name,
            SUM(dwmc IS NULL OR TRIM(dwmc) = '') AS missing_department,
            SUM(dzyx IS NULL OR TRIM(dzyx) = '') AS missing_email,
            SUM(yddh IS NULL OR TRIM(yddh) = '') AS missing_mobile
        FROM t_cx_zzqxryxx
        WHERE rylx = 1
        SQL,
    'duplicate_teacher_numbers' => <<<'SQL'
        SELECT COUNT(*) AS duplicate_groups
        FROM (
            SELECT TRIM(xgh)
            FROM t_cx_zzqxryxx
            WHERE rylx = 1 AND xgh IS NOT NULL AND TRIM(xgh) <> ''
            GROUP BY TRIM(xgh)
            HAVING COUNT(*) > 1
        ) AS duplicates
        SQL,
];

$results = [];
foreach ($queries as $name => $sql) {
    $results[$name] = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
