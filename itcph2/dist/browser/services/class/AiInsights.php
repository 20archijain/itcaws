<?php

/**
 * ARIA AI Insights - Controller
 *
 * Entry point for AI insights requests. Handles request parsing, context filter
 * resolution, and ACL before delegating to AiInsightsService for execution.
 */

require_once $include_path . 'defined_index.php';

// phpcs:ignore
class AiInsights
{
    private $_dbConn = null;
    private $_data = null;
    private $_iUserId = null;
    private $_arrAccessInfo = [];

    public function __construct($dbConn, $data, $arrAccessInfo, $iUserId)
    {
        $this->_dbConn = $dbConn;
        $this->_data = $data;
        $this->_arrAccessInfo = $arrAccessInfo;
        $this->_iUserId = $iUserId;
    }

    /**
     * Return dropdown options for a given scope type, optionally filtered by parent.
     * Request params: type (branch|region|circle|section|district|wd_code|ds), parent_type, parent_value
     */
    final public function getScopeOptions()
    {
        $type        = strtolower(trim(getFormData($this->_data, 'type')));
        $parentType  = strtolower(trim(getFormData($this->_data, 'parent_type')));
        $parentValue = trim(getFormData($this->_data, 'parent_value'));

        if (isEmptyString($type)) {
            echo json_encode(responseMessage(['type is required']));
            return;
        }

        try {
            $dsn = 'mysql:host=' . constant('DB_HOSTNAME') . ';dbname=' . $GLOBALS['DB_DBNAME'] . ';charset=utf8mb4';
            $pdo = new PDO($dsn, $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $options = $this->fetchScopeOptions($pdo, $type, $parentType, $parentValue);
            echo json_encode(responseMessage([], 1, ['options' => $options], true));
        } catch (Exception $e) {
            error_log('AiInsights getScopeOptions error: ' . $e->getMessage());
            echo json_encode(responseMessage(['An error occurred: ' . $e->getMessage()]));
        }
    }

    private function fetchScopeOptions(PDO $pdo, string $type, string $parentType, string $parentValue): array
    {
        $hasParent = ($parentType !== '' && $parentValue !== '');

        $sql    = ;
        $params = [];

        switch ($type) {
            case 'branch':
                if ($hasParent && $parentType === 'district') {
                    $sql = "SELECT DISTINCT main_branch AS val FROM tblbranch
                            WHERE dstatus = 0 AND main_branch IS NOT NULL AND main_branch != ''
                              AND LOWER(district) = LOWER(:pv)
                            ORDER BY main_branch";
                    $params[':pv'] = $parentValue;
                } else {
                    $sql = "SELECT DISTINCT main_branch AS val FROM tblbranch
                            WHERE dstatus = 0 AND main_branch IS NOT NULL AND main_branch != ''
                            ORDER BY main_branch";
                }
                break;

            case 'region':
                if ($hasParent && $parentType === 'district') {
                    $sql = "SELECT DISTINCT branch_name AS val FROM tblbranch
                            WHERE dstatus = 0 AND branch_name IS NOT NULL AND branch_name != ''
                              AND LOWER(district) = LOWER(:pv)
                            ORDER BY branch_name";
                    $params[':pv'] = $parentValue;
                } elseif ($hasParent && $parentType === 'branch') {
                    $sql = "SELECT DISTINCT branch_name AS val FROM tblbranch
                            WHERE dstatus = 0 AND branch_name IS NOT NULL AND branch_name != ''
                              AND LOWER(main_branch) = LOWER(:pv)
                            ORDER BY branch_name";
                    $params[':pv'] = $parentValue;
                } else {
                    $sql = "SELECT DISTINCT branch_name AS val FROM tblbranch
                            WHERE dstatus = 0 AND branch_name IS NOT NULL AND branch_name != ''
                            ORDER BY branch_name";
                }
                break;

            case 'circle':
                if ($hasParent && $parentType === 'region') {
                    $sql = "SELECT DISTINCT pt.circle AS val
                            FROM tblproject_team pt
                            JOIN tblbranch b ON b.branch_id = pt.branch_id
                            WHERE pt.dstatus = 0 AND pt.s_id = 99 AND pt.is_type = 0
                              AND pt.circle IS NOT NULL AND pt.circle != ''
                              AND LOWER(b.branch_name) = LOWER(:pv)
                            ORDER BY pt.circle";
                    $params[':pv'] = $parentValue;
                } elseif ($hasParent && $parentType === 'branch') {
                    $sql = "SELECT DISTINCT pt.circle AS val
                            FROM tblproject_team pt
                            JOIN tblbranch b ON b.branch_id = pt.branch_id
                            WHERE pt.dstatus = 0 AND pt.s_id = 99 AND pt.is_type = 0
                              AND pt.circle IS NOT NULL AND pt.circle != ''
                              AND LOWER(b.main_branch) = LOWER(:pv)
                            ORDER BY pt.circle";
                    $params[':pv'] = $parentValue;
                } else {
                    $sql = "SELECT DISTINCT circle AS val FROM tblproject_team
                            WHERE dstatus = 0 AND s_id = 99 AND is_type = 0
                              AND circle IS NOT NULL AND circle != ''
                            ORDER BY circle";
                }
                break;

            case 'section':
                if ($hasParent && $parentType === 'circle') {
                    $sql = "SELECT DISTINCT section AS val FROM tblproject_team
                            WHERE dstatus = 0 AND s_id = 99 AND is_type = 0
                              AND section IS NOT NULL AND section != ''
                              AND LOWER(circle) = LOWER(:pv)
                            ORDER BY section";
                    $params[':pv'] = $parentValue;
                } elseif ($hasParent && $parentType === 'region') {
                    $sql = "SELECT DISTINCT pt.section AS val
                            FROM tblproject_team pt
                            JOIN tblbranch b ON b.branch_id = pt.branch_id
                            WHERE pt.dstatus = 0 AND pt.s_id = 99 AND pt.is_type = 0
                              AND pt.section IS NOT NULL AND pt.section != ''
                              AND LOWER(b.branch_name) = LOWER(:pv)
                            ORDER BY pt.section";
                    $params[':pv'] = $parentValue;
                } elseif ($hasParent && $parentType === 'branch') {
                    $sql = "SELECT DISTINCT pt.section AS val
                            FROM tblproject_team pt
                            JOIN tblbranch b ON b.branch_id = pt.branch_id
                            WHERE pt.dstatus = 0 AND pt.s_id = 99 AND pt.is_type = 0
                              AND pt.section IS NOT NULL AND pt.section != ''
                              AND LOWER(b.main_branch) = LOWER(:pv)
                            ORDER BY pt.section";
                    $params[':pv'] = $parentValue;
                } else {
                    $sql = "SELECT DISTINCT section AS val FROM tblproject_team
                            WHERE dstatus = 0 AND s_id = 99 AND is_type = 0
                              AND section IS NOT NULL AND section != ''
                            ORDER BY section";
                }
                break;

            case 'district':
                if ($hasParent && $parentType === 'branch') {
                    $sql = "SELECT DISTINCT district AS val FROM tblbranch
                            WHERE dstatus = 0 AND district IS NOT NULL AND district != ''
                              AND LOWER(main_branch) = LOWER(:pv)
                            ORDER BY district";
                    $params[':pv'] = $parentValue;
                } else {
                    $sql = "SELECT DISTINCT district AS val FROM tblbranch
                            WHERE dstatus = 0 AND district IS NOT NULL AND district != ''
                            ORDER BY district";
                }
                break;

            case 'wd_code':
                if ($hasParent && $parentType === 'section') {
                    $sql = "SELECT DISTINCT wd_code AS val FROM tblproject_team
                            WHERE dstatus = 0 AND s_id = 99 AND is_type = 0
                              AND wd_code IS NOT NULL AND wd_code != ''
                              AND LOWER(section) = LOWER(:pv)
                            ORDER BY wd_code";
                    $params[':pv'] = $parentValue;
                } elseif ($hasParent && $parentType === 'circle') {
                    $sql = "SELECT DISTINCT wd_code AS val FROM tblproject_team
                            WHERE dstatus = 0 AND s_id = 99 AND is_type = 0
                              AND wd_code IS NOT NULL AND wd_code != ''
                              AND LOWER(circle) = LOWER(:pv)
                            ORDER BY wd_code";
                    $params[':pv'] = $parentValue;
                } elseif ($hasParent && $parentType === 'region') {
                    $sql = "SELECT DISTINCT pt.wd_code AS val
                            FROM tblproject_team pt
                            JOIN tblbranch b ON b.branch_id = pt.branch_id
                            WHERE pt.dstatus = 0 AND pt.s_id = 99 AND pt.is_type = 0
                              AND pt.wd_code IS NOT NULL AND pt.wd_code != ''
                              AND LOWER(b.branch_name) = LOWER(:pv)
                            ORDER BY pt.wd_code";
                    $params[':pv'] = $parentValue;
                } elseif ($hasParent && $parentType === 'branch') {
                    $sql = "SELECT DISTINCT pt.wd_code AS val
                            FROM tblproject_team pt
                            JOIN tblbranch b ON b.branch_id = pt.branch_id
                            WHERE pt.dstatus = 0 AND pt.s_id = 99 AND pt.is_type = 0
                              AND pt.wd_code IS NOT NULL AND pt.wd_code != ''
                              AND LOWER(b.main_branch) = LOWER(:pv)
                            ORDER BY pt.wd_code";
                    $params[':pv'] = $parentValue;
                } else {
                    $sql = "SELECT DISTINCT wd_code AS val FROM tblproject_team
                            WHERE dstatus = 0 AND s_id = 99 AND is_type = 0
                              AND wd_code IS NOT NULL AND wd_code != ''
                            ORDER BY wd_code";
                }
                break;

            case 'ds':
                if ($hasParent && $parentType === 'wd_code') {
                    $sql = "SELECT DISTINCT team_name AS val FROM tblproject_team
                            WHERE dstatus = 0 AND s_id = 99 AND is_type = 0
                              AND team_name IS NOT NULL AND team_name != ''
                              AND LOWER(wd_code) = LOWER(:pv)
                            ORDER BY team_name";
                    $params[':pv'] = $parentValue;
                } elseif ($hasParent && $parentType === 'section') {
                    $sql = "SELECT DISTINCT team_name AS val FROM tblproject_team
                            WHERE dstatus = 0 AND s_id = 99 AND is_type = 0
                              AND team_name IS NOT NULL AND team_name != ''
                              AND LOWER(section) = LOWER(:pv)
                            ORDER BY team_name";
                    $params[':pv'] = $parentValue;
                } elseif ($hasParent && $parentType === 'circle') {
                    $sql = "SELECT DISTINCT team_name AS val FROM tblproject_team
                            WHERE dstatus = 0 AND s_id = 99 AND is_type = 0
                              AND team_name IS NOT NULL AND team_name != ''
                              AND LOWER(circle) = LOWER(:pv)
                            ORDER BY team_name";
                    $params[':pv'] = $parentValue;
                } elseif ($hasParent && $parentType === 'region') {
                    $sql = "SELECT DISTINCT pt.team_name AS val
                            FROM tblproject_team pt
                            JOIN tblbranch b ON b.branch_id = pt.branch_id
                            WHERE pt.dstatus = 0 AND pt.s_id = 99 AND pt.is_type = 0
                              AND pt.team_name IS NOT NULL AND pt.team_name != ''
                              AND LOWER(b.branch_name) = LOWER(:pv)
                            ORDER BY pt.team_name";
                    $params[':pv'] = $parentValue;
                } elseif ($hasParent && $parentType === 'branch') {
                    $sql = "SELECT DISTINCT pt.team_name AS val
                            FROM tblproject_team pt
                            JOIN tblbranch b ON b.branch_id = pt.branch_id
                            WHERE pt.dstatus = 0 AND pt.s_id = 99 AND pt.is_type = 0
                              AND pt.team_name IS NOT NULL AND pt.team_name != ''
                              AND LOWER(b.main_branch) = LOWER(:pv)
                            ORDER BY pt.team_name";
                    $params[':pv'] = $parentValue;
                } else {
                    $sql = "SELECT DISTINCT team_name AS val FROM tblproject_team
                            WHERE dstatus = 0 AND s_id = 99 AND is_type = 0
                              AND team_name IS NOT NULL AND team_name != ''
                            ORDER BY team_name";
                }
                break;

            default:
                return [];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return array_column($stmt->fetchAll(), 'val');
    }

    final public function getInsights()
    {
        $query = getFormData($this->_data, "query");
        if (isEmptyString($query)) {
            $arrMessage = responseMessage(["Query is required"]);
            echo json_encode($arrMessage);
            return;
        }

        try {
            $dsn = 'mysql:host=' . constant("DB_HOSTNAME") . ';dbname=' . $GLOBALS['DB_DBNAME'] . ';charset=utf8mb4';
            $pdo = new PDO($dsn, $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            require_once __DIR__ . '/AiInsightsService.php';
            $service = new \Services\AiInsights\AiInsightsService($pdo, $this->_arrAccessInfo);

            $filters = [
                'date_from' => getFormData($this->_data, "startDate"),
                'date_to'   => getFormData($this->_data, "endDate"),
            ];

            // Resolve context filters sent from the UI (separate from query text)
            $contextNote = '';
            $debugNote = '';
            $rawContextFilters = html_entity_decode(getFormData($this->_data, "context_filters"), ENT_QUOTES);
            if (!isEmptyString($rawContextFilters)) {
                $contextFilters = json_decode($rawContextFilters, true);
                if (is_array($contextFilters) && !empty($contextFilters)) {
                    list($resolvedFilters, $contextNote) = $this->resolveContextFilters($contextFilters, $pdo);
                    $filters = array_merge($filters, $resolvedFilters);
                    $filters['context_resolved'] = true;
                    $debugNote = 'Scope applied: ' . json_encode($resolvedFilters);
                } else {
                    $debugNote = 'context_filters parse failed. Raw: ' . substr($rawContextFilters, 0, 100);
                }
            }

            $result = $service->getInsights($query, $filters);

            if ($debugNote) {
                $result['detectedFilters'] = array_merge([$debugNote], $result['detectedFilters'] ?? []);
            }
            if ($contextNote) {
                $result['context_note'] = $contextNote;
            }

            $arrMessage = responseMessage([], 1, $result, true);
            echo json_encode($arrMessage);
        } catch (Exception $e) {
            error_log("AiInsights bridge error: " . $e->getMessage());
            $arrMessage = responseMessage(["An error occurred: " . $e->getMessage()]);
            echo json_encode($arrMessage);
        }
    }

    private function resolveContextFilters(array $contextFilters, PDO $pdo): array
    {
        $resolved = [];
        $notes    = [];

        foreach ($contextFilters as $cf) {
            $requestedType = strtolower(trim($cf['type'] ?? ''));
            $value         = trim($cf['value'] ?? '');
            if ($value === '') {
                continue;
            }

            $match = $this->findEntityInDb($value, $pdo);

            if ($match === null) {
                $notes[] = "Note: Could not verify '$value' in the database. Results may be empty.";
                $resolved = array_merge($resolved, $this->mapTypeToFilter($requestedType, $value));
                continue;
            }

            $actualType  = $match['type'];
            $actualValue = $match['value'];

            if ($actualType !== $requestedType) {
                $notes[] = "ARIA Note: '$value' is a " . $this->friendlyTypeName($actualType)
                    . ", not a " . $this->friendlyTypeName($requestedType)
                    . ". Showing data for " . $this->friendlyTypeName($actualType) . " '" . $actualValue . "'.";
            }

            $resolved = array_merge($resolved, $this->mapTypeToFilter($actualType, $actualValue));
        }

        return [$resolved, implode(' | ', $notes)];
    }

    private function findEntityInDb(string $value, PDO $pdo): ?array
    {
        $v = str_replace("'", "''", $value);

        $stmt = $pdo->query("SELECT DISTINCT main_branch FROM tblbranch
            WHERE dstatus = 0 AND main_branch IS NOT NULL AND main_branch != ''
            AND LOWER(main_branch) = LOWER('$v') LIMIT 1");
        if ($row = $stmt->fetch()) {
            return ['type' => 'branch', 'value' => $row['main_branch']];
        }

        $stmt = $pdo->query("SELECT DISTINCT branch_name FROM tblbranch
            WHERE dstatus = 0 AND branch_name IS NOT NULL AND branch_name != ''
            AND LOWER(branch_name) = LOWER('$v') LIMIT 1");
        if ($row = $stmt->fetch()) {
            return ['type' => 'region', 'value' => $row['branch_name']];
        }

        $stmt = $pdo->query("SELECT DISTINCT circle FROM tblproject_team
            WHERE dstatus = 0 AND s_id = 99 AND is_type = 0
            AND circle IS NOT NULL AND circle != ''
            AND LOWER(circle) = LOWER('$v') LIMIT 1");
        if ($row = $stmt->fetch()) {
            return ['type' => 'circle', 'value' => $row['circle']];
        }

        $stmt = $pdo->query("SELECT DISTINCT section FROM tblproject_team
            WHERE dstatus = 0 AND s_id = 99 AND is_type = 0
            AND section IS NOT NULL AND section != ''
            AND LOWER(section) = LOWER('$v') LIMIT 1");
        if ($row = $stmt->fetch()) {
            return ['type' => 'section', 'value' => $row['section']];
        }

        $stmt = $pdo->query("SELECT DISTINCT district FROM tblbranch
            WHERE dstatus = 0 AND district IS NOT NULL AND district != ''
            AND LOWER(district) = LOWER('$v') LIMIT 1");
        if ($row = $stmt->fetch()) {
            return ['type' => 'district', 'value' => $row['district']];
        }

        $stmt = $pdo->query("SELECT DISTINCT wd_code FROM tblproject_team
            WHERE dstatus = 0 AND s_id = 99 AND is_type = 0
            AND wd_code IS NOT NULL AND wd_code != ''
            AND LOWER(wd_code) = LOWER('$v') LIMIT 1");
        if ($row = $stmt->fetch()) {
            return ['type' => 'wd_code', 'value' => $row['wd_code']];
        }

        $stmt = $pdo->query("SELECT DISTINCT team_name FROM tblproject_team
            WHERE dstatus = 0 AND s_id = 99 AND is_type = 0
            AND team_name IS NOT NULL AND team_name != ''
            AND LOWER(team_name) = LOWER('$v') LIMIT 1");
        if ($row = $stmt->fetch()) {
            return ['type' => 'ds', 'value' => $row['team_name']];
        }

        return null;
    }

    private function mapTypeToFilter(string $type, string $value): array
    {
        switch ($type) {
            case 'branch':
                return ['main_branch' => $value];
            case 'region':
                return ['region'      => [$value]];
            case 'circle':
                return ['circle'      => [$value]];
            case 'section':
                return ['section'     => [$value]];
            case 'district':
                return ['district'    => [$value]];
            case 'wd_code':
                return ['wd_code'     => [$value]];
            case 'ds':
                return ['ds_name'     => $value];
            default:
                return [];
        }
    }

    private function friendlyTypeName(string $type): string
    {
        $map = [
            'branch'   => 'Branch',
            'region'   => 'Region',
            'circle'   => 'Circle',
            'section'  => 'Section',
            'district' => 'District',
            'wd_code'  => 'WD Code',
            'wd code'  => 'WD Code',
            'ds'       => 'DS',
        ];
        return $map[strtolower($type)] ?? ucfirst($type);
    }
}
