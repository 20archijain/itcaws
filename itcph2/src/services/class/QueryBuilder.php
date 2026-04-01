<?php
/**
 * ============================================================================
 * SCALABLE AI INSIGHTS FRAMEWORK - Query Builder Engine
 * ============================================================================
 *
 * Senior Developer Pattern:
 * - Configuration-driven (no manual SQL per new query type)
 * - Type-safe with proper error handling
 * - Reusable components for aggregations, filters, sorting
 * - Extensible metric system
 *
 * Usage: $builder = new QueryBuilder($config, $dbConnection);
 *        $query = $builder->buildQuery('product_sales', $filters);
 *
 * ============================================================================
 */

namespace AiInsights;

class QueryBuilder
{
    private $config;
    private $pdo;
    private $dbTable = 'tblvands_summary';
    private $projectTeamTable = 'tblproject_team';
    private $branchTable = 'tblbranch';

    private $metrics = [];
    private $joins = [];
    private $where = [];
    private $select = [];
    private $groupBy = [];
    private $orderBy = [];
    private $limit = null;
    private $params = [];

    private $aggregationFunctions = [
        'SUM' => 'SUM(%s)',
        'COUNT' => 'COUNT(%s)',
        'AVG' => 'AVG(%s)',
        'MAX' => 'MAX(%s)',
        'MIN' => 'MIN(%s)',
        'DISTINCT' => 'DISTINCT %s',
        'PERCENTAGE' => 'PERCENTAGE',  // Custom handler
        'DIV' => 'DIVISION',             // Custom handler
    ];

    public function __construct($config, $pdo)
    {
        $this->config = $config;
        $this->pdo = $pdo;
        $this->initializeJoins();
    }

    /**
     * Build SQL query from configuration
     */
    public function buildQuery($queryName, $filters = [])
    {
        if (!isset($this->config['queries'][$queryName])) {
            throw new \Exception("Query configuration not found: $queryName");
        }

        $queryConfig = $this->config['queries'][$queryName];

        // Reset query builder state
        $this->resetState();

        // Build based on query type
        switch ($queryConfig['type']) {
            case 'aggregation':
                return $this->buildAggregationQuery($queryConfig, $filters);
            case 'time_series':
                return $this->buildTimeSeriesQuery($queryConfig, $filters);
            default:
                throw new \Exception("Unknown query type: " . $queryConfig['type']);
        }
    }

    /**
     * Build aggregation query
     */
    private function buildAggregationQuery($queryConfig, $filters = [])
    {
        if (isset($queryConfig['include_date']) && $queryConfig['include_date']) {
            $this->select[] = 'a.date';
        }

        // Build dimensions and aggregations
        foreach ($queryConfig['dimensions'] as $dimension) {
            $field = $dimension['field'];

            if ($field === 'branch') {
                $this->select[] = "d.branch_name AS branch";
                $this->groupBy[] = "d.branch_name";
            } elseif ($field === 'ds_name') {
                $this->select[] = "b.team_name AS ds_name";
                $this->groupBy[] = "b.team_name";
            } elseif ($field === 'circle' || $field === 'section') {
                $this->select[] = "b.$field AS dimension_field";
                $this->groupBy[] = "b.$field";
                $this->where[] = "b.$field IS NOT NULL AND b.$field != ''";
            } elseif ($field === 'main_branch') {
                $this->select[] = "d.main_branch AS dimension_field";
                $this->groupBy[] = "d.main_branch";
                $this->where[] = "d.main_branch IS NOT NULL AND d.main_branch != ''";
            } elseif ($field === 'district') {
                $this->select[] = "d.district AS dimension_field";
                $this->groupBy[] = "d.district";
                $this->where[] = "d.district IS NOT NULL AND d.district != ''";
            } else {
                $this->select[] = "a.$field AS dimension_field";
                $this->groupBy[] = "a.$field";
            }

            // Add sub-fields if present
            if (isset($dimension['sub_fields'])) {
                foreach ($dimension['sub_fields'] as $subField) {
                    if ($subField === 'district' && $field === 'branch') {
                        $this->select[] = "d.district AS sub_field_$subField";
                    } else {
                        $this->select[] = "a.$subField AS sub_field_$subField";
                    }
                }
            }

            $aggregationMap = [];

            if (isset($dimension['aggregations'])) {
                // First pass: process all standard aggregations
                foreach ($dimension['aggregations'] as $agg) {
                    if (is_array($agg)) {
                        foreach ($agg as $aggName => $aggFormula) {
                            if (is_string($aggFormula)) {
                                if (strpos($aggFormula, 'PERCENTAGE') !== 0 && strpos($aggFormula, 'DIV') !== 0) {
                                    $this->processAggregation($aggName, $aggFormula);
                                    $aggregationMap[$aggName] = $aggFormula;
                                }
                            }
                        }
                    }
                }

                // Second pass: process derived aggregations (PERCENTAGE, DIV)
                foreach ($dimension['aggregations'] as $agg) {
                    if (is_array($agg)) {
                        foreach ($agg as $aggName => $aggFormula) {
                            if (is_string($aggFormula)) {
                                if (strpos($aggFormula, 'PERCENTAGE') === 0 || strpos($aggFormula, 'DIV') === 0) {
                                    $this->processAggregation($aggName, $aggFormula, $aggregationMap);
                                }
                            }
                        }
                    }
                }
            }
        }

        // Apply filters
        $this->applyFilters($filters);

        // Apply sorting
        if (isset($queryConfig['sorting'])) {
            $sortOrder = $this->detectSortOrder($filters, $queryConfig['sorting']);
            $this->orderBy[] = $sortOrder;
        }

        // Apply limit
        if (isset($queryConfig['limit'])) {
            $this->limit = $queryConfig['limit'];
        }

        return $this->buildFinalQuery();
    }

    /**
     * Build time series query
     */
    private function buildTimeSeriesQuery($queryConfig, $filters = [])
    {
        $timeField = $queryConfig['time_field'];
        $this->select[] = "a.$timeField AS time_period";
        $this->groupBy[] = "DATE(a.$timeField)";

        if (isset($queryConfig['aggregations'])) {
            $aggregationMap = [];

            // First pass: process non-derived aggregations
            foreach ($queryConfig['aggregations'] as $agg) {
                if (is_array($agg)) {
                    foreach ($agg as $aggName => $aggFormula) {
                        if (is_string($aggFormula)) {
                            if (strpos($aggFormula, 'PERCENTAGE') !== 0 && strpos($aggFormula, 'DIV') !== 0) {
                                $this->processAggregation($aggName, $aggFormula);
                                $aggregationMap[$aggName] = $aggFormula;
                            }
                        }
                    }
                }
            }

            // Second pass: process derived aggregations
            foreach ($queryConfig['aggregations'] as $agg) {
                if (is_array($agg)) {
                    foreach ($agg as $aggName => $aggFormula) {
                        if (is_string($aggFormula)) {
                            if (strpos($aggFormula, 'PERCENTAGE') === 0 || strpos($aggFormula, 'DIV') === 0) {
                                $this->processAggregation($aggName, $aggFormula, $aggregationMap);
                            }
                        }
                    }
                }
            }
        }

        // Apply filters
        $this->applyFilters($filters);

        // Sort by time ascending
        $this->orderBy[] = "DATE(a.$timeField) ASC";

        return $this->buildFinalQuery();
    }

    /**
     * Parse formula with function call - handles nested parentheses
     */
    private function extractFunctionArgs($formula, $functionName)
    {
        $pattern = $functionName . '\(';
        if (strpos($formula, $pattern) === false) {
            return null;
        }

        $startPos = strpos($formula, $pattern) + strlen($pattern);
        $parensCount = 1;
        $currentPos = $startPos;
        $args = [];
        $currentArg = '';

        while ($currentPos < strlen($formula) && $parensCount > 0) {
            $char = $formula[$currentPos];

            if ($char === '(') {
                $parensCount++;
                $currentArg .= $char;
            } elseif ($char === ')') {
                $parensCount--;
                if ($parensCount === 0) {
                    $args[] = trim($currentArg);
                } else {
                    $currentArg .= $char;
                }
            } elseif ($char === ',' && $parensCount === 1) {
                $args[] = trim($currentArg);
                $currentArg = '';
            } else {
                $currentArg .= $char;
            }

            $currentPos++;
        }

        return count($args) > 0 ? $args : null;
    }

    /**
     * Process aggregation and add to select
     */
    private function processAggregation($aggName, $aggFormula, $aggregationMap = [])
    {
        if (strpos($aggFormula, 'PERCENTAGE') === 0) {
            $percentageFormula = $aggFormula;

            foreach ($aggregationMap as $refName => $refFormula) {
                $percentageFormula = str_replace($refName, $refFormula, $percentageFormula);
            }

            $args = $this->extractFunctionArgs($percentageFormula, 'PERCENTAGE');
            if ($args && count($args) === 2) {
                $percentageFormula = '(' . trim($args[0]) . ' / ' . trim($args[1]) . ') * 100';
            } else {
                $percentageFormula = preg_replace('/PERCENTAGE\((.*?),(.*?)\)/', '($1 / $2) * 100', $percentageFormula);
            }
            $this->select[] = $percentageFormula . " AS " . $aggName;
        } elseif (strpos($aggFormula, 'DIV') === 0) {
            $divFormula = $aggFormula;

            foreach ($aggregationMap as $refName => $refFormula) {
                $divFormula = str_replace($refName, $refFormula, $divFormula);
            }

            $args = $this->extractFunctionArgs($divFormula, 'DIV');
            if ($args && count($args) === 2) {
                $divFormula = '(' . trim($args[0]) . ' / ' . trim($args[1]) . ')';
            } else {
                $divFormula = preg_replace('/DIV\((.*?),(.*?)\)/', '($1 / $2)', $divFormula);
            }
            $this->select[] = $divFormula . " AS " . $aggName;
        } else {
            $this->select[] = "$aggFormula AS $aggName";
        }
    }

    /**
     * Apply filters to query (uses parameterized queries for security)
     */
    private function applyFilters($filters)
    {
        $this->where[] = "a.dstatus = 0";
        $this->where[] = "b.dstatus = 0";
        $this->where[] = "d.dstatus = 0";
        $this->where[] = "b.s_id = 99";

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $this->where[] = "a.activity_date BETWEEN :date_from AND :date_to";
            $this->params[':date_from'] = $filters['date_from'];
            $this->params[':date_to'] = $filters['date_to'];
        }

        if (isset($filters['user_teams']) && !empty($filters['user_teams'])) {
            $teamList = $filters['user_teams'];
            if (is_string($teamList) && preg_match('/^\([\d,]+\)$/', $teamList)) {
                $this->where[] = "b.team_id IN $teamList";
            }
        }

        if (isset($filters['district']) && !empty($filters['district'])) {
            $districts = is_array($filters['district']) ? $filters['district'] : [$filters['district']];
            $placeholders = $this->buildPlaceholders('district', $districts);
            $this->where[] = "d.district IN ($placeholders)";
        }

        if (isset($filters['branch']) && !empty($filters['branch'])) {
            $branches = is_array($filters['branch']) ? $filters['branch'] : [$filters['branch']];
            $placeholders = $this->buildPlaceholders('branch', $branches);
            $this->where[] = "d.branch_name IN ($placeholders)";
        }

        if (isset($filters['main_branch']) && !empty($filters['main_branch'])) {
            $mainBranches = is_array($filters['main_branch']) ? $filters['main_branch'] : [$filters['main_branch']];
            $placeholders = $this->buildPlaceholders('main_branch', $mainBranches);
            $this->where[] = "d.main_branch IN ($placeholders)";
        }

        if (isset($filters['branch_id']) && !empty($filters['branch_id'])) {
            $this->where[] = "d.branch_id = :branch_id";
            $this->params[':branch_id'] = intval($filters['branch_id']);
        }

        if (isset($filters['circle']) && !empty($filters['circle'])) {
            $circles = is_array($filters['circle']) ? $filters['circle'] : [$filters['circle']];
            $placeholders = $this->buildPlaceholders('circle', $circles);
            $this->where[] = "b.circle IN ($placeholders)";
        }

        if (isset($filters['section']) && !empty($filters['section'])) {
            $sections = is_array($filters['section']) ? $filters['section'] : [$filters['section']];
            $placeholders = $this->buildPlaceholders('section', $sections);
            $this->where[] = "b.section IN ($placeholders)";
        }

        if (isset($filters['wd_code']) && !empty($filters['wd_code'])) {
            $wdCodes = is_array($filters['wd_code']) ? $filters['wd_code'] : [$filters['wd_code']];
            $placeholders = $this->buildPlaceholders('wdcode', $wdCodes);
            $this->where[] = "b.wd_code IN ($placeholders)";
        }

        if (isset($filters['ds_name']) && !empty($filters['ds_name'])) {
            $dsNames = is_array($filters['ds_name']) ? $filters['ds_name'] : [$filters['ds_name']];
            $placeholders = $this->buildPlaceholders('dsname', $dsNames);
            $this->where[] = "b.team_name IN ($placeholders)";
        }
    }

    /**
     * Build parameterized placeholders for IN clause
     */
    private function buildPlaceholders($prefix, $values)
    {
        $placeholders = [];
        foreach ($values as $i => $val) {
            $key = ":{$prefix}_{$i}";
            $placeholders[] = $key;
            $this->params[$key] = $val;
        }
        return implode(', ', $placeholders);
    }

    /**
     * Detect sort order from filters (best vs worst)
     */
    private function detectSortOrder($filters, $sortingConfig)
    {
        $flatFilters = [];
        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                $flatFilters[] = implode(' ', $value);
            } else {
                $flatFilters[] = (string)$value;
            }
        }
        $queryText = strtolower(implode(' ', $flatFilters));

        if (strpos($queryText, 'worst') !== false ||
            strpos($queryText, 'bottom') !== false ||
            strpos($queryText, 'low') !== false) {
            return $sortingConfig['worst'] ?? 'DESC';
        }

        return $sortingConfig['best'] ?? 'DESC';
    }

    /**
     * Build final SQL query
     */
    private function buildFinalQuery()
    {
        $query = "SELECT " . implode(', ', $this->select)
               . " FROM " . $this->dbTable . " a";

        foreach ($this->joins as $join) {
            $query .= " " . $join;
        }

        if (!empty($this->where)) {
            $query .= " WHERE " . implode(' AND ', $this->where);
        }

        if (!empty($this->groupBy)) {
            $query .= " GROUP BY " . implode(', ', $this->groupBy);
        }

        if (!empty($this->orderBy)) {
            $query .= " ORDER BY " . implode(', ', $this->orderBy);
        }

        if ($this->limit) {
            $query .= " LIMIT " . intval($this->limit);
        }

        return $query;
    }

    /**
     * Execute query and return results
     */
    public function execute($queryName, $filters = [])
    {
        try {
            $query = $this->buildQuery($queryName, $filters);
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($this->params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("QueryBuilder Error for '$queryName': " . $e->getMessage() . "\nQuery: " . ($query ?? 'N/A') . "\nParams: " . json_encode($this->params));
            throw new \Exception("Query execution failed: " . $e->getMessage() . " (Query: $queryName)");
        }
    }

    /**
     * Initialize standard joins
     */
    private function initializeJoins()
    {
        $this->joins['project_team'] = "LEFT JOIN {$this->projectTeamTable} b ON a.team_id = b.team_id";
        $this->joins['branch'] = "LEFT JOIN {$this->branchTable} d ON b.branch_id = d.branch_id";
    }

    /**
     * Reset query builder state
     */
    private function resetState()
    {
        $this->select = [];
        $this->where = [];
        $this->groupBy = [];
        $this->orderBy = [];
        $this->joins = [];
        $this->limit = null;
        $this->params = [];
        $this->initializeJoins();
    }

    public function getAvailableQueries()
    {
        return array_keys($this->config['queries']);
    }

    public function getQueryConfig($queryName)
    {
        return $this->config['queries'][$queryName] ?? null;
    }

    /**
     * Intelligent query routing using cumulative keyword scoring.
     */
    public function findQueryByKeywords($text)
    {
        $result = $this->findQueryByKeywordsWithScores($text);
        return $result['query'];
    }

    /**
     * Same as findQueryByKeywords but also returns scores for ambiguity detection.
     */
    public function findQueryByKeywordsWithScores($text)
    {
        $text = strtolower(trim($text));
        $text = $this->normalizeConversationalPhrasing($text);
        $scores = [];

        foreach ($this->config['queries'] as $queryName => $queryConfig) {
            if (!isset($queryConfig['keywords'])) {
                continue;
            }
            $score = 0;
            foreach ($queryConfig['keywords'] as $keyword) {
                $kw = strtolower($keyword);
                if (strpos($text, $kw) !== false) {
                    $len = strlen($kw);
                    $score += $len * $len;
                }
            }
            if ($score > 0) {
                $scores[$queryName] = $score;
            }
        }

        if (!empty($scores)) {
            arsort($scores);
            $top = array_key_first($scores);
            $topScore = $scores[$top];
            $second = null;
            $secondScore = 0;
            $skip = true;
            foreach ($scores as $q => $s) {
                if ($skip) { $skip = false; continue; }
                $second = $q; $secondScore = $s; break;
            }
            $isAmbiguous = $second !== null && $topScore > 0 && ($secondScore / $topScore) >= 0.75;
            return [
                'query' => $top,
                'scores' => $scores,
                'isAmbiguous' => $isAmbiguous,
                'topQuery' => $top,
                'secondQuery' => $second
            ];
        }

        $fallback = $this->detectFallbackIntent($text);
        return [
            'query' => $fallback,
            'scores' => [],
            'isAmbiguous' => false,
            'topQuery' => $fallback,
            'secondQuery' => null
        ];
    }

    /**
     * Get clarifying questions when query is ambiguous.
     */
    public function getClarifyingQuestions($text, $scores = [], $topQuery = null, $secondQuery = null, $detectedEntities = [])
    {
        $questions = [];
        $text = strtolower(trim($text));
        $text = $this->normalizeConversationalPhrasing($text);
        $wordCount = count(preg_split('/\s+/', trim($text)));

        if ($wordCount <= 3 && !empty($detectedEntities)) {
            $entityLabel = implode(', ', array_slice($detectedEntities, 0, 2));
            $questions[] = "Best DS in $entityLabel";
            $questions[] = "Sales performance in $entityLabel";
            $questions[] = "Scorecard for $entityLabel";
            $questions[] = "Qualified attendance in $entityLabel";
        }

        if ($topQuery && $secondQuery) {
            $qNames = [
                'ds_performance' => 'DS performance',
                'product_sales' => 'Product sales',
                'branch_qualified_attendance' => 'Qualified attendance',
                'executive_summary' => 'Executive summary',
                'geographic_heatmap' => 'Sales on map',
                'period_comparison' => 'Period comparison',
                'growth_decline' => 'Growth/decline',
                'anomaly_detection' => 'Anomalies',
                'category_sales' => 'Category sales',
                'focus_brand_analysis' => 'Focus brand',
            ];
            $a = $qNames[$topQuery] ?? $topQuery;
            $b = $qNames[$secondQuery] ?? $secondQuery;
            $questions[] = "Did you mean: $a or $b? Try: \"$a for this period\" or \"$b breakdown\"";
        }

        if (empty($questions)) {
            $questions = [
                'Best DS in my region',
                'Top selling products this month',
                'Compare regions performance',
                'Give me a morning briefing'
            ];
        }

        return array_slice(array_unique($questions), 0, 4);
    }

    /**
     * Normalize conversational wrappers so intent detection focuses on analytics.
     */
    private function normalizeConversationalPhrasing($text)
    {
        if ($text === '') {
            return $text;
        }

        $patterns = [
            '/\b(i\s+want|i\s+need|i\s+wish|i\'d\s+like|i\s+would\s+like)\b/',
            '/\b(can|could|would|will)\s+you\b/',
            '/\b(show|give|tell)\s+me\b/',
            '/\b(help\s+me\s+with)\b/',
            '/\b(let\s+me\s+know)\b/',
            '/\bplease\b/',
            '/\bpls\b/',
            '/\bkindly\b/',
        ];

        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, ' ', $text);
        }

        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * Fallback intent detection for vague or conversational queries.
     */
    private function detectFallbackIntent($text)
    {
        $fallbackMap = [
            'executive_summary' => [
                'how are we', 'how is everything', 'how is it going', 'how we doing',
                'what is the status', 'give me status', 'quick update', 'quick overview',
                'what are the numbers', 'give me numbers', 'kpi', 'scorecard',
                'action items', 'what should i focus', 'what needs my attention',
                'brief me', 'update me', 'morning update', 'daily update',
                'how was today', 'how was yesterday', 'what happened today',
                'performance report', 'performance update', 'project health',
                'tell me everything', 'full picture', 'big picture',
                'how is my region', 'how is my area', 'my numbers',
                'sales report', 'monthly report', 'weekly report',
            ],
            'anomaly_detection' => [
                'anything wrong', 'any problem', 'any issue', 'any concern',
                'something off', 'looks wrong', 'not normal', 'looks strange',
                'why did sales drop', 'why are sales down', 'what went wrong',
                'investigate', 'look into',
            ],
            'growth_decline' => [
                'who is improving', 'who is slipping', 'where are we losing',
                'where are we gaining', 'momentum',
            ],
            'ds_performance' => [
                'how is my team', 'team performance', 'my people',
                'who needs coaching', 'who are my stars',
                'field force', 'ground team', 'feet on street',
            ],
            'product_sales' => [
                'sales breakdown', 'what is selling', 'market analysis',
                'revenue breakdown', 'sku analysis', 'sku performance',
            ],
        ];

        foreach ($fallbackMap as $queryName => $phrases) {
            foreach ($phrases as $phrase) {
                if (strpos($text, $phrase) !== false) {
                    return $queryName;
                }
            }
        }

        if (strlen($text) > 0) {
            return 'executive_summary';
        }

        return null;
    }
}

?>
