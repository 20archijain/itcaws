<?php
/**
 * ARIA AI Insights - Executor
 *
 * Handles query routing, SQL execution via QueryBuilder, and OpenAI response
 * generation. Also formats chart and map data returned to the frontend.
 */

namespace AiInsights;

use Symfony\Component\Yaml\Yaml;

class InsightsExecutor
{
    private $queryBuilder;
    private $config;
    private $pdo;
    private $openAiClient;
    private $arrAccessInfo = [];
    private $detectedFilters = [];
    private $entityCache = null;                 // request-level entity cache
    private static $fileCacheTtl = 600;          // 10-minute file cache TTL (seconds)

    public function __construct($pdo, $arrAccessInfo = [])
    {
        $this->pdo = $pdo;
        $this->arrAccessInfo = $arrAccessInfo;
        $this->loadConfiguration();
        $this->queryBuilder = new QueryBuilder($this->config, $pdo);
        $this->initializeOpenAi();
    }

    /**
     * Load JSON configuration
     */
    private function loadConfiguration()
    {
        $configPath = __DIR__ . '/../config/ai-insights-queries.json';

        if (!file_exists($configPath)) {
            throw new \Exception("Configuration file not found: $configPath");
        }

        $this->config = json_decode(file_get_contents($configPath), true);

        if ($this->config === null) {
            throw new \Exception("Failed to parse configuration file: " . json_last_error_msg());
        }
    }

    /**
     * Initialize OpenAI client
     */
    private function initializeOpenAi()
    {
        // Check for PHP constant first (from stdsettings), then environment variable
        $apiKey = null;
        if (defined('OPENAI_API_KEY')) {
            $apiKey = OPENAI_API_KEY;
        } else {
            $apiKey = getenv('OPENAI_API_KEY');
        }

        if (!$apiKey) {
            throw new \Exception("OPENAI_API_KEY not configured. Check stdsettings.inc.php or environment variables.");
        }

        // Store API key for use in callOpenAi method
        $this->openAiApiKey = $apiKey;
    }

    /**
     * Load entity list once per request; serve subsequent calls from memory.
     * Also persists a 10-minute file cache so repeated requests skip the remote DB query.
     */
    private function loadEntityCache(): array
    {
        // Request-level: already loaded this request
        if ($this->entityCache !== null) {
            return $this->entityCache;
        }

        // File-level: check temp cache
        $cacheFile = sys_get_temp_dir() . '/aria_entity_cache.json';
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < self::$fileCacheTtl) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if (is_array($cached)) {
                $this->entityCache = $cached;
                return $this->entityCache;
            }
        }

        // Fetch from DB (one query, shared by spell correction + entity detection)
        $sql = "SELECT 'product'     AS entity_type, product_name AS entity_value
                    FROM tblbranch_pickupstock_products
                    WHERE dstatus = 0 AND json_id = 99 AND team_type = 0 AND product_name IS NOT NULL AND product_name != ''
                UNION ALL
                SELECT 'category', category_name FROM tblbranch_pickupstock_products
                    WHERE dstatus = 0 AND json_id = 99 AND team_type = 0 AND category_name IS NOT NULL AND category_name != ''
                UNION ALL
                SELECT 'district',    district    FROM tblbranch WHERE dstatus = 0 AND district    IS NOT NULL AND district    != ''
                UNION ALL
                SELECT 'main_branch', main_branch FROM tblbranch WHERE dstatus = 0 AND main_branch IS NOT NULL AND main_branch != ''
                UNION ALL
                SELECT 'region',      branch_name FROM tblbranch WHERE dstatus = 0 AND branch_name IS NOT NULL AND branch_name != ''
                UNION ALL
                SELECT 'wd_code',   wd_code   FROM tblproject_team WHERE dstatus = 0 AND is_type = 0 AND s_id = 99 AND wd_code   IS NOT NULL AND wd_code   != ''
                UNION ALL
                SELECT 'circle',    circle    FROM tblproject_team WHERE dstatus = 0 AND is_type = 0 AND s_id = 99 AND circle    IS NOT NULL AND circle    != ''
                UNION ALL
                SELECT 'section',   section   FROM tblproject_team WHERE dstatus = 0 AND is_type = 0 AND s_id = 99 AND section   IS NOT NULL AND section   != ''
                UNION ALL
                SELECT 'ds_name',   team_name FROM tblproject_team WHERE dstatus = 0 AND is_type = 0 AND s_id = 99 AND team_name IS NOT NULL AND team_name != ''";

        try {
            $rows = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->entityCache = [];
            return $this->entityCache;
        }

        // Group by type
        $entities = [];
        foreach ($rows as $row) {
            if (!empty($row['entity_value'])) {
                $entities[$row['entity_type']][] = $row['entity_value'];
            }
        }

        // Write file cache (silently ignore write failures)
        @file_put_contents($cacheFile, json_encode($entities), LOCK_EX);

        $this->entityCache = $entities;
        return $this->entityCache;
    }

    /**
     * Main entry point: Get insights from natural language query
     */
    public function getInsights($query, $filters = [])
    {
        try {
            // Step 0: Apply spell correction to query (typos in entity names)
            $query = $this->applySpellCorrection($query);

            // Step 0b: Off-topic / small talk — respond with a friendly redirect
            $offTopic = $this->detectOffTopicQuery($query);
            if ($offTopic !== null) {
                return $offTopic;
            }

            // Step 1: Detect which query type matches the natural language
            $matchResult = $this->queryBuilder->findQueryByKeywordsWithScores($query);
            $matchedQueryName = $matchResult['query'];

            if (!$matchedQueryName) {
                return [
                    'success' => false,
                    'message' => 'Could not understand your query. Try asking about: products, DS performance, regions, districts, branches, circles, trends, WD codes, or ask for an executive summary.',
                    'suggestions' => [
                        'Which products are selling best?',
                        'How is each DS performing?',
                        'Which region has best qualified attendance?',
                        'Compare performance across circles',
                        'Show sales trends over time',
                        'Top WD codes by sales',
                        'Compare this month vs last month',
                        'Which day has highest sales?',
                        'Which regions are declining?',
                        'Scorecard for EGAU',
                        'Give me an executive summary'
                    ]
                ];
            }

            // Step 2: Extract filters from natural language (includes smart detection + ACL)
            $extractedFilters = $this->extractFiltersFromQuery($query, $filters);

            // Step 2a: Apply clear-intent routing overrides BEFORE ambiguity check,
            // so queries with unambiguous keywords are never caught by disambiguation.
            $queryLower = strtolower($query);

            // Breeze override: any mention of "breeze", "RMD", "stockist DS", "MDO"
            if (preg_match('/\bbreeze\b/i', $queryLower)
                || preg_match('/\bRMD\b/i', $queryLower)
                || preg_match('/\bstockist\s*ds\b/i', $queryLower)
                || preg_match('/\bMDO\b/i', $queryLower)
            ) {
                $matchedQueryName = 'breeze_insights';
                $matchResult['isAmbiguous'] = false;
            }

            // Anomaly override: "anomaly", "anomalies", or "unusual pattern(s)" → anomaly_detection
            if (preg_match('/\banomal(?:y|ies)\b/i', $queryLower)
                || preg_match('/\bunusual\s+(pattern|patterns|trend|trends|activity|activities)\b/i', $queryLower)
            ) {
                $matchedQueryName = 'anomaly_detection';
                $matchResult['isAmbiguous'] = false;
            }

            // "Most productive DS" → DS performance
            if (preg_match('/\bproductive\b/i', $queryLower) && preg_match('/\bds\b/i', $queryLower)) {
                $matchedQueryName = 'ds_performance';
                $matchResult['isAmbiguous'] = false;
            }

            // Month vs month: "compare january 2026 vs february 2026" → period_comparison
            if (!empty($extractedFilters['period_comparison_month_vs_month'])) {
                $matchedQueryName = 'period_comparison';
                $matchResult['isAmbiguous'] = false;
            }

            // Scorecard override: any explicit "scorecard" intent bypasses ambiguity check.
            // The detailed DS vs hierarchy routing happens later (lines 232+) after filters are applied.
            if (preg_match('/\bscore\s*card\b/i', $queryLower)
                || preg_match('/\bprofile\s+(?:of|for)\b/i', $queryLower)
                || preg_match('/\breport\s+card\b/i', $queryLower)
            ) {
                $matchResult['isAmbiguous'] = false;
            }

            // Step 2b: Check for ambiguity - return clarifying questions instead of guessing
            $ambiguityCheck = $this->checkAmbiguityAndGetClarifyingQuestions($query, $matchResult, $extractedFilters);
            if ($ambiguityCheck !== null) {
                return $ambiguityCheck;
            }

            $hasComparisonIntent = (strpos($queryLower, 'compare') !== false ||
                strpos($queryLower, 'vs') !== false || strpos($queryLower, 'versus') !== false ||
                strpos($queryLower, 'difference') !== false || strpos($queryLower, 'this month and last') !== false ||
                strpos($queryLower, 'last month') !== false);
            $hasFocusBrandIntent = (strpos($queryLower, 'focus brand') !== false ||
                strpos($queryLower, 'focus product') !== false || strpos($queryLower, 'focusbrand') !== false ||
                strpos($queryLower, 'priority brand') !== false || strpos($queryLower, 'focus sku') !== false ||
                strpos($queryLower, 'hero sku') !== false || strpos($queryLower, 'flagship') !== false);

            // Scorecard routing: detect "scorecard" intent for any hierarchy level
            $hasScorecardIntent = (strpos($queryLower, 'scorecard') !== false ||
                strpos($queryLower, 'score card') !== false || strpos($queryLower, 'profile') !== false ||
                strpos($queryLower, 'report card') !== false || strpos($queryLower, 'performance card') !== false);

            // DS scorecard: only when a specific DS name is detected AND the query isn't
            // a generic list query or a query type that should never become a DS scorecard.
            // Non-DS-scorecard query types (product/sales/geo/etc.) must NOT be overridden.
            $neverDsScorecardTypes = [
                'product_sales', 'category_sales', 'product_comparison', 'focus_brand_analysis',
                'outlet_coverage', 'geographic_heatmap', 'anomaly_detection', 'growth_decline',
                'period_comparison', 'executive_summary', 'daily_sales_trend', 'time_productivity',
                'route_analysis', 'inventory_analysis', 'outlet_sales', 'active_ds_count',
                'branch_qualified_attendance', 'circle_performance', 'district_performance',
                'section_performance', 'branch_performance', 'region_performance', 'day_of_week',
                'wd_code_performance',
            ];
            $isGenericDsListQuery = in_array($matchedQueryName, ['ds_performance']) &&
                (strpos($queryLower, 'best') !== false || strpos($queryLower, 'worst') !== false ||
                 strpos($queryLower, 'top') !== false || strpos($queryLower, 'bottom') !== false);
            // Also protect explicit product/location intent from DS scorecard hijack
            $hasExplicitProductIntent = preg_match(
                '/\b(best|top|worst|bottom|highest|lowest)\s+(selling\s+)?(product|brand|sku|item)\b/i',
                $queryLower
            ) || preg_match('/\bproduct\s+(sales|ranking|analysis|performance|breakdown|report|wise)\b/i', $queryLower);
            if (!empty($extractedFilters['ds_name'])
                && !$isGenericDsListQuery
                && !$hasExplicitProductIntent
                && !in_array($matchedQueryName, $neverDsScorecardTypes)
            ) {
                $matchedQueryName = 'ds_scorecard';
            }

            // Hierarchy-level scorecard routing (from most specific to least).
            // Run regardless of whether keyword matching already picked ds_scorecard,
            // because hierarchy entities (region/branch/district/circle/section) take precedence.
            if ($hasScorecardIntent) {
                if (!empty($extractedFilters['wd_code'])) {
                    $matchedQueryName = 'hierarchy_scorecard';
                    $extractedFilters['scorecard_level'] = 'wd_code';
                } elseif (!empty($extractedFilters['section'])) {
                    $matchedQueryName = 'hierarchy_scorecard';
                    $extractedFilters['scorecard_level'] = 'section';
                } elseif (!empty($extractedFilters['circle'])) {
                    $matchedQueryName = 'hierarchy_scorecard';
                    $extractedFilters['scorecard_level'] = 'circle';
                } elseif (!empty($extractedFilters['region'])) {
                    $matchedQueryName = 'hierarchy_scorecard';
                    $extractedFilters['scorecard_level'] = 'region';
                } elseif (!empty($extractedFilters['main_branch'])) {
                    $matchedQueryName = 'hierarchy_scorecard';
                    $extractedFilters['scorecard_level'] = 'branch';
                } elseif (!empty($extractedFilters['district'])) {
                    $matchedQueryName = 'hierarchy_scorecard';
                    $extractedFilters['scorecard_level'] = 'district';
                }
                // If no hierarchy entity found, leave matchedQueryName as-is (ds_scorecard or default)
            }

            if ($hasFocusBrandIntent) {
                $matchedQueryName = 'focus_brand_analysis';
            } elseif (!empty($extractedFilters['product']) && $hasComparisonIntent) {
                $matchedQueryName = 'product_comparison';
            } elseif (!empty($extractedFilters['category']) &&
                in_array($matchedQueryName, ['product_sales', 'executive_summary', 'period_comparison'])) {
                if ($hasComparisonIntent) {
                    $matchedQueryName = 'product_comparison';
                } else {
                    $matchedQueryName = 'category_sales';
                }
            }

            // Entity vs Entity: "Bihar vs UP East" or "NLUC vs EGAU" — one view, two scorecards
            if (preg_match('/^(.+?)\s+vs\.?\s+(.+)$/i', trim($query), $vsMatch)) {
                $leftRaw = trim($vsMatch[1]);
                $rightRaw = trim($vsMatch[2]);
                $isPeriodVs = preg_match('/this\s+month|last\s+month|previous\s+period|current\s+period/i', $leftRaw . ' ' . $rightRaw);
                if (!$isPeriodVs && strlen($leftRaw) >= 2 && strlen($rightRaw) >= 2) {
                    $resolved = $this->resolveEntityComparisonPair($leftRaw, $rightRaw);
                    if ($resolved) {
                        $matchedQueryName = 'entity_comparison';
                        $extractedFilters['comparison_left'] = $resolved['left'];
                        $extractedFilters['comparison_right'] = $resolved['right'];
                        $extractedFilters['comparison_level'] = $resolved['level'];
                    }
                }
            }

            // Dedicated handlers for complex query types
            $specialHandlers = [
                'entity_comparison'            => 'handleEntityComparison',
                'active_ds_count'             => 'handleActiveDsCount',
                'product_sales'               => 'handleProductSales',
                'ds_performance'              => 'handleDsPerformance',
                'wd_code_performance'         => 'handleWdCodePerformance',
                'period_comparison'           => 'handlePeriodComparison',
                'day_of_week'                 => 'handleDayOfWeek',
                'growth_decline'              => 'handleGrowthDecline',
                'executive_summary'           => 'handleExecutiveSummary',
                'anomaly_detection'           => 'handleAnomalyDetection',
                'branch_qualified_attendance' => 'handleBranchQualifiedAttendance',
                'circle_performance'          => 'handleDimensionPerformance',
                'district_performance'        => 'handleDimensionPerformance',
                'section_performance'         => 'handleDimensionPerformance',
                'branch_performance'          => 'handleDimensionPerformance',
                'region_performance'          => 'handleDimensionPerformance',
                'daily_sales_trend'           => 'handleDailySalesTrend',
                'outlet_coverage'             => 'handleOutletCoverage',
                'time_productivity'           => 'handleTimeProductivity',
                'route_analysis'              => 'handleRouteAnalysis',
                'inventory_analysis'          => 'handleInventoryAnalysis',
                'category_sales'              => 'handleCategorySales',
                'product_comparison'          => 'handleProductComparison',
                'focus_brand_analysis'        => 'handleFocusBrandAnalysis',
                'ds_scorecard'                => 'handleDsScorecard',
                'hierarchy_scorecard'         => 'handleHierarchyScorecard',
                'outlet_sales'                => 'handleOutletSales',
                'geographic_heatmap'          => 'handleGeographicHeatmap',
                'breeze_insights'             => 'handleBreezeInsights',
                'ds_leaderboard'             => 'handleDsLeaderboard',
                'outlet_visit_frequency'     => 'handleOutletVisitFrequency',
            ];

            if (isset($specialHandlers[$matchedQueryName])) {
                $handler = $specialHandlers[$matchedQueryName];
                $result = $this->$handler($extractedFilters, $query);

                return [
                    'success' => true,
                    'query_type' => $matchedQueryName,
                    'query_name' => $result['query_name'] ?? ($this->queryBuilder->getQueryConfig($matchedQueryName)['name'] ?? $matchedQueryName),
                    'data' => $result,
                    'ai_text' => $result['ai_text'] ?? '',
                    'filters' => $extractedFilters,
                    'detected_filters' => $this->detectedFilters,
                    'date_range' => $this->getDateRange($extractedFilters),
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }

            // Generic handler: Execute via QueryBuilder
            $data = $this->queryBuilder->execute($matchedQueryName, $extractedFilters);
            $formattedData = $this->formatQueryResults($matchedQueryName, $data);
            $aiText = $this->generateAiInsights($matchedQueryName, $query, $formattedData, $extractedFilters);

            return [
                'success' => true,
                'query_type' => $matchedQueryName,
                'query_name' => $this->queryBuilder->getQueryConfig($matchedQueryName)['name'],
                'data' => $formattedData,
                'ai_text' => $aiText,
                'filters' => $extractedFilters,
                'detected_filters' => $this->detectedFilters,
                'date_range' => $this->getDateRange($extractedFilters),
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            error_log("AI Insights Error: " . $e->getMessage() . " | File: " . $e->getFile() . ":" . $e->getLine());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'An error occurred while processing your query.'
            ];
        }
    }

    /**
     * Extract filters from natural language query
     * Combines all entity detection into a single query for efficiency
     */
    private function extractFiltersFromQuery($query, $providedFilters = [])
    {
        $filters = $providedFilters;
        $queryLower = strtolower($query);
        $this->detectedFilters = [];

        // ACL: inject user_teams from access info
        if (isset($this->arrAccessInfo['user_teams']) && !empty($this->arrAccessInfo['user_teams'])) {
            $filters['user_teams'] = $this->arrAccessInfo['user_teams'];
        }

        // Combined entity detection: single query fetches all entity types at once
        try {
            $this->detectAllEntities($queryLower, $filters);
        } catch (\Exception $e) {
            error_log("Filter detection error: " . $e->getMessage());
        }

        // High-level geographic zones like "south of india" or "north india"
        $zoneInfo = $this->detectGeographicZone($queryLower);
        if ($zoneInfo) {
            $filters['zone'] = $zoneInfo['zone'];
            $filters['main_branch'] = $zoneInfo['main_branches'];
            $this->detectedFilters[] = "Zone: " . $zoneInfo['label'];
        }

        // Month-vs-month: "compare january 2026 vs february 2026" → use later month as current so period comparison shows both
        $monthVsMonth = $this->parseMonthVsMonthInQuery($queryLower);
        if ($monthVsMonth) {
            $filters['date_from'] = $monthVsMonth['from'];
            $filters['date_to'] = $monthVsMonth['to'];
            $filters['period_comparison_month_vs_month'] = true;
            $this->detectedFilters[] = 'Date: ' . $monthVsMonth['label'];
        } else {
            // Smart date parsing from natural language (before default fallback)
            $parsedDates = $this->parseDateFromQuery($queryLower);
            if ($parsedDates) {
                $filters['date_from'] = $parsedDates['from'];
                $filters['date_to'] = $parsedDates['to'];
                $this->detectedFilters[] = "Date: " . $parsedDates['label'];
            }
        }

        // Default date range: last 30 days
        if (!isset($filters['date_from'])) {
            $filters['date_from'] = date('Y-m-d', strtotime('-30 days'));
            $filters['date_to'] = date('Y-m-d');
        }

        return $filters;
    }

    /**
     * Detect all entities in one pass.
     * Hierarchy: District → Branch (main_branch) → Region (branch_name) → Circle → Section → WD Code → DS
     */
    private function detectAllEntities($queryLower, &$filters)
    {
        $entities = $this->loadEntityCache();
        if (empty($entities)) return;

        // 1. Product detection (longest match)
        if (!isset($filters['product']) && !empty($entities['product'])) {
            $product = $this->findBestMatch($queryLower, $entities['product']);
            if ($product) {
                $filters['product'] = $product;
                $this->detectedFilters[] = "Product: $product";
            }
        }

        // 1b. Category detection (longest match)
        if (!isset($filters['category']) && !empty($entities['category'])) {
            $category = $this->findBestMatch($queryLower, $entities['category']);
            if ($category) {
                $filters['category'] = $category;
                $this->detectedFilters[] = "Category: $category";
            }
        }

        // 2. District detection
        if (!isset($filters['district']) && !empty($entities['district'])) {
            $district = $this->findBestMatch($queryLower, $entities['district']);
            if ($district) {
                $filters['district'] = [$district];
                $this->detectedFilters[] = "District: $district";
            }
        }

        // 3. Branch detection (main_branch from tblbranch, e.g. EGAU, NLUC)
        if (!isset($filters['main_branch']) && !empty($entities['main_branch'])) {
            $mainBranch = $this->findBestMatch($queryLower, $entities['main_branch']);
            if ($mainBranch) {
                $filters['main_branch'] = $mainBranch;
                $this->detectedFilters[] = "Branch: $mainBranch";
            }
        }

        // 4. Region detection (branch_name from tblbranch, e.g. UP East, Bihar)
        if (!isset($filters['region']) && !empty($entities['region'])) {
            $regions = [];
            foreach ($entities['region'] as $region) {
                if (!empty($region) && strpos($queryLower, strtolower($region)) !== false) {
                    $regions[] = $region;
                }
            }
            if (!empty($regions)) {
                $filters['region'] = $regions;
                try {
                    $stmt2 = $this->pdo->prepare("SELECT branch_id FROM tblbranch WHERE dstatus = 0 AND LOWER(branch_name) = :name LIMIT 1");
                    $stmt2->execute([':name' => strtolower(trim($regions[0]))]);
                    $branchRow = $stmt2->fetch(\PDO::FETCH_ASSOC);
                    if ($branchRow) {
                        $filters['branch_id'] = intval($branchRow['branch_id']);
                        $filters['branch_name'] = $regions[0];
                    }
                } catch (\Exception $e) {}
                $this->detectedFilters[] = "Region: " . implode(', ', $regions);
            }
        }

        // 5. WD Code detection
        if (!isset($filters['wd_code']) && !empty($entities['wd_code'])) {
            $wdCode = $this->findBestMatch($queryLower, $entities['wd_code']);
            if ($wdCode) {
                $filters['wd_code'] = [$wdCode];
                $this->detectedFilters[] = "WD Code: $wdCode";
            }
        }

        // 6. Circle detection
        if (!isset($filters['circle']) && !empty($entities['circle'])) {
            $circle = $this->findBestMatch($queryLower, $entities['circle']);
            if ($circle) {
                $filters['circle'] = [$circle];
                $this->detectedFilters[] = "Circle: $circle";
            }
        }

        // 7. Section detection
        if (!isset($filters['section']) && !empty($entities['section'])) {
            $section = $this->findBestMatch($queryLower, $entities['section']);
            if ($section) {
                $filters['section'] = [$section];
                $this->detectedFilters[] = "Section: $section";
            }
        }

        // 8. DS name detection (for scorecard)
        // Skip when query is clearly about products/sales/metrics, not a specific DS person.
        // Also skip "best/top DS" list queries so "Test" doesn't match inside "best".
        $isGenericDsListIntent = preg_match('/\b(best|top|worst|bottom)\s+ds\b/i', $queryLower);
        $isProductMetricQuery  = preg_match('/\b(product|sku|brand|item|selling|coverage|outlet|revenue|sales|category|growth|anomal|trend)\b/i', $queryLower)
            && !preg_match('/\bds\b/i', $queryLower);
        if (!isset($filters['ds_name']) && !empty($entities['ds_name'])
            && !$isGenericDsListIntent && !$isProductMetricQuery
        ) {
            $dsName = $this->findBestMatch($queryLower, $entities['ds_name']);
            if ($dsName && strlen($dsName) >= 3) {
                $filters['ds_name'] = $dsName;
                $this->detectedFilters[] = "DS: $dsName";
            }
        }
    }

    /**
     * Detect broad geographic zones (North/East/West/South India) from query text
     * and map them to main_branch codes as defined in tblbranch.
     */
    private function detectGeographicZone($queryLower)
    {
        // Normalize common India / zone phrases
        $zones = [
            'south' => [
                'keywords' => [
                    'south of india', 'south india', 'southern india',
                    'south zone', 'southern zone', 'south region', 'southern region'
                ],
                // South distribution main_branch codes (from tblbranch)
                'main_branches' => ['SRAY', 'SBLR', 'SCHE', 'SCOI', 'SERN', 'SHYD', 'SKAR'],
                'label' => 'South India'
            ],
            'north' => [
                'keywords' => [
                    'north of india', 'north india', 'northern india',
                    'north zone', 'northern zone', 'north region', 'northern region'
                ],
                // North distribution approx main_branch codes
                'main_branches' => ['NEUP', 'NSAH', 'NCHA', 'NLUC', 'NJPR', 'NDEL', 'NJAM', 'PILOT'],
                'label' => 'North India'
            ],
            'east' => [
                'keywords' => [
                    'east of india', 'east india', 'eastern india',
                    'east zone', 'eastern zone', 'east region', 'eastern region'
                ],
                'main_branches' => ['EGAU', 'EPAT', 'EBEN', 'ECAL', 'EVIZ', 'EORI'],
                'label' => 'East India'
            ],
            'west' => [
                'keywords' => [
                    'west of india', 'west india', 'western india',
                    'west zone', 'western zone', 'west region', 'western region'
                ],
                // West distribution main_branch codes (examples from tblbranch)
                'main_branches' => ['WPUN', 'WNAG', 'WBHO', 'WAHM', 'WMUM', 'WPUN-SWD', 'WNAG-SWD'],
                'label' => 'West India'
            ],
        ];

        foreach ($zones as $zoneKey => $cfg) {
            foreach ($cfg['keywords'] as $kw) {
                if (strpos($queryLower, $kw) !== false) {
                    return [
                        'zone' => $zoneKey,
                        'label' => $cfg['label'],
                        'main_branches' => $cfg['main_branches'],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Detect product name from query using DB lookup (longest match wins)
     */
    private function detectProductFromQuery($queryLower)
    {
        try {
            $stmt = $this->pdo->query("SELECT DISTINCT product_name FROM tblbranch_pickupstock_products WHERE dstatus = 0 AND json_id = 99 AND team_type = 0 AND product_name IS NOT NULL AND product_name != ''");
            if (!$stmt) return null;
            $products = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            return $this->findBestMatch($queryLower, $products);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generic entity detection from DB - find longest matching value in query text
     */
    private function detectEntityFromDb($sql, $queryLower)
    {
        try {
            $stmt = $this->pdo->query($sql);
            if (!$stmt) return null;
            $values = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            return $this->findBestMatch($queryLower, $values);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Detect off-topic / small-talk queries (greetings, personal questions, etc.).
     * Returns a redirect response array, or null if the query is on-topic.
     */
    private function detectOffTopicQuery($query)
    {
        $q = trim($query);
        if ($q === '') {
            return null;
        }
        $lower = strtolower($q);
        $words = preg_split('/\s+/', $lower, -1, PREG_SPLIT_NO_EMPTY);
        $wordCount = count($words);

        // Patterns that indicate casual / non-analytics questions
        $offTopicPatterns = [
            // Greetings
            '/^(hi|hello|hey|good\s+(morning|afternoon|evening|night)|howdy|yo)\s*[!.]?$/i',
            '/^(hi|hello|hey)\s+(there|all|team|guys)?\s*[!.]?$/i',
            // How are you / small talk
            '/^how\s+are\s+you(\s|$)/i',
            '/^how\s+do\s+you\s+do\s*[?.]?$/i',
            '/^how\s+is\s+it\s+going\s*[?.]?$/i',
            '/^what(\'s|\s+is)\s+up\s*[?.]?$/i',
            '/^what(\'s|\s+is)\s+new\s*[?.]?$/i',
            '/^how\s+have\s+you\s+been\s*[?.]?$/i',
            // Personal / lunch / food
            '/^(have\s+you\s+)?(done|had)\s+(lunch|breakfast|dinner|food)/i',
            '/^did\s+you\s+(eat|have)\s+(lunch|breakfast|dinner)/i',
            '/^are\s+you\s+(hungry|free|busy)\s*[?.]?$/i',
            '/^(let\'?s?|shall\s+we)\s+(go\s+for\s+)?(lunch|coffee|breakfast)/i',
            // Thanks / bye
            '/^(thank\s+you|thanks|thankyou)\s*[!.]?$/i',
            '/^(bye|goodbye|see\s+you|later)\s*[!.]?$/i',
            // Meta / chatbot
            '/^what(\'s|\s+is)\s+your\s+name\s*[?.]?$/i',
            '/^who\s+are\s+you\s*[?.]?$/i',
            '/^tell\s+me\s+a\s+joke\s*[?.]?$/i',
            '/^can\s+you\s+(talk|chat)\s*[?.]?$/i',
        ];

        foreach ($offTopicPatterns as $pattern) {
            if (preg_match($pattern, $lower)) {
                return $this->offTopicRedirectResponse();
            }
        }

        // Short generic questions that don't look like analytics (2–6 words, no business keywords)
        $businessKeywords = ['sales', 'product', 'ds', 'region', 'branch', 'district', 'trend', 'score', 'performance',
            'compare', 'top', 'worst', 'best', 'anomal', 'summary', 'attendance', 'qualif', 'outlet', 'route', 'wd'];
        $hasBusiness = false;
        foreach ($businessKeywords as $kw) {
            if (strpos($lower, $kw) !== false) {
                $hasBusiness = true;
                break;
            }
        }
        if ($wordCount <= 6 && !$hasBusiness) {
            $genericQuestion = preg_match('/^(how|what|when|where|why|who|is|are|can|do|did|have|has)\s+/i', $lower)
                && !preg_match('/\b(sales|product|region|branch|district|ds|trend|performance|compare|top|best|worst)\b/i', $lower);
            if ($genericQuestion) {
                return $this->offTopicRedirectResponse();
            }
        }

        return null;
    }

    /**
     * Standard response when user asks an off-topic question — friendly redirect + suggestions.
     */
    private function offTopicRedirectResponse()
    {
        return [
            'success' => false,
            'clarifying_questions' => true,
            'off_topic' => true,
            'message' => 'I\'m here to help with sales and performance insights — things like top products, DS performance, regions, and trends. I can\'t chat about lunch or the weather, but try one of these:',
            'suggestions' => [
                'Top selling products this month',
                'Best performing DS',
                'Compare this month vs last month',
                'Any anomalies or unusual patterns?',
                'Give me an executive summary',
            ]
        ];
    }

    /**
     * Resolve "X vs Y" to two known entities (region or branch). Returns null if either unknown.
     */
    private function resolveEntityComparisonPair($leftRaw, $rightRaw)
    {
        $left = trim($leftRaw);
        $right = trim($rightRaw);
        if ($left === '' || $right === '') {
            return null;
        }
        $leftLower = strtolower($left);
        $rightLower = strtolower($right);

        // Try region (branch_name)
        $stmt = $this->pdo->query("SELECT DISTINCT branch_name FROM tblbranch WHERE dstatus = 0 AND branch_name IS NOT NULL AND branch_name != ''");
        if ($stmt) {
            $regions = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $foundLeft = null;
            $foundRight = null;
            foreach ($regions as $r) {
                if (strtolower($r) === $leftLower) $foundLeft = $r;
                if (strtolower($r) === $rightLower) $foundRight = $r;
            }
            if ($foundLeft && $foundRight && $foundLeft !== $foundRight) {
                return ['left' => $foundLeft, 'right' => $foundRight, 'level' => 'region'];
            }
        }

        // Try branch (main_branch)
        $stmt = $this->pdo->query("SELECT DISTINCT main_branch FROM tblbranch WHERE dstatus = 0 AND main_branch IS NOT NULL AND main_branch != ''");
        if ($stmt) {
            $branches = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $foundLeft = null;
            $foundRight = null;
            foreach ($branches as $b) {
                if (strtolower($b) === $leftLower) $foundLeft = $b;
                if (strtolower($b) === $rightLower) $foundRight = $b;
            }
            if ($foundLeft && $foundRight && $foundLeft !== $foundRight) {
                return ['left' => $foundLeft, 'right' => $foundRight, 'level' => 'branch'];
            }
        }

        return null;
    }

    /**
     * Handler: Compare two entities (e.g. Bihar vs UP East) — side-by-side scorecards.
     */
    private function handleEntityComparison($filters, $queryText = '')
    {
        $leftName = $filters['comparison_left'] ?? '';
        $rightName = $filters['comparison_right'] ?? '';
        $level = $filters['comparison_level'] ?? 'region';
        if ($leftName === '' || $rightName === '') {
            return [
                'query_name' => 'Entity Comparison',
                'record_count' => 0,
                'records' => [],
                'metrics' => [],
                'ai_text' => 'Could not resolve both entities. Try e.g. "Bihar vs UP East" or "NLUC vs EGAU".',
                'comparison_left' => null,
                'comparison_right' => null,
            ];
        }

        $filtersLeft = $filters;
        $filtersRight = $filters;
        if ($level === 'region') {
            $filtersLeft['region'] = [$leftName];
            $filtersRight['region'] = [$rightName];
            $filtersLeft['scorecard_level'] = 'region';
            $filtersRight['scorecard_level'] = 'region';
        } else {
            $filtersLeft['main_branch'] = $leftName;
            $filtersRight['main_branch'] = $rightName;
            $filtersLeft['scorecard_level'] = 'branch';
            $filtersRight['scorecard_level'] = 'branch';
        }

        $resultLeft = $this->handleHierarchyScorecard($filtersLeft, $queryText);
        $resultRight = $this->handleHierarchyScorecard($filtersRight, $queryText);

        $cardLeft = $resultLeft['records'][0] ?? null;
        $cardRight = $resultRight['records'][0] ?? null;

        $aiContext = "Comparison: $leftName vs $rightName\n"
            . "Left ($leftName): " . ($cardLeft ? "Sales {$cardLeft['totalSales']}, Qual {$cardLeft['qualificationRate']}%, DS {$cardLeft['totalDsCount']}" : "No data") . "\n"
            . "Right ($rightName): " . ($cardRight ? "Sales {$cardRight['totalSales']}, Qual {$cardRight['qualificationRate']}%, DS {$cardRight['totalDsCount']}" : "No data");
        $aiText = $this->callOpenAi(
            "User asked: \"$queryText\"\n"
            . "IMPORTANT: All sales figures are in UNITS (sticks/packs), not currency. Do not use \$ or any currency symbol.\n"
            . $aiContext . "\n\n"
            . "Provide a short comparison: (1) Which entity is ahead on sales and qualification, (2) One key difference, (3) One recommendation. Use #### headings."
        );

        return [
            'query_name' => "Compare: $leftName vs $rightName",
            'record_count' => 2,
            'records' => [],
            'comparison_left' => $cardLeft ? array_merge($cardLeft, ['entityName' => $leftName]) : null,
            'comparison_right' => $cardRight ? array_merge($cardRight, ['entityName' => $rightName]) : null,
            'comparison_level' => $level,
            'metrics' => [
                'total_sales' => ($cardLeft['totalSales'] ?? 0) + ($cardRight['totalSales'] ?? 0),
                'avg_daily_sales' => 0,
            ],
            'ai_text' => $aiText,
        ];
    }

    /**
     * Spell correction: fuzzy match against known entities when exact match fails.
     * Uses Levenshtein distance. Returns corrected query string.
     */
    private function applySpellCorrection($query)
    {
        $queryLower = strtolower(trim($query));
        $words = preg_split('/\s+/', $queryLower, -1, PREG_SPLIT_NO_EMPTY);
        $corrected = $query;
        $anyChange = false;

        $entityMap = $this->loadEntityCache();
        if (empty($entityMap)) return $query;

        // Flatten to a single list for fuzzy matching (branches, regions, districts, products)
        $spellCheckTypes = ['region', 'main_branch', 'district', 'product'];
        $allEntities = [];
        foreach ($spellCheckTypes as $t) {
            if (!empty($entityMap[$t])) {
                foreach ($entityMap[$t] as $v) {
                    if (strlen($v) >= 3) {
                        $allEntities[] = ['entity_type' => $t, 'entity_value' => $v];
                    }
                }
            }
        }

        // Common English intent/query words that must never be treated as entity typos
        $skipWords = [
            'best', 'top', 'worst', 'bottom', 'show', 'give', 'get', 'list', 'find',
            'all', 'any', 'the', 'and', 'for', 'with', 'from', 'this', 'that',
            'who', 'what', 'how', 'when', 'where', 'which', 'why',
            'performing', 'performance', 'performer', 'performers',
            'compare', 'comparison', 'versus', 'trend', 'trends', 'growth',
            'decline', 'declining', 'growing', 'analysis', 'summary', 'overview',
            'report', 'card', 'scorecard', 'sales', 'today', 'week', 'month', 'year',
            'high', 'low', 'most', 'least', 'more', 'less', 'new', 'old', 'active',
            'inactive', 'qualified', 'coverage', 'route', 'outlet', 'daily', 'weekly',
            'improving', 'dropping', 'increasing', 'decreasing', 'product', 'brand',
            'category', 'region', 'district', 'branch', 'circle', 'section', 'code',
        ];

        foreach ($words as $word) {
            if (strlen($word) < 3) continue;
            if (in_array(strtolower($word), $skipWords)) continue;
            $best = $this->findFuzzyMatch($word, array_column($allEntities, 'entity_value'), 2);
            if ($best !== null) {
                $corrected = preg_replace('/\b' . preg_quote($word, '/') . '\b/ui', $best, $corrected, 1);
                $anyChange = true;
            }
        }
        return $anyChange ? $corrected : $query;
    }

    /**
     * Fuzzy match: find closest candidate by Levenshtein distance within threshold.
     */
    private function findFuzzyMatch($input, $candidates, $maxDistance = 2)
    {
        $inputLen = strlen($input);
        if ($inputLen < 3) return null;
        $threshold = $inputLen <= 5 ? 1 : ($inputLen <= 8 ? 2 : 3);
        if ($maxDistance !== null) $threshold = min($threshold, $maxDistance);

        $best = null;
        $bestDist = PHP_INT_MAX;
        foreach ($candidates as $c) {
            if (empty($c) || strlen($c) < 3) continue;
            $dist = levenshtein($input, strtolower($c));
            if ($dist <= $threshold && $dist < $bestDist) {
                $bestDist = $dist;
                $best = $c;
            }
        }
        return $best;
    }

    /**
     * Check if query is ambiguous; if so, return response with clarifying questions.
     */
    private function checkAmbiguityAndGetClarifyingQuestions($query, $matchResult, $extractedFilters)
    {
        $isAmbiguous = $matchResult['isAmbiguous'] ?? false;
        $wordCount = count(preg_split('/\s+/', trim($query)));
        $hasEntity = !empty($extractedFilters['region']) || !empty($extractedFilters['district']) ||
            !empty($extractedFilters['main_branch']) || !empty($extractedFilters['product']) ||
            !empty($extractedFilters['category']);

        // When the user set explicit context filters via the UI, skip short-query disambiguation
        // (they already know what they want — the entity is intentional, not ambiguous)
        $contextResolved = !empty($extractedFilters['context_resolved']);
        // Queries with clear intent keywords should never be disambiguated
        $hasClearIntent = preg_match('/\bscore\s*card\b/i', $query)
            || preg_match('/\breport\s+card\b/i', $query)
            || preg_match('/\bbreeze\b/i', $query)
            || preg_match('/\banomal(?:y|ies)\b/i', $query)
            || preg_match('/\bsummary\b/i', $query)
            || preg_match('/\btrend\b/i', $query)
            || preg_match('/\bheatmap\b/i', $query);
        $needsDisambiguation = (!$contextResolved && $wordCount <= 3 && $hasEntity && !$hasClearIntent) || ($isAmbiguous && !$contextResolved);
        if (!$needsDisambiguation) return null;

        $entities = [];
        if (!empty($extractedFilters['region'])) $entities[] = is_array($extractedFilters['region']) ? implode(', ', $extractedFilters['region']) : $extractedFilters['region'];
        if (!empty($extractedFilters['district'])) $entities[] = is_array($extractedFilters['district']) ? implode(', ', $extractedFilters['district']) : $extractedFilters['district'];
        if (!empty($extractedFilters['main_branch'])) $entities[] = $extractedFilters['main_branch'];
        if (!empty($extractedFilters['product'])) $entities[] = $extractedFilters['product'];

        $questions = $this->queryBuilder->getClarifyingQuestions(
            $query,
            $matchResult['scores'] ?? [],
            $matchResult['topQuery'] ?? null,
            $matchResult['secondQuery'] ?? null,
            $entities
        );

        return [
            'success' => false,
            'clarifying_questions' => true,
            'message' => 'Your query could mean a few different things. Which of these are you looking for?',
            'suggestions' => $questions,
            'detected_filters' => $this->detectedFilters ?? []
        ];
    }

    /**
     * Find the longest matching substring from candidates in query text
     * Replicates old AiInsights.php findBestMatch() behavior
     */
    private function findBestMatch($queryLower, $candidates)
    {
        $bestMatch = null;
        $bestLen = 0;
        foreach ($candidates as $candidate) {
            if (empty($candidate)) continue;
            $candidateLower = strtolower(trim($candidate));
            // Only match candidates with 2+ chars to avoid false positives
            if (strlen($candidateLower) < 2) continue;
            // Use word-boundary matching to prevent "Test" matching inside "best",
            // "in" matching inside "selling", etc. (?<![a-z0-9]) and (?![a-z0-9])
            // act as portable word boundaries without requiring \b on special chars.
            $pattern = '/(?<![a-z0-9])' . preg_quote($candidateLower, '/') . '(?![a-z0-9])/u';
            if (preg_match($pattern, $queryLower)) {
                if (strlen($candidateLower) > $bestLen) {
                    $bestLen = strlen($candidateLower);
                    $bestMatch = $candidate; // return original casing
                }
            }
        }
        return $bestMatch;
    }

    /**
     * Handler: Active DS Count
     */
    private function handleActiveDsCount($filters, $queryText = '')
    {
        $countRow = $this->getActiveDsCount($filters);
        $totalDs = intval($countRow['total_ds'] ?? 0);

        // Determine the hierarchy level and build breakdown
        $breakdown = [];
        $breakdownLabel = '';
        $scope = '';
        $branchIds = $this->resolveBranchIds($filters);
        $branchIdCond = !empty($branchIds)
            ? " AND a.branch_id IN (" . implode(',', array_map('intval', $branchIds)) . ")"
            : '';
        $baseCond = "a.dstatus = 0 AND a.is_type = 0 AND a.s_id = 99" . $branchIdCond;

        if (!empty($filters['user_teams'])) {
            $teamList = $filters['user_teams'];
            if (is_string($teamList) && preg_match('/^\([\d,]+\)$/', $teamList)) {
                $baseCond .= " AND a.team_id IN $teamList";
            }
        }

        if (!empty($filters['wd_code'])) {
            $scope = is_array($filters['wd_code']) ? $filters['wd_code'][0] : $filters['wd_code'];
        } elseif (!empty($filters['section'])) {
            $scope = is_array($filters['section']) ? $filters['section'][0] : $filters['section'];
            $secVal = str_replace("'", "''", $scope);
            $breakdownLabel = 'WD Code';
            $sql = "SELECT a.wd_code AS name, COUNT(DISTINCT a.team_id) AS ds_count
                    FROM tblproject_team a WHERE $baseCond AND a.section = '$secVal'
                    AND a.wd_code IS NOT NULL AND a.wd_code != ''
                    GROUP BY a.wd_code ORDER BY ds_count DESC";
            $stmt = $this->pdo->query($sql);
            if ($stmt) $breakdown = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } elseif (!empty($filters['circle'])) {
            $scope = is_array($filters['circle']) ? $filters['circle'][0] : $filters['circle'];
            $cVal = str_replace("'", "''", $scope);
            $breakdownLabel = 'Section';
            $sql = "SELECT a.section AS name, COUNT(DISTINCT a.team_id) AS ds_count
                    FROM tblproject_team a WHERE $baseCond AND a.circle = '$cVal'
                    AND a.section IS NOT NULL AND a.section != ''
                    GROUP BY a.section ORDER BY ds_count DESC";
            $stmt = $this->pdo->query($sql);
            if ($stmt) $breakdown = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } elseif (!empty($filters['region'])) {
            $scope = is_array($filters['region']) ? $filters['region'][0] : $filters['region'];
            $breakdownLabel = 'Circle';
            $sql = "SELECT a.circle AS name, COUNT(DISTINCT a.team_id) AS ds_count
                    FROM tblproject_team a WHERE $baseCond
                    AND a.circle IS NOT NULL AND a.circle != ''
                    GROUP BY a.circle ORDER BY ds_count DESC";
            $stmt = $this->pdo->query($sql);
            if ($stmt) $breakdown = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } elseif (!empty($filters['main_branch'])) {
            $scope = is_array($filters['main_branch']) ? $filters['main_branch'][0] : $filters['main_branch'];
            $breakdownLabel = 'Region';
            $sql = "SELECT d.branch_name AS name, COUNT(DISTINCT a.team_id) AS ds_count
                    FROM tblproject_team a
                    INNER JOIN tblbranch d ON a.branch_id = d.branch_id
                    WHERE $baseCond
                    GROUP BY d.branch_name ORDER BY ds_count DESC";
            $stmt = $this->pdo->query($sql);
            if ($stmt) $breakdown = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } elseif (!empty($filters['district'])) {
            $scope = is_array($filters['district']) ? $filters['district'][0] : $filters['district'];
            $breakdownLabel = 'Branch';
            $sql = "SELECT d.main_branch AS name, COUNT(DISTINCT a.team_id) AS ds_count
                    FROM tblproject_team a
                    INNER JOIN tblbranch d ON a.branch_id = d.branch_id
                    WHERE $baseCond
                    GROUP BY d.main_branch ORDER BY ds_count DESC";
            $stmt = $this->pdo->query($sql);
            if ($stmt) $breakdown = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $breakdownLabel = 'District';
            $sql = "SELECT d.district AS name, COUNT(DISTINCT a.team_id) AS ds_count
                    FROM tblproject_team a
                    INNER JOIN tblbranch d ON a.branch_id = d.branch_id
                    WHERE $baseCond
                    GROUP BY d.district ORDER BY ds_count DESC";
            $stmt = $this->pdo->query($sql);
            if ($stmt) $breakdown = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        $breakdownFormatted = [];
        foreach ($breakdown as $b) {
            $breakdownFormatted[] = [
                'name' => $b['name'] ?? 'Unknown',
                'dsCount' => intval($b['ds_count'] ?? 0)
            ];
        }

        $aiText = $this->buildActiveDsSummary($countRow, $filters);
        if (!empty($breakdownFormatted)) {
            $aiText .= "\n\nBreakdown by {$breakdownLabel}:\n";
            foreach (array_slice($breakdownFormatted, 0, 10) as $item) {
                $aiText .= "- {$item['name']}: {$item['dsCount']} DS\n";
            }
        }

        return [
            'query_name' => 'Active DS Count',
            'description' => 'Count active DS within a region or filter',
            'record_count' => 1,
            'records' => [$countRow],
            'metrics' => ['total_ds' => $totalDs],
            'breakdown' => $breakdownFormatted,
            'breakdown_label' => $breakdownLabel,
            'scope' => $scope,
            'ai_text' => $aiText
        ];
    }

    /**
     * Handler: Product Sales
     */
    private function handleProductSales($filters, $queryText = '')
    {
        return $this->getProductSalesData($filters, $queryText);
    }

    /**
     * Handler: DS Performance
     */
    private function handleDsPerformance($filters, $queryText = '')
    {
        return $this->getDsPerformanceData($filters, $queryText);
    }

    /**
     * Get active DS count from tblproject_team
     */
    private function getActiveDsCount($filters)
    {
        $conditions = [
            'a.s_id = 99',
            'a.is_type = 0',
            'a.dstatus = 0'
        ];

        // Resolve branch_ids from hierarchy filters
        $branchIds = $this->resolveBranchIds($filters);
        if (!empty($branchIds)) {
            $idList = implode(',', array_map('intval', $branchIds));
            $conditions[] = "a.branch_id IN ($idList)";
        } elseif (!empty($filters['branch_id'])) {
            $conditions[] = 'a.branch_id = ' . intval($filters['branch_id']);
        }

        // ACL
        if (!empty($filters['user_teams'])) {
            $teamList = $filters['user_teams'];
            if (is_string($teamList) && preg_match('/^\([\d,]+\)$/', $teamList)) {
                $conditions[] = "a.team_id IN $teamList";
            }
        }

        // Circle filter
        if (!empty($filters['circle']) && is_array($filters['circle'])) {
            $names = implode(',', array_map(fn($v) => "'" . str_replace("'", "''", $v) . "'", $filters['circle']));
            $conditions[] = "a.circle IN ($names)";
        }
        // Section filter
        if (!empty($filters['section']) && is_array($filters['section'])) {
            $names = implode(',', array_map(fn($v) => "'" . str_replace("'", "''", $v) . "'", $filters['section']));
            $conditions[] = "a.section IN ($names)";
        }
        // WD Code filter
        if (!empty($filters['wd_code']) && is_array($filters['wd_code'])) {
            $codes = implode(',', array_map(fn($v) => "'" . str_replace("'", "''", $v) . "'", $filters['wd_code']));
            $conditions[] = "a.wd_code IN ($codes)";
        }

        $sql = 'SELECT COUNT(DISTINCT a.team_id) AS total_ds FROM tblproject_team a'
             . ' WHERE ' . implode(' AND ', $conditions);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ?: ['total_ds' => 0];
    }

    /**
     * Build a short summary for active DS count
     */
    private function buildActiveDsSummary($countRow, $filters)
    {
        $count = intval($countRow['total_ds'] ?? 0);
        $scope = '';

        if (!empty($filters['region'])) {
            $scope = ' in ' . (is_array($filters['region']) ? $filters['region'][0] : $filters['region']);
        } elseif (!empty($filters['branch_name'])) {
            $scope = ' in ' . $filters['branch_name'];
        } elseif (!empty($filters['branch_id'])) {
            $scope = ' (filtered)';
        }

        return "Active DS count{$scope}: {$count}.";
    }

    /**
     * Get product sales data using pivoted column schema
     * Products are stored as separate columns (total_sale_product1..78) in tblvands_summary,
     * with column-to-name mapping in tblbranch_pickupstock_products
     */
    private function getProductSalesData($filters, $queryText = '')
    {
        $startDate = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $filters['date_to'] ?? date('Y-m-d');
        $queryLower = strtolower($queryText);
        $showWorst = (strpos($queryLower, 'worst') !== false ||
                      strpos($queryLower, 'bottom') !== false ||
                      strpos($queryLower, 'lowest') !== false ||
                      strpos($queryLower, 'poor') !== false);

        $productFilter = $this->buildProductTableFilter($filters);

        $sql = "SELECT DISTINCT summary_column_name, product_name FROM tblbranch_pickupstock_products WHERE dstatus = 0" . $productFilter;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $productRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $productCol = [];
        $productColName = [];
        $seenCols = [];  // Track unique column names
        if (!empty($productRows)) {
            foreach ($productRows as $row) {
                $summaryCol = $row['summary_column_name'] ?? '';
                $productName = $row['product_name'] ?? '';
                if ($summaryCol && !isset($seenCols[$summaryCol])) {
                    $seenCols[$summaryCol] = true;
                    $productCol[] = $summaryCol;
                    $productColName[$summaryCol] = $productName;
                }
            }
        }

        if (empty($productCol)) {
            return [
                'query_name' => 'Product Sales Analysis',
                'description' => 'No product columns found for the selected filters.',
                'record_count' => 0,
                'records' => [],
                'metrics' => ['total_sales' => 0],
                'ai_text' => 'No product data found for the selected filters.'
            ];
        }

        // Step 3: Build WHERE conditions for main summary query
        $conditions = $this->buildBaseConditions($filters);
        $whereClause = implode(' AND ', $conditions);

        // Step 4: Get per-product sales (flat SELECT, no deep nesting)
        $sumSelect = [];
        foreach ($productCol as $col) {
            $sumSelect[] = "SUM(a.$col) AS `$col`";
        }

        $perProductQuery = "SELECT " . implode(', ', $sumSelect) . " FROM tblvands_summary a"
            . " INNER JOIN tblproject_team b ON a.team_id = b.team_id"
            . " INNER JOIN tblbranch d ON b.branch_id = d.branch_id"
            . " WHERE $whereClause";

        $stmt = $this->pdo->prepare($perProductQuery);
        $stmt->execute();
        $productRow = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Step 5: Build topProducts array, merging columns with the same product name
        // Derive grand total from actual product column sums (not total_sales_deliveries)
        $topProducts = [];
        $productTotals = [];
        $totalSales = 0;
        if ($productRow) {
            foreach ($productCol as $col) {
                $productTotal = floatval($productRow[$col] ?? 0);
                $productName = $productColName[$col] ?? $col;
                if (isset($productTotals[$productName])) {
                    $productTotals[$productName] += $productTotal;
                } else {
                    $productTotals[$productName] = $productTotal;
                }
            }

            $totalSales = round(array_sum($productTotals), 2);

            foreach ($productTotals as $productName => $productTotal) {
                $productTotal = round($productTotal, 2);
                if ($showWorst && $productTotal <= 0) {
                    continue;
                }
                $share = $totalSales > 0 ? round(($productTotal / $totalSales) * 100, 2) : 0;
                $topProducts[] = [
                    'productName' => $productName,
                    'totalSales' => $productTotal,
                    'sharePercent' => $share
                ];
            }

            // Sort by totalSales
            usort($topProducts, function($a, $b) use ($showWorst) {
                return $showWorst
                    ? ($a['totalSales'] <=> $b['totalSales'])
                    : ($b['totalSales'] <=> $a['totalSales']);
            });

            // Top 5
            $topProducts = array_slice($topProducts, 0, 5);
        }

        // Step 7: Calculate days in range
        $days = (strtotime($endDate) - strtotime($startDate)) / 86400 + 1;
        $avgDailySales = $days > 0 ? round($totalSales / $days, 2) : 0;

        // Build AI text summary with dynamic prompt based on best/worst context
        $aiText = '';
        try {
            $perspective = $showWorst ? 'worst/underperforming' : 'top/best-performing';
            $sectionLabels = $showWorst
                ? "Use headings: '#### 1. Worst Performers', '#### 2. Why They Underperform', '#### 3. Risk Assessment', '#### 4. Recommendations to Improve'"
                : "Use headings: '#### 1. Top Performers', '#### 2. Sales Concentration', '#### 3. Underperformers', '#### 4. Recommendations'";
            $context = "User asked: \"$queryText\"\n"
                . "IMPORTANT: All sales values are in UNITS (sticks/packs), not currency. Do not use \$ or any currency symbol.\n"
                . "Context: User is asking about the {$perspective} products.\n"
                . "Total Sales (units): $totalSales\nAvg Daily Sales (units): $avgDailySales\n"
                . "Products (sorted by {$perspective}):\n" . json_encode($topProducts, JSON_PRETTY_PRINT) . "\n\n"
                . "Analyze product performance from the {$perspective} perspective. {$sectionLabels}\n"
                . "Be specific with numbers and percentages. Keep each section concise (2-3 bullet points).";
            $aiText = $this->callOpenAi($context);
        } catch (\Exception $e) {
            $label = $showWorst ? "Worst product" : "Top product";
            $aiText = "$label: " . ($topProducts[0]['productName'] ?? 'N/A') . " with sales of " . ($topProducts[0]['totalSales'] ?? 0) . ".";
        }

        return [
            'query_name' => 'Product Sales Analysis',
            'description' => 'Product performance and sales breakdown',
            'record_count' => count($topProducts),
            'records' => $topProducts,
            'metrics' => [
                'total_sales' => round($totalSales, 2),
                'avg_daily_sales' => $avgDailySales,
                'unique_products' => count($topProducts)
            ],
            'ai_text' => $aiText,
            'show_worst' => $showWorst
        ];
    }

    /**
     * Get DS performance data
     * DS name, WD code come from tblproject_team (b), branch/district from tblbranch (d)
     * Sales use dynamic product columns from tblbranch_pickupstock_products
     */
    private function getDsPerformanceData($filters, $queryText = '')
    {
        $startDate = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $filters['date_to'] ?? date('Y-m-d');
        $queryLower = strtolower($queryText);
        $showWorst = (strpos($queryLower, 'worst') !== false ||
                      strpos($queryLower, 'bottom') !== false ||
                      strpos($queryLower, 'lowest') !== false ||
                      strpos($queryLower, 'poor') !== false);

        // Use the pre-computed total_sales_deliveries column directly
        // IMPORTANT: Avoid summing 78 individual product columns (SUM(col1)+SUM(col2)+...)
        // as it creates a deep expression tree that causes MariaDB thread stack overrun
        $salesExpr = 'SUM(a.total_sales_deliveries)';

        // Step 2: Build WHERE conditions (uses shared helper, includes ACL + all filters)
        $conditions = $this->buildBaseConditions($filters);
        $whereClause = implode(' AND ', $conditions);

        // Step 3: Build and execute DS performance query
        // Uses total_sales_deliveries (pre-computed) to avoid deep expression tree
        $sortDir = $showWorst ? 'ASC' : 'DESC';
        $dsQuery = "SELECT b.team_id, b.team_name, b.wd_code, d.branch_name, d.district,"
            . " SUM(CASE WHEN a.is_qualified = 1 THEN 1 ELSE 0 END) AS qualifiedDays,"
            . " COUNT(DISTINCT a.activity_date) AS totalDays,"
            . " $salesExpr AS totalSales"
            . " FROM tblvands_summary a"
            . " INNER JOIN tblproject_team b ON a.team_id = b.team_id"
            . " INNER JOIN tblbranch d ON b.branch_id = d.branch_id"
            . " WHERE $whereClause"
            . " GROUP BY b.team_id, b.team_name, b.wd_code, d.branch_name, d.district"
            . " ORDER BY totalSales $sortDir"
            . " LIMIT 10";

        $stmt = $this->pdo->prepare($dsQuery);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Step 4: Format DS records
        $dsPerformance = [];
        foreach ($rows as $row) {
            $qualifiedDays = intval($row['qualifiedDays'] ?? 0);
            $totalDays = intval($row['totalDays'] ?? 0);
            $totalSales = floatval($row['totalSales'] ?? 0);
            $qualificationRate = $totalDays > 0 ? round(($qualifiedDays / $totalDays) * 100, 2) : 0;

            $dsPerformance[] = [
                'teamId' => $row['team_id'] ?? null,
                'dsName' => $row['team_name'] ?? 'Unknown',
                'wdCode' => $row['wd_code'] ?? null,
                'region' => $row['branch_name'] ?? null,
                'district' => $row['district'] ?? null,
                'qualifiedDays' => $qualifiedDays,
                'totalDays' => $totalDays,
                'qualificationRate' => $qualificationRate,
                'totalSales' => round($totalSales, 2)
            ];
        }

        // Step 5: Calculate aggregate metrics
        $totalSalesAll = array_sum(array_column($dsPerformance, 'totalSales'));
        $avgQualRate = count($dsPerformance) > 0
            ? round(array_sum(array_column($dsPerformance, 'qualificationRate')) / count($dsPerformance), 2)
            : 0;
        $days = max(1, (strtotime($endDate) - strtotime($startDate)) / 86400 + 1);

        // Build AI text with dynamic perspective
        $aiText = '';
        try {
            $perspective = $showWorst ? 'worst/underperforming' : 'top/best-performing';
            $sectionLabels = $showWorst
                ? "Use headings: '#### 1. Weakest Performers', '#### 2. Common Issues', '#### 3. Risk Areas', '#### 4. Improvement Actions'"
                : "Use headings: '#### 1. Top Performers', '#### 2. Performance Patterns', '#### 3. Areas for Improvement', '#### 4. Recommendations'";
            $context = "User asked: \"$queryText\"\n"
                . "IMPORTANT: All sales values are in UNITS (sticks/packs), not currency. Do not use \$ or any currency symbol.\n"
                . "Context: User is asking about the {$perspective} DS (Distribution Supervisors).\n"
                . "Total DS: " . count($dsPerformance) . "\nTotal Sales (units): $totalSalesAll\nAvg Qualification Rate: $avgQualRate%\n"
                . "DS Data (sorted by {$perspective}):\n" . json_encode($dsPerformance, JSON_PRETTY_PRINT) . "\n\n"
                . "Analyze DS performance from the {$perspective} perspective. {$sectionLabels}\n"
                . "Weave sales and qualification into ONE cohesive narrative per DS (e.g. 'X sold 100 units with 85% qualification, suggesting...'). Do NOT use separate sections for sales vs qualification.\n"
                . "Be specific with numbers and percentages. Keep each section concise (2-3 bullet points).";
            $aiText = $this->callOpenAi($context);
        } catch (\Exception $e) {
            $label = $showWorst ? "Worst DS" : "Top DS";
            $aiText = "Found " . count($dsPerformance) . " DS records. $label: " . ($dsPerformance[0]['dsName'] ?? 'N/A') . ".";
        }

        // Get actual total DS count (not just the LIMIT 10 shown)
        $totalDsCountSql = "SELECT COUNT(DISTINCT b.team_id) AS cnt FROM tblvands_summary a"
            . " INNER JOIN tblproject_team b ON a.team_id = b.team_id"
            . " INNER JOIN tblbranch d ON b.branch_id = d.branch_id"
            . " WHERE $whereClause";
        $stmtCount = $this->pdo->prepare($totalDsCountSql);
        $stmtCount->execute();
        $totalDsAll = intval($stmtCount->fetchColumn() ?: 0);

        return [
            'query_name' => 'DS Performance Analysis',
            'description' => 'Distribution Supervisor performance breakdown',
            'record_count' => count($dsPerformance),
            'records' => $dsPerformance,
            'metrics' => [
                'total_sales' => round($totalSalesAll, 2),
                'avg_daily_sales' => round($totalSalesAll / $days, 2),
                'total_ds' => count($dsPerformance),
                'total_ds_all' => $totalDsAll,
                'avg_qualification_rate' => $avgQualRate
            ],
            'ai_text' => $aiText,
            'show_worst' => $showWorst
        ];
    }
    // ========================================================================
    // NEW QUERY HANDLERS
    // ========================================================================

    /**
     * Handler: WD Code Performance
     */
    private function handleWdCodePerformance($filters, $queryText = '')
    {
        $startDate = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $filters['date_to'] ?? date('Y-m-d');
        $queryLower = strtolower($queryText);
        $showWorst = $this->isWorstQuery($queryLower);

        $conditions = $this->buildBaseConditions($filters);
        $whereClause = implode(' AND ', $conditions);
        $sortDir = $showWorst ? 'ASC' : 'DESC';

        $sql = "SELECT b.wd_code,
                    d.branch_name,
                    SUM(a.total_sales_deliveries) AS totalSales,
                    COUNT(DISTINCT a.team_id) AS dsCount,
                    SUM(CASE WHEN a.is_qualified = 1 THEN 1 ELSE 0 END) AS qualifiedDays,
                    COUNT(*) AS totalDays,
                    COUNT(DISTINCT a.activity_date) AS uniqueDays
                FROM tblvands_summary a
                LEFT JOIN tblproject_team b ON a.team_id = b.team_id
                LEFT JOIN tblbranch d ON b.branch_id = d.branch_id
                WHERE $whereClause
                AND b.wd_code IS NOT NULL AND b.wd_code != ''
                GROUP BY b.wd_code, d.branch_name
                ORDER BY totalSales $sortDir
                LIMIT 25";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $records = [];
        foreach ($rows as $row) {
            $qualifiedDays = intval($row['qualifiedDays'] ?? 0);
            $totalDays = intval($row['totalDays'] ?? 0);
            $qualRate = $totalDays > 0 ? round(($qualifiedDays / $totalDays) * 100, 2) : 0;
            $records[] = [
                'wdCode' => $row['wd_code'],
                'region' => $row['branch_name'] ?? '',
                'totalSales' => round(floatval($row['totalSales']), 2),
                'dsCount' => intval($row['dsCount']),
                'qualifiedDays' => $qualifiedDays,
                'totalDays' => $totalDays,
                'qualificationRate' => $qualRate
            ];
        }

        $totalSalesAll = array_sum(array_column($records, 'totalSales'));
        // Add share percent
        foreach ($records as &$rec) {
            $rec['sharePercent'] = $totalSalesAll > 0 ? round(($rec['totalSales'] / $totalSalesAll) * 100, 2) : 0;
        }

        $days = max(1, (strtotime($endDate) - strtotime($startDate)) / 86400 + 1);
        $aiText = $this->generateAiForData('wd_code_performance', $queryText, $records, $totalSalesAll);

        return [
            'query_name' => 'WD Code Performance',
            'record_count' => count($records),
            'records' => $records,
            'metrics' => [
                'total_sales' => round($totalSalesAll, 2),
                'avg_daily_sales' => round($totalSalesAll / $days, 2),
                'total_wd_codes' => count($records)
            ],
            'ai_text' => $aiText,
            'show_worst' => $showWorst
        ];
    }

    /**
     * Handler: Period Comparison (Month-over-Month, Week-over-Week)
     */
    private function handlePeriodComparison($filters, $queryText = '')
    {
        $endDate = $filters['date_to'] ?? date('Y-m-d');
        $startDate = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));

        // Calculate current period length
        $periodDays = max(1, (strtotime($endDate) - strtotime($startDate)) / 86400 + 1);

        // Previous period = same length before start date
        $prevEnd = date('Y-m-d', strtotime($startDate . ' -1 day'));
        $prevStart = date('Y-m-d', strtotime($prevEnd . " -" . ($periodDays - 1) . " days"));

        $baseWhere = "a.dstatus = 0 AND b.dstatus = 0 AND d.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0";
        if (!empty($filters['user_teams'])) {
            $teamList = $filters['user_teams'];
            if (is_string($teamList) && preg_match('/^\([\d,]+\)$/', $teamList)) {
                $baseWhere .= " AND b.team_id IN $teamList";
            }
        }
        $branchFilter = $this->buildBranchFilterSql($filters);
        $baseWhere .= $branchFilter;

        // Current period data
        $sqlCurrent = "SELECT
                SUM(a.total_sales_deliveries) AS totalSales,
                COUNT(DISTINCT a.team_id) AS activeDsCount,
                SUM(CASE WHEN a.is_qualified = 1 THEN 1 ELSE 0 END) AS qualifiedDays,
                COUNT(*) AS totalDays,
                COUNT(DISTINCT a.activity_date) AS uniqueDays
            FROM tblvands_summary a
            LEFT JOIN tblproject_team b ON a.team_id = b.team_id
            LEFT JOIN tblbranch d ON b.branch_id = d.branch_id
            WHERE $baseWhere AND a.activity_date BETWEEN :start AND :end";

        $stmt = $this->pdo->prepare($sqlCurrent);
        $stmt->execute([':start' => $startDate, ':end' => $endDate]);
        $current = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Previous period data
        $stmt = $this->pdo->prepare($sqlCurrent);
        $stmt->execute([':start' => $prevStart, ':end' => $prevEnd]);
        $previous = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Branch-level comparison
        $sqlBranch = "SELECT d.branch_name,
                SUM(a.total_sales_deliveries) AS totalSales,
                COUNT(DISTINCT a.team_id) AS dsCount,
                SUM(CASE WHEN a.is_qualified = 1 THEN 1 ELSE 0 END) AS qualifiedDays,
                COUNT(*) AS totalDays
            FROM tblvands_summary a
            LEFT JOIN tblproject_team b ON a.team_id = b.team_id
            LEFT JOIN tblbranch d ON b.branch_id = d.branch_id
            WHERE $baseWhere AND a.activity_date BETWEEN :start AND :end
            GROUP BY d.branch_name
            ORDER BY totalSales DESC LIMIT 10";

        $stmt = $this->pdo->prepare($sqlBranch);
        $stmt->execute([':start' => $startDate, ':end' => $endDate]);
        $currentBranches = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare($sqlBranch);
        $stmt->execute([':start' => $prevStart, ':end' => $prevEnd]);
        $prevBranches = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Build comparison map
        $prevBranchMap = [];
        foreach ($prevBranches as $pb) {
            $prevBranchMap[$pb['branch_name']] = $pb;
        }

        $comparison = [];
        foreach ($currentBranches as $cb) {
            $branchName = $cb['branch_name'];
            $prevSales = floatval($prevBranchMap[$branchName]['totalSales'] ?? 0);
            $currSales = floatval($cb['totalSales']);
            $change = $prevSales > 0 ? round((($currSales - $prevSales) / $prevSales) * 100, 2) : ($currSales > 0 ? 100 : 0);

            $comparison[] = [
                'name' => $branchName,
                'currentSales' => round($currSales, 2),
                'previousSales' => round($prevSales, 2),
                'changePercent' => $change,
                'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat')
            ];
        }

        $curTotal = floatval($current['totalSales'] ?? 0);
        $prevTotal = floatval($previous['totalSales'] ?? 0);
        $overallChange = $prevTotal > 0 ? round((($curTotal - $prevTotal) / $prevTotal) * 100, 2) : 0;

        $curQualRate = intval($current['totalDays']) > 0 ? round((intval($current['qualifiedDays']) / intval($current['totalDays'])) * 100, 2) : 0;
        $prevQualRate = intval($previous['totalDays']) > 0 ? round((intval($previous['qualifiedDays']) / intval($previous['totalDays'])) * 100, 2) : 0;

        $aiContext = "Period Comparison:\n"
            . "Current ({$startDate} to {$endDate}): {$curTotal} units, {$curQualRate}% qualified\n"
            . "Previous ({$prevStart} to {$prevEnd}): {$prevTotal} units, {$prevQualRate}% qualified\n"
            . "Overall Change: {$overallChange}%\n"
            . "Branch breakdown:\n" . json_encode($comparison, JSON_PRETTY_PRINT);
        $aiText = $this->generateAiForData('period_comparison', $queryText, $comparison, $curTotal, $aiContext);

        return [
            'query_name' => 'Period Comparison',
            'record_count' => count($comparison),
            'records' => $comparison,
            'metrics' => [
                'current_total_sales' => round($curTotal, 2),
                'previous_total_sales' => round($prevTotal, 2),
                'change_percent' => $overallChange,
                'current_qualification_rate' => $curQualRate,
                'previous_qualification_rate' => $prevQualRate,
                'current_active_ds' => intval($current['activeDsCount'] ?? 0),
                'previous_active_ds' => intval($previous['activeDsCount'] ?? 0)
            ],
            'period' => [
                'current' => ['start' => $startDate, 'end' => $endDate],
                'previous' => ['start' => $prevStart, 'end' => $prevEnd]
            ],
            'ai_text' => $aiText
        ];
    }

    /**
     * Handler: Day-of-Week Analysis
     */
    private function handleDayOfWeek($filters, $queryText = '')
    {
        $conditions = $this->buildBaseConditions($filters);
        $whereClause = implode(' AND ', $conditions);

        $sql = "SELECT
                DAYOFWEEK(a.activity_date) AS dow_num,
                DAYNAME(a.activity_date) AS day_name,
                SUM(a.total_sales_deliveries) AS totalSales,
                COUNT(DISTINCT a.team_id) AS activeDsCount,
                SUM(CASE WHEN a.is_qualified = 1 THEN 1 ELSE 0 END) AS qualifiedDays,
                COUNT(*) AS totalDays,
                COUNT(DISTINCT a.activity_date) AS weekCount
            FROM tblvands_summary a
            LEFT JOIN tblproject_team b ON a.team_id = b.team_id
            LEFT JOIN tblbranch d ON b.branch_id = d.branch_id
            WHERE $whereClause
            GROUP BY DAYOFWEEK(a.activity_date), DAYNAME(a.activity_date)
            ORDER BY dow_num ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $records = [];
        foreach ($rows as $row) {
            $weekCount = max(1, intval($row['weekCount']));
            $totalSales = floatval($row['totalSales']);
            $qualifiedDays = intval($row['qualifiedDays']);
            $totalDays = intval($row['totalDays']);

            $records[] = [
                'dayName' => $row['day_name'],
                'dayNumber' => intval($row['dow_num']),
                'totalSales' => round($totalSales, 2),
                'avgSales' => round($totalSales / $weekCount, 2),
                'activeDsAvg' => round(intval($row['activeDsCount']) / $weekCount, 0),
                'qualificationRate' => $totalDays > 0 ? round(($qualifiedDays / $totalDays) * 100, 2) : 0,
                'weekCount' => $weekCount
            ];
        }

        $totalSalesAll = array_sum(array_column($records, 'totalSales'));
        $bestDay = !empty($records) ? array_reduce($records, function($carry, $item) {
            return ($carry === null || $item['avgSales'] > $carry['avgSales']) ? $item : $carry;
        }) : null;
        $worstDay = !empty($records) ? array_reduce($records, function($carry, $item) {
            return ($carry === null || $item['avgSales'] < $carry['avgSales']) ? $item : $carry;
        }) : null;

        $aiText = $this->generateAiForData('day_of_week', $queryText, $records, $totalSalesAll);

        return [
            'query_name' => 'Day-of-Week Analysis',
            'record_count' => count($records),
            'records' => $records,
            'metrics' => [
                'total_sales' => round($totalSalesAll, 2),
                'best_day' => $bestDay['dayName'] ?? 'N/A',
                'best_day_avg' => $bestDay['avgSales'] ?? 0,
                'worst_day' => $worstDay['dayName'] ?? 'N/A',
                'worst_day_avg' => $worstDay['avgSales'] ?? 0
            ],
            'ai_text' => $aiText
        ];
    }

    /**
     * Handler: Growth/Decline Detection
     */
    private function handleGrowthDecline($filters, $queryText = '')
    {
        $endDate = $filters['date_to'] ?? date('Y-m-d');
        $startDate = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $periodDays = max(1, (strtotime($endDate) - strtotime($startDate)) / 86400 + 1);
        $midDate = date('Y-m-d', strtotime($startDate . ' +' . intval($periodDays / 2) . ' days'));

        $baseWhere = "a.dstatus = 0 AND b.dstatus = 0 AND d.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0";
        if (!empty($filters['user_teams'])) {
            $teamList = $filters['user_teams'];
            if (is_string($teamList) && preg_match('/^\([\d,]+\)$/', $teamList)) {
                $baseWhere .= " AND b.team_id IN $teamList";
            }
        }
        $baseWhere .= $this->buildBranchFilterSql($filters);

        // Determine dimension: category, product, region, branch, circle, section, district, or DS
        $queryLower = strtolower($queryText);

        // Category / Product growth — delegate to specialized method
        if (strpos($queryLower, 'categor') !== false) {
            return $this->handleCategoryGrowthMode($startDate, $endDate, $midDate, $filters, $queryText, 'category');
        }
        if (strpos($queryLower, 'product') !== false || strpos($queryLower, 'brand') !== false || strpos($queryLower, 'sku') !== false) {
            return $this->handleCategoryGrowthMode($startDate, $endDate, $midDate, $filters, $queryText, 'product');
        }

        $dimension = 'd.branch_name';
        $dimLabel = 'region';
        if (strpos($queryLower, 'section') !== false) {
            $dimension = 'b.section';
            $dimLabel = 'section';
        } elseif (strpos($queryLower, 'circle') !== false) {
            $dimension = 'b.circle';
            $dimLabel = 'circle';
        } elseif (strpos($queryLower, 'district') !== false) {
            $dimension = 'd.district';
            $dimLabel = 'district';
        } elseif (preg_match('/\bwd[\s_]?code\b|\bwdcode\b/', $queryLower)) {
            $dimension = 'b.wd_code';
            $dimLabel = 'wd_code';
        } elseif (strpos($queryLower, 'branch') !== false) {
            $dimension = 'd.main_branch';
            $dimLabel = 'branch';
        } elseif (strpos($queryLower, 'ds') !== false || strpos($queryLower, 'team') !== false) {
            $dimension = 'b.team_name';
            $dimLabel = 'ds';
        }

        $sql = "SELECT $dimension AS dim,
                SUM(CASE WHEN a.activity_date < :mid THEN a.total_sales_deliveries ELSE 0 END) AS firstHalfSales,
                SUM(CASE WHEN a.activity_date >= :mid2 THEN a.total_sales_deliveries ELSE 0 END) AS secondHalfSales
            FROM tblvands_summary a
            LEFT JOIN tblproject_team b ON a.team_id = b.team_id
            LEFT JOIN tblbranch d ON b.branch_id = d.branch_id
            WHERE $baseWhere
            AND a.activity_date BETWEEN :start AND :end
            AND $dimension IS NOT NULL AND $dimension != ''
            GROUP BY $dimension
            HAVING SUM(CASE WHEN a.activity_date < :mid3 THEN a.total_sales_deliveries ELSE 0 END) > 0
                OR SUM(CASE WHEN a.activity_date >= :mid4 THEN a.total_sales_deliveries ELSE 0 END) > 0
            ORDER BY (SUM(CASE WHEN a.activity_date >= :mid5 THEN a.total_sales_deliveries ELSE 0 END) - SUM(CASE WHEN a.activity_date < :mid6 THEN a.total_sales_deliveries ELSE 0 END)) DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':mid' => $midDate, ':mid2' => $midDate, ':mid3' => $midDate, ':mid4' => $midDate, ':mid5' => $midDate, ':mid6' => $midDate, ':start' => $startDate, ':end' => $endDate]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $records = [];
        foreach ($rows as $row) {
            $first = floatval($row['firstHalfSales']);
            $second = floatval($row['secondHalfSales']);
            $change = $first > 0 ? round((($second - $first) / $first) * 100, 2) : ($second > 0 ? 100 : 0);

            $records[] = [
                'name' => $row['dim'],
                'firstHalfSales' => round($first, 2),
                'secondHalfSales' => round($second, 2),
                'changePercent' => $change,
                'direction' => $change > 5 ? 'growing' : ($change < -5 ? 'declining' : 'stable'),
                'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat')
            ];
        }

        // Separate growing and declining
        $growing = array_filter($records, function($r) { return $r['direction'] === 'growing'; });
        $declining = array_filter($records, function($r) { return $r['direction'] === 'declining'; });

        $aiText = $this->generateAiForData('growth_decline', $queryText, $records, 0,
            "Growing: " . count($growing) . " | Declining: " . count($declining) . " | Dimension: $dimLabel");

        return [
            'query_name' => 'Growth & Decline Analysis',
            'record_count' => count($records),
            'records' => $records,
            'metrics' => [
                'growing_count' => count($growing),
                'declining_count' => count($declining),
                'stable_count' => count($records) - count($growing) - count($declining),
                'dimension' => $dimLabel
            ],
            'period' => [
                'first_half' => ['start' => $startDate, 'end' => date('Y-m-d', strtotime($midDate . ' -1 day'))],
                'second_half' => ['start' => $midDate, 'end' => $endDate]
            ],
            'ai_text' => $aiText
        ];
    }

    /**
     * Category / Product Growth Mode (called from handleGrowthDecline)
     * Splits the date range into two halves and compares category or product totals.
     */
    private function handleCategoryGrowthMode($startDate, $endDate, $midDate, $filters, $queryText = '', $granularity = 'category')
    {
        $productFilter = $this->buildProductTableFilter($filters);

        if ($granularity === 'product') {
            $sql = "SELECT DISTINCT product_name AS label, summary_column_name
                    FROM tblbranch_pickupstock_products
                    WHERE dstatus = 0 AND product_name IS NOT NULL AND product_name != ''
                    AND summary_column_name IS NOT NULL AND summary_column_name != ''" . $productFilter;
        } else {
            $sql = "SELECT DISTINCT category_name AS label, summary_column_name
                    FROM tblbranch_pickupstock_products
                    WHERE dstatus = 0 AND category_name IS NOT NULL AND category_name != ''
                    AND summary_column_name IS NOT NULL AND summary_column_name != ''" . $productFilter;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $productRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($productRows)) {
            // No product config — fall back to region-level growth
            $filters['date_from'] = $startDate;
            $filters['date_to'] = $endDate;
            return $this->handleGrowthDeclineFallback($startDate, $endDate, $midDate, $filters, $queryText);
        }

        // Build label → columns map (multiple columns can map to one category)
        $labelMap = [];
        $allCols = [];
        $seenCols = [];
        foreach ($productRows as $row) {
            $label = $row['label'];
            $col = $row['summary_column_name'];
            if (!isset($seenCols[$col])) {
                $seenCols[$col] = true;
                $allCols[] = $col;
            }
            if (!isset($labelMap[$label])) {
                $labelMap[$label] = [];
            }
            if (!in_array($col, $labelMap[$label])) {
                $labelMap[$label][] = $col;
            }
        }

        if (empty($allCols)) {
            $filters['date_from'] = $startDate;
            $filters['date_to'] = $endDate;
            return $this->handleGrowthDeclineFallback($startDate, $endDate, $midDate, $filters, $queryText);
        }

        // Build conditions (includes date range via buildBaseConditions)
        $conditions = $this->buildBaseConditions($filters);
        $whereClause = implode(' AND ', $conditions);

        // Dynamic SELECT: each column split by first/second half
        $selects = [];
        foreach ($allCols as $col) {
            $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
            $selects[] = "SUM(CASE WHEN a.activity_date < '$midDate' THEN a.`$col` ELSE 0 END) AS `{$safe}_first`";
            $selects[] = "SUM(CASE WHEN a.activity_date >= '$midDate' THEN a.`$col` ELSE 0 END) AS `{$safe}_second`";
        }

        $sql = "SELECT " . implode(', ', $selects) . "
                FROM tblvands_summary a
                INNER JOIN tblproject_team b ON a.team_id = b.team_id AND b.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0
                INNER JOIN tblbranch d ON b.branch_id = d.branch_id AND d.dstatus = 0
                WHERE $whereClause";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            $filters['date_from'] = $startDate;
            $filters['date_to'] = $endDate;
            return $this->handleGrowthDeclineFallback($startDate, $endDate, $midDate, $filters, $queryText);
        }

        // Aggregate totals per label
        $records = [];
        foreach ($labelMap as $label => $cols) {
            $first = 0.0;
            $second = 0.0;
            foreach ($cols as $col) {
                $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
                $first += floatval($row["{$safe}_first"] ?? 0);
                $second += floatval($row["{$safe}_second"] ?? 0);
            }
            if ($first == 0 && $second == 0) {
                continue; // skip empty
            }
            $change = $first > 0 ? round((($second - $first) / $first) * 100, 2) : ($second > 0 ? 100.0 : 0.0);
            $records[] = [
                'name'           => $label,
                'firstHalfSales' => round($first, 2),
                'secondHalfSales'=> round($second, 2),
                'changePercent'  => $change,
                'direction'      => $change > 5 ? 'growing' : ($change < -5 ? 'declining' : 'stable'),
                'trend'          => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat'),
            ];
        }

        // Sort by change descending (growers first)
        usort($records, function($a, $b) { return $b['changePercent'] <=> $a['changePercent']; });

        $growing  = array_values(array_filter($records, function($r) { return $r['direction'] === 'growing'; }));
        $declining = array_values(array_filter($records, function($r) { return $r['direction'] === 'declining'; }));
        $dimLabel  = $granularity === 'product' ? 'product' : 'category';

        $aiText = $this->generateAiForData('growth_decline', $queryText, $records, 0,
            ucfirst($dimLabel) . " growth analysis. Growing: " . count($growing) . " | Declining: " . count($declining));

        return [
            'query_name'   => ucfirst($dimLabel) . ' Growth & Decline',
            'record_count' => count($records),
            'records'      => $records,
            'metrics'      => [
                'growing_count'  => count($growing),
                'declining_count'=> count($declining),
                'stable_count'   => count($records) - count($growing) - count($declining),
                'dimension'      => $dimLabel,
            ],
            'period' => [
                'first_half'  => ['start' => $startDate, 'end' => date('Y-m-d', strtotime($midDate . ' -1 day'))],
                'second_half' => ['start' => $midDate,   'end' => $endDate],
            ],
            'ai_text' => $aiText,
        ];
    }

    /**
     * Fallback: region-level growth (used when product config missing)
     */
    private function handleGrowthDeclineFallback($startDate, $endDate, $midDate, $filters, $queryText = '')
    {
        $baseWhere = "a.dstatus = 0 AND b.dstatus = 0 AND d.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0";
        if (!empty($filters['user_teams'])) {
            $teamList = $filters['user_teams'];
            if (is_string($teamList) && preg_match('/^\([\d,]+\)$/', $teamList)) {
                $baseWhere .= " AND b.team_id IN $teamList";
            }
        }
        $baseWhere .= $this->buildBranchFilterSql($filters);

        $sql = "SELECT d.branch_name AS dim,
                SUM(CASE WHEN a.activity_date < :mid THEN a.total_sales_deliveries ELSE 0 END) AS firstHalfSales,
                SUM(CASE WHEN a.activity_date >= :mid2 THEN a.total_sales_deliveries ELSE 0 END) AS secondHalfSales
            FROM tblvands_summary a
            LEFT JOIN tblproject_team b ON a.team_id = b.team_id
            LEFT JOIN tblbranch d ON b.branch_id = d.branch_id
            WHERE $baseWhere AND a.activity_date BETWEEN :start AND :end
            AND d.branch_name IS NOT NULL AND d.branch_name != ''
            GROUP BY d.branch_name
            ORDER BY (SUM(CASE WHEN a.activity_date >= :mid3 THEN a.total_sales_deliveries ELSE 0 END) - SUM(CASE WHEN a.activity_date < :mid4 THEN a.total_sales_deliveries ELSE 0 END)) DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':mid' => $midDate, ':mid2' => $midDate, ':mid3' => $midDate, ':mid4' => $midDate, ':start' => $startDate, ':end' => $endDate]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $records = [];
        foreach ($rows as $r) {
            $first = floatval($r['firstHalfSales']);
            $second = floatval($r['secondHalfSales']);
            $change = $first > 0 ? round((($second - $first) / $first) * 100, 2) : ($second > 0 ? 100 : 0);
            $records[] = [
                'name' => $r['dim'], 'firstHalfSales' => round($first, 2),
                'secondHalfSales' => round($second, 2), 'changePercent' => $change,
                'direction' => $change > 5 ? 'growing' : ($change < -5 ? 'declining' : 'stable'),
                'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat'),
            ];
        }

        $growing  = array_filter($records, function($r) { return $r['direction'] === 'growing'; });
        $declining = array_filter($records, function($r) { return $r['direction'] === 'declining'; });
        $aiText = $this->generateAiForData('growth_decline', $queryText, $records, 0,
            "Growing: " . count($growing) . " | Declining: " . count($declining) . " | Dimension: region");

        return [
            'query_name' => 'Growth & Decline Analysis', 'record_count' => count($records),
            'records' => $records,
            'metrics' => ['growing_count' => count($growing), 'declining_count' => count($declining),
                'stable_count' => count($records) - count($growing) - count($declining), 'dimension' => 'region'],
            'period' => ['first_half' => ['start' => $startDate, 'end' => date('Y-m-d', strtotime($midDate . ' -1 day'))],
                'second_half' => ['start' => $midDate, 'end' => $endDate]],
            'ai_text' => $aiText,
        ];
    }

    /**
     * Handler: Executive Summary
     */
    private function handleExecutiveSummary($filters, $queryText = '')
    {
        $startDate = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $filters['date_to'] ?? date('Y-m-d');

        $baseWhere = "a.dstatus = 0 AND b.dstatus = 0 AND d.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0";
        if (!empty($filters['user_teams'])) {
            $teamList = $filters['user_teams'];
            if (is_string($teamList) && preg_match('/^\([\d,]+\)$/', $teamList)) {
                $baseWhere .= " AND b.team_id IN $teamList";
            }
        }
        $baseWhere .= $this->buildBranchFilterSql($filters);
        $dateCondition = "a.activity_date BETWEEN :start AND :end";

        // 1. Overall metrics (DS-only, with hierarchy filter)
        $sql = "SELECT
                SUM(a.total_sales_deliveries) AS totalSales,
                COUNT(DISTINCT a.team_id) AS activeDsCount,
                COUNT(DISTINCT d.branch_id) AS activeBranches,
                SUM(CASE WHEN a.is_qualified = 1 THEN 1 ELSE 0 END) AS qualifiedDays,
                COUNT(*) AS totalDays,
                COUNT(DISTINCT a.activity_date) AS uniqueDays,
                AVG(a.total_sales_deliveries) AS avgDailySalesPerDs,
                SUM(CASE WHEN a.is_beat_adherence = 'Yes' THEN 1 ELSE 0 END) AS routeAdherence
            FROM tblvands_summary a
            INNER JOIN tblproject_team b ON a.team_id = b.team_id AND b.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0
            INNER JOIN tblbranch d ON b.branch_id = d.branch_id AND d.dstatus = 0
            WHERE $baseWhere AND $dateCondition";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':start' => $startDate, ':end' => $endDate]);
        $overall = $stmt->fetch(\PDO::FETCH_ASSOC);

        // 2. Top 5 regions by sales (with qualification rate)
        $sql = "SELECT d.branch_name,
                SUM(a.total_sales_deliveries) AS totalSales,
                COUNT(DISTINCT a.team_id) AS dsCount,
                SUM(CASE WHEN a.is_qualified = 1 THEN 1 ELSE 0 END) AS qualifiedDays,
                COUNT(*) AS totalDays
            FROM tblvands_summary a
            INNER JOIN tblproject_team b ON a.team_id = b.team_id AND b.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0
            INNER JOIN tblbranch d ON b.branch_id = d.branch_id AND d.dstatus = 0
            WHERE $baseWhere AND $dateCondition
            GROUP BY d.branch_name ORDER BY totalSales DESC LIMIT 5";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':start' => $startDate, ':end' => $endDate]);
        $topBranches = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // 3. Bottom 5 regions by sales (with qualification rate)
        $sql = "SELECT d.branch_name,
                SUM(a.total_sales_deliveries) AS totalSales,
                COUNT(DISTINCT a.team_id) AS dsCount,
                SUM(CASE WHEN a.is_qualified = 1 THEN 1 ELSE 0 END) AS qualifiedDays,
                COUNT(*) AS totalDays
            FROM tblvands_summary a
            INNER JOIN tblproject_team b ON a.team_id = b.team_id AND b.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0
            INNER JOIN tblbranch d ON b.branch_id = d.branch_id AND d.dstatus = 0
            WHERE $baseWhere AND $dateCondition
            GROUP BY d.branch_name HAVING totalSales > 0 ORDER BY totalSales ASC LIMIT 5";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':start' => $startDate, ':end' => $endDate]);
        $bottomBranches = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($topBranches as &$row) {
            $td = intval($row['totalDays'] ?? 0);
            $row['qualificationRate'] = $td > 0 ? round((intval($row['qualifiedDays'] ?? 0) / $td) * 100, 2) : 0;
        }
        foreach ($bottomBranches as &$row) {
            $td = intval($row['totalDays'] ?? 0);
            $row['qualificationRate'] = $td > 0 ? round((intval($row['qualifiedDays'] ?? 0) / $td) * 100, 2) : 0;
        }

        $totalSales = floatval($overall['totalSales'] ?? 0);
        $totalDays = intval($overall['totalDays'] ?? 0);
        $qualifiedDays = intval($overall['qualifiedDays'] ?? 0);
        $qualRate = $totalDays > 0 ? round(($qualifiedDays / $totalDays) * 100, 2) : 0;
        $routeAdherence = intval($overall['routeAdherence'] ?? 0);
        $routeRate = $totalDays > 0 ? round(($routeAdherence / $totalDays) * 100, 2) : 0;
        $days = max(1, (strtotime($endDate) - strtotime($startDate)) / 86400 + 1);

        $summaryData = [
            'totalSales' => round($totalSales, 2),
            'avgDailySales' => round($totalSales / $days, 2),
            'activeDsCount' => intval($overall['activeDsCount'] ?? 0),
            'activeBranches' => intval($overall['activeBranches'] ?? 0),
            'qualificationRate' => $qualRate,
            'routeAdherenceRate' => $routeRate,
            'topBranches' => $topBranches,
            'bottomBranches' => $bottomBranches
        ];

        $isSingleDay = ($startDate === $endDate);
        $queryLower = strtolower($queryText ?? '');
        $isMorningBriefing = (strpos($queryLower, 'morning briefing') !== false || strpos($queryLower, 'morning update') !== false || strpos($queryLower, 'daily briefing') !== false);
        $queryLabel = $isMorningBriefing ? 'Morning Briefing' : ($isSingleDay && $startDate === date('Y-m-d') ? "Today's Summary" : 'Executive Summary');

        // Heatmap points for the map (all executive summary / morning briefing, any date range)
        $todayHeatmapPoints = [];
        $mapSql = "SELECT d.branch_name AS regionName, d.district, b.circle,
                    SUM(a.total_sales_deliveries) AS totalSales,
                    COUNT(DISTINCT b.team_id) AS dsCount,
                    ROUND(AVG(att.lt), 6) AS lat, ROUND(AVG(att.lg), 6) AS lng
                FROM tblvands_summary a
                INNER JOIN tblproject_team b ON a.team_id = b.team_id AND b.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0
                INNER JOIN tblbranch d ON b.branch_id = d.branch_id AND d.dstatus = 0
                LEFT JOIN (
                    SELECT team_id, AVG(lt) AS lt, AVG(lg) AS lg FROM tblattendance
                    WHERE s_id = 99 AND dstatus = 0 AND lt != 0 AND lg != 0 AND capture_date BETWEEN :start AND :end
                    GROUP BY team_id
                ) att ON b.team_id = att.team_id
                WHERE $baseWhere AND a.activity_date BETWEEN :start AND :end
                GROUP BY d.branch_name, d.district, b.circle
                HAVING lat IS NOT NULL AND lat != 0 AND lng IS NOT NULL AND lng != 0
                ORDER BY totalSales DESC";
        try {
            $mapStmt = $this->pdo->prepare($mapSql);
            $mapStmt->execute([':start' => $startDate, ':end' => $endDate]);
            $mapRows = $mapStmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($mapRows as $row) {
                $todayHeatmapPoints[] = [
                    'lat' => floatval($row['lat']),
                    'lng' => floatval($row['lng']),
                    'sales' => round(floatval($row['totalSales'] ?? 0), 0),
                    'region' => $row['regionName'] ?? '',
                    'district' => $row['district'] ?? '',
                    'circle' => $row['circle'] ?? '',
                    'dsCount' => intval($row['dsCount'] ?? 0),
                ];
            }
        } catch (\Exception $e) {
            // ignore
        }

        // Top 5 DS for the period
        $topDs = [];
        try {
            $dsSql = "SELECT b.team_id, b.team_name,
                    SUM(a.total_sales_deliveries) AS totalSales,
                    SUM(CASE WHEN a.is_qualified = 1 THEN 1 ELSE 0 END) AS qualifiedDays,
                    COUNT(*) AS totalDays
                FROM tblvands_summary a
                INNER JOIN tblproject_team b ON a.team_id = b.team_id AND b.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0
                INNER JOIN tblbranch d ON b.branch_id = d.branch_id AND d.dstatus = 0
                WHERE $baseWhere AND $dateCondition
                GROUP BY b.team_id, b.team_name ORDER BY totalSales DESC LIMIT 5";
            $dsStmt = $this->pdo->prepare($dsSql);
            $dsStmt->execute([':start' => $startDate, ':end' => $endDate]);
            foreach ($dsStmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
                $td = intval($r['totalDays'] ?? 0);
                $topDs[] = [
                    'dsName' => $r['team_name'] ?? 'Unknown',
                    'totalSales' => round(floatval($r['totalSales'] ?? 0), 0),
                    'qualificationRate' => $td > 0 ? round((intval($r['qualifiedDays'] ?? 0) / $td) * 100, 2) : 0,
                ];
            }
        } catch (\Exception $e) {
            // ignore
        }

        // Daily trend for the period
        $dailyTrend = [];
        try {
            $trendSql = "SELECT a.activity_date AS date, SUM(a.total_sales_deliveries) AS totalSales
                FROM tblvands_summary a
                INNER JOIN tblproject_team b ON a.team_id = b.team_id AND b.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0
                INNER JOIN tblbranch d ON b.branch_id = d.branch_id AND d.dstatus = 0
                WHERE $baseWhere AND $dateCondition
                GROUP BY a.activity_date ORDER BY a.activity_date";
            $trendStmt = $this->pdo->prepare($trendSql);
            $trendStmt->execute([':start' => $startDate, ':end' => $endDate]);
            foreach ($trendStmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
                $dailyTrend[] = ['date' => $r['date'], 'totalSales' => round(floatval($r['totalSales'] ?? 0), 0)];
            }
        } catch (\Exception $e) {
            // ignore
        }

        // Top 5 products for the period (same filters)
        $topProducts = $this->getTopProductsForPeriod($filters, $baseWhere, $startDate, $endDate, 5);

        $aiContext = ($isSingleDay ? "Daily Summary for $startDate" : "Executive Summary for {$startDate} to {$endDate}") . ":\n"
            . json_encode($summaryData, JSON_PRETTY_PRINT);
        if (!empty($topDs)) {
            $aiContext .= "\n\nTop 5 DS: " . json_encode($topDs, JSON_PRETTY_PRINT);
        }
        if (!empty($topProducts)) {
            $aiContext .= "\n\nTop 5 Products: " . json_encode($topProducts, JSON_PRETTY_PRINT);
        }
        $aiText = '';
        try {
            $prompt = "User asked: \"$queryText\"\n"
                . "IMPORTANT: All sales figures are in UNITS (sticks/packs), not currency. Do not use \$ or any currency symbol.\n"
                . ($isSingleDay ? "This is a SINGLE-DAY (today) summary: focus on what happened today - DS who marked attendance, qualified count, sales so far, and actionable insights.\n" : '')
                . ($isMorningBriefing ? "This is a MORNING BRIEFING: be concise but comprehensive. Include specific, actionable next steps managers can take today.\n" : '')
                . $aiContext . "\n\n"
                . "Provide a comprehensive " . ($isMorningBriefing ? 'morning briefing ' : ($isSingleDay ? 'daily ' : '')) . "summary covering: (1) Overall health, (2) Key metrics (DS marked attendance, qualified, sales), (3) Top & bottom performers, (4) Areas of concern, (5) 3–5 specific, actionable recommendations (what to do today / this week to improve).";
            $aiText = $this->callOpenAi($prompt);
        } catch (\Exception $e) {
            $aiText = $queryLabel . ": Total Sales " . round($totalSales) . " units, {$qualRate}% qualification, " . intval($overall['activeDsCount']) . " DS marked attendance.";
        }

        return [
            'query_name' => $queryLabel,
            'is_today_summary' => $isSingleDay && $startDate === date('Y-m-d'),
            'today_heatmap_points' => $todayHeatmapPoints,
            'record_count' => 1,
            'records' => [$summaryData],
            'metrics' => [
                'total_sales' => round($totalSales, 2),
                'avg_daily_sales' => round($totalSales / $days, 2),
                'active_ds' => intval($overall['activeDsCount'] ?? 0),
                'active_branches' => intval($overall['activeBranches'] ?? 0),
                'qualification_rate' => $qualRate,
                'route_adherence_rate' => $routeRate
            ],
            'top_branches' => $topBranches,
            'bottom_branches' => $bottomBranches,
            'top_ds' => $topDs,
            'daily_trend' => $dailyTrend,
            'top_products' => $topProducts,
            'ai_text' => $aiText
        ];
    }

    /**
     * Get top N products for a date range (for executive summary / morning briefing).
     * Uses same branch filters as executive summary.
     */
    private function getTopProductsForPeriod($filters, $baseWhere, $startDate, $endDate, $limit = 5)
    {
        $productFilter = $this->buildProductTableFilter($filters);
        $stmt = $this->pdo->prepare("SELECT DISTINCT summary_column_name, product_name FROM tblbranch_pickupstock_products WHERE dstatus = 0" . $productFilter);
        $stmt->execute();
        $productRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $productCol = [];
        $productColName = [];
        $seenCols = [];
        foreach ($productRows as $row) {
            $summaryCol = $row['summary_column_name'] ?? '';
            $productName = $row['product_name'] ?? '';
            if ($summaryCol && !isset($seenCols[$summaryCol])) {
                $seenCols[$summaryCol] = true;
                $productCol[] = $summaryCol;
                $productColName[$summaryCol] = $productName;
            }
        }
        if (empty($productCol)) {
            return [];
        }
        $sumSelect = [];
        foreach ($productCol as $col) {
            $sumSelect[] = "SUM(a.$col) AS `" . str_replace('`', '``', $col) . "`";
        }
        $dateCondition = "a.activity_date BETWEEN :start AND :end";
        $sql = "SELECT " . implode(', ', $sumSelect) . " FROM tblvands_summary a
            INNER JOIN tblproject_team b ON a.team_id = b.team_id AND b.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0
            INNER JOIN tblbranch d ON b.branch_id = d.branch_id AND d.dstatus = 0
            WHERE $baseWhere AND $dateCondition";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':start' => $startDate, ':end' => $endDate]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) return [];
            $productTotals = [];
            foreach ($productCol as $col) {
                $productTotal = floatval($row[$col] ?? 0);
                $productName = $productColName[$col] ?? $col;
                $productTotals[$productName] = ($productTotals[$productName] ?? 0) + $productTotal;
            }
            arsort($productTotals);
            $out = [];
            $i = 0;
            foreach ($productTotals as $name => $total) {
                if ($i >= $limit) break;
                $out[] = ['productName' => $name, 'totalSales' => round($total, 0)];
                $i++;
            }
            return $out;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Handler: Anomaly Detection
     */
    private function handleAnomalyDetection($filters, $queryText = '')
    {
        $startDate = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $filters['date_to'] ?? date('Y-m-d');

        $baseWhere = "a.dstatus = 0 AND b.dstatus = 0 AND d.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0";
        if (!empty($filters['user_teams'])) {
            $teamList = $filters['user_teams'];
            if (is_string($teamList) && preg_match('/^\([\d,]+\)$/', $teamList)) {
                $baseWhere .= " AND b.team_id IN $teamList";
            }
        }
        $baseWhere .= $this->buildBranchFilterSql($filters);

        // Get daily sales data per branch to detect anomalies (Sundays excluded — non-working day)
        $sql = "SELECT d.branch_name, d.district, d.main_branch, a.activity_date AS sale_date,
                SUM(a.total_sales_deliveries) AS dailySales,
                COUNT(DISTINCT a.team_id) AS activeDsCount,
                DAYOFWEEK(a.activity_date) AS dow
            FROM tblvands_summary a
            LEFT JOIN tblproject_team b ON a.team_id = b.team_id
            LEFT JOIN tblbranch d ON b.branch_id = d.branch_id
            WHERE $baseWhere AND a.activity_date BETWEEN :start AND :end AND DAYOFWEEK(a.activity_date) != 1
            GROUP BY d.branch_name, d.district, d.main_branch, a.activity_date
            ORDER BY d.branch_name, sale_date";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':start' => $startDate, ':end' => $endDate]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Group by branch and compute stats
        $branchData = [];
        $branchMeta = [];
        foreach ($rows as $row) {
            $branch = $row['branch_name'] ?? 'Unknown';
            $branchData[$branch][] = [
                'date'    => $row['sale_date'],
                'sales'   => floatval($row['dailySales']),
                'dsCount' => intval($row['activeDsCount']),
                'dow'     => intval($row['dow']),
            ];
            if (!isset($branchMeta[$branch])) {
                $branchMeta[$branch] = [
                    'district'   => $row['district']    ?? '',
                    'mainBranch' => $row['main_branch'] ?? '',
                ];
            }
        }

        $anomalies = [];
        $dowNames  = [2 => 'Mon', 3 => 'Tue', 4 => 'Wed', 5 => 'Thu', 6 => 'Fri', 7 => 'Sat'];

        foreach ($branchData as $branch => $dailyData) {
            if (count($dailyData) < 3) continue;

            // ── Step 1: Build per-weekday baseline (mean + stdDev for sales and DS count) ──
            $dowGroups = [];
            foreach ($dailyData as $day) {
                $d = $day['dow'];
                $dowGroups[$d]['sales'][] = $day['sales'];
                $dowGroups[$d]['ds'][]    = $day['dsCount'];
            }

            $dowBaseline = [];
            foreach ($dowGroups as $d => $g) {
                $n     = count($g['sales']);
                $sMean = array_sum($g['sales']) / $n;
                $sVar  = $n >= 2
                    ? array_sum(array_map(function ($v) use ($sMean) { return pow($v - $sMean, 2); }, $g['sales'])) / $n
                    : 0;
                $dowBaseline[$d] = [
                    'mean'   => $sMean,
                    'stdDev' => sqrt($sVar),
                    'dsMean' => array_sum($g['ds']) / $n,
                    'count'  => $n,
                ];
            }

            // Overall fallback — used when a weekday has fewer than 2 data points
            $allSales      = array_column($dailyData, 'sales');
            $overallMean   = array_sum($allSales) / count($allSales);
            $overallVar    = array_sum(array_map(function ($v) use ($overallMean) { return pow($v - $overallMean, 2); }, $allSales)) / count($allSales);
            $overallStdDev = sqrt($overallVar);
            $overallDsMean = array_sum(array_column($dailyData, 'dsCount')) / count($dailyData);

            // ── Step 2: Score each day against its weekday baseline ──
            foreach ($dailyData as $day) {
                $d          = $day['dow'];
                $bl         = $dowBaseline[$d] ?? null;
                $useWeekday = $bl && $bl['count'] >= 2 && $bl['stdDev'] > 0;

                $mean       = $useWeekday ? $bl['mean']   : $overallMean;
                $stdDev     = $useWeekday ? $bl['stdDev'] : $overallStdDev;
                $expectedDs = $useWeekday ? $bl['dsMean'] : $overallDsMean;

                if ($stdDev == 0) continue;

                $zScore = ($day['sales'] - $mean) / $stdDev;
                if (abs($zScore) <= 2.0) continue;

                // ── Step 3: Auto-tag root cause ──
                $dsDropPct = $expectedDs > 0 ? (($expectedDs - $day['dsCount']) / $expectedDs) * 100 : 0;

                if ($day['dsCount'] == 0) {
                    $cause = 'no_ds_in_field';
                } elseif ($zScore < 0 && $dsDropPct >= 30) {
                    $cause = 'attendance_issue';
                } elseif ($zScore < 0) {
                    $cause = 'market_issue';
                } elseif ($zScore > 0 && $day['dsCount'] > $expectedDs * 1.2) {
                    $cause = 'extra_ds_deployed';
                } else {
                    $cause = 'demand_spike';
                }

                $severity = abs($zScore) > 3 ? 'high' : 'medium';
                $anomalies[] = [
                    'branch'           => $branch,
                    'date'             => $day['date'],
                    'weekday'          => $dowNames[$d] ?? '',
                    'district'         => $branchMeta[$branch]['district']   ?? '',
                    'mainBranch'       => $branchMeta[$branch]['mainBranch'] ?? '',
                    'actualSales'      => round($day['sales'], 2),
                    'expectedSales'    => round($mean, 2),
                    'zScore'           => round($zScore, 2),
                    'type'             => $zScore > 0 ? 'spike' : 'drop',
                    'severity'         => $severity,
                    'deviationPercent' => round((($day['sales'] - $mean) / $mean) * 100, 2),
                    'activeDsCount'    => $day['dsCount'],
                    'expectedDsCount'  => round($expectedDs, 1),
                    'cause'            => $cause,
                    'urgency'          => $this->assignAnomalyUrgency(round($zScore, 2), $severity, $cause, false),
                    'isRegional'       => false,
                ];
            }
        }

        // ── Regional Contagion Detection ──
        // ≥3 branches dropping in the same district on the same day = regional event (not branch-specific)
        $dropsByDistrictDate = [];
        foreach ($anomalies as $idx => $a) {
            if ($a['type'] === 'drop' && !empty($a['district'])) {
                $key = $a['date'] . '||' . $a['district'];
                $dropsByDistrictDate[$key][] = $idx;
            }
        }
        $regionalEvents = [];
        foreach ($dropsByDistrictDate as $key => $indices) {
            if (count($indices) < 3) continue;
            [$rDate, $rDistrict] = explode('||', $key, 2);
            foreach ($indices as $idx) {
                $anomalies[$idx]['isRegional'] = true;
            }
            $branchList = array_values(array_map(fn($i) => $anomalies[$i]['branch'], $indices));
            $avgZ        = array_sum(array_map(fn($i) => abs($anomalies[$i]['zScore']), $indices)) / count($indices);
            $uRank       = ['critical' => 0, 'warning' => 1, 'watch' => 2];
            $worstU      = 'watch';
            foreach ($indices as $i) {
                if (($uRank[$anomalies[$i]['urgency']] ?? 2) < ($uRank[$worstU] ?? 2)) {
                    $worstU = $anomalies[$i]['urgency'];
                }
            }
            $regionalEvents[] = [
                'date'             => $rDate,
                'district'         => $rDistrict,
                'affectedBranches' => $branchList,
                'branchCount'      => count($branchList),
                'avgZScore'        => round($avgZ, 2),
                'urgency'          => $worstU,
            ];
        }
        usort($regionalEvents, fn($a, $b) => $b['branchCount'] <=> $a['branchCount']);
        $regionalEventCount = count($regionalEvents);

        // ── Phase 3: Urgency sort (critical → warning → watch → |z-score|) ──
        $urgencyOrder = ['critical' => 0, 'warning' => 1, 'watch' => 2];
        usort($anomalies, function ($a, $b) use ($urgencyOrder) {
            $ua = $urgencyOrder[$a['urgency']] ?? 3;
            $ub = $urgencyOrder[$b['urgency']] ?? 3;
            if ($ua !== $ub) return $ua <=> $ub;
            return abs($b['zScore']) <=> abs($a['zScore']);
        });

        // Compute metrics from the full anomaly list (before slicing) so summary cards are accurate
        $spikes           = count(array_filter($anomalies, fn($a) => $a['type'] === 'spike'));
        $drops            = count(array_filter($anomalies, fn($a) => $a['type'] === 'drop'));
        $highSeverity     = count(array_filter($anomalies, fn($a) => $a['severity'] === 'high'));
        $attendanceIssues = count(array_filter($anomalies, fn($a) => $a['cause'] === 'attendance_issue'));
        $marketIssues     = count(array_filter($anomalies, fn($a) => $a['cause'] === 'market_issue'));
        $noDs             = count(array_filter($anomalies, fn($a) => $a['cause'] === 'no_ds_in_field'));
        $criticalCount    = count(array_filter($anomalies, fn($a) => $a['urgency'] === 'critical'));
        $warningCount     = count(array_filter($anomalies, fn($a) => $a['urgency'] === 'warning'));
        $watchCount       = count(array_filter($anomalies, fn($a) => $a['urgency'] === 'watch'));

        // Limit to 200 for the response payload (charts + table) — enough for 3-month ranges
        $anomalies = array_slice($anomalies, 0, 200);

        // ── Phase 2: Focus brand anomalies ──
        $focusBrandAnomalies = $this->detectFocusBrandAnomalies($filters, $startDate, $endDate, $baseWhere);
        $fbDrops  = count(array_filter($focusBrandAnomalies, fn($a) => $a['type'] === 'drop'));
        $fbSpikes = count(array_filter($focusBrandAnomalies, fn($a) => $a['type'] === 'spike'));

        // ── Phase 3: Streak detection ──
        $streaks     = $this->detectStreaks(array_merge($anomalies, $focusBrandAnomalies));
        $streakCount = count($streaks);

        // ── Phase 4a: DS breakdown per anomaly (branch + date) ──
        if (!empty($anomalies)) {
            $anomalyBranches = array_values(array_unique(array_column($anomalies, 'branch')));
            $anomalyDates    = array_values(array_unique(array_column($anomalies, 'date')));
            $bParams = [];
            foreach ($anomalyBranches as $i => $b) $bParams[":ab$i"] = $b;
            $dParams = [];
            foreach ($anomalyDates as $i => $d) $dParams[":ad$i"] = $d;
            $bIn = implode(',', array_keys($bParams));
            $dIn = implode(',', array_keys($dParams));

            $dsSql = "SELECT d.branch_name, a.activity_date, b.team_name,
                          SUM(a.total_sales_deliveries) AS sales
                      FROM tblvands_summary a
                      JOIN tblproject_team b ON a.team_id = b.team_id
                      JOIN tblbranch d ON b.branch_id = d.branch_id
                      WHERE $baseWhere AND d.branch_name IN ($bIn) AND a.activity_date IN ($dIn)
                      GROUP BY d.branch_name, a.activity_date, b.team_id, b.team_name
                      ORDER BY d.branch_name, a.activity_date, sales DESC";

            $dsStmt = $this->pdo->prepare($dsSql);
            $dsStmt->execute(array_merge($bParams, $dParams));
            $dsBreakdownMap = [];
            foreach ($dsStmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $key = $row['branch_name'] . '|' . $row['activity_date'];
                $dsBreakdownMap[$key][] = ['dsName' => $row['team_name'] ?? 'Unknown', 'sales' => round(floatval($row['sales']), 0)];
            }
            foreach ($anomalies as &$a) {
                $a['dsBreakdown'] = $dsBreakdownMap[$a['branch'] . '|' . $a['date']] ?? [];
            }
            unset($a);

            // ── Phase 4b: Anomaly map points (avg branch lat/lng coloured by worst urgency) ──
            $mbParams = [];
            foreach ($anomalyBranches as $i => $b) $mbParams[":mb$i"] = $b;
            $mbIn = implode(',', array_keys($mbParams));

            $mapSql = "SELECT d.branch_name,
                           ROUND(AVG(NULLIF(att.lt, 0)), 6) AS lat,
                           ROUND(AVG(NULLIF(att.lg, 0)), 6) AS lng
                       FROM tblproject_team b
                       JOIN tblbranch d ON b.branch_id = d.branch_id AND d.dstatus = 0
                       LEFT JOIN tblattendance att ON b.team_id = att.team_id
                           AND att.s_id = 99 AND att.dstatus = 0
                           AND att.lt != 0 AND att.lg != 0
                           AND att.capture_date BETWEEN :mstart AND :mend
                       WHERE b.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0
                           AND d.branch_name IN ($mbIn)
                       GROUP BY d.branch_name
                       HAVING lat IS NOT NULL AND lat != 0";

            $mapStmt = $this->pdo->prepare($mapSql);
            $mapStmt->execute(array_merge($mbParams, [':mstart' => $startDate, ':mend' => $endDate]));
            $branchCoords = [];
            foreach ($mapStmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $branchCoords[$row['branch_name']] = ['lat' => floatval($row['lat']), 'lng' => floatval($row['lng'])];
            }

            $urgencyRank = ['critical' => 0, 'warning' => 1, 'watch' => 2];
            $branchSummary = [];
            foreach ($anomalies as $a) {
                $br = $a['branch'];
                if (!isset($branchSummary[$br])) $branchSummary[$br] = ['count' => 0, 'urgency' => 'watch'];
                $branchSummary[$br]['count']++;
                if (($urgencyRank[$a['urgency']] ?? 2) < ($urgencyRank[$branchSummary[$br]['urgency']] ?? 2)) {
                    $branchSummary[$br]['urgency'] = $a['urgency'];
                }
            }

            $anomalyMapPoints = [];
            foreach ($branchSummary as $br => $info) {
                if (!isset($branchCoords[$br])) continue;
                $anomalyMapPoints[] = [
                    'branch'       => $br,
                    'lat'          => $branchCoords[$br]['lat'],
                    'lng'          => $branchCoords[$br]['lng'],
                    'urgency'      => $info['urgency'],
                    'anomalyCount' => $info['count'],
                ];
            }
        } else {
            $anomalyMapPoints = [];
        }

        $aiContext = "Urgency — Critical: $criticalCount, Warning: $warningCount, Watch: $watchCount\n"
            . "Spikes: $spikes, Drops: $drops, High severity: $highSeverity\n"
            . "Root causes — Attendance issues: $attendanceIssues, Market/demand issues: $marketIssues, No DS in field: $noDs\n"
            . ($regionalEventCount > 0 ? "Regional contagion events (3+ branches in same district dropping same day): $regionalEventCount\n" : '')
            . ($streakCount > 0 ? "Consecutive-day streaks detected: $streakCount\n" : '')
            . "\nOverall anomaly data (top 10, urgency-sorted, day-of-week adjusted baseline, Sundays excluded):\n"
            . json_encode(array_slice($anomalies, 0, 10), JSON_PRETTY_PRINT);

        if (!empty($regionalEvents)) {
            $aiContext .= "\n\nRegional Contagion Events (district-wide drops — likely external cause, NOT branch-specific):\n"
                . json_encode($regionalEvents, JSON_PRETTY_PRINT);
        }

        if (!empty($focusBrandAnomalies)) {
            $aiContext .= "\n\nFocus Brand Anomalies ($fbDrops drops, $fbSpikes spikes — HIGHEST PRIORITY, these are the company's priority SKUs):\n"
                . json_encode(array_slice($focusBrandAnomalies, 0, 5), JSON_PRETTY_PRINT);
        }

        if (!empty($streaks)) {
            $aiContext .= "\n\nStreak Alerts (consecutive anomaly days per branch):\n"
                . json_encode($streaks, JSON_PRETTY_PRINT);
        }

        $aiText = $this->callOpenAi(
            "User asked: \"$queryText\"\n"
            . "IMPORTANT: All sales figures are in UNITS (sticks/packs), not currency. Do not use \$ or any currency symbol.\n"
            . "Context: Anomaly detection. Sundays excluded. Day-of-week adjusted baseline used. Anomalies are urgency-triaged (critical/warning/watch).\n"
            . "Each anomaly has a pre-tagged cause: attendance_issue (DS count dropped ≥30%), market_issue (DS normal but sales dropped), no_ds_in_field (zero DS), demand_spike (organic surge), extra_ds_deployed (extra DS drove spike).\n"
            . ($regionalEventCount > 0 ? "REGIONAL CONTAGION DETECTED: $regionalEventCount district(s) had 3+ branches dropping on the same day — this is likely an external/market-wide event, NOT individual branch failure. Do NOT blame branch managers for these.\n" : '')
            . (!empty($streaks) ? "STREAKS DETECTED: Some branches have consecutive anomaly days — these compound into serious business risk.\n" : '')
            . (!empty($focusBrandAnomalies) ? "Focus brand anomalies are listed separately — treat these as HIGHEST PRIORITY.\n" : '')
            . $aiContext . "\n\n"
            . "Provide: (1) CRITICAL items first — regional events, streaks, focus brand drops. (2) For regional events, suggest external cause investigation (holidays, competition, weather). (3) Summary by urgency level. (4) Root cause breakdown with actions. Use #### headings."
        );

        return [
            'query_name'            => 'Anomaly Detection',
            'record_count'          => count($anomalies),
            'records'               => $anomalies,
            'focus_brand_anomalies' => $focusBrandAnomalies,
            'streaks'               => $streaks,
            'regional_events'       => $regionalEvents,
            'anomaly_map_points'    => $anomalyMapPoints,
            'metrics'               => [
                'total_anomalies'           => count($anomalies),
                'spikes'                    => $spikes,
                'drops'                     => $drops,
                'high_severity'             => $highSeverity,
                'branches_analyzed'         => count($branchData),
                'attendance_issues'         => $attendanceIssues,
                'market_issues'             => $marketIssues,
                'no_ds'                     => $noDs,
                'focus_brand_anomaly_count' => count($focusBrandAnomalies),
                'critical_count'            => $criticalCount,
                'warning_count'             => $warningCount,
                'watch_count'               => $watchCount,
                'streak_count'              => $streakCount,
                'regional_event_count'      => $regionalEventCount,
            ],
            'ai_text' => $aiText,
        ];
    }

    /**
     * Phase 2: Detect anomalies in focus brand products per branch.
     * Loads focus brand column mappings from tblbranch_pickupstock_products,
     * builds a single dynamic query, and applies the same day-of-week
     * Z-score baseline + root cause tagging as the main anomaly handler.
     */
    private function detectFocusBrandAnomalies($filters, $startDate, $endDate, $baseWhere)
    {
        // Load focus brand product mappings for all branches
        $mappingStmt = $this->pdo->prepare(
            "SELECT p.branch_id, p.product_name, p.summary_column_name, p.category_name, d.branch_name
             FROM tblbranch_pickupstock_products p
             JOIN tblbranch d ON p.branch_id = d.branch_id
             WHERE p.is_focusbrand = 1 AND p.dstatus = 0 AND d.dstatus = 0 AND p.json_id = 99
             ORDER BY p.branch_id, p.sort_order"
        );
        $mappingStmt->execute();
        $mappings = $mappingStmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($mappings)) return [];

        // Validate column names — whitelist only total_sale_productN (prevents SQL injection)
        $validColPattern = '/^total_sale_product\d+$/';
        $uniqueCols = array_values(array_unique(array_filter(
            array_column($mappings, 'summary_column_name'),
            fn($col) => preg_match($validColPattern, $col)
        )));

        if (empty($uniqueCols)) return [];

        // Build branch → [col → product info] lookup
        $branchProducts = [];
        foreach ($mappings as $m) {
            $col = $m['summary_column_name'];
            if (!preg_match($validColPattern, $col)) continue;
            $branchProducts[$m['branch_name']][$col] = [
                'productName'  => $m['product_name'],
                'categoryName' => $m['category_name'],
            ];
        }

        // One dynamic query for all focus brand columns
        $colSelects = array_map(fn($col) => "SUM(a.$col) AS $col", $uniqueCols);
        $sql = "SELECT d.branch_name, a.activity_date AS sale_date,
                    DAYOFWEEK(a.activity_date) AS dow,
                    COUNT(DISTINCT a.team_id) AS activeDsCount,
                    " . implode(', ', $colSelects) . "
                FROM tblvands_summary a
                LEFT JOIN tblproject_team b ON a.team_id = b.team_id
                LEFT JOIN tblbranch d ON b.branch_id = d.branch_id
                WHERE $baseWhere AND a.activity_date BETWEEN :start AND :end
                    AND DAYOFWEEK(a.activity_date) != 1
                GROUP BY d.branch_name, a.activity_date
                ORDER BY d.branch_name, sale_date";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':start' => $startDate, ':end' => $endDate]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($rows)) return [];

        // Group daily data by (branch, product_column)
        $productData = [];
        foreach ($rows as $row) {
            $branchName = $row['branch_name'] ?? 'Unknown';
            if (!isset($branchProducts[$branchName])) continue;

            $dow     = intval($row['dow']);
            $dsCount = intval($row['activeDsCount']);
            $date    = $row['sale_date'];

            foreach ($branchProducts[$branchName] as $col => $info) {
                $productData[$branchName][$col][] = [
                    'date'    => $date,
                    'sales'   => floatval($row[$col] ?? 0),
                    'dow'     => $dow,
                    'dsCount' => $dsCount,
                ];
            }
        }

        // Apply day-of-week Z-score baseline + root cause per (branch, product)
        $anomalies = [];
        $dowNames  = [2 => 'Mon', 3 => 'Tue', 4 => 'Wed', 5 => 'Thu', 6 => 'Fri', 7 => 'Sat'];

        foreach ($productData as $branchName => $productCols) {
            foreach ($productCols as $col => $dailyData) {
                if (count($dailyData) < 3) continue;
                if (array_sum(array_column($dailyData, 'sales')) == 0) continue; // product not active

                $info = $branchProducts[$branchName][$col];

                // Per-weekday baseline
                $dowGroups = [];
                foreach ($dailyData as $day) {
                    $d = $day['dow'];
                    $dowGroups[$d]['sales'][] = $day['sales'];
                    $dowGroups[$d]['ds'][]    = $day['dsCount'];
                }

                $dowBaseline = [];
                foreach ($dowGroups as $d => $g) {
                    $n     = count($g['sales']);
                    $sMean = array_sum($g['sales']) / $n;
                    $sVar  = $n >= 2
                        ? array_sum(array_map(fn($v) => pow($v - $sMean, 2), $g['sales'])) / $n
                        : 0;
                    $dowBaseline[$d] = [
                        'mean'   => $sMean,
                        'stdDev' => sqrt($sVar),
                        'dsMean' => array_sum($g['ds']) / $n,
                        'count'  => $n,
                    ];
                }

                $allSales      = array_column($dailyData, 'sales');
                $overallMean   = array_sum($allSales) / count($allSales);
                $overallVar    = array_sum(array_map(fn($v) => pow($v - $overallMean, 2), $allSales)) / count($allSales);
                $overallStdDev = sqrt($overallVar);
                $overallDsMean = array_sum(array_column($dailyData, 'dsCount')) / count($dailyData);

                foreach ($dailyData as $day) {
                    $d          = $day['dow'];
                    $bl         = $dowBaseline[$d] ?? null;
                    $useWeekday = $bl && $bl['count'] >= 2 && $bl['stdDev'] > 0;

                    $mean       = $useWeekday ? $bl['mean']   : $overallMean;
                    $stdDev     = $useWeekday ? $bl['stdDev'] : $overallStdDev;
                    $expectedDs = $useWeekday ? $bl['dsMean'] : $overallDsMean;

                    if ($stdDev == 0) continue;

                    $zScore = ($day['sales'] - $mean) / $stdDev;
                    if (abs($zScore) <= 2.0) continue;

                    $dsDropPct = $expectedDs > 0 ? (($expectedDs - $day['dsCount']) / $expectedDs) * 100 : 0;

                    if ($day['dsCount'] == 0) {
                        $cause = 'no_ds_in_field';
                    } elseif ($zScore < 0 && $dsDropPct >= 30) {
                        $cause = 'attendance_issue';
                    } elseif ($zScore < 0) {
                        $cause = 'market_issue';
                    } elseif ($zScore > 0 && $day['dsCount'] > $expectedDs * 1.2) {
                        $cause = 'extra_ds_deployed';
                    } else {
                        $cause = 'demand_spike';
                    }

                    $fbSeverity = abs($zScore) > 3 ? 'high' : 'medium';
                    $anomalies[] = [
                        'branch'           => $branchName,
                        'date'             => $day['date'],
                        'weekday'          => $dowNames[$d] ?? '',
                        'productName'      => $info['productName'],
                        'categoryName'     => $info['categoryName'],
                        'actualSales'      => round($day['sales'], 2),
                        'expectedSales'    => round($mean, 2),
                        'zScore'           => round($zScore, 2),
                        'type'             => $zScore > 0 ? 'spike' : 'drop',
                        'severity'         => $fbSeverity,
                        'deviationPercent' => round((($day['sales'] - $mean) / $mean) * 100, 2),
                        'activeDsCount'    => $day['dsCount'],
                        'expectedDsCount'  => round($expectedDs, 1),
                        'cause'            => $cause,
                        'urgency'          => $this->assignAnomalyUrgency(round($zScore, 2), $fbSeverity, $cause, true),
                    ];
                }
            }
        }

        $urgencyOrder = ['critical' => 0, 'warning' => 1, 'watch' => 2];
        usort($anomalies, function ($a, $b) use ($urgencyOrder) {
            $ua = $urgencyOrder[$a['urgency']] ?? 3;
            $ub = $urgencyOrder[$b['urgency']] ?? 3;
            if ($ua !== $ub) return $ua <=> $ub;
            return abs($b['zScore']) <=> abs($a['zScore']);
        });
        return array_slice($anomalies, 0, 20);
    }

    /**
     * Phase 3: Assign urgency level (critical / warning / watch) to an anomaly.
     * Focus brand drops are elevated to critical regardless of z-score magnitude.
     */
    private function assignAnomalyUrgency(float $zScore, string $severity, string $cause, bool $isFocusBrand): string
    {
        $absZ   = abs($zScore);
        $isDrop = $zScore < 0;

        if ($absZ > 3.5) return 'critical';
        if ($isFocusBrand && $isDrop && $absZ > 2.0) return 'critical';
        if ($cause === 'no_ds_in_field' && $absZ > 2.5) return 'critical';

        if ($absZ > 2.5) return 'warning';
        if (in_array($cause, ['attendance_issue', 'market_issue', 'no_ds_in_field'], true)) return 'warning';

        return 'watch';
    }

    /**
     * Phase 3: Detect streaks of 3+ consecutive anomaly days for the same branch.
     * Consecutive = date diff ≤ 2 calendar days (allows for Sunday gap).
     */
    private function detectStreaks(array $anomalies): array
    {
        $byBranch = [];
        foreach ($anomalies as $a) {
            $byBranch[$a['branch']][] = $a;
        }

        $streaks = [];
        foreach ($byBranch as $branch => $items) {
            // Deduplicate by (date, type) — multiple anomalies on the same day don't constitute multiple streak days
            $seen   = [];
            $unique = [];
            foreach ($items as $item) {
                $key = $item['date'] . '|' . $item['type'];
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $unique[]   = $item;
                }
            }
            $items = $unique;
            usort($items, fn($a, $b) => strcmp($a['date'], $b['date']));
            $n = count($items);
            $i = 0;
            while ($i < $n) {
                $type     = $items[$i]['type'];
                $prevDate = new \DateTime($items[$i]['date']);
                $j        = $i + 1;
                while ($j < $n && $items[$j]['type'] === $type) {
                    $curr = new \DateTime($items[$j]['date']);
                    if ($prevDate->diff($curr)->days > 2) break;
                    $prevDate = $curr;
                    $j++;
                }
                $len = $j - $i;
                if ($len >= 3) {
                    $slice  = array_slice($items, $i, $len);
                    $avgZ   = array_sum(array_map(fn($a) => abs($a['zScore']), $slice)) / $len;
                    $streaks[] = [
                        'branch'    => $branch,
                        'type'      => $type,
                        'days'      => $len,
                        'startDate' => $items[$i]['date'],
                        'endDate'   => $items[$j - 1]['date'],
                        'avgZScore' => round($avgZ, 2),
                        'urgency'   => $len >= 5 ? 'critical' : ($len >= 4 ? 'warning' : 'watch'),
                    ];
                }
                $i = $j;
            }
        }

        usort($streaks, fn($a, $b) => $b['days'] <=> $a['days']);
        return $streaks;
    }

    // ========================================================================
    // ADDITIONAL HANDLERS
    // ========================================================================

    /**
     * Handler: Branch Qualified Attendance (dynamic hierarchy level)
     */
    private function handleBranchQualifiedAttendance($filters, $queryText = '')
    {
        $queryLower = strtolower($queryText);
        $showWorst = $this->isWorstQuery($queryLower);
        $conditions = $this->buildBaseConditions($filters);
        $whereClause = implode(' AND ', $conditions);
        $sortDir = $showWorst ? 'ASC' : 'DESC';

        // Detect hierarchy level from query
        $groupCol = 'd.branch_name';
        $dimLabel = 'Region';
        $limit = 10;
        if (preg_match('/\bsection\b/', $queryLower)) {
            $groupCol = 'b.section'; $dimLabel = 'Section'; $limit = 15;
        } elseif (preg_match('/\bcircle\b/', $queryLower)) {
            $groupCol = 'b.circle'; $dimLabel = 'Circle'; $limit = 15;
        } elseif (preg_match('/\bwd[\s_]?code\b|\bwdcode\b/', $queryLower)) {
            $groupCol = 'b.wd_code'; $dimLabel = 'WD Code'; $limit = 25;
        } elseif (preg_match('/\bbranch\b/', $queryLower) && !preg_match('/\bregion\b/', $queryLower)) {
            $groupCol = 'd.main_branch'; $dimLabel = 'Branch';
        } elseif (preg_match('/\bdistrict\b/', $queryLower)) {
            $groupCol = 'd.district'; $dimLabel = 'District';
        }

        $sql = "SELECT $groupCol AS dimName, d.district,
                    SUM(CASE WHEN a.is_qualified = 1 THEN 1 ELSE 0 END) AS qualifiedDays,
                    COUNT(*) AS totalDays,
                    COUNT(DISTINCT a.team_id) AS totalDs,
                    SUM(a.total_sales_deliveries) AS totalSales,
                    COUNT(DISTINCT a.activity_date) AS uniqueDays
                FROM tblvands_summary a
                INNER JOIN tblproject_team b ON a.team_id = b.team_id
                INNER JOIN tblbranch d ON b.branch_id = d.branch_id
                WHERE $whereClause AND $groupCol IS NOT NULL AND $groupCol != ''
                GROUP BY $groupCol" . ($groupCol !== 'd.district' ? ", d.district" : "") . "
                ORDER BY (SUM(CASE WHEN a.is_qualified = 1 THEN 1 ELSE 0 END) / COUNT(*)) $sortDir
                LIMIT $limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $records = [];
        foreach ($rows as $row) {
            $qualifiedDays = intval($row['qualifiedDays'] ?? 0);
            $totalDays = intval($row['totalDays'] ?? 0);
            $qualRate = $totalDays > 0 ? round(($qualifiedDays / $totalDays) * 100, 2) : 0;
            $records[] = [
                'regionName' => $row['dimName'] ?? '',
                'district' => $row['district'] ?? '',
                'qualifiedDays' => $qualifiedDays,
                'totalDays' => $totalDays,
                'qualificationRate' => $qualRate,
                'totalDs' => intval($row['totalDs'] ?? 0),
                'totalSales' => round(floatval($row['totalSales'] ?? 0), 2)
            ];
        }

        $totalSalesAll = array_sum(array_column($records, 'totalSales'));
        $avgQualRate = count($records) > 0
            ? round(array_sum(array_column($records, 'qualificationRate')) / count($records), 2) : 0;

        $aiText = $this->generateAiForData('branch_qualified_attendance', $queryText, $records, $totalSalesAll, '', true);

        return [
            'query_name' => ($showWorst ? 'Worst' : 'Best') . " $dimLabel Qualified Attendance",
            'record_count' => count($records),
            'records' => $records,
            'dim_label' => $dimLabel,
            'metrics' => [
                'total_sales' => round($totalSalesAll, 2),
                'total_count' => count($records),
                'avg_qualification_rate' => $avgQualRate
            ],
            'ai_text' => $aiText,
            'show_worst' => $showWorst
        ];
    }

    /**
     * Handler: Dimension Performance (circle, district, section, branch, wd_code, region)
     */
    private function handleDimensionPerformance($filters, $queryText = '')
    {
        $queryLower = strtolower($queryText);
        $showWorst = $this->isWorstQuery($queryLower);
        $conditions = $this->buildBaseConditions($filters);
        $whereClause = implode(' AND ', $conditions);
        $sortDir = $showWorst ? 'ASC' : 'DESC';

        // Detect which dimension to group by.
        // Use "compare X" pattern first, then fallback to keyword presence.
        // "region" must be checked before "branch" because queries like
        // "compare regions of EGAU branch" want region as the dimension.
        $dimension = 'd.district';
        $dimLabel = 'district';
        if (strpos($queryLower, 'section') !== false) {
            $dimension = 'b.section'; $dimLabel = 'section';
        } elseif (strpos($queryLower, 'circle') !== false) {
            $dimension = 'b.circle'; $dimLabel = 'circle';
        } elseif (strpos($queryLower, 'wd code') !== false || strpos($queryLower, 'wd_code') !== false || strpos($queryLower, 'wdcode') !== false) {
            $dimension = 'b.wd_code'; $dimLabel = 'wd_code';
        } elseif (strpos($queryLower, 'region') !== false) {
            $dimension = 'd.branch_name'; $dimLabel = 'region';
        } elseif (preg_match('/\bbranch(es)?\s+(performance|comparison|wise|ranking|breakdown)\b/', $queryLower) ||
                  preg_match('/\bcompare\s+branch/', $queryLower)) {
            $dimension = 'd.main_branch'; $dimLabel = 'branch';
        } elseif (strpos($queryLower, 'district') !== false) {
            $dimension = 'd.district'; $dimLabel = 'district';
        }

        $limit = ($dimLabel === 'wd_code') ? 25 : 20;

        $sql = "SELECT $dimension AS name,
                    SUM(a.total_sales_deliveries) AS totalSales,
                    COUNT(DISTINCT a.team_id) AS dsCount,
                    SUM(CASE WHEN a.is_qualified = 1 THEN 1 ELSE 0 END) AS qualifiedDays,
                    COUNT(*) AS totalDays,
                    ROUND((SUM(CASE WHEN a.is_beat_adherence = 'Yes' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) * 100, 1) AS adherenceRate
                FROM tblvands_summary a
                INNER JOIN tblproject_team b ON a.team_id = b.team_id
                INNER JOIN tblbranch d ON b.branch_id = d.branch_id
                WHERE $whereClause AND $dimension IS NOT NULL AND $dimension != ''
                GROUP BY $dimension
                ORDER BY totalSales $sortDir
                LIMIT $limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $records = [];
        foreach ($rows as $row) {
            $qualDays = intval($row['qualifiedDays'] ?? 0);
            $totalDays = intval($row['totalDays'] ?? 0);
            $records[] = [
                'name' => $row['name'] ?? '',
                'totalSales' => round(floatval($row['totalSales'] ?? 0), 2),
                'dsCount' => intval($row['dsCount'] ?? 0),
                'qualifiedDays' => $qualDays,
                'totalDays' => $totalDays,
                'qualificationRate' => $totalDays > 0 ? round(($qualDays / $totalDays) * 100, 2) : 0,
                'adherenceRate' => floatval($row['adherenceRate'] ?? 0)
            ];
        }

        $totalSalesAll = array_sum(array_column($records, 'totalSales'));
        $aiText = $this->generateAiForData($dimLabel . '_performance', $queryText, $records, $totalSalesAll, '', true);

        // Fetch geographic heatmap data for comparison visualization
        $startDate = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $filters['date_to'] ?? date('Y-m-d');
        $heatmapPoints = $this->getComparisonHeatmapPoints($filters, $startDate, $endDate);

        return [
            'query_name' => ucfirst($dimLabel) . ' Performance',
            'record_count' => count($records),
            'records' => $records,
            'metrics' => ['total_sales' => round($totalSalesAll, 2)],
            'ai_text' => $aiText,
            'compare_type' => $dimLabel,
            'show_worst' => $showWorst,
            'heatmap_points' => $heatmapPoints
        ];
    }

    /**
     * Get geographic heatmap points for comparison queries
     */
    private function getComparisonHeatmapPoints($filters, $startDate, $endDate)
    {
        $points = [];
        try {
            $baseWhere = "b.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0 AND br.dstatus = 0";
            if (!empty($filters['user_teams'])) {
                $teamList = $filters['user_teams'];
                if (is_string($teamList) && preg_match('/^\([\d,]+\)$/', $teamList)) {
                    $baseWhere .= " AND b.team_id IN $teamList";
                }
            }
            // Apply hierarchy filters so heatmap is scoped to the same data as the comparison
            if (!empty($filters['main_branch'])) {
                $val = is_array($filters['main_branch']) ? $filters['main_branch'][0] : $filters['main_branch'];
                $baseWhere .= " AND br.main_branch = '" . str_replace("'", "''", $val) . "'";
            }
            if (!empty($filters['region']) && is_array($filters['region'])) {
                $regionNames = implode(',', array_map(fn($v) => "'" . str_replace("'", "''", $v) . "'", $filters['region']));
                $baseWhere .= " AND br.branch_name IN ($regionNames)";
            }
            if (!empty($filters['district']) && is_array($filters['district'])) {
                $districtNames = implode(',', array_map(fn($v) => "'" . str_replace("'", "''", $v) . "'", $filters['district']));
                $baseWhere .= " AND br.district IN ($districtNames)";
            }
            if (!empty($filters['circle']) && is_array($filters['circle'])) {
                $circleNames = implode(',', array_map(fn($v) => "'" . str_replace("'", "''", $v) . "'", $filters['circle']));
                $baseWhere .= " AND b.circle IN ($circleNames)";
            }
            if (!empty($filters['section']) && is_array($filters['section'])) {
                $sectionNames = implode(',', array_map(fn($v) => "'" . str_replace("'", "''", $v) . "'", $filters['section']));
                $baseWhere .= " AND b.section IN ($sectionNames)";
            }

            $sql = "SELECT br.branch_name AS regionName, br.district, br.main_branch,
                           b.circle, SUM(a.total_sales_deliveries) AS totalSales,
                           COUNT(DISTINCT b.team_id) AS dsCount,
                           AVG(att.lt) AS avgLat, AVG(att.lg) AS avgLng
                    FROM tblvands_summary a
                    INNER JOIN tblproject_team b ON a.team_id = b.team_id
                    INNER JOIN tblbranch br ON b.branch_id = br.branch_id AND br.dstatus = 0
                    LEFT JOIN tblattendance att ON b.team_id = att.team_id AND att.s_id = 99 AND att.dstatus = 0
                        AND att.lt != 0 AND att.lg != 0 AND att.capture_date BETWEEN :start AND :end
                    WHERE a.dstatus = 0 AND $baseWhere
                        AND a.activity_date BETWEEN :start2 AND :end2
                    GROUP BY br.branch_name, br.district, br.main_branch, b.circle
                    HAVING avgLat IS NOT NULL AND avgLng IS NOT NULL
                    ORDER BY totalSales DESC LIMIT 50";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':start' => $startDate, ':end' => $endDate, ':start2' => $startDate, ':end2' => $endDate]);
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $p) {
                $points[] = [
                    'lat' => floatval($p['avgLat']),
                    'lng' => floatval($p['avgLng']),
                    'sales' => round(floatval($p['totalSales']), 0),
                    'region' => $p['regionName'],
                    'district' => $p['district'] ?? '',
                    'branch' => $p['main_branch'] ?? '',
                    'circle' => $p['circle'] ?? '',
                    'dsCount' => intval($p['dsCount'])
                ];
            }
        } catch (\Exception $e) {}
        return $points;
    }

    /**
     * Handler: Daily Sales Trend
     */
    private function handleDailySalesTrend($filters, $queryText = '')
    {
        $conditions = $this->buildBaseConditions($filters);
        $whereClause = implode(' AND ', $conditions);

        $sql = "SELECT a.activity_date,
                    SUM(a.total_sales_deliveries) AS totalSales,
                    COUNT(DISTINCT a.team_id) AS dsActive,
                    SUM(CASE WHEN a.is_qualified = 1 THEN 1 ELSE 0 END) AS qualifiedDays,
                    COUNT(*) AS totalDays
                FROM tblvands_summary a
                INNER JOIN tblproject_team b ON a.team_id = b.team_id
                INNER JOIN tblbranch d ON b.branch_id = d.branch_id
                WHERE $whereClause
                GROUP BY a.activity_date
                ORDER BY a.activity_date ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $records = [];
        foreach ($rows as $row) {
            $qualDays = intval($row['qualifiedDays'] ?? 0);
            $totalDays = intval($row['totalDays'] ?? 0);
            $records[] = [
                'date' => $row['activity_date'] ?? '',
                'totalSales' => round(floatval($row['totalSales'] ?? 0), 2),
                'dsActive' => intval($row['dsActive'] ?? 0),
                'qualifiedRate' => $totalDays > 0 ? round(($qualDays / $totalDays) * 100, 2) : 0
            ];
        }

        $totalSalesAll = array_sum(array_column($records, 'totalSales'));
        $days = count($records);
        $aiText = $this->generateAiForData('daily_sales_trend', $queryText, $records, $totalSalesAll);

        return [
            'query_name' => 'Daily Sales Trend',
            'record_count' => count($records),
            'records' => $records,
            'metrics' => [
                'total_sales' => round($totalSalesAll, 2),
                'avg_daily_sales' => $days > 0 ? round($totalSalesAll / $days, 2) : 0
            ],
            'ai_text' => $aiText
        ];
    }

    /**
     * Handler: Outlet Coverage
     */
    private function handleOutletCoverage($filters, $queryText = '')
    {
        $queryLower = strtolower($queryText);
        $showWorst = $this->isWorstQuery($queryLower);
        $conditions = $this->buildBaseConditions($filters);
        $whereClause = implode(' AND ', $conditions);
        $sortDir = $showWorst ? 'ASC' : 'DESC';

        $sql = "SELECT b.team_name AS ds_name,
                    SUM(a.planned_outlets) AS total_outlets_planned,
                    SUM(a.total_sales_deliveries) AS total_outlets_visited,
                    SUM(a.total_sellin_shops) AS total_outlets_billed,
                    COUNT(DISTINCT a.activity_date) AS working_days
                FROM tblvands_summary a
                INNER JOIN tblproject_team b ON a.team_id = b.team_id
                INNER JOIN tblbranch d ON b.branch_id = d.branch_id
                WHERE $whereClause
                GROUP BY b.team_id, b.team_name
                HAVING SUM(a.planned_outlets) > 0
                ORDER BY (SUM(a.total_sellin_shops) / NULLIF(SUM(a.total_sales_deliveries), 0)) $sortDir
                LIMIT 10";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $records = [];
        foreach ($rows as $row) {
            $planned = intval($row['total_outlets_planned'] ?? 0);
            $visited = intval($row['total_outlets_visited'] ?? 0);
            $billed = intval($row['total_outlets_billed'] ?? 0);
            $coverageRate = $planned > 0 ? round(($visited / $planned) * 100, 2) : 0;
            $billingRate = $visited > 0 ? round(($billed / $visited) * 100, 2) : 0;
            $records[] = [
                'dsName' => $row['ds_name'] ?? 'Unknown',
                'plannedOutlets' => $planned,
                'visitedOutlets' => $visited,
                'billedOutlets' => $billed,
                'coverageRate' => $coverageRate,
                'billingRate' => $billingRate,
                'workingDays' => intval($row['working_days'] ?? 0)
            ];
        }

        $aiText = $this->generateAiForData('outlet_coverage', $queryText, $records, 0);

        return [
            'query_name' => 'Outlet Coverage',
            'record_count' => count($records),
            'records' => $records,
            'metrics' => ['total_ds' => count($records)],
            'ai_text' => $aiText,
            'show_worst' => $showWorst
        ];
    }

    /**
     * Handler: Time Productivity
     */
    private function handleTimeProductivity($filters, $queryText = '')
    {
        $queryLower = strtolower($queryText);
        $showWorst = $this->isWorstQuery($queryLower);
        $conditions = $this->buildBaseConditions($filters);
        $whereClause = implode(' AND ', $conditions);
        $sortDir = $showWorst ? 'ASC' : 'DESC';

        $sql = "SELECT b.team_name AS ds_name,
                    AVG(a.time_in_market) AS avg_time_in_market,
                    AVG(a.total_meter_travelled / 1000) AS avg_km,
                    SUM(a.total_sales_deliveries) AS total_sales,
                    SUM(a.time_in_market) AS total_time,
                    COUNT(DISTINCT a.activity_date) AS working_days
                FROM tblvands_summary a
                INNER JOIN tblproject_team b ON a.team_id = b.team_id
                INNER JOIN tblbranch d ON b.branch_id = d.branch_id
                WHERE $whereClause AND a.time_in_market > 0
                GROUP BY b.team_id, b.team_name
                ORDER BY (SUM(a.total_sales_deliveries) / NULLIF(SUM(a.time_in_market), 0)) $sortDir
                LIMIT 10";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $records = [];
        foreach ($rows as $row) {
            $totalTime = floatval($row['total_time'] ?? 0);
            $totalSales = floatval($row['total_sales'] ?? 0);
            $records[] = [
                'dsName' => $row['ds_name'] ?? 'Unknown',
                'avgTimeInMarket' => round(floatval($row['avg_time_in_market'] ?? 0), 0),
                'avgKm' => round(floatval($row['avg_km'] ?? 0), 1),
                'totalSales' => round($totalSales, 0),
                'salesPerMinute' => $totalTime > 0 ? round($totalSales / $totalTime, 2) : 0
            ];
        }

        $aiText = $this->generateAiForData('time_productivity', $queryText, $records, 0);

        return [
            'query_name' => 'Time Productivity',
            'record_count' => count($records),
            'records' => $records,
            'metrics' => ['total_ds' => count($records)],
            'ai_text' => $aiText,
            'show_worst' => $showWorst
        ];
    }

    /**
     * Handler: Route Adherence
     */
    private function handleRouteAnalysis($filters, $queryText = '')
    {
        $queryLower = strtolower($queryText);
        $showWorst = $this->isWorstQuery($queryLower);
        $conditions = $this->buildBaseConditions($filters);
        $whereClause = implode(' AND ', $conditions);
        $sortDir = $showWorst ? 'ASC' : 'DESC';

        $sql = "SELECT b.team_name AS ds_name,
                    COUNT(*) AS total_days,
                    SUM(CASE WHEN a.is_beat_adherence = 'Yes' THEN 1 ELSE 0 END) AS routes_adhered
                FROM tblvands_summary a
                INNER JOIN tblproject_team b ON a.team_id = b.team_id
                INNER JOIN tblbranch d ON b.branch_id = d.branch_id
                WHERE $whereClause
                GROUP BY b.team_id, b.team_name
                HAVING COUNT(*) > 0
                ORDER BY (SUM(CASE WHEN a.is_beat_adherence = 'Yes' THEN 1 ELSE 0 END) / COUNT(*)) $sortDir
                LIMIT 10";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $records = [];
        foreach ($rows as $row) {
            $totalDays = intval($row['total_days'] ?? 0);
            $adherence = intval($row['routes_adhered'] ?? 0);
            $records[] = [
                'dsName' => $row['ds_name'] ?? 'Unknown',
                'totalDays' => $totalDays,
                'adherenceDays' => $adherence,
                'adherenceRate' => $totalDays > 0 ? round(($adherence / $totalDays) * 100, 2) : 0
            ];
        }

        $avgRate = count($records) > 0
            ? round(array_sum(array_column($records, 'adherenceRate')) / count($records), 2) : 0;
        $aiText = $this->generateAiForData('route_analysis', $queryText, $records, 0, "Avg adherence: $avgRate%");

        return [
            'query_name' => 'Route Adherence Analysis',
            'record_count' => count($records),
            'records' => $records,
            'metrics' => ['total_ds' => count($records), 'avg_adherence_rate' => $avgRate],
            'ai_text' => $aiText,
            'show_worst' => $showWorst
        ];
    }

    /**
     * Handler: Inventory Analysis
     */
    private function handleInventoryAnalysis($filters, $queryText = '')
    {
        $queryLower = strtolower($queryText);
        $showWorst = $this->isWorstQuery($queryLower);
        $conditions = $this->buildBaseConditions($filters);
        $whereClause = implode(' AND ', $conditions);
        $sortDir = $showWorst ? 'ASC' : 'DESC';

        $sql = "SELECT b.team_name AS ds_name,
                    SUM(a.total_sales_deliveries) AS total_sales,
                    COUNT(DISTINCT a.activity_date) AS total_days
                FROM tblvands_summary a
                INNER JOIN tblproject_team b ON a.team_id = b.team_id
                INNER JOIN tblbranch d ON b.branch_id = d.branch_id
                WHERE $whereClause
                GROUP BY b.team_id, b.team_name
                HAVING COUNT(DISTINCT a.activity_date) > 0
                ORDER BY (SUM(a.total_sales_deliveries) / COUNT(DISTINCT a.activity_date)) $sortDir
                LIMIT 10";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $records = [];
        foreach ($rows as $row) {
            $totalSales = floatval($row['total_sales'] ?? 0);
            $totalDays = intval($row['total_days'] ?? 0);
            $records[] = [
                'dsName' => $row['ds_name'] ?? 'Unknown',
                'totalSales' => round($totalSales, 0),
                'totalDays' => $totalDays,
                'avgDailySales' => $totalDays > 0 ? round($totalSales / $totalDays, 1) : 0
            ];
        }

        $aiText = $this->generateAiForData('inventory_analysis', $queryText, $records, 0);

        return [
            'query_name' => 'Inventory & Sales Analysis',
            'record_count' => count($records),
            'records' => $records,
            'metrics' => ['total_ds' => count($records)],
            'ai_text' => $aiText,
            'show_worst' => $showWorst
        ];
    }

    // ========================================================================
    // DS SCORECARD HANDLER
    // ========================================================================

    /**
     * Handler: DS Scorecard - comprehensive individual profile
     * Queries sales, qualification, route adherence, time, outlets, products for one DS
     */
    private function handleDsScorecard($filters, $queryText = '')
    {
        $dsName = $filters['ds_name'] ?? null;
        if (!$dsName) {
            return [
                'query_name' => 'DS Scorecard',
                'record_count' => 0, 'records' => [],
                'metrics' => [], 'ai_text' => 'Please specify a DS name.'
            ];
        }

        $startDate = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $filters['date_to'] ?? date('Y-m-d');

        // Resolve team_id from name
        $stmt = $this->pdo->prepare(
            "SELECT team_id, team_name, wd_code, circle, section FROM tblproject_team
             WHERE dstatus = 0 AND is_type = 0 AND s_id = 99 AND LOWER(team_name) = :name LIMIT 1"
        );
        $stmt->execute([':name' => strtolower(trim($dsName))]);
        $dsRow = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$dsRow) {
            return [
                'query_name' => "DS Scorecard: $dsName",
                'record_count' => 0, 'records' => [],
                'metrics' => [], 'ai_text' => "DS '$dsName' not found in the system."
            ];
        }
        $teamId = intval($dsRow['team_id']);
        $dsDisplayName = $dsRow['team_name'];
        $wdCode = $dsRow['wd_code'] ?? '';
        $circle = $dsRow['circle'] ?? '';

        // Get full hierarchy info
        $regionName = '';
        $district = '';
        $mainBranch = '';
        $section = $dsRow['section'] ?? '';
        try {
            $stmt = $this->pdo->prepare(
                "SELECT d.branch_name, d.district, d.main_branch FROM tblproject_team b
                 INNER JOIN tblbranch d ON b.branch_id = d.branch_id
                 WHERE b.team_id = :tid LIMIT 1"
            );
            $stmt->execute([':tid' => $teamId]);
            $bRow = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($bRow) {
                $regionName = $bRow['branch_name'];
                $district = $bRow['district'];
                $mainBranch = $bRow['main_branch'] ?? '';
            }
        } catch (\Exception $e) {}

        $baseWhere = "a.dstatus = 0 AND a.team_id = :tid AND a.activity_date BETWEEN :start AND :end";

        // 1. Sales & qualification
        $sql = "SELECT SUM(a.total_sales_deliveries) AS totalSales,
                       SUM(CASE WHEN a.is_qualified = 1 THEN 1 ELSE 0 END) AS qualifiedDays,
                       COUNT(DISTINCT a.activity_date) AS totalDays,
                       SUM(a.planned_outlets) AS plannedOutlets,
                       SUM(a.total_sellin_shops) AS billedOutlets,
                       SUM(CASE WHEN a.is_beat_adherence = 'Yes' THEN 1 ELSE 0 END) AS adherenceDays,
                       AVG(a.time_in_market) AS avgTimeInMarket,
                       AVG(a.total_meter_travelled / 1000) AS avgKm
                FROM tblvands_summary a
                WHERE $baseWhere";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':tid' => $teamId, ':start' => $startDate, ':end' => $endDate]);
        $main = $stmt->fetch(\PDO::FETCH_ASSOC);

        $totalSales = floatval($main['totalSales'] ?? 0);
        $qualifiedDays = intval($main['qualifiedDays'] ?? 0);
        $totalDays = intval($main['totalDays'] ?? 0);
        $qualRate = $totalDays > 0 ? round(($qualifiedDays / $totalDays) * 100, 2) : 0;
        $plannedOutlets = intval($main['plannedOutlets'] ?? 0);
        $billedOutlets = intval($main['billedOutlets'] ?? 0);
        $coverageRate = $plannedOutlets > 0 ? round(($billedOutlets / $plannedOutlets) * 100, 2) : 0;
        $adherenceDays = intval($main['adherenceDays'] ?? 0);
        $adherenceRate = $totalDays > 0 ? round(($adherenceDays / $totalDays) * 100, 2) : 0;
        $avgTimeInMarket = round(floatval($main['avgTimeInMarket'] ?? 0), 0);
        $avgKm = round(floatval($main['avgKm'] ?? 0), 1);
        $avgDailySales = $totalDays > 0 ? round($totalSales / $totalDays, 0) : 0;

        // 2. Peer comparison (average of all DS in same period)
        $peerWhere = "a.dstatus = 0 AND b.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0 AND a.activity_date BETWEEN :start AND :end";
        if (!empty($filters['user_teams'])) {
            $teamList = $filters['user_teams'];
            if (is_string($teamList) && preg_match('/^\([\d,]+\)$/', $teamList)) {
                $peerWhere .= " AND b.team_id IN $teamList";
            }
        }
        $peerSql = "SELECT AVG(ds_sales) AS avgSales, AVG(ds_qual) AS avgQual FROM (
                SELECT b.team_id,
                    SUM(a.total_sales_deliveries) AS ds_sales,
                    (SUM(CASE WHEN a.is_qualified=1 THEN 1 ELSE 0 END) / NULLIF(COUNT(DISTINCT a.activity_date),0)) * 100 AS ds_qual
                FROM tblvands_summary a
                INNER JOIN tblproject_team b ON a.team_id = b.team_id
                WHERE $peerWhere
                GROUP BY b.team_id
            ) peer";
        $stmt = $this->pdo->prepare($peerSql);
        $stmt->execute([':start' => $startDate, ':end' => $endDate]);
        $peer = $stmt->fetch(\PDO::FETCH_ASSOC);
        $peerAvgSales = round(floatval($peer['avgSales'] ?? 0), 0);
        $peerAvgQual = round(floatval($peer['avgQual'] ?? 0), 1);

        // 3. Daily trend for this DS
        $trendSql = "SELECT a.activity_date AS actDate,
                            a.total_sales_deliveries AS sales,
                            a.is_qualified AS qualified
                     FROM tblvands_summary a
                     WHERE $baseWhere ORDER BY a.activity_date";
        $stmt = $this->pdo->prepare($trendSql);
        $stmt->execute([':tid' => $teamId, ':start' => $startDate, ':end' => $endDate]);
        $dailyTrend = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $trendRecords = array_map(function($r) {
            return [
                'date' => $r['actDate'],
                'sales' => floatval($r['sales']),
                'qualified' => intval($r['qualified'])
            ];
        }, $dailyTrend);

        // 4. Product breakdown for this DS
        $dsBranchCond = " AND json_id = 99 AND team_type = 0";
        $stmt = $this->pdo->prepare(
            "SELECT b.branch_id FROM tblproject_team b WHERE b.team_id = :tid LIMIT 1"
        );
        $stmt->execute([':tid' => $teamId]);
        $bIdRow = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($bIdRow) {
            $dsBranchCond .= " AND branch_id = " . intval($bIdRow['branch_id']);
        }

        $prodSql = "SELECT DISTINCT summary_column_name, product_name
                    FROM tblbranch_pickupstock_products
                    WHERE dstatus = 0 AND summary_column_name IS NOT NULL" . $dsBranchCond;
        $stmt = $this->pdo->prepare($prodSql);
        $stmt->execute();
        $prodCols = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $productBreakdown = [];
        if (!empty($prodCols)) {
            $sumParts = [];
            $colMap = [];
            $seen = [];
            foreach ($prodCols as $pc) {
                $col = $pc['summary_column_name'];
                if (isset($seen[$col])) continue;
                $seen[$col] = true;
                $sumParts[] = "SUM(a.$col) AS `$col`";
                $colMap[$col] = $pc['product_name'];
            }
            if (!empty($sumParts)) {
                $ppSql = "SELECT " . implode(', ', $sumParts)
                    . " FROM tblvands_summary a WHERE a.dstatus = 0 AND a.team_id = :tid"
                    . " AND a.activity_date BETWEEN :start AND :end";
                $stmt = $this->pdo->prepare($ppSql);
                $stmt->execute([':tid' => $teamId, ':start' => $startDate, ':end' => $endDate]);
                $ppRow = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($ppRow) {
                    $prodTotals = [];
                    foreach ($colMap as $col => $name) {
                        $val = floatval($ppRow[$col] ?? 0);
                        if (isset($prodTotals[$name])) { $prodTotals[$name] += $val; }
                        else { $prodTotals[$name] = $val; }
                    }
                    arsort($prodTotals);
                    foreach (array_slice($prodTotals, 0, 8, true) as $name => $val) {
                        if ($val > 0) {
                            $productBreakdown[] = ['productName' => $name, 'totalSales' => round($val, 2)];
                        }
                    }
                }
            }
        }

        // 5. Map data: attendance check-in locations (last 15 days for performance)
        $mapLocations = [];
        try {
            $mapSql = "SELECT capture_date, capture_datetime, lt, lg, other_details
                       FROM tblattendance
                       WHERE team_id = :tid AND s_id = 99 AND dstatus = 0
                         AND capture_date BETWEEN :start AND :end AND lt != 0 AND lg != 0
                       ORDER BY capture_datetime ASC LIMIT 100";
            $stmt = $this->pdo->prepare($mapSql);
            $stmt->execute([':tid' => $teamId, ':start' => $startDate, ':end' => $endDate]);
            $attRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($attRows as $ar) {
                $routeName = '';
                if (!empty($ar['other_details'])) {
                    $det = json_decode($ar['other_details'], true);
                    if (!empty($det['route']) && is_array($det['route'])) {
                        $routeName = implode(', ', $det['route']);
                    }
                }
                $mapLocations[] = [
                    'lat' => floatval($ar['lt']),
                    'lng' => floatval($ar['lg']),
                    'date' => $ar['capture_date'],
                    'time' => $ar['capture_datetime'],
                    'type' => 'attendance',
                    'route' => $routeName
                ];
            }
        } catch (\Exception $e) {
            error_log("Map locations error: " . $e->getMessage());
        }

        // 6. Map data: outlet locations from route_details
        $outletLocations = [];
        try {
            $outSql = "SELECT outlet_name, route_name, lt, lg, market_name, outlet_type, shop_type
                       FROM tblroute_details
                       WHERE team_id = :tid AND dstatus = 0 AND lt != 0 AND lg != 0
                       LIMIT 200";
            $stmt = $this->pdo->prepare($outSql);
            $stmt->execute([':tid' => $teamId]);
            $outRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($outRows as $or) {
                $outletLocations[] = [
                    'lat' => floatval($or['lt']),
                    'lng' => floatval($or['lg']),
                    'name' => trim($or['outlet_name'] ?? ''),
                    'route' => trim($or['route_name'] ?? ''),
                    'market' => trim($or['market_name'] ?? ''),
                    'type' => trim($or['outlet_type'] ?? ''),
                    'shopType' => trim($or['shop_type'] ?? '')
                ];
            }
        } catch (\Exception $e) {
            error_log("Outlet locations error: " . $e->getMessage());
        }

        // Compute performance rating
        $salesVsPeer = $peerAvgSales > 0 ? round(($totalSales / $peerAvgSales) * 100, 0) : 0;
        $rating = 'Average';
        if ($qualRate >= 90 && $salesVsPeer >= 120) $rating = 'Excellent';
        elseif ($qualRate >= 80 && $salesVsPeer >= 100) $rating = 'Good';
        elseif ($qualRate < 60 || $salesVsPeer < 60) $rating = 'Needs Improvement';
        elseif ($qualRate < 75 || $salesVsPeer < 80) $rating = 'Below Average';

        $scorecard = [
            'dsName' => $dsDisplayName,
            'wdCode' => $wdCode,
            'section' => $section,
            'circle' => $circle,
            'region' => $regionName,
            'branch' => $mainBranch,
            'district' => $district,
            'rating' => $rating,
            'totalSales' => round($totalSales, 0),
            'avgDailySales' => $avgDailySales,
            'qualifiedDays' => $qualifiedDays,
            'totalDays' => $totalDays,
            'qualificationRate' => $qualRate,
            'adherenceDays' => $adherenceDays,
            'adherenceRate' => $adherenceRate,
            'plannedOutlets' => $plannedOutlets,
            'billedOutlets' => $billedOutlets,
            'coverageRate' => $coverageRate,
            'avgTimeInMarket' => $avgTimeInMarket,
            'avgKm' => $avgKm,
            'peerAvgSales' => $peerAvgSales,
            'peerAvgQualification' => $peerAvgQual,
            'salesVsPeer' => $salesVsPeer,
        ];

        $aiContext = "DS Scorecard for: $dsDisplayName\n"
            . "District: $district | Branch: $mainBranch | Region: $regionName | Circle: $circle | Section: $section | WD: $wdCode\n"
            . "Rating: $rating\n"
            . "Sales: $totalSales units (Avg daily: $avgDailySales) | Peer avg: $peerAvgSales | vs Peer: {$salesVsPeer}%\n"
            . "Qualification: {$qualRate}% ($qualifiedDays/$totalDays days) | Peer avg: {$peerAvgQual}%\n"
            . "Route Adherence: {$adherenceRate}% ($adherenceDays/$totalDays days)\n"
            . "Coverage: {$coverageRate}% ($billedOutlets/$plannedOutlets outlets)\n"
            . "Avg Time in Market: {$avgTimeInMarket} min | Avg Km: {$avgKm}\n"
            . "Top Products:\n" . json_encode($productBreakdown, JSON_PRETTY_PRINT);
        $aiText = $this->generateAiForData('ds_scorecard', $queryText, [$scorecard], $totalSales, $aiContext, true);

        return [
            'query_name' => "DS Scorecard: $dsDisplayName",
            'record_count' => 1,
            'records' => [$scorecard],
            'daily_trend' => $trendRecords,
            'product_breakdown' => $productBreakdown,
            'map_locations' => $mapLocations,
            'outlet_locations' => $outletLocations,
            'metrics' => [
                'total_sales' => round($totalSales, 0),
                'avg_daily_sales' => $avgDailySales,
                'qualification_rate' => $qualRate,
                'adherence_rate' => $adherenceRate,
                'coverage_rate' => $coverageRate,
                'rating' => $rating
            ],
            'ai_text' => $aiText
        ];
    }

    // ========================================================================
    // HIERARCHY SCORECARD HANDLER
    // ========================================================================

    /**
     * Handler: Scorecard for any hierarchy level (District, Branch, Region, Circle, Section, WD Code)
     * Aggregates sales, qualification, route adherence, outlet coverage, time metrics for the entity
     */
    private function handleHierarchyScorecard($filters, $queryText = '')
    {
        $level = $filters['scorecard_level'] ?? 'district';
        $startDate = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $filters['date_to'] ?? date('Y-m-d');

        // Determine the entity name, SQL grouping, and join conditions
        $entityName = '';
        $teamWhere = 'b.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0';
        $branchJoin = 'INNER JOIN tblbranch d ON b.branch_id = d.branch_id AND d.dstatus = 0';

        switch ($level) {
            case 'district':
                $entityName = is_array($filters['district'] ?? null) ? ($filters['district'][0] ?? '') : ($filters['district'] ?? '');
                $teamWhere .= " AND d.district = :entity";
                break;
            case 'branch':
                $entityName = $filters['main_branch'] ?? '';
                $teamWhere .= " AND d.main_branch = :entity";
                break;
            case 'region':
                $entityName = is_array($filters['region'] ?? null) ? ($filters['region'][0] ?? '') : ($filters['region'] ?? '');
                $teamWhere .= " AND d.branch_name = :entity";
                break;
            case 'circle':
                $entityName = is_array($filters['circle'] ?? null) ? ($filters['circle'][0] ?? '') : ($filters['circle'] ?? '');
                $teamWhere .= " AND b.circle = :entity";
                break;
            case 'section':
                $entityName = is_array($filters['section'] ?? null) ? ($filters['section'][0] ?? '') : ($filters['section'] ?? '');
                $teamWhere .= " AND b.section = :entity";
                break;
            case 'wd_code':
                $entityName = is_array($filters['wd_code'] ?? null) ? ($filters['wd_code'][0] ?? '') : ($filters['wd_code'] ?? '');
                $teamWhere .= " AND b.wd_code = :entity";
                break;
        }

        if (empty($entityName)) {
            return [
                'query_name' => ucfirst($level) . ' Scorecard',
                'record_count' => 0, 'records' => [], 'metrics' => [],
                'ai_text' => "Please specify a $level name for the scorecard."
            ];
        }

        $levelLabel = ucfirst($level);

        // Get team IDs under this entity
        $teamSql = "SELECT b.team_id FROM tblproject_team b $branchJoin WHERE $teamWhere";
        $stmt = $this->pdo->prepare($teamSql);
        $stmt->execute([':entity' => $entityName]);
        $teamRows = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $totalDsCount = count($teamRows);

        if ($totalDsCount == 0) {
            return [
                'query_name' => "$levelLabel Scorecard: $entityName",
                'record_count' => 0, 'records' => [], 'metrics' => [],
                'ai_text' => "No DS found under $levelLabel '$entityName'."
            ];
        }

        $teamIds = implode(',', array_map('intval', $teamRows));

        // Aggregate metrics
        $sql = "SELECT SUM(a.total_sales_deliveries) AS totalSales,
                       SUM(CASE WHEN a.is_qualified = 1 THEN 1 ELSE 0 END) AS qualifiedDays,
                       COUNT(*) AS totalDays,
                       SUM(a.planned_outlets) AS plannedOutlets,
                       SUM(a.total_sellin_shops) AS billedOutlets,
                       SUM(CASE WHEN a.is_beat_adherence = 'Yes' THEN 1 ELSE 0 END) AS adherenceDays,
                       AVG(a.time_in_market) AS avgTimeInMarket,
                       AVG(a.total_meter_travelled / 1000) AS avgKm,
                       COUNT(DISTINCT a.team_id) AS activeDs,
                       COUNT(DISTINCT a.activity_date) AS uniqueDays
                FROM tblvands_summary a
                WHERE a.dstatus = 0 AND a.team_id IN ($teamIds)
                  AND a.activity_date BETWEEN :start AND :end";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':start' => $startDate, ':end' => $endDate]);
        $main = $stmt->fetch(\PDO::FETCH_ASSOC);

        $totalSales = floatval($main['totalSales'] ?? 0);
        $qualifiedDays = intval($main['qualifiedDays'] ?? 0);
        $totalDays = intval($main['totalDays'] ?? 0);
        $qualRate = $totalDays > 0 ? round(($qualifiedDays / $totalDays) * 100, 2) : 0;
        $plannedOutlets = intval($main['plannedOutlets'] ?? 0);
        $billedOutlets = intval($main['billedOutlets'] ?? 0);
        $coverageRate = $plannedOutlets > 0 ? round(($billedOutlets / $plannedOutlets) * 100, 2) : 0;
        $adherenceDays = intval($main['adherenceDays'] ?? 0);
        $adherenceRate = $totalDays > 0 ? round(($adherenceDays / $totalDays) * 100, 2) : 0;
        $avgTimeInMarket = round(floatval($main['avgTimeInMarket'] ?? 0), 0);
        $avgKm = round(floatval($main['avgKm'] ?? 0), 1);
        $activeDs = intval($main['activeDs'] ?? 0);
        $uniqueDays = intval($main['uniqueDays'] ?? 0);
        $avgDailySales = $uniqueDays > 0 ? round($totalSales / $uniqueDays, 0) : 0;

        // Hierarchy context: what does this entity contain?
        $hierarchyInfo = $this->getHierarchyContext($level, $entityName);

        // Top/bottom DS performance within this entity
        $dsPerfSql = "SELECT b.team_name AS dsName, b.wd_code AS wdCode,
                             SUM(a.total_sales_deliveries) AS sales,
                             ROUND((SUM(CASE WHEN a.is_qualified=1 THEN 1 ELSE 0 END) / NULLIF(COUNT(DISTINCT a.activity_date),0)) * 100, 1) AS qualRate
                      FROM tblvands_summary a
                      INNER JOIN tblproject_team b ON a.team_id = b.team_id
                      WHERE a.dstatus = 0 AND a.team_id IN ($teamIds)
                        AND a.activity_date BETWEEN :start AND :end
                      GROUP BY b.team_id, b.team_name, b.wd_code
                      ORDER BY sales DESC";
        $stmt = $this->pdo->prepare($dsPerfSql);
        $stmt->execute([':start' => $startDate, ':end' => $endDate]);
        $allDs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $topDs = array_slice($allDs, 0, 5);
        $bottomDs = array_slice(array_reverse($allDs), 0, 5);

        // Daily trend for this entity
        $trendSql = "SELECT a.activity_date AS actDate,
                            SUM(a.total_sales_deliveries) AS sales,
                            COUNT(DISTINCT a.team_id) AS dsCount
                     FROM tblvands_summary a
                     WHERE a.dstatus = 0 AND a.team_id IN ($teamIds)
                       AND a.activity_date BETWEEN :start AND :end
                     GROUP BY a.activity_date ORDER BY a.activity_date";
        $stmt = $this->pdo->prepare($trendSql);
        $stmt->execute([':start' => $startDate, ':end' => $endDate]);
        $trendRecords = array_map(function($r) {
            return [
                'date' => $r['actDate'],
                'sales' => floatval($r['sales']),
                'dsCount' => intval($r['dsCount'])
            ];
        }, $stmt->fetchAll(\PDO::FETCH_ASSOC));

        // Sub-entity breakdown (next level down)
        $subBreakdown = $this->getSubEntityBreakdown($level, $entityName, $teamIds, $startDate, $endDate);

        // Geographic heatmap points for this entity
        $heatmapPoints = [];
        try {
            $heatSql = "SELECT d.branch_name AS regionName, d.district, d.main_branch, b.circle,
                               SUM(a.total_sales_deliveries) AS totalSales,
                               COUNT(DISTINCT a.team_id) AS dsCount,
                               AVG(att.lt) AS avgLat, AVG(att.lg) AS avgLng
                        FROM tblvands_summary a
                        INNER JOIN tblproject_team b ON a.team_id = b.team_id AND b.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0
                        INNER JOIN tblbranch d ON b.branch_id = d.branch_id AND d.dstatus = 0
                        LEFT JOIN tblattendance att ON b.team_id = att.team_id AND att.s_id = 99 AND att.dstatus = 0
                            AND att.lt != 0 AND att.lg != 0 AND att.capture_date BETWEEN :hs AND :he
                        WHERE a.dstatus = 0 AND a.team_id IN ($teamIds)
                          AND a.activity_date BETWEEN :hs2 AND :he2
                        GROUP BY d.branch_name, d.district, d.main_branch, b.circle
                        HAVING avgLat IS NOT NULL AND avgLng IS NOT NULL
                        ORDER BY totalSales DESC LIMIT 50";
            $hStmt = $this->pdo->prepare($heatSql);
            $hStmt->execute([':hs' => $startDate, ':he' => $endDate, ':hs2' => $startDate, ':he2' => $endDate]);
            foreach ($hStmt->fetchAll(\PDO::FETCH_ASSOC) as $p) {
                $heatmapPoints[] = [
                    'lat' => floatval($p['avgLat']),
                    'lng' => floatval($p['avgLng']),
                    'sales' => round(floatval($p['totalSales']), 0),
                    'region' => $p['regionName'] ?? '',
                    'district' => $p['district'] ?? '',
                    'branch' => $p['main_branch'] ?? '',
                    'circle' => $p['circle'] ?? '',
                    'dsCount' => intval($p['dsCount'])
                ];
            }
        } catch (\Exception $e) {}

        // Rating
        $rating = 'Average';
        if ($qualRate >= 85 && $adherenceRate >= 85) $rating = 'Excellent';
        elseif ($qualRate >= 75 && $adherenceRate >= 75) $rating = 'Good';
        elseif ($qualRate < 55 || $adherenceRate < 55) $rating = 'Needs Improvement';
        elseif ($qualRate < 65 || $adherenceRate < 65) $rating = 'Below Average';

        $scorecard = [
            'level' => $level,
            'levelLabel' => $levelLabel,
            'entityName' => $entityName,
            'rating' => $rating,
            'totalSales' => round($totalSales, 0),
            'avgDailySales' => $avgDailySales,
            'totalDsCount' => $totalDsCount,
            'activeDs' => $activeDs,
            'qualifiedDays' => $qualifiedDays,
            'totalDays' => $totalDays,
            'qualificationRate' => $qualRate,
            'adherenceDays' => $adherenceDays,
            'adherenceRate' => $adherenceRate,
            'plannedOutlets' => $plannedOutlets,
            'billedOutlets' => $billedOutlets,
            'coverageRate' => $coverageRate,
            'avgTimeInMarket' => $avgTimeInMarket,
            'avgKm' => $avgKm,
            'hierarchyInfo' => $hierarchyInfo,
        ];

        $aiContext = "$levelLabel Scorecard for: $entityName\n"
            . "Rating: $rating | DS Count: $totalDsCount (Active: $activeDs)\n"
            . "Sales: $totalSales (Avg/day: $avgDailySales)\n"
            . "Qualification: {$qualRate}% | Adherence: {$adherenceRate}% | Coverage: {$coverageRate}%\n"
            . "Time: {$avgTimeInMarket}min | Km: {$avgKm}\n"
            . "Top DS: " . json_encode($topDs) . "\n"
            . "Bottom DS: " . json_encode($bottomDs) . "\n"
            . "Sub-breakdown: " . json_encode($subBreakdown);
        $aiText = $this->generateAiForData('hierarchy_scorecard', $queryText, [$scorecard], $totalSales, $aiContext, true);

        return [
            'query_name' => "$levelLabel Scorecard: $entityName",
            'query_type' => 'hierarchy_scorecard',
            'record_count' => 1,
            'records' => [$scorecard],
            'daily_trend' => $trendRecords,
            'top_ds' => $topDs,
            'bottom_ds' => $bottomDs,
            'sub_breakdown' => $subBreakdown,
            'heatmap_points' => $heatmapPoints,
            'metrics' => [
                'total_sales' => round($totalSales, 0),
                'avg_daily_sales' => $avgDailySales,
                'total_ds' => $totalDsCount,
                'active_ds' => $activeDs,
                'qualification_rate' => $qualRate,
                'adherence_rate' => $adherenceRate,
                'coverage_rate' => $coverageRate,
                'rating' => $rating
            ],
            'ai_text' => $aiText
        ];
    }

    /**
     * Get hierarchy context info for a given level and entity
     */
    private function getHierarchyContext($level, $entityName)
    {
        $info = ['parent' => '', 'children' => []];
        try {
            switch ($level) {
                case 'district':
                    $stmt = $this->pdo->prepare("SELECT DISTINCT main_branch FROM tblbranch WHERE dstatus = 0 AND district = :e");
                    $stmt->execute([':e' => $entityName]);
                    $info['children'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                    $info['childLabel'] = 'Branches';
                    break;
                case 'branch':
                    $stmt = $this->pdo->prepare("SELECT DISTINCT district FROM tblbranch WHERE dstatus = 0 AND main_branch = :e LIMIT 1");
                    $stmt->execute([':e' => $entityName]);
                    $info['parent'] = $stmt->fetchColumn() ?: '';
                    $stmt = $this->pdo->prepare("SELECT DISTINCT branch_name FROM tblbranch WHERE dstatus = 0 AND main_branch = :e");
                    $stmt->execute([':e' => $entityName]);
                    $info['children'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                    $info['childLabel'] = 'Regions';
                    $info['parentLabel'] = 'District';
                    break;
                case 'region':
                    $stmt = $this->pdo->prepare("SELECT main_branch, district FROM tblbranch WHERE dstatus = 0 AND branch_name = :e LIMIT 1");
                    $stmt->execute([':e' => $entityName]);
                    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if ($row) {
                        $info['parent'] = $row['main_branch'];
                        $info['grandParent'] = $row['district'];
                        $info['parentLabel'] = 'Branch';
                        $info['grandParentLabel'] = 'District';
                    }
                    $stmt = $this->pdo->prepare(
                        "SELECT DISTINCT b.circle FROM tblproject_team b
                         INNER JOIN tblbranch d ON b.branch_id = d.branch_id
                         WHERE b.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0 AND d.branch_name = :e AND b.circle IS NOT NULL AND b.circle != ''"
                    );
                    $stmt->execute([':e' => $entityName]);
                    $info['children'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                    $info['childLabel'] = 'Circles';
                    break;
                case 'circle':
                    $stmt = $this->pdo->prepare(
                        "SELECT DISTINCT d.branch_name FROM tblproject_team b
                         INNER JOIN tblbranch d ON b.branch_id = d.branch_id
                         WHERE b.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0 AND b.circle = :e LIMIT 1"
                    );
                    $stmt->execute([':e' => $entityName]);
                    $info['parent'] = $stmt->fetchColumn() ?: '';
                    $info['parentLabel'] = 'Region';
                    $stmt = $this->pdo->prepare(
                        "SELECT DISTINCT section FROM tblproject_team
                         WHERE dstatus = 0 AND is_type = 0 AND s_id = 99 AND circle = :e AND section IS NOT NULL AND section != ''"
                    );
                    $stmt->execute([':e' => $entityName]);
                    $info['children'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                    $info['childLabel'] = 'Sections';
                    break;
                case 'section':
                    $stmt = $this->pdo->prepare("SELECT DISTINCT circle FROM tblproject_team WHERE dstatus = 0 AND is_type = 0 AND s_id = 99 AND section = :e LIMIT 1");
                    $stmt->execute([':e' => $entityName]);
                    $info['parent'] = $stmt->fetchColumn() ?: '';
                    $info['parentLabel'] = 'Circle';
                    $stmt = $this->pdo->prepare(
                        "SELECT DISTINCT wd_code FROM tblproject_team
                         WHERE dstatus = 0 AND is_type = 0 AND s_id = 99 AND section = :e AND wd_code IS NOT NULL AND wd_code != ''"
                    );
                    $stmt->execute([':e' => $entityName]);
                    $info['children'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                    $info['childLabel'] = 'WD Codes';
                    break;
                case 'wd_code':
                    $stmt = $this->pdo->prepare("SELECT DISTINCT section, circle FROM tblproject_team WHERE dstatus = 0 AND is_type = 0 AND s_id = 99 AND wd_code = :e LIMIT 1");
                    $stmt->execute([':e' => $entityName]);
                    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if ($row) {
                        $info['parent'] = $row['section'] ?? '';
                        $info['grandParent'] = $row['circle'] ?? '';
                        $info['parentLabel'] = 'Section';
                        $info['grandParentLabel'] = 'Circle';
                    }
                    $stmt = $this->pdo->prepare(
                        "SELECT DISTINCT team_name FROM tblproject_team
                         WHERE dstatus = 0 AND is_type = 0 AND s_id = 99 AND wd_code = :e"
                    );
                    $stmt->execute([':e' => $entityName]);
                    $info['children'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                    $info['childLabel'] = 'DS';
                    break;
            }
        } catch (\Exception $e) {}
        return $info;
    }

    /**
     * Get sub-entity breakdown for the next level down in the hierarchy
     */
    private function getSubEntityBreakdown($level, $entityName, $teamIds, $startDate, $endDate)
    {
        $breakdown = [];
        $groupCol = '';
        $groupLabel = '';

        switch ($level) {
            case 'district':
                $groupCol = 'd.main_branch';
                $groupLabel = 'Branch';
                break;
            case 'branch':
                $groupCol = 'd.branch_name';
                $groupLabel = 'Region';
                break;
            case 'region':
                $groupCol = 'b.circle';
                $groupLabel = 'Circle';
                break;
            case 'circle':
                $groupCol = 'b.section';
                $groupLabel = 'Section';
                break;
            case 'section':
                $groupCol = 'b.wd_code';
                $groupLabel = 'WD Code';
                break;
            case 'wd_code':
                $groupCol = 'b.team_name';
                $groupLabel = 'DS';
                break;
        }

        if (empty($groupCol)) return $breakdown;

        try {
            $sql = "SELECT $groupCol AS subEntity,
                           SUM(a.total_sales_deliveries) AS sales,
                           ROUND((SUM(CASE WHEN a.is_qualified=1 THEN 1 ELSE 0 END) / NULLIF(COUNT(*),0)) * 100, 1) AS qualRate,
                           ROUND((SUM(CASE WHEN a.is_beat_adherence='Yes' THEN 1 ELSE 0 END) / NULLIF(COUNT(*),0)) * 100, 1) AS adherenceRate,
                           COUNT(DISTINCT a.team_id) AS dsCount
                    FROM tblvands_summary a
                    INNER JOIN tblproject_team b ON a.team_id = b.team_id AND b.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0
                    INNER JOIN tblbranch d ON b.branch_id = d.branch_id AND d.dstatus = 0
                    WHERE a.dstatus = 0 AND a.team_id IN ($teamIds)
                      AND a.activity_date BETWEEN :start AND :end
                      AND $groupCol IS NOT NULL AND $groupCol != ''
                    GROUP BY $groupCol ORDER BY sales DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':start' => $startDate, ':end' => $endDate]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $breakdown[] = [
                    'name' => $r['subEntity'],
                    'sales' => floatval($r['sales']),
                    'qualRate' => floatval($r['qualRate']),
                    'adherenceRate' => floatval($r['adherenceRate']),
                    'dsCount' => intval($r['dsCount'])
                ];
            }
        } catch (\Exception $e) {}

        return ['label' => $groupLabel, 'data' => $breakdown];
    }

    // ========================================================================
    // CATEGORY / PRODUCT COMPARISON / FOCUS BRAND HANDLERS
    // ========================================================================

    /**
     * Handler: Category Sales Analysis
     * Groups products by category_name from tblbranch_pickupstock_products,
     * sums their respective columns from tblvands_summary
     */
    private function handleCategorySales($filters, $queryText = '')
    {
        $startDate = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $filters['date_to'] ?? date('Y-m-d');
        $queryLower = strtolower($queryText);
        $showWorst = $this->isWorstQuery($queryLower);
        $specificCategory = $filters['category'] ?? null;

        $productFilter = $this->buildProductTableFilter($filters);

        $sql = "SELECT DISTINCT category_name, summary_column_name, product_name, is_focusbrand
                FROM tblbranch_pickupstock_products
                WHERE dstatus = 0 AND category_name IS NOT NULL AND category_name != ''
                AND summary_column_name IS NOT NULL AND summary_column_name != ''" . $productFilter;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $productRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($productRows)) {
            return [
                'query_name' => 'Category Sales Analysis',
                'record_count' => 0, 'records' => [], 'metrics' => ['total_sales' => 0],
                'ai_text' => 'No category data found.'
            ];
        }

        $categoryMap = [];
        $allCols = [];
        $seenCols = [];
        foreach ($productRows as $row) {
            $cat = $row['category_name'];
            $col = $row['summary_column_name'];
            $prod = $row['product_name'];
            if (!isset($seenCols[$col])) {
                $seenCols[$col] = true;
                $allCols[] = $col;
            }
            if (!isset($categoryMap[$cat])) {
                $categoryMap[$cat] = ['columns' => [], 'products' => []];
            }
            if (!in_array($col, $categoryMap[$cat]['columns'])) {
                $categoryMap[$cat]['columns'][] = $col;
            }
            $categoryMap[$cat]['products'][$col] = $prod;
        }

        $conditions = $this->buildBaseConditions($filters);
        $whereClause = implode(' AND ', $conditions);

        // Sum individual product columns (NOT total_sales_deliveries which is a different aggregate)
        $sumSelect = [];
        foreach ($allCols as $col) {
            $sumSelect[] = "SUM(a.$col) AS `$col`";
        }
        $perProductQuery = "SELECT " . implode(', ', $sumSelect) . " FROM tblvands_summary a"
            . " INNER JOIN tblproject_team b ON a.team_id = b.team_id"
            . " INNER JOIN tblbranch d ON b.branch_id = d.branch_id"
            . " WHERE $whereClause";
        $stmt = $this->pdo->prepare($perProductQuery);
        $stmt->execute();
        $productRow = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Build category results and compute grand total from actual product sums
        $categoryResults = [];
        $productBreakdown = [];
        $grandTotal = 0;
        foreach ($categoryMap as $catName => $catInfo) {
            $catTotal = 0;
            $catProducts = [];
            foreach ($catInfo['columns'] as $col) {
                $val = floatval($productRow[$col] ?? 0);
                $prodName = $catInfo['products'][$col] ?? $col;
                $catTotal += $val;
                $catProducts[] = [
                    'productName' => $prodName,
                    'totalSales' => round($val, 2),
                    'columnName' => $col
                ];
            }
            $grandTotal += $catTotal;
            usort($catProducts, function($a, $b) { return $b['totalSales'] <=> $a['totalSales']; });

            $categoryResults[] = [
                'categoryName' => $catName,
                'totalSales' => round($catTotal, 2),
                'sharePercent' => 0,
                'productCount' => count($catProducts),
                'products' => $catProducts
            ];

            if ($specificCategory && strtolower($catName) === strtolower($specificCategory)) {
                $productBreakdown = $catProducts;
            }
        }

        // Now compute share using the correct grand total (sum of all product columns)
        $totalSales = round($grandTotal, 2);
        foreach ($categoryResults as &$catResult) {
            $catResult['sharePercent'] = $totalSales > 0
                ? round(($catResult['totalSales'] / $totalSales) * 100, 2)
                : 0;
        }
        unset($catResult);

        usort($categoryResults, function($a, $b) use ($showWorst) {
            return $showWorst
                ? ($a['totalSales'] <=> $b['totalSales'])
                : ($b['totalSales'] <=> $a['totalSales']);
        });

        $days = max(1, (strtotime($endDate) - strtotime($startDate)) / 86400 + 1);
        $avgDailySales = round($totalSales / $days, 2);

        $aiContext = "Category Sales Breakdown:\n"
            . "Total Sales (all products): $totalSales units\n"
            . "Avg Daily: $avgDailySales units\n"
            . ($specificCategory ? "Focused on category: $specificCategory\n" : '')
            . "Categories:\n" . json_encode(array_map(function($c) {
                return ['category' => $c['categoryName'], 'sales' => $c['totalSales'], 'share' => $c['sharePercent'] . '%', 'products' => count($c['products'])];
            }, $categoryResults), JSON_PRETTY_PRINT);
        if ($specificCategory && !empty($productBreakdown)) {
            $aiContext .= "\n\nProduct breakdown for $specificCategory:\n" . json_encode($productBreakdown, JSON_PRETTY_PRINT);
        }
        $aiText = $this->generateAiForData('category_sales', $queryText, $categoryResults, $totalSales, $aiContext);

        return [
            'query_name' => $specificCategory ? "Sales: $specificCategory" : 'Category Sales Analysis',
            'record_count' => count($categoryResults),
            'records' => $categoryResults,
            'product_breakdown' => $productBreakdown,
            'specific_category' => $specificCategory,
            'metrics' => [
                'total_sales' => round($totalSales, 2),
                'avg_daily_sales' => $avgDailySales,
                'category_count' => count($categoryResults)
            ],
            'ai_text' => $aiText,
            'show_worst' => $showWorst
        ];
    }

    /**
     * Handler: Product/Category Period Comparison
     * Compares a specific product or category's sales between current and previous period
     */
    private function handleProductComparison($filters, $queryText = '')
    {
        $endDate = $filters['date_to'] ?? date('Y-m-d');
        $startDate = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $productName = $filters['product'] ?? null;
        $categoryName = $filters['category'] ?? null;
        $entityLabel = $productName ?: $categoryName ?: 'All Products';

        $periodDays = max(1, (strtotime($endDate) - strtotime($startDate)) / 86400 + 1);
        $prevEnd = date('Y-m-d', strtotime($startDate . ' -1 day'));
        $prevStart = date('Y-m-d', strtotime($prevEnd . " -" . ($periodDays - 1) . " days"));

        $productFilter = $this->buildProductTableFilter($filters, 'p');

        $colSql = "SELECT DISTINCT p.summary_column_name, p.product_name, p.category_name
                   FROM tblbranch_pickupstock_products p
                   WHERE p.dstatus = 0 AND p.summary_column_name IS NOT NULL" . $productFilter;
        $colConditions = [];
        if ($productName) {
            $colConditions[] = "LOWER(p.product_name) = '" . strtolower(str_replace("'", "''", $productName)) . "'";
        }
        if ($categoryName) {
            $colConditions[] = "LOWER(p.category_name) = '" . strtolower(str_replace("'", "''", $categoryName)) . "'";
        }
        if (!empty($colConditions)) {
            $colSql .= " AND (" . implode(' OR ', $colConditions) . ")";
        }
        $stmt = $this->pdo->prepare($colSql);
        $stmt->execute();
        $colRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($colRows)) {
            return [
                'query_name' => "Comparison: $entityLabel",
                'record_count' => 0, 'records' => [], 'metrics' => [],
                'ai_text' => "Could not find product/category '$entityLabel' in the system."
            ];
        }

        $targetCols = [];
        $colToProduct = [];
        $seenCols = [];
        foreach ($colRows as $row) {
            $col = $row['summary_column_name'];
            if (!isset($seenCols[$col])) {
                $seenCols[$col] = true;
                $targetCols[] = $col;
                $colToProduct[$col] = $row['product_name'];
            }
        }

        $baseWhere = "a.dstatus = 0 AND b.dstatus = 0 AND d.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0";
        if (!empty($filters['user_teams'])) {
            $teamList = $filters['user_teams'];
            if (is_string($teamList) && preg_match('/^\([\d,]+\)$/', $teamList)) {
                $baseWhere .= " AND b.team_id IN $teamList";
            }
        }
        $baseWhere .= $this->buildBranchFilterSql($filters);

        $sumParts = [];
        foreach ($targetCols as $col) {
            $sumParts[] = "COALESCE(SUM(a.$col), 0)";
        }
        $totalExpr = implode(' + ', $sumParts);

        $perProductSelect = [];
        foreach ($targetCols as $col) {
            $perProductSelect[] = "SUM(a.$col) AS `$col`";
        }

        $aggSql = "SELECT ($totalExpr) AS entityTotal, " . implode(', ', $perProductSelect)
            . " FROM tblvands_summary a"
            . " INNER JOIN tblproject_team b ON a.team_id = b.team_id"
            . " INNER JOIN tblbranch d ON b.branch_id = d.branch_id"
            . " WHERE $baseWhere AND a.activity_date BETWEEN :start AND :end";

        $stmt = $this->pdo->prepare($aggSql);
        $stmt->execute([':start' => $startDate, ':end' => $endDate]);
        $current = $stmt->fetch(\PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare($aggSql);
        $stmt->execute([':start' => $prevStart, ':end' => $prevEnd]);
        $previous = $stmt->fetch(\PDO::FETCH_ASSOC);

        $curTotal = floatval($current['entityTotal'] ?? 0);
        $prevTotal = floatval($previous['entityTotal'] ?? 0);
        $overallChange = $prevTotal > 0 ? round((($curTotal - $prevTotal) / $prevTotal) * 100, 2) : ($curTotal > 0 ? 100 : 0);
        $direction = $overallChange > 5 ? 'up' : ($overallChange < -5 ? 'down' : 'flat');

        $productComparison = [];
        foreach ($targetCols as $col) {
            $curVal = floatval($current[$col] ?? 0);
            $prevVal = floatval($previous[$col] ?? 0);
            $change = $prevVal > 0 ? round((($curVal - $prevVal) / $prevVal) * 100, 2) : ($curVal > 0 ? 100 : 0);
            $productComparison[] = [
                'name' => $colToProduct[$col] ?? $col,
                'currentSales' => round($curVal, 2),
                'previousSales' => round($prevVal, 2),
                'changePercent' => $change,
                'direction' => $change > 5 ? 'up' : ($change < -5 ? 'down' : 'flat')
            ];
        }
        usort($productComparison, function($a, $b) { return $b['currentSales'] <=> $a['currentSales']; });

        $branchSql = "SELECT d.branch_name, ($totalExpr) AS totalSales"
            . " FROM tblvands_summary a"
            . " INNER JOIN tblproject_team b ON a.team_id = b.team_id"
            . " INNER JOIN tblbranch d ON b.branch_id = d.branch_id"
            . " WHERE $baseWhere AND a.activity_date BETWEEN :start AND :end"
            . " GROUP BY d.branch_name ORDER BY totalSales DESC LIMIT 10";
        $stmt = $this->pdo->prepare($branchSql);
        $stmt->execute([':start' => $startDate, ':end' => $endDate]);
        $currentBranches = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare($branchSql);
        $stmt->execute([':start' => $prevStart, ':end' => $prevEnd]);
        $prevBranches = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $prevBranchMap = [];
        foreach ($prevBranches as $pb) { $prevBranchMap[$pb['branch_name']] = $pb; }

        $branchComparison = [];
        foreach ($currentBranches as $cb) {
            $branchName = $cb['branch_name'];
            $curSales = floatval($cb['totalSales']);
            $prevSales = floatval($prevBranchMap[$branchName]['totalSales'] ?? 0);
            $change = $prevSales > 0 ? round((($curSales - $prevSales) / $prevSales) * 100, 2) : ($curSales > 0 ? 100 : 0);
            $branchComparison[] = [
                'name' => $branchName,
                'currentSales' => round($curSales, 2),
                'previousSales' => round($prevSales, 2),
                'changePercent' => $change,
                'direction' => $change > 5 ? 'up' : ($change < -5 ? 'down' : 'flat')
            ];
        }

        $aiContext = "Product/Category Comparison for: $entityLabel\n"
            . "Current Period ({$startDate} to {$endDate}): {$curTotal} units\n"
            . "Previous Period ({$prevStart} to {$prevEnd}): {$prevTotal} units\n"
            . "Overall Change: {$overallChange}% ({$direction})\n"
            . "Product breakdown:\n" . json_encode($productComparison, JSON_PRETTY_PRINT) . "\n"
            . "Top branches:\n" . json_encode($branchComparison, JSON_PRETTY_PRINT);
        $aiText = $this->generateAiForData('product_comparison', $queryText, $productComparison, $curTotal, $aiContext);

        return [
            'query_name' => "Comparison: $entityLabel",
            'record_count' => count($productComparison),
            'records' => $productComparison,
            'branch_comparison' => $branchComparison,
            'metrics' => [
                'current_total' => round($curTotal, 2),
                'previous_total' => round($prevTotal, 2),
                'change_percent' => $overallChange,
                'direction' => $direction,
                'product_count' => count($productComparison)
            ],
            'period' => [
                'current' => ['start' => $startDate, 'end' => $endDate],
                'previous' => ['start' => $prevStart, 'end' => $prevEnd]
            ],
            'entity_label' => $entityLabel,
            'ai_text' => $aiText
        ];
    }

    /**
     * Handler: Focus Brand Analysis
     * Compares focus brand performance (is_focusbrand=1) vs non-focus brands
     */
    private function handleFocusBrandAnalysis($filters, $queryText = '')
    {
        $startDate = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $filters['date_to'] ?? date('Y-m-d');
        $queryLower = strtolower($queryText);
        $showWorst = $this->isWorstQuery($queryLower);

        $productFilter = $this->buildProductTableFilter($filters);

        $sql = "SELECT DISTINCT summary_column_name, product_name, category_name, is_focusbrand
                FROM tblbranch_pickupstock_products
                WHERE dstatus = 0 AND summary_column_name IS NOT NULL AND summary_column_name != ''" . $productFilter;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $productRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($productRows)) {
            return [
                'query_name' => 'Focus Brand Analysis',
                'record_count' => 0, 'records' => [], 'metrics' => ['total_sales' => 0],
                'ai_text' => 'No product data found.'
            ];
        }

        $focusCols = [];
        $nonFocusCols = [];
        $colToProduct = [];
        $colToCategory = [];
        $seenCols = [];
        foreach ($productRows as $row) {
            $col = $row['summary_column_name'];
            if (isset($seenCols[$col])) continue;
            $seenCols[$col] = true;
            $colToProduct[$col] = $row['product_name'];
            $colToCategory[$col] = $row['category_name'];
            if (intval($row['is_focusbrand']) === 1) {
                $focusCols[] = $col;
            } else {
                $nonFocusCols[] = $col;
            }
        }

        $conditions = $this->buildBaseConditions($filters);
        $whereClause = implode(' AND ', $conditions);

        $allCols = array_merge($focusCols, $nonFocusCols);
        $sumSelect = [];
        foreach ($allCols as $col) {
            $sumSelect[] = "SUM(a.$col) AS `$col`";
        }
        $totalQuery = "SELECT " . implode(', ', $sumSelect)
            . " FROM tblvands_summary a"
            . " INNER JOIN tblproject_team b ON a.team_id = b.team_id"
            . " INNER JOIN tblbranch d ON b.branch_id = d.branch_id"
            . " WHERE $whereClause";
        $stmt = $this->pdo->prepare($totalQuery);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Derive grand total from actual product column sums (not total_sales_deliveries)
        $focusTotal = 0;
        $nonFocusTotal = 0;
        $focusProducts = [];
        $nonFocusProducts = [];

        foreach ($focusCols as $col) {
            $val = floatval($row[$col] ?? 0);
            $focusTotal += $val;
            $focusProducts[] = [
                'productName' => $colToProduct[$col] ?? $col,
                'categoryName' => $colToCategory[$col] ?? 'Unknown',
                'totalSales' => round($val, 2),
                'isFocusBrand' => true
            ];
        }
        foreach ($nonFocusCols as $col) {
            $val = floatval($row[$col] ?? 0);
            $nonFocusTotal += $val;
            $nonFocusProducts[] = [
                'productName' => $colToProduct[$col] ?? $col,
                'categoryName' => $colToCategory[$col] ?? 'Unknown',
                'totalSales' => round($val, 2),
                'isFocusBrand' => false
            ];
        }

        usort($focusProducts, function($a, $b) { return $b['totalSales'] <=> $a['totalSales']; });
        usort($nonFocusProducts, function($a, $b) { return $b['totalSales'] <=> $a['totalSales']; });

        $grandTotal = $focusTotal + $nonFocusTotal;
        $focusShare = $grandTotal > 0 ? round(($focusTotal / $grandTotal) * 100, 2) : 0;
        $nonFocusShare = $grandTotal > 0 ? round(($nonFocusTotal / $grandTotal) * 100, 2) : 0;

        foreach ($focusProducts as &$fp) {
            $fp['sharePercent'] = $grandTotal > 0 ? round(($fp['totalSales'] / $grandTotal) * 100, 2) : 0;
        }
        unset($fp);

        $days = max(1, (strtotime($endDate) - strtotime($startDate)) / 86400 + 1);

        $records = [
            ['name' => 'Focus Brands', 'totalSales' => round($focusTotal, 2), 'sharePercent' => $focusShare, 'productCount' => count($focusProducts)],
            ['name' => 'Non-Focus Brands', 'totalSales' => round($nonFocusTotal, 2), 'sharePercent' => $nonFocusShare, 'productCount' => count($nonFocusProducts)]
        ];

        $aiContext = "Focus Brand Analysis:\n"
            . "Grand Total: $grandTotal units\n"
            . "Focus Brands: $focusTotal units ({$focusShare}%)\n"
            . "Non-Focus Brands: $nonFocusTotal units ({$nonFocusShare}%)\n"
            . "Top Focus Products:\n" . json_encode(array_slice($focusProducts, 0, 5), JSON_PRETTY_PRINT)
            . "\nTop Non-Focus Products:\n" . json_encode(array_slice($nonFocusProducts, 0, 5), JSON_PRETTY_PRINT);
        $aiText = $this->generateAiForData('focus_brand_analysis', $queryText, $records, $grandTotal, $aiContext);

        return [
            'query_name' => 'Focus Brand Analysis',
            'record_count' => count($focusProducts),
            'records' => $records,
            'focus_products' => array_slice($focusProducts, 0, 10),
            'non_focus_products' => array_slice($nonFocusProducts, 0, 5),
            'metrics' => [
                'total_sales' => round($grandTotal, 2),
                'avg_daily_sales' => round($grandTotal / $days, 2),
                'focus_total' => round($focusTotal, 2),
                'non_focus_total' => round($nonFocusTotal, 2),
                'focus_share' => $focusShare,
                'focus_count' => count($focusProducts),
                'non_focus_count' => count($nonFocusProducts)
            ],
            'ai_text' => $aiText,
            'show_worst' => $showWorst
        ];
    }

    // ========================================================================
    // OUTLET-LEVEL SALES & GEOGRAPHIC HEATMAP HANDLERS
    // ========================================================================

    /**
     * Handler: Outlet-level Sales - granular outlet performance
     */
    private function handleOutletSales($filters, $queryText = '')
    {
        $startDate = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $filters['date_to'] ?? date('Y-m-d');

        $where = "r.dstatus = 0 AND r.capture_date BETWEEN :start AND :end AND r.s_id = 99";
        $params = [':start' => $startDate, ':end' => $endDate];

        if (!empty($filters['user_teams'])) {
            $teamList = $filters['user_teams'];
            if (is_string($teamList) && preg_match('/^\([\d,]+\)$/', $teamList)) {
                $where .= " AND r.team_id IN $teamList";
            }
        }

        // Hierarchy filters (region = branch_name, branch = main_branch)
        $hierarchyFilter = '';
        if (!empty($filters['district']) && is_array($filters['district'])) {
            $hierarchyFilter .= " AND br.district = :dist";
            $params[':dist'] = $filters['district'][0];
        } elseif (!empty($filters['district'])) {
            $hierarchyFilter .= " AND br.district = :dist";
            $params[':dist'] = $filters['district'];
        }
        if (!empty($filters['main_branch'])) {
            $mb = is_array($filters['main_branch']) ? $filters['main_branch'][0] : $filters['main_branch'];
            $hierarchyFilter .= " AND br.main_branch = :mainBr";
            $params[':mainBr'] = $mb;
        }
        if (!empty($filters['region']) && is_array($filters['region'])) {
            $hierarchyFilter .= " AND br.branch_name = :regionName";
            $params[':regionName'] = $filters['region'][0];
        } elseif (!empty($filters['branch_name'])) {
            $hierarchyFilter .= " AND br.branch_name = :regionName";
            $params[':regionName'] = $filters['branch_name'];
        } elseif (!empty($filters['branch']) && !isset($params[':regionName'])) {
            $hierarchyFilter .= " AND br.branch_name = :regionName";
            $params[':regionName'] = is_array($filters['branch']) ? $filters['branch'][0] : $filters['branch'];
        }
        if (!empty($filters['circle']) && is_array($filters['circle'])) {
            $hierarchyFilter .= " AND t.circle = :circ";
            $params[':circ'] = $filters['circle'][0];
        } elseif (!empty($filters['circle'])) {
            $hierarchyFilter .= " AND t.circle = :circ";
            $params[':circ'] = $filters['circle'];
        }
        if (!empty($filters['section']) && is_array($filters['section'])) {
            $hierarchyFilter .= " AND t.section = :sec";
            $params[':sec'] = $filters['section'][0];
        } elseif (!empty($filters['section'])) {
            $hierarchyFilter .= " AND t.section = :sec";
            $params[':sec'] = $filters['section'];
        }
        if (!empty($filters['wd_code']) && is_array($filters['wd_code'])) {
            $hierarchyFilter .= " AND t.wd_code = :wd";
            $params[':wd'] = $filters['wd_code'][0];
        } elseif (!empty($filters['wd_code'])) {
            $hierarchyFilter .= " AND t.wd_code = :wd";
            $params[':wd'] = $filters['wd_code'];
        }

        // Outlet = tblroute_details.rec_id; in survey_response_details the link is ques_3 (rec_id)
        $sql = "SELECT
                    r.ques_3 AS outlet_id,
                    MAX(rd.outlet_name) AS outletName,
                    MAX(rd.market_name) AS market,
                    MAX(rd.outlet_type) AS outletType,
                    MAX(rd.shop_type) AS shopType,
                    MAX(t.team_name) AS dsName,
                    MAX(t.wd_code) AS wdCode,
                    COUNT(*) AS visitCount,
                    SUM(r.netAmount) AS totalRevenue,
                    ROUND(AVG(r.netAmount), 2) AS avgOrderValue,
                    MAX(r.lt) AS lat,
                    MAX(r.lg) AS lng
                FROM tblsurvey_response_details r
                INNER JOIN tblproject_team t ON r.team_id = t.team_id AND t.dstatus = 0 AND t.is_type = 0 AND t.s_id = 99
                INNER JOIN tblbranch br ON t.branch_id = br.branch_id AND br.dstatus = 0
                LEFT JOIN tblroute_details rd ON rd.rec_id = r.ques_3 AND rd.dstatus = 0
                WHERE $where $hierarchyFilter AND r.ques_3 IS NOT NULL AND r.ques_3 != '' AND CAST(r.ques_3 AS UNSIGNED) > 0
                GROUP BY r.ques_3
                ORDER BY totalRevenue DESC
                LIMIT 25";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $outlets = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $totalRevenue = 0;
        $totalVisits = 0;
        $records = [];
        foreach ($outlets as $o) {
            $rev = floatval($o['totalRevenue'] ?? 0);
            $visits = intval($o['visitCount'] ?? 0);
            $totalRevenue += $rev;
            $totalVisits += $visits;
            $records[] = [
                'outletName' => $o['outletName'] ?: 'Outlet #' . ($o['outlet_id'] ?? ''),
                'market' => $o['market'] ?? '',
                'outletType' => $o['outletType'] ?? '',
                'shopType' => $o['shopType'] ?? '',
                'dsName' => $o['dsName'] ?? '',
                'wdCode' => $o['wdCode'] ?? '',
                'visitCount' => $visits,
                'totalRevenue' => round($rev, 2),
                'avgOrderValue' => round(floatval($o['avgOrderValue'] ?? 0), 2),
                'lat' => floatval($o['lat'] ?? 0),
                'lng' => floatval($o['lng'] ?? 0),
            ];
        }

        // Overall stats (outlet = ques_3 = rec_id in tblroute_details)
        $statsSql = "SELECT COUNT(DISTINCT r.ques_3) AS totalOutlets,
                            COUNT(*) AS totalTransactions,
                            SUM(r.netAmount) AS grandRevenue,
                            ROUND(AVG(r.netAmount), 2) AS overallAvgOrder
                     FROM tblsurvey_response_details r
                     INNER JOIN tblproject_team t ON r.team_id = t.team_id AND t.dstatus = 0 AND t.is_type = 0 AND t.s_id = 99
                     INNER JOIN tblbranch br ON t.branch_id = br.branch_id AND br.dstatus = 0
                     WHERE $where $hierarchyFilter AND r.ques_3 IS NOT NULL AND r.ques_3 != '' AND CAST(r.ques_3 AS UNSIGNED) > 0";
        $stmt = $this->pdo->prepare($statsSql);
        $stmt->execute($params);
        $stats = $stmt->fetch(\PDO::FETCH_ASSOC);

        $perspective = $this->isWorstQuery(strtolower($queryText)) ? 'worst' : 'top';
        if ($perspective === 'worst') {
            $records = array_reverse($records);
        }

        $aiContext = "Outlet-level sales ($perspective outlets):\n"
            . "Total outlets: " . ($stats['totalOutlets'] ?? 0) . " | Transactions: " . ($stats['totalTransactions'] ?? 0) . "\n"
            . "Grand revenue: " . round(floatval($stats['grandRevenue'] ?? 0), 2) . " | Avg order: " . ($stats['overallAvgOrder'] ?? 0) . "\n"
            . "Top outlets:\n" . json_encode(array_slice($records, 0, 5), JSON_PRETTY_PRINT);
        $aiText = $this->generateAiForData('outlet_sales', $queryText, $records, $totalRevenue, $aiContext);

        return [
            'query_name' => ucfirst($perspective) . ' Outlets by Sales',
            'record_count' => count($records),
            'records' => $records,
            'metrics' => [
                'total_outlets' => intval($stats['totalOutlets'] ?? 0),
                'total_transactions' => intval($stats['totalTransactions'] ?? 0),
                'grand_revenue' => round(floatval($stats['grandRevenue'] ?? 0), 0),
                'avg_order_value' => round(floatval($stats['overallAvgOrder'] ?? 0), 2),
            ],
            'ai_text' => $aiText
        ];
    }

    /**
     * Handler: Geographic Sales Heatmap - lat/lng aggregated sales
     */
    private function handleGeographicHeatmap($filters, $queryText = '')
    {
        $startDate = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $filters['date_to'] ?? date('Y-m-d');

        // Build WHERE with hierarchy filters (district/main_branch/region/circle/section/wd_code)
        $whereParts = [
            "a.dstatus = 0",
            "b.dstatus = 0",
            "br.dstatus = 0",
            "b.s_id = 99",
            "b.is_type = 0",
            "a.activity_date BETWEEN :start AND :end",
        ];
        $params = [':start' => $startDate, ':end' => $endDate];

        // ACL
        if (!empty($filters['user_teams'])) {
            $teamList = $filters['user_teams'];
            if (is_string($teamList) && preg_match('/^\([\d,]+\)$/', $teamList)) {
                $whereParts[] = "b.team_id IN $teamList";
            }
        }

        // District filter (tblbranch.district like NDIS/EDIS/SDIS/WDIS)
        if (!empty($filters['district']) && is_array($filters['district'])) {
            $ph = [];
            foreach (array_values($filters['district']) as $i => $v) {
                $key = ":dist$i";
                $ph[] = $key;
                $params[$key] = $v;
            }
            if (!empty($ph)) $whereParts[] = "br.district IN (" . implode(',', $ph) . ")";
        }
        // Branch filter (tblbranch.main_branch like EGAU/EPAT/...)
        if (!empty($filters['main_branch'])) {
            $val = is_array($filters['main_branch']) ? ($filters['main_branch'][0] ?? '') : $filters['main_branch'];
            if ($val !== '') { $whereParts[] = "br.main_branch = :mainBranch"; $params[':mainBranch'] = $val; }
        }
        // Region filter (tblbranch.branch_name like Bihar/Assam/...)
        if (!empty($filters['region']) && is_array($filters['region'])) {
            $ph = [];
            foreach (array_values($filters['region']) as $i => $v) {
                $key = ":reg$i";
                $ph[] = $key;
                $params[$key] = $v;
            }
            if (!empty($ph)) $whereParts[] = "br.branch_name IN (" . implode(',', $ph) . ")";
        }
        if (!empty($filters['branch_name'])) {
            $whereParts[] = "br.branch_name = :branchName";
            $params[':branchName'] = $filters['branch_name'];
        }
        // Circle / Section / WD
        if (!empty($filters['circle']) && is_array($filters['circle'])) {
            $ph = [];
            foreach (array_values($filters['circle']) as $i => $v) {
                $key = ":cir$i";
                $ph[] = $key;
                $params[$key] = $v;
            }
            if (!empty($ph)) $whereParts[] = "b.circle IN (" . implode(',', $ph) . ")";
        }
        if (!empty($filters['section']) && is_array($filters['section'])) {
            $ph = [];
            foreach (array_values($filters['section']) as $i => $v) {
                $key = ":sec$i";
                $ph[] = $key;
                $params[$key] = $v;
            }
            if (!empty($ph)) $whereParts[] = "b.section IN (" . implode(',', $ph) . ")";
        }
        if (!empty($filters['wd_code']) && is_array($filters['wd_code'])) {
            $ph = [];
            foreach (array_values($filters['wd_code']) as $i => $v) {
                $key = ":wd$i";
                $ph[] = $key;
                $params[$key] = $v;
            }
            if (!empty($ph)) $whereParts[] = "b.wd_code IN (" . implode(',', $ph) . ")";
        }

        $where = implode(' AND ', $whereParts);

        // Aggregate sales with lat/lng from attendance (include main_branch for hierarchy)
        $sql = "SELECT
                    br.main_branch AS mainBranch,
                    br.branch_name AS regionName,
                    br.district,
                    b.circle,
                    SUM(a.total_sales_deliveries) AS totalSales,
                    COUNT(DISTINCT b.team_id) AS dsCount,
                    ROUND(AVG(att.lt), 6) AS lat,
                    ROUND(AVG(att.lg), 6) AS lng
                FROM tblvands_summary a
                INNER JOIN tblproject_team b ON a.team_id = b.team_id AND b.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0
                INNER JOIN tblbranch br ON b.branch_id = br.branch_id AND br.dstatus = 0
                LEFT JOIN (
                    SELECT team_id, AVG(lt) AS lt, AVG(lg) AS lg
                    FROM tblattendance
                    WHERE s_id = 99 AND dstatus = 0 AND lt != 0 AND lg != 0
                      AND capture_date BETWEEN :start AND :end
                    GROUP BY team_id
                ) att ON b.team_id = att.team_id
                WHERE $where
                GROUP BY br.main_branch, br.branch_name, br.district, b.circle
                HAVING lat IS NOT NULL AND lat != 0 AND lng IS NOT NULL AND lng != 0
                ORDER BY totalSales DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $points = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Detect requested scope (show this + below hierarchy)
        $mapScope = 'all';
        if (!empty($filters['wd_code'])) {
            $mapScope = 'wd_code';
        } elseif (!empty($filters['section'])) {
            $mapScope = 'section';
        } elseif (!empty($filters['circle'])) {
            $mapScope = 'circle';
        } elseif (!empty($filters['region']) || !empty($filters['branch_name'])) {
            $mapScope = 'region';
        } elseif (!empty($filters['main_branch'])) {
            $mapScope = 'branch';
        } elseif (!empty($filters['district'])) {
            $mapScope = 'district';
        }

        $heatmapPoints = [];
        $maxSales = 0;
        $districtSummary = [];
        $branchSummary = [];
        $regionSummary = [];
        $circleSummary = [];

        foreach ($points as $p) {
            $sales = floatval($p['totalSales']);
            if ($sales > $maxSales) $maxSales = $sales;
            $mainBranch = $p['mainBranch'] ?? '';
            $regionName = $p['regionName'] ?? '';
            $district = $p['district'] ?? '';
            $circle = $p['circle'] ?? '';

            $heatmapPoints[] = [
                'lat' => floatval($p['lat']),
                'lng' => floatval($p['lng']),
                'sales' => round($sales, 0),
                'region' => $regionName,
                'district' => $district,
                'circle' => $circle,
                'dsCount' => intval($p['dsCount']),
            ];

            if ($district !== '' && $district !== 'NULL') {
                if (!isset($districtSummary[$district])) {
                    $districtSummary[$district] = ['name' => $district, 'totalSales' => 0, 'regions' => [], 'dsCount' => 0];
                }
                $districtSummary[$district]['totalSales'] += $sales;
                $districtSummary[$district]['regions'][$regionName] = true;
                $districtSummary[$district]['dsCount'] += intval($p['dsCount']);
            }
            if ($mainBranch !== '' && $mainBranch !== 'NULL') {
                if (!isset($branchSummary[$mainBranch])) {
                    $branchSummary[$mainBranch] = ['name' => $mainBranch, 'totalSales' => 0, 'regions' => [], 'dsCount' => 0];
                }
                $branchSummary[$mainBranch]['totalSales'] += $sales;
                $branchSummary[$mainBranch]['regions'][$regionName] = true;
                $branchSummary[$mainBranch]['dsCount'] += intval($p['dsCount']);
            }
            if ($regionName !== '' && $regionName !== 'NULL') {
                if (!isset($regionSummary[$regionName])) {
                    $regionSummary[$regionName] = ['name' => $regionName, 'totalSales' => 0, 'districts' => [], 'dsCount' => 0];
                }
                $regionSummary[$regionName]['totalSales'] += $sales;
                $regionSummary[$regionName]['districts'][$district] = true;
                $regionSummary[$regionName]['dsCount'] += intval($p['dsCount']);
            }

            if ($circle !== '' && $circle !== 'NULL') {
                if (!isset($circleSummary[$circle])) {
                    $circleSummary[$circle] = ['name' => $circle, 'totalSales' => 0, 'regions' => [], 'dsCount' => 0];
                }
                $circleSummary[$circle]['totalSales'] += $sales;
                $circleSummary[$circle]['regions'][$regionName] = true;
                $circleSummary[$circle]['dsCount'] += intval($p['dsCount']);
            }
        }

        $grandTotal = array_sum(array_column($heatmapPoints, 'sales'));

        foreach ($districtSummary as &$d) { $d['regions'] = count($d['regions']); }
        foreach ($branchSummary as &$d) { $d['regions'] = count($d['regions']); }
        foreach ($regionSummary as &$d) { $d['districts'] = count($d['districts']); }
        foreach ($circleSummary as &$d) { $d['regions'] = count($d['regions']); }

        $addShare = function(&$arr) use ($grandTotal) {
            foreach ($arr as &$r) {
                $r['totalSales'] = round($r['totalSales'], 0);
                $r['sharePercent'] = $grandTotal > 0 ? round(($r['totalSales'] / $grandTotal) * 100, 1) : 0;
            }
        };
        usort($districtSummary, fn($a, $b) => $b['totalSales'] <=> $a['totalSales']);
        usort($branchSummary, fn($a, $b) => $b['totalSales'] <=> $a['totalSales']);
        usort($regionSummary, fn($a, $b) => $b['totalSales'] <=> $a['totalSales']);
        usort($circleSummary, fn($a, $b) => $b['totalSales'] <=> $a['totalSales']);
        $addShare($districtSummary);
        $addShare($branchSummary);
        $addShare($regionSummary);
        $addShare($circleSummary);

        // Counts at each hierarchy level (scoped by filters)
        $uniqueDistricts = count($districtSummary);
        $uniqueBranches = count($branchSummary);
        $uniqueRegions = count($regionSummary);
        $uniqueCircles = count($circleSummary);
        $sectionCount = 0;
        $wdCount = 0;
        if ($grandTotal > 0) {
            $countSql = "SELECT COUNT(DISTINCT b.section) AS sec, COUNT(DISTINCT b.wd_code) AS wd
                FROM tblvands_summary a
                INNER JOIN tblproject_team b ON a.team_id = b.team_id AND b.dstatus = 0 AND b.s_id = 99 AND b.is_type = 0
                INNER JOIN tblbranch br ON b.branch_id = br.branch_id AND br.dstatus = 0
                WHERE $where";
            try {
                $cs = $this->pdo->prepare($countSql);
                $cs->execute($params);
                $row = $cs->fetch(\PDO::FETCH_ASSOC);
                $sectionCount = intval($row['sec'] ?? 0);
                $wdCount = intval($row['wd'] ?? 0);
            } catch (\Exception $e) { /* ignore */ }
        }

        // Section summary (only when needed)
        $sectionSummary = [];
        if (in_array($mapScope, ['region', 'circle', 'section', 'wd_code'], true) && $grandTotal > 0) {
            $secSql = "SELECT b.section AS name,
                    SUM(a.total_sales_deliveries) AS totalSales,
                    COUNT(DISTINCT b.team_id) AS dsCount
                FROM tblvands_summary a
                INNER JOIN tblproject_team b ON a.team_id = b.team_id
                INNER JOIN tblbranch br ON b.branch_id = br.branch_id
                WHERE $where AND b.section IS NOT NULL AND b.section != ''
                GROUP BY b.section
                ORDER BY totalSales DESC";
            try {
                $ss = $this->pdo->prepare($secSql);
                $ss->execute($params);
                $sectionSummary = $ss->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                foreach ($sectionSummary as &$r) {
                    $r['totalSales'] = round(floatval($r['totalSales'] ?? 0), 0);
                    $r['dsCount'] = intval($r['dsCount'] ?? 0);
                    $r['sharePercent'] = $grandTotal > 0 ? round(($r['totalSales'] / $grandTotal) * 100, 1) : 0;
                }
            } catch (\Exception $e) { $sectionSummary = []; }
        }

        // WD summary (only when needed)
        $wdSummary = [];
        if (in_array($mapScope, ['circle', 'section', 'wd_code'], true) && $grandTotal > 0) {
            $wdSql = "SELECT b.wd_code AS name,
                    SUM(a.total_sales_deliveries) AS totalSales,
                    COUNT(DISTINCT b.team_id) AS dsCount
                FROM tblvands_summary a
                INNER JOIN tblproject_team b ON a.team_id = b.team_id
                INNER JOIN tblbranch br ON b.branch_id = br.branch_id
                WHERE $where AND b.wd_code IS NOT NULL AND b.wd_code != ''
                GROUP BY b.wd_code
                ORDER BY totalSales DESC
                LIMIT 200";
            try {
                $ws = $this->pdo->prepare($wdSql);
                $ws->execute($params);
                $wdSummary = $ws->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                foreach ($wdSummary as &$r) {
                    $r['totalSales'] = round(floatval($r['totalSales'] ?? 0), 0);
                    $r['dsCount'] = intval($r['dsCount'] ?? 0);
                    $r['sharePercent'] = $grandTotal > 0 ? round(($r['totalSales'] / $grandTotal) * 100, 1) : 0;
                }
            } catch (\Exception $e) { $wdSummary = []; }
        }

        // Only return the summaries relevant to the scope (plus the next levels)
        $outDistrict = $districtSummary;
        $outBranch = $branchSummary;
        $outRegion = $regionSummary;
        $outCircle = $circleSummary;
        $outSection = $sectionSummary;
        $outWd = $wdSummary;
        if ($mapScope === 'branch') {
            $outDistrict = [];
            $outSection = [];
            $outWd = [];
        } elseif ($mapScope === 'region') {
            $outDistrict = [];
            $outBranch = [];
            $outWd = [];
        } elseif ($mapScope === 'circle') {
            $outDistrict = [];
            $outBranch = [];
            $outRegion = [];
        } elseif ($mapScope === 'district') {
            $outCircle = [];
            $outSection = [];
            $outWd = [];
        } elseif ($mapScope === 'all') {
            $outCircle = [];
            $outSection = [];
            $outWd = [];
        }

        $aiContext = "Geographic sales overview:\n"
            . "Districts: $uniqueDistricts | Branches: $uniqueBranches | Regions: $uniqueRegions | Grand total: $grandTotal\n"
            . "Top regions:\n" . json_encode(array_slice($regionSummary, 0, 5), JSON_PRETTY_PRINT);
        $aiText = $this->generateAiForData('geographic_heatmap', $queryText, $heatmapPoints, $grandTotal, $aiContext);

        return [
            'query_name' => 'Geographic Sales Heatmap',
            'record_count' => count($heatmapPoints),
            'records' => $heatmapPoints,
            'map_scope_level' => $mapScope,
            'district_summary' => array_values($outDistrict),
            'branch_summary' => array_values($outBranch),
            'region_summary' => array_values($outRegion),
            'circle_summary' => array_values($outCircle),
            'section_summary' => array_values($outSection),
            'wd_summary' => array_values($outWd),
            'metrics' => [
                'grand_total_sales' => round($grandTotal, 0),
                'total_districts' => $uniqueDistricts,
                'total_branches' => $uniqueBranches,
                'total_regions' => $uniqueRegions,
                'total_circles' => $uniqueCircles,
                'total_sections' => $sectionCount,
                'total_wd_codes' => $wdCount,
                'max_circle_sales' => round($maxSales, 0),
            ],
            'ai_text' => $aiText
        ];
    }

    // ========================================================================
    // HELPER METHODS
    // ========================================================================

    /**
     * Build base WHERE conditions for custom handlers
     */
    private function buildBaseConditions($filters)
    {
        $conditions = [
            'a.dstatus = 0',
            'b.dstatus = 0',
            'd.dstatus = 0',
            'b.s_id = 99',
            'b.is_type = 0'
        ];

        $startDate = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $filters['date_to'] ?? date('Y-m-d');
        $conditions[] = "a.activity_date BETWEEN '$startDate' AND '$endDate'";

        // ACL
        if (!empty($filters['user_teams'])) {
            $teamList = $filters['user_teams'];
            if (is_string($teamList) && preg_match('/^\([\d,]+\)$/', $teamList)) {
                $conditions[] = "b.team_id IN $teamList";
            }
        }

        // District filter
        if (!empty($filters['district']) && is_array($filters['district'])) {
            $districtNames = implode(',', array_map(function($v) {
                return "'" . str_replace("'", "''", $v) . "'";
            }, $filters['district']));
            $conditions[] = "d.district IN ($districtNames)";
        }

        // Branch filter (main_branch, e.g. EGAU)
        if (!empty($filters['main_branch'])) {
            $val = is_array($filters['main_branch']) ? $filters['main_branch'][0] : $filters['main_branch'];
            $conditions[] = "d.main_branch = '" . str_replace("'", "''", $val) . "'";
        }

        // Region filter (branch_name, e.g. Bihar, UP East)
        if (!empty($filters['region']) && is_array($filters['region'])) {
            $regionNames = implode(',', array_map(function($v) {
                return "'" . str_replace("'", "''", $v) . "'";
            }, $filters['region']));
            $conditions[] = "d.branch_name IN ($regionNames)";
        }
        // Legacy fallback
        if (!empty($filters['branch_name'])) {
            $conditions[] = "d.branch_name = '" . str_replace("'", "''", $filters['branch_name']) . "'";
        }

        // Circle filter
        if (!empty($filters['circle']) && is_array($filters['circle'])) {
            $circleNames = implode(',', array_map(function($v) {
                return "'" . str_replace("'", "''", $v) . "'";
            }, $filters['circle']));
            $conditions[] = "b.circle IN ($circleNames)";
        }

        // Section filter
        if (!empty($filters['section']) && is_array($filters['section'])) {
            $sectionNames = implode(',', array_map(function($v) {
                return "'" . str_replace("'", "''", $v) . "'";
            }, $filters['section']));
            $conditions[] = "b.section IN ($sectionNames)";
        }

        // WD Code filter
        if (!empty($filters['wd_code']) && is_array($filters['wd_code'])) {
            $wdCodes = implode(',', array_map(function($v) {
                return "'" . str_replace("'", "''", $v) . "'";
            }, $filters['wd_code']));
            $conditions[] = "b.wd_code IN ($wdCodes)";
        }

        return $conditions;
    }

    /**
     * Build branch filter SQL fragment for custom handlers
     */
    private function buildBranchFilterSql($filters)
    {
        $sql = '';
        if (!empty($filters['region']) && is_array($filters['region'])) {
            $regionNames = implode(',', array_map(function($v) {
                return "'" . str_replace("'", "''", $v) . "'";
            }, $filters['region']));
            $sql .= " AND d.branch_name IN ($regionNames)";
        }
        if (!empty($filters['main_branch']) && !is_array($filters['main_branch'])) {
            $sql .= " AND d.main_branch = '" . str_replace("'", "''", $filters['main_branch']) . "'";
        }
        if (!empty($filters['district']) && is_array($filters['district'])) {
            $districtNames = implode(',', array_map(function($v) {
                return "'" . str_replace("'", "''", $v) . "'";
            }, $filters['district']));
            $sql .= " AND d.district IN ($districtNames)";
        }
        if (!empty($filters['circle']) && is_array($filters['circle'])) {
            $circleNames = implode(',', array_map(function($v) {
                return "'" . str_replace("'", "''", $v) . "'";
            }, $filters['circle']));
            $sql .= " AND b.circle IN ($circleNames)";
        }
        if (!empty($filters['section']) && is_array($filters['section'])) {
            $sectionNames = implode(',', array_map(function($v) {
                return "'" . str_replace("'", "''", $v) . "'";
            }, $filters['section']));
            $sql .= " AND b.section IN ($sectionNames)";
        }
        return $sql;
    }

    /**
     * Resolve branch_ids from filters and build a WHERE fragment for tblbranch_pickupstock_products.
     * Always includes json_id=99 AND team_type=0 (DS only).
     * Returns SQL condition string to append after WHERE.
     */
    private function buildProductTableFilter($filters, $alias = '')
    {
        $pfx = $alias ? "$alias." : '';
        $cond = " AND {$pfx}json_id = 99 AND {$pfx}team_type = 0";

        $branchIds = $this->resolveBranchIds($filters);
        if (!empty($branchIds)) {
            $idList = implode(',', array_map('intval', $branchIds));
            $cond .= " AND {$pfx}branch_id IN ($idList)";
        }
        return $cond;
    }

    /**
     * Resolve branch_id(s) from hierarchy filters by querying tblbranch.
     */
    private function resolveBranchIds($filters)
    {
        $conditions = ["dstatus = 0"];

        if (!empty($filters['region']) && is_array($filters['region'])) {
            $names = implode(',', array_map(fn($v) => "'" . str_replace("'", "''", $v) . "'", $filters['region']));
            $conditions[] = "branch_name IN ($names)";
        }
        if (!empty($filters['main_branch'])) {
            $val = is_array($filters['main_branch']) ? $filters['main_branch'][0] : $filters['main_branch'];
            $conditions[] = "main_branch = '" . str_replace("'", "''", $val) . "'";
        }
        if (!empty($filters['district']) && is_array($filters['district'])) {
            $names = implode(',', array_map(fn($v) => "'" . str_replace("'", "''", $v) . "'", $filters['district']));
            $conditions[] = "district IN ($names)";
        }

        if (count($conditions) <= 1) {
            return [];
        }

        try {
            $sql = "SELECT branch_id FROM tblbranch WHERE " . implode(' AND ', $conditions);
            $stmt = $this->pdo->query($sql);
            return $stmt ? $stmt->fetchAll(\PDO::FETCH_COLUMN) : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Parse "MonthA vs MonthB" or "compare MonthA vs MonthB" (e.g. january 2026 vs february 2026).
     * Returns ['from','to'] for the LATER month (current period) so period comparison uses it as current and computes previous = earlier month.
     * Returns null if pattern not found.
     */
    private function parseMonthVsMonthInQuery($queryLower)
    {
        $months = [
            'january' => 1, 'jan' => 1, 'february' => 2, 'feb' => 2,
            'march' => 3, 'mar' => 3, 'april' => 4, 'apr' => 4,
            'may' => 5, 'june' => 6, 'jun' => 6,
            'july' => 7, 'jul' => 7, 'august' => 8, 'aug' => 8,
            'september' => 9, 'sep' => 9, 'october' => 10, 'oct' => 10,
            'november' => 11, 'nov' => 11, 'december' => 12, 'dec' => 12
        ];
        $monthNames = array_keys($months);
        $pat = '\b(' . implode('|', $monthNames) . ')\s*(\d{4})?\s+vs\.?\s+(' . implode('|', $monthNames) . ')\s*(\d{4})?';
        if (!preg_match('/' . $pat . '/i', $queryLower, $m)) {
            return null;
        }
        $m1 = strtolower(trim($m[1]));
        $y1 = !empty($m[2]) ? (int) $m[2] : (int) date('Y');
        $m2 = strtolower(trim($m[3]));
        $y2 = !empty($m[4]) ? (int) $m[4] : (int) date('Y');
        if (empty($m[2]) && empty($m[4])) {
            $y1 = $y2 = (int) date('Y');
        } elseif (empty($m[2])) {
            $y1 = $y2;
        } elseif (empty($m[4])) {
            $y2 = $y1;
        }
        $num1 = $months[$m1];
        $num2 = $months[$m2];
        $ts1 = mktime(0, 0, 0, $num1, 1, $y1);
        $ts2 = mktime(0, 0, 0, $num2, 1, $y2);
        $today = strtotime(date('Y-m-d'));
        if ($ts2 >= $ts1) {
            $currentNum = $num2;
            $currentY = $y2;
            $prevNum = $num1;
            $prevY = $y1;
            $labelMonthCur = ucfirst($m2);
            $labelMonthPrev = ucfirst($m1);
        } else {
            $currentNum = $num1;
            $currentY = $y1;
            $prevNum = $num2;
            $prevY = $y2;
            $labelMonthCur = ucfirst($m1);
            $labelMonthPrev = ucfirst($m2);
        }
        $from = sprintf('%04d-%02d-01', $currentY, $currentNum);
        $endTs = mktime(0, 0, 0, $currentNum + 1, 0, $currentY);
        $to = date('Y-m-d', $endTs);
        if ($to > date('Y-m-d')) {
            $to = date('Y-m-d');
        }
        $label = $labelMonthCur . ' ' . $currentY . ' vs ' . $labelMonthPrev . ' ' . $prevY;
        return ['from' => $from, 'to' => $to, 'label' => $label];
    }

    /**
     * Parse natural language date expressions from query text.
     * Returns ['from' => 'Y-m-d', 'to' => 'Y-m-d', 'label' => '...'] or null.
     */
    private function parseDateFromQuery($queryLower)
    {
        $today = date('Y-m-d');
        $year = date('Y');
        $month = date('n');

        // "yesterday"
        if (strpos($queryLower, 'yesterday') !== false) {
            $d = date('Y-m-d', strtotime('-1 day'));
            return ['from' => $d, 'to' => $d, 'label' => 'Yesterday'];
        }

        // "today", "today's", "todays" (e.g. give me todays summary, today's summary)
        if (preg_match('/\btoday\'?s?\b/', $queryLower) || preg_match('/\btoday\b/', $queryLower)) {
            return ['from' => $today, 'to' => $today, 'label' => 'Today'];
        }

        // "last N days" (e.g., "last 7 days", "last 90 days")
        if (preg_match('/last\s+(\d+)\s+days?/', $queryLower, $m)) {
            $n = intval($m[1]);
            return ['from' => date('Y-m-d', strtotime("-{$n} days")), 'to' => $today, 'label' => "Last $n days"];
        }

        // "last week"
        if (strpos($queryLower, 'last week') !== false) {
            $start = date('Y-m-d', strtotime('monday last week'));
            $end = date('Y-m-d', strtotime('sunday last week'));
            return ['from' => $start, 'to' => $end, 'label' => 'Last week'];
        }

        // "this week"
        if (strpos($queryLower, 'this week') !== false) {
            $start = date('Y-m-d', strtotime('monday this week'));
            return ['from' => $start, 'to' => $today, 'label' => 'This week'];
        }

        // "last month"
        if (strpos($queryLower, 'last month') !== false && strpos($queryLower, 'vs') === false
            && strpos($queryLower, 'compare') === false && strpos($queryLower, 'and last') === false) {
            $start = date('Y-m-01', strtotime('first day of last month'));
            $end = date('Y-m-t', strtotime('last day of last month'));
            return ['from' => $start, 'to' => $end, 'label' => 'Last month'];
        }

        // "this month"
        if (strpos($queryLower, 'this month') !== false && strpos($queryLower, 'vs') === false
            && strpos($queryLower, 'compare') === false && strpos($queryLower, 'and last') === false) {
            $start = date('Y-m-01');
            return ['from' => $start, 'to' => $today, 'label' => 'This month'];
        }

        // "last quarter"
        if (strpos($queryLower, 'last quarter') !== false) {
            $qtr = ceil($month / 3) - 1;
            if ($qtr <= 0) { $qtr = 4; $year--; }
            $start = date('Y-m-d', mktime(0, 0, 0, ($qtr - 1) * 3 + 1, 1, $year));
            $end = date('Y-m-t', mktime(0, 0, 0, $qtr * 3, 1, $year));
            return ['from' => $start, 'to' => $end, 'label' => "Q$qtr $year"];
        }

        // "this quarter"
        if (strpos($queryLower, 'this quarter') !== false) {
            $qtr = ceil($month / 3);
            $start = date('Y-m-d', mktime(0, 0, 0, ($qtr - 1) * 3 + 1, 1, intval($year)));
            return ['from' => $start, 'to' => $today, 'label' => "Q$qtr $year"];
        }

        // "year to date" / "ytd"
        if (strpos($queryLower, 'year to date') !== false || preg_match('/\bytd\b/', $queryLower)) {
            return ['from' => "$year-01-01", 'to' => $today, 'label' => "YTD $year"];
        }

        // Named month + optional year: "january", "january 2026", "jan 2026"
        $months = [
            'january' => 1, 'jan' => 1, 'february' => 2, 'feb' => 2,
            'march' => 3, 'mar' => 3, 'april' => 4, 'apr' => 4,
            'may' => 5, 'june' => 6, 'jun' => 6,
            'july' => 7, 'jul' => 7, 'august' => 8, 'aug' => 8,
            'september' => 9, 'sep' => 9, 'october' => 10, 'oct' => 10,
            'november' => 11, 'nov' => 11, 'december' => 12, 'dec' => 12
        ];
        foreach ($months as $name => $num) {
            if (preg_match('/\b' . $name . '\s*(\d{4})?\b/', $queryLower, $m)) {
                $y = !empty($m[1]) ? intval($m[1]) : intval($year);
                $start = sprintf('%04d-%02d-01', $y, $num);
                $end = date('Y-m-t', mktime(0, 0, 0, $num, 1, $y));
                if ($end > $today) $end = $today;
                $ucName = ucfirst($name);
                return ['from' => $start, 'to' => $end, 'label' => "$ucName $y"];
            }
        }

        return null;
    }

    /**
     * Check if query is asking for worst/bottom performers
     */
    private function isWorstQuery($queryLower)
    {
        return (strpos($queryLower, 'worst') !== false ||
                strpos($queryLower, 'bottom') !== false ||
                strpos($queryLower, 'lowest') !== false ||
                strpos($queryLower, 'poor') !== false);
    }

    /**
     * Generate AI text for any data set (shared helper)
     * @param bool $multiMetricNarrative When true, ask AI to weave sales, qualification, adherence into one narrative (no separate sections)
     */
    private function generateAiForData($queryType, $queryText, $records, $totalSales = 0, $extraContext = '', $multiMetricNarrative = false)
    {
        try {
            $queryLower = strtolower($queryText);
            $isWorst = $this->isWorstQuery($queryLower);
            $perspective = $isWorst ? 'worst/underperforming' : 'top/best-performing';
            $context = "User asked: \"$queryText\"\n"
                . "IMPORTANT: All sales figures are in UNITS (sticks/packs), not currency. Do not use \$ or any currency symbol.\n"
                . "Context: User is asking from a {$perspective} perspective. Tailor your headings and analysis accordingly.\n"
                . "Query Type: $queryType\n"
                . "Records: " . count($records) . "\n"
                . ($totalSales > 0 ? "Total Sales: $totalSales units\n" : '')
                . ($extraContext ? "$extraContext\n" : '')
                . "Data:\n" . json_encode(array_slice($records, 0, 10), JSON_PRETTY_PRINT) . "\n\n"
                . "Provide concise, actionable analysis with key insights and recommendations. Use #### headings that match the user's query intent. Keep each section to 2-3 bullet points.";
            if ($multiMetricNarrative) {
                $context .= "\n\nCRITICAL: Weave sales, qualification, and adherence into ONE cohesive narrative. Do NOT use separate sections for each metric - integrate them into a single story (e.g. 'DS X sold 100 units with 85% qualification but only 60% route adherence, suggesting...').";
            }
            return $this->callOpenAi($context);
        } catch (\Exception $e) {
            return "Analysis complete. " . count($records) . " records found.";
        }
    }
    /**
     * Extract entities from query text
     */
    private function extractEntityFromQuery($queryText, $entities)
    {
        $found = [];
        foreach ($entities as $entity) {
            if (strpos($queryText, strtolower($entity)) !== false) {
                $found[] = $entity;
            }
        }
        return $found;
    }

    /**
     * Lookup branch_id from branch_name in tblbranch
     */
    private function lookupBranchIdByName($branchName)
    {
        try {
            $nameLower = strtolower(trim($branchName));

            // Query tblbranch to find branch_id matching branch_name
            $sql = "SELECT branch_id FROM tblbranch
                    WHERE dstatus = 0
                    AND LOWER(branch_name) = :name
                    LIMIT 1";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':name' => $nameLower]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($result && isset($result['branch_id'])) {
                return intval($result['branch_id']);
            }

            // Fallback: search by district name
            $sql = "SELECT branch_id FROM tblbranch
                    WHERE dstatus = 0
                    AND LOWER(district) = :name
                    LIMIT 1";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':name' => $nameLower]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return ($result && isset($result['branch_id'])) ? intval($result['branch_id']) : 0;
        } catch (\Exception $e) {
            error_log("Branch lookup error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Extract branches from query by checking tblbranch
     */
    private function extractBranchesFromQuery($queryText)
    {
        try {
            // Get all branch names from tblbranch (the source of truth)
            $stmt = $this->pdo->query("SELECT DISTINCT branch_name FROM tblbranch WHERE dstatus = 0");
            if (!$stmt) {
                return [];
            }

            $branches = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            if (empty($branches)) {
                return [];
            }

            $found = [];
            $queryLower = strtolower($queryText);

            foreach ($branches as $branch) {
                if (!empty($branch) && strpos($queryLower, strtolower($branch)) !== false) {
                    $found[] = $branch;
                }
            }

            return $found;
        } catch (\Exception $e) {
            // Log error but don't crash - branch extraction is optional
            error_log("Branch extraction error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Format query results based on query type
     */
    private function formatQueryResults($queryName, $data)
    {
        $queryConfig = $this->queryBuilder->getQueryConfig($queryName);

        return [
            'query_name' => $queryConfig['name'],
            'description' => $queryConfig['description'],
            'record_count' => count($data),
            'records' => $data,
            'metrics' => $this->calculateMetrics($queryConfig, $data)
        ];
    }

    /**
     * Calculate summary metrics
     */
    private function calculateMetrics($queryConfig, $data)
    {
        if (empty($data)) {
            return [];
        }

        $metrics = [];

        // Calculate from defined metrics in config
        if (isset($queryConfig['metrics'])) {
            foreach ($queryConfig['metrics'] as $metric) {
                $field = $metric['field'];
                $values = array_column($data, $field);

                // Calculate aggregate based on format
                if (isset($metric['format']) && $metric['format'] === 'percent') {
                    // Average of percentages
                    $metrics[$field] = round(array_sum($values) / count($values), 2);
                } else {
                    // Sum of numbers
                    $metrics[$field] = array_sum($values);
                }
            }
        }

        return $metrics;
    }

    /**
     * Generate AI insights using OpenAI
     */
    private function generateAiInsights($queryName, $query, $formattedData, $filters)
    {
        try {
            $queryConfig = $this->queryBuilder->getQueryConfig($queryName);

            // Build context for OpenAI
            $context = "User asked: \"$query\"\n\n";
            $context .= "IMPORTANT: All sales figures are in UNITS (sticks/packs), not currency. Do not use $ or any currency symbol.\n";
            $context .= "Query Type: " . $queryConfig['name'] . "\n";
            $context .= "Records Found: " . $formattedData['record_count'] . "\n";
            $context .= "Data Summary:\n" . json_encode($formattedData['records'], JSON_PRETTY_PRINT) . "\n\n";

            // Use AI prompt from configuration
            $aiPrompt = $queryConfig['ai_prompt'] ?? "Analyze this data and provide actionable insights.";
            $fullPrompt = $context . $aiPrompt;

            // Call OpenAI
            $response = $this->callOpenAi($fullPrompt);

            return $response;

        } catch (\Exception $e) {
            return "Unable to generate AI insights: " . $e->getMessage();
        }
    }

    /**
     * Call OpenAI API
     */
    private function callOpenAi($prompt, $temperature = 0.2)
    {
        $url = 'https://api.openai.com/v1/chat/completions';
        $model = getenv('OPENAI_MODEL') ?: 'gpt-4o-mini';

        $data = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a senior business analyst providing concise, actionable insights on sales and team performance data for ITC cigarette distribution. IMPORTANT RULES: (1) All sales figures are in UNITS (sticks/packs), NOT currency - never use $ or ₹ symbols. (2) Tailor your headings and tone to match the user query - if they ask for "worst" or "bottom", use headings like "Weakest Performers", not "Top Performers". (3) Keep analysis concise: 2-3 bullet points per section. (4) Always complete your response - do not leave sentences unfinished.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => $temperature,
            'max_tokens' => 500,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->openAiApiKey,
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2TLS,
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \Exception("Failed to connect to OpenAI API: $curlError");
        }

        $result = json_decode($response, true);

        if (!isset($result['choices'][0]['message']['content'])) {
            throw new \Exception("Invalid response from OpenAI API");
        }

        return $result['choices'][0]['message']['content'];
    }

    /**
     * Get date range string
     */
    private function getDateRange($filters)
    {
        $from = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $to = $filters['date_to'] ?? date('Y-m-d');

        return [
            'from' => $from,
            'to' => $to,
            'display' => date('M d, Y', strtotime($from)) . ' to ' . date('M d, Y', strtotime($to))
        ];
    }

    /**
     * Get all available query types (for UI documentation)
     */
    public function getAvailableQueryTypes()
    {
        $queries = [];
        foreach ($this->queryBuilder->getAvailableQueries() as $queryName) {
            $config = $this->queryBuilder->getQueryConfig($queryName);
            $queries[] = [
                'name' => $queryName,
                'display_name' => $config['name'],
                'description' => $config['description'],
                'keywords' => $config['keywords'],
                'example_questions' => [
                    // Generate example questions from keywords
                    'Which ' . implode(', ', array_slice($config['keywords'], 0, 2)) . ' has best performance?'
                ]
            ];
        }
        return $queries;
    }

    // ============================================================
    // Breeze Field Force Insights
    // Tables: tblbreeze_response_data  (is_type 6=RMD, 8=Stockist DS)
    // ============================================================
    private function handleBreezeInsights($filters, $queryText = '')
    {
        $startDate = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate   = $filters['date_to']   ?? date('Y-m-d');

        $whereParts = [
            "r.dstatus = 0",
            "r.type IN ('RMD', 'Stockist DS')",
            "r.capture_date BETWEEN :start AND :end",
        ];
        $params = [':start' => $startDate, ':end' => $endDate];

        // Main-branch filter (branch_name in breeze = main_branch code e.g. EVIZ, NJPR)
        if (!empty($filters['main_branch'])) {
            $whereParts[] = "r.branch_name = :main_branch";
            $params[':main_branch'] = $filters['main_branch'];
        }
        // District filter — use COALESCE since r.district can be NULL (JOIN fills it from tblbranch)
        if (!empty($filters['district'])) {
            $whereParts[] = "COALESCE(r.district, b.district) = :district";
            $params[':district'] = $filters['district'];
        }
        // Circle/branch filter (circle in breeze ≈ branch-level code e.g. VIJ, NAF)
        if (!empty($filters['branch'])) {
            $whereParts[] = "r.circle = :circle_f";
            $params[':circle_f'] = $filters['branch'];
        }
        // Section filter
        if (!empty($filters['section'])) {
            $whereParts[] = "r.section = :section_f";
            $params[':section_f'] = $filters['section'];
        }
        // WD code filter
        if (!empty($filters['wd_code'])) {
            $whereParts[] = "r.wd_code = :wd_code_f";
            $params[':wd_code_f'] = $filters['wd_code'];
        }

        $whereClause = implode(' AND ', $whereParts);

        $sql = "SELECT
                    r.capture_date,
                    COALESCE(r.district, b.district)    AS district,
                    r.branch_name,
                    r.circle,
                    r.section,
                    r.wd_code,
                    r.ds_id,
                    r.type,
                    r.ds_name,
                    r.qualified,
                    r.present,
                    TIME_TO_SEC(r.total_time_spent) / 60         AS time_min,
                    r.total_km_travelled                          AS km,
                    r.planned_outlets,
                    r.outlet_re_visit                             AS outlets_visited,
                    COALESCE(r.total_sale / NULLIF(r.value_m, 0), 0) AS sales_m,
                    DAYOFWEEK(r.capture_date)                     AS dow
                FROM tblbreeze_response_data r
                LEFT JOIN tblbranch b ON r.branch_id = b.branch_id AND b.dstatus = 0
                WHERE $whereClause
                ORDER BY r.capture_date, r.branch_name, r.type";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $emptyMetrics = [
            'totalPresent' => 0, 'totalQualified' => 0, 'qualRate' => 0,
            'totalSalesM' => 0, 'avgSalesM' => 0, 'totalRecords' => 0,
            'rmdCount' => 0, 'stockistCount' => 0, 'anomalyCount' => 0,
        ];
        if (empty($rows)) {
            return [
                'query_name'       => 'Breeze Field Force Insights',
                'isBreezeQuery'    => true,
                'breezeData'       => [],
                'breezeDailyTrend' => [],
                'breezeAnomalies'  => [],
                'metrics'          => $emptyMetrics,
                'ai_text'          => 'No Breeze data found for the selected date range and filters.',
            ];
        }

        // ── Aggregate per branch + type and build daily trend ──
        $branchTypeData       = [];
        $dailyTotals          = [];
        $branchDailyAnomaly   = [];   // key = "branch||type", val = [['date'=>…, 'sales'=>…, 'dow'=>…], …]

        foreach ($rows as $r) {
            $key     = ($r['branch_name'] ?? '') . '||' . ($r['type'] ?? '');
            $date    = $r['capture_date'];
            $salesM  = floatval($r['sales_m']);
            $timeMin = floatval($r['time_min']);
            $outlets = intval($r['outlets_visited']);
            $km      = floatval($r['km']);
            $dow     = intval($r['dow']);

            if (!isset($branchTypeData[$key])) {
                $branchTypeData[$key] = [
                    'branch'       => $r['branch_name'] ?? '',
                    'district'     => $r['district']    ?? '',
                    'circle'       => $r['circle']      ?? '',
                    'type'         => $r['type']        ?? '',
                    'present'      => 0,   'qualified'    => 0,
                    'totalSalesM'  => 0.0, 'totalTimeMin' => 0.0,
                    'totalOutlets' => 0,   'totalKm'      => 0.0,
                    'records'      => 0,
                ];
            }
            $d = &$branchTypeData[$key];
            $d['present']      += intval($r['present']);
            $d['qualified']    += intval($r['qualified']);
            $d['totalSalesM']  += $salesM;
            $d['totalTimeMin'] += $timeMin;
            $d['totalOutlets'] += $outlets;
            $d['totalKm']      += $km;
            $d['records']      ++;
            unset($d);

            // Daily trend (all types combined by date)
            if (!isset($dailyTotals[$date])) {
                $dailyTotals[$date] = ['date' => $date, 'totalSalesM' => 0.0, 'present' => 0, 'qualified' => 0, 'outlets' => 0];
            }
            $dailyTotals[$date]['totalSalesM'] += $salesM;
            $dailyTotals[$date]['present']     += intval($r['present']);
            $dailyTotals[$date]['qualified']   += intval($r['qualified']);
            $dailyTotals[$date]['outlets']     += $outlets;

            // Per-branch-type daily list for anomaly detection
            $branchDailyAnomaly[$key][] = ['date' => $date, 'sales' => $salesM, 'dow' => $dow];
        }

        // Build summary rows
        $summaryRows = [];
        foreach ($branchTypeData as $data) {
            $n = max($data['records'], 1);
            $summaryRows[] = [
                'branch'       => $data['branch'],
                'district'     => $data['district'],
                'circle'       => $data['circle'],
                'type'         => $data['type'],
                'present'      => $data['present'],
                'qualified'    => $data['qualified'],
                'qualRate'     => $data['present'] > 0 ? round(($data['qualified'] / $data['present']) * 100, 1) : 0,
                'totalSalesM'  => round($data['totalSalesM'], 2),
                'avgSalesM'    => round($data['totalSalesM'] / $n, 2),
                'avgTimeMin'   => round($data['totalTimeMin'] / $n, 1),
                'avgKm'        => round($data['totalKm'] / $n, 1),
                'totalOutlets' => $data['totalOutlets'],
                'avgOutlets'   => round($data['totalOutlets'] / $n, 1),
            ];
        }
        usort($summaryRows, fn($a, $b) => $b['totalSalesM'] <=> $a['totalSalesM']);

        ksort($dailyTotals);
        $breezeDailyTrend = array_values($dailyTotals);

        // ── Anomaly detection on breeze sales (same Z-score logic as main anomaly) ──
        $dowNames     = [2 => 'Mon', 3 => 'Tue', 4 => 'Wed', 5 => 'Thu', 6 => 'Fri', 7 => 'Sat'];
        $breezeAnomalies = [];

        foreach ($branchDailyAnomaly as $key => $dayList) {
            if (count($dayList) < 3) continue;
            [$branchCode, $teamType] = explode('||', $key, 2);

            $dowGroups = [];
            foreach ($dayList as $day) { $dowGroups[$day['dow']][] = $day['sales']; }

            $dowBaseline = [];
            foreach ($dowGroups as $d => $vals) {
                $n    = count($vals);
                $mean = array_sum($vals) / $n;
                $var  = $n >= 2
                    ? array_sum(array_map(fn($v) => pow($v - $mean, 2), $vals)) / $n
                    : 0;
                $dowBaseline[$d] = ['mean' => $mean, 'stdDev' => sqrt($var), 'count' => $n];
            }
            $allSales      = array_column($dayList, 'sales');
            $overallMean   = array_sum($allSales) / count($allSales);
            $overallVar    = array_sum(array_map(fn($v) => pow($v - $overallMean, 2), $allSales)) / count($allSales);
            $overallStdDev = sqrt($overallVar);

            foreach ($dayList as $day) {
                $d          = $day['dow'];
                $bl         = $dowBaseline[$d] ?? null;
                $useWeekday = $bl && $bl['count'] >= 2 && $bl['stdDev'] > 0;
                $mean       = $useWeekday ? $bl['mean']   : $overallMean;
                $stdDev     = $useWeekday ? $bl['stdDev'] : $overallStdDev;
                if ($stdDev == 0) continue;

                $zScore = ($day['sales'] - $mean) / $stdDev;
                if (abs($zScore) <= 2.0) continue;

                $breezeAnomalies[] = [
                    'branch'           => $branchCode,
                    'type'             => $teamType,
                    'date'             => $day['date'],
                    'weekday'          => $dowNames[$d] ?? '',
                    'actualSales'      => round($day['sales'], 2),
                    'expectedSales'    => round($mean, 2),
                    'zScore'           => round($zScore, 2),
                    'anomalyType'      => $zScore > 0 ? 'spike' : 'drop',
                    'severity'         => abs($zScore) > 3 ? 'high' : 'medium',
                    'deviationPercent' => $mean > 0 ? round((($day['sales'] - $mean) / $mean) * 100, 1) : 0,
                ];
            }
        }
        usort($breezeAnomalies, fn($a, $b) => abs($b['zScore']) <=> abs($a['zScore']));
        $breezeAnomalies = array_slice($breezeAnomalies, 0, 100);

        // ── Overall metrics ──
        $allPresent   = array_sum(array_column($summaryRows, 'present'));
        $allQualified = array_sum(array_column($summaryRows, 'qualified'));
        $allSalesM    = array_sum(array_column($summaryRows, 'totalSalesM'));
        $rmdDsIds = [];
        $stockistDsIds = [];
        foreach ($rows as $r) {
            if ($r['type'] === 'RMD' && !empty($r['ds_id'])) {
                $rmdDsIds[$r['ds_id']] = true;
            } elseif ($r['type'] === 'Stockist DS' && !empty($r['ds_id'])) {
                $stockistDsIds[$r['ds_id']] = true;
            }
        }
        $rmdCount     = count($rmdDsIds);
        $stockistCount = count($stockistDsIds);

        $metrics = [
            'totalPresent'   => $allPresent,
            'totalQualified' => $allQualified,
            'qualRate'       => $allPresent > 0 ? round(($allQualified / $allPresent) * 100, 1) : 0,
            'totalSalesM'    => round($allSalesM, 2),
            'avgSalesM'      => count($summaryRows) > 0 ? round($allSalesM / count($summaryRows), 2) : 0,
            'totalRecords'   => count($rows),
            'rmdCount'       => $rmdCount,
            'stockistCount'  => $stockistCount,
            'anomalyCount'   => count($breezeAnomalies),
        ];

        // ── AI narrative ──
        $aiText = $this->callOpenAi(
            "User asked: \"$queryText\"\n"
            . "IMPORTANT: Sales figures are in M (millions/units). Do NOT use Rs/₹ or currency symbols.\n"
            . "Context: Breeze field-force data. RMD = Route Market Developer. Stockist DS = Stockist Direct Seller. "
            . "This data is manually filled and is typically one day behind.\n"
            . "Date range: $startDate → $endDate\n"
            . "Summary: Present=$allPresent, Qualified=$allQualified, Qual Rate={$metrics['qualRate']}%, "
            . "Total Sales={$metrics['totalSalesM']}M, Distinct RMD DS=$rmdCount, Distinct Stockist DS=$stockistCount\n"
            . "Anomalies: " . count($breezeAnomalies) . "\n"
            . "Top branches by sales:\n" . json_encode(array_slice($summaryRows, 0, 10), JSON_PRETTY_PRINT)
            . (!empty($breezeAnomalies) ? "\nTop anomalies:\n" . json_encode(array_slice($breezeAnomalies, 0, 5), JSON_PRETTY_PRINT) : '')
            . "\n\nProvide: (1) Overall performance summary. (2) RMD vs Stockist DS comparison. "
            . "(3) Top and bottom performers. (4) Attendance analysis. (5) Any anomalies or concerns. Use #### headings."
        );

        return [
            'query_name'       => 'Breeze Field Force Insights',
            'isBreezeQuery'    => true,
            'breezeData'       => $summaryRows,
            'breezeDailyTrend' => $breezeDailyTrend,
            'breezeAnomalies'  => $breezeAnomalies,
            'metrics'          => $metrics,
            'ai_text'          => $aiText,
        ];
    }

    // ========================================================================
    // DS LEADERBOARD
    // ========================================================================

    /**
     * Handler: DS Leaderboard
     * Returns Top-10 and Bottom-10 DS with rank-change vs previous period.
     */
    private function handleDsLeaderboard($filters, $queryText = '')
    {
        $startDate = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate   = $filters['date_to']   ?? date('Y-m-d');

        // Compute previous period of same length for rank-change comparison
        $periodDays = max(1, (int) round((strtotime($endDate) - strtotime($startDate)) / 86400) + 1);
        $prevEnd    = date('Y-m-d', strtotime($startDate) - 86400);
        $prevStart  = date('Y-m-d', strtotime($prevEnd)   - ($periodDays - 1) * 86400);

        $conditions = $this->buildBaseConditions($filters);
        $whereClause = implode(' AND ', $conditions);

        // ── Current period ──
        $sql = "SELECT b.team_id, b.team_name, b.wd_code, d.branch_name, d.district,
                       SUM(a.total_sales_deliveries) AS totalSales,
                       SUM(CASE WHEN a.is_qualified = 1 THEN 1 ELSE 0 END) AS qualifiedDays,
                       COUNT(DISTINCT a.activity_date) AS totalDays
                FROM tblvands_summary a
                INNER JOIN tblproject_team b ON a.team_id = b.team_id
                INNER JOIN tblbranch d ON b.branch_id = d.branch_id
                WHERE $whereClause
                GROUP BY b.team_id, b.team_name, b.wd_code, d.branch_name, d.district
                ORDER BY totalSales DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $currentRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Build rank map for current period
        $currentRankMap = [];
        foreach ($currentRows as $idx => $row) {
            $currentRankMap[$row['team_id']] = $idx + 1;
        }

        // ── Previous period (same WHERE but different dates) ──
        $prevConditions = array_map(function($c) use ($startDate, $endDate, $prevStart, $prevEnd) {
            return str_replace(
                ["'$startDate'", "'$endDate'"],
                ["'$prevStart'", "'$prevEnd'"],
                $c
            );
        }, $conditions);
        $prevWhere = implode(' AND ', $prevConditions);

        $prevSql = "SELECT b.team_id,
                           SUM(a.total_sales_deliveries) AS totalSales
                    FROM tblvands_summary a
                    INNER JOIN tblproject_team b ON a.team_id = b.team_id
                    INNER JOIN tblbranch d ON b.branch_id = d.branch_id
                    WHERE $prevWhere
                    GROUP BY b.team_id
                    ORDER BY totalSales DESC";

        $prevStmt = $this->pdo->prepare($prevSql);
        $prevStmt->execute();
        $prevRows = $prevStmt->fetchAll(\PDO::FETCH_ASSOC);

        $prevRankMap = [];
        foreach ($prevRows as $idx => $row) {
            $prevRankMap[$row['team_id']] = $idx + 1;
        }

        // ── Build full ranked list ──
        $totalDs = count($currentRows);
        $allDs   = [];
        foreach ($currentRows as $idx => $row) {
            $rankNow  = $idx + 1;
            $rankPrev = $prevRankMap[$row['team_id']] ?? null;
            $rankChange = ($rankPrev !== null) ? ($rankPrev - $rankNow) : null; // positive = moved up

            $qualDays  = intval($row['qualifiedDays'] ?? 0);
            $totalDays = intval($row['totalDays'] ?? 0);

            $allDs[] = [
                'rank'             => $rankNow,
                'rankPrev'         => $rankPrev,
                'rankChange'       => $rankChange,
                'teamId'           => $row['team_id'],
                'dsName'           => $row['team_name'] ?? 'Unknown',
                'wdCode'           => $row['wd_code'] ?? '',
                'region'           => $row['branch_name'] ?? '',
                'district'         => $row['district'] ?? '',
                'totalSales'       => round(floatval($row['totalSales'] ?? 0), 2),
                'qualifiedDays'    => $qualDays,
                'totalDays'        => $totalDays,
                'qualificationRate'=> $totalDays > 0 ? round(($qualDays / $totalDays) * 100, 1) : 0,
            ];
        }

        $top10    = array_slice($allDs, 0, 10);
        $bottom10 = array_slice(array_reverse($allDs), 0, 10);

        // Overall stats
        $grandTotal = array_sum(array_column($allDs, 'totalSales'));
        $avgSales   = $totalDs > 0 ? round($grandTotal / $totalDs, 2) : 0;

        $aiText = $this->callOpenAi(
            "User asked: \"$queryText\"\n"
            . "IMPORTANT: Sales figures are in UNITS (sticks/packs). Do NOT use Rs/₹ or currency symbols.\n"
            . "Date range: $startDate → $endDate  |  Previous period: $prevStart → $prevEnd\n"
            . "Total DS in ranking: $totalDs  |  Grand total sales: $grandTotal  |  Avg sales/DS: $avgSales\n"
            . "Top 10 DS:\n" . json_encode($top10, JSON_PRETTY_PRINT) . "\n"
            . "Bottom 10 DS:\n" . json_encode($bottom10, JSON_PRETTY_PRINT) . "\n\n"
            . "Provide: (1) Summary of top performers with rank changes. (2) Bottom performers and concerns. "
            . "(3) Notable movers (biggest rank jumps/drops). (4) Actionable recommendations. Use #### headings."
        );

        return [
            'query_name'     => 'DS Leaderboard',
            'isDsLeaderboard'=> true,
            'top_10'         => $top10,
            'bottom_10'      => $bottom10,
            'record_count'   => $totalDs,
            'metrics'        => [
                'total_ds'    => $totalDs,
                'grand_total' => round($grandTotal, 2),
                'avg_sales'   => $avgSales,
                'period_days' => $periodDays,
            ],
            'ai_text'        => $aiText,
        ];
    }

    // ========================================================================
    // OUTLET VISIT FREQUENCY
    // ========================================================================

    /**
     * Handler: Outlet Visit Frequency Analysis
     * Returns under-visited outlets with visit counts, last visit date, map points.
     */
    private function handleOutletVisitFrequency($filters, $queryText = '')
    {
        $startDate = $filters['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate   = $filters['date_to']   ?? date('Y-m-d');

        $where  = "r.dstatus = 0 AND r.capture_date BETWEEN :start AND :end AND r.s_id = 99";
        $params = [':start' => $startDate, ':end' => $endDate];

        if (!empty($filters['user_teams'])) {
            $teamList = $filters['user_teams'];
            if (is_string($teamList) && preg_match('/^\([\d,]+\)$/', $teamList)) {
                $where .= " AND r.team_id IN $teamList";
            }
        }

        $hierarchyFilter = '';
        if (!empty($filters['district']) && is_array($filters['district'])) {
            $hierarchyFilter .= " AND br.district = :dist";
            $params[':dist'] = $filters['district'][0];
        }
        if (!empty($filters['main_branch'])) {
            $mb = is_array($filters['main_branch']) ? $filters['main_branch'][0] : $filters['main_branch'];
            $hierarchyFilter .= " AND br.main_branch = :mainBr";
            $params[':mainBr'] = $mb;
        }
        if (!empty($filters['region']) && is_array($filters['region'])) {
            $hierarchyFilter .= " AND br.branch_name = :regionName";
            $params[':regionName'] = $filters['region'][0];
        } elseif (!empty($filters['branch_name'])) {
            $hierarchyFilter .= " AND br.branch_name = :regionName";
            $params[':regionName'] = $filters['branch_name'];
        } elseif (!empty($filters['branch']) && !isset($params[':regionName'])) {
            $hierarchyFilter .= " AND br.branch_name = :regionName";
            $params[':regionName'] = is_array($filters['branch']) ? $filters['branch'][0] : $filters['branch'];
        }
        if (!empty($filters['circle']) && is_array($filters['circle'])) {
            $hierarchyFilter .= " AND t.circle = :circ";
            $params[':circ'] = $filters['circle'][0];
        }
        if (!empty($filters['section']) && is_array($filters['section'])) {
            $hierarchyFilter .= " AND t.section = :sec";
            $params[':sec'] = $filters['section'][0];
        }
        if (!empty($filters['wd_code']) && is_array($filters['wd_code'])) {
            $hierarchyFilter .= " AND t.wd_code = :wd";
            $params[':wd'] = $filters['wd_code'][0];
        }

        // Total distinct valid route outlets visited in the period
        // INNER JOIN tblroute_details ensures we only count real outlets (filters garbage ques_3 values)
        $totalSql = "SELECT COUNT(DISTINCT r.ques_3) AS total
                     FROM tblsurvey_response_details r
                     INNER JOIN tblproject_team t ON r.team_id = t.team_id AND t.dstatus = 0 AND t.is_type = 0 AND t.s_id = 99
                     INNER JOIN tblbranch br ON t.branch_id = br.branch_id AND br.dstatus = 0
                     INNER JOIN tblroute_details rd ON rd.rec_id = r.ques_3 AND rd.dstatus = 0
                     WHERE $where $hierarchyFilter";
        $stmtTotal = $this->pdo->prepare($totalSql);
        $stmtTotal->execute($params);
        $totalOutlets = intval($stmtTotal->fetchColumn() ?: 0);

        // Outlets grouped by distinct visit days — sorted ascending (least visited first)
        // COUNT(DISTINCT r.capture_date) = actual days visited, not transaction rows
        $sql = "SELECT
                    r.ques_3 AS outlet_id,
                    MAX(rd.outlet_name) AS outletName,
                    MAX(rd.market_name) AS market,
                    MAX(rd.outlet_type) AS outletType,
                    MAX(t.team_name) AS dsName,
                    MAX(t.wd_code) AS wdCode,
                    MAX(br.branch_name) AS region,
                    COUNT(DISTINCT r.capture_date) AS visitCount,
                    MAX(r.capture_date) AS lastVisitDate,
                    MIN(r.capture_date) AS firstVisitDate,
                    MAX(r.lt) AS lat,
                    MAX(r.lg) AS lng
                FROM tblsurvey_response_details r
                INNER JOIN tblproject_team t ON r.team_id = t.team_id AND t.dstatus = 0 AND t.is_type = 0 AND t.s_id = 99
                INNER JOIN tblbranch br ON t.branch_id = br.branch_id AND br.dstatus = 0
                INNER JOIN tblroute_details rd ON rd.rec_id = r.ques_3 AND rd.dstatus = 0
                WHERE $where $hierarchyFilter
                GROUP BY r.ques_3
                ORDER BY visitCount ASC, lastVisitDate ASC
                LIMIT 50";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $outletRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Compute period days for "expected" visits context
        $periodDays    = max(1, (int) round((strtotime($endDate) - strtotime($startDate)) / 86400) + 1);
        $expectedVisits = max(1, (int) round($periodDays / 7)); // ~weekly expectation

        $records = [];
        $mapPoints = [];
        // Buckets represent distinct visit-day counts
        $visitBuckets = ['1 day' => 0, '2 days' => 0, '3-5 days' => 0, '6-12 days' => 0, '13+ days' => 0];

        foreach ($outletRows as $o) {
            $visits = intval($o['visitCount'] ?? 0);
            $lat    = floatval($o['lat'] ?? 0);
            $lng    = floatval($o['lng'] ?? 0);

            // Bucket by distinct visit-days
            if ($visits <= 1)       $visitBuckets['1 day']++;
            elseif ($visits === 2)  $visitBuckets['2 days']++;
            elseif ($visits <= 5)   $visitBuckets['3-5 days']++;
            elseif ($visits <= 12)  $visitBuckets['6-12 days']++;
            else                    $visitBuckets['13+ days']++;

            $records[] = [
                'outletId'      => $o['outlet_id'] ?? '',
                'outletName'    => $o['outletName'] ?: 'Outlet #' . ($o['outlet_id'] ?? ''),
                'market'        => $o['market'] ?? '',
                'outletType'    => $o['outletType'] ?? '',
                'dsName'        => $o['dsName'] ?? '',
                'wdCode'        => $o['wdCode'] ?? '',
                'region'        => $o['region'] ?? '',
                'visitCount'    => $visits,
                'lastVisitDate' => $o['lastVisitDate'] ?? '',
                'firstVisitDate'=> $o['firstVisitDate'] ?? '',
                'daysSinceVisit'=> !empty($o['lastVisitDate']) ? (int) round((time() - strtotime($o['lastVisitDate'])) / 86400) : null,
                'lat'           => $lat,
                'lng'           => $lng,
            ];

            if ($lat && $lng) {
                $mapPoints[] = [
                    'name'       => $o['outletName'] ?: 'Outlet #' . ($o['outlet_id'] ?? ''),
                    'lat'        => $lat,
                    'lng'        => $lng,
                    'visitCount' => $visits,
                    'dsName'     => $o['dsName'] ?? '',
                ];
            }
        }

        $avgVisits   = count($records) > 0 ? round(array_sum(array_column($records, 'visitCount')) / count($records), 1) : 0;
        $neverVisited = 0; // Can't detect from visit data alone
        $lowVisited   = $visitBuckets['1 day'] + $visitBuckets['2 days'];

        $aiText = $this->callOpenAi(
            "User asked: \"$queryText\"\n"
            . "Date range: $startDate → $endDate  ($periodDays days)  |  Expected visit frequency: ~$expectedVisits visits\n"
            . "Total outlets in scope: $totalOutlets  |  Showing 50 least-visited outlets\n"
            . "Never visited: $neverVisited  |  Visited only 1-2 times: $lowVisited  |  Avg visits: $avgVisits\n"
            . "Frequency distribution: " . json_encode($visitBuckets) . "\n"
            . "Sample under-visited outlets:\n" . json_encode(array_slice($records, 0, 10), JSON_PRETTY_PRINT) . "\n\n"
            . "Provide: (1) Summary of outlet coverage gaps. (2) Most neglected outlets/areas. "
            . "(3) Which DS has the most under-visited outlets. (4) Actionable recommendations to improve visit frequency. Use #### headings."
        );

        return [
            'query_name'            => 'Outlet Visit Frequency',
            'isOutletVisitFrequency'=> true,
            'records'               => $records,
            'outlet_visit_map_points' => $mapPoints,
            'visit_buckets'         => $visitBuckets,
            'record_count'          => count($records),
            'metrics'               => [
                'total_outlets'     => $totalOutlets,
                'shown_count'       => count($records),
                'never_visited'     => $neverVisited,
                'low_visited'       => $lowVisited,
                'avg_visits'        => $avgVisits,
                'expected_visits'   => $expectedVisits,
                'period_days'       => $periodDays,
            ],
            'ai_text'               => $aiText,
        ];
    }
}

?>
