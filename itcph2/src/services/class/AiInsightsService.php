<?php
/**
 * ============================================================================
 * SCALABLE AI INSIGHTS FRAMEWORK - Service Entry Point
 * ============================================================================
 *
 * Thin service layer between AiInsights (controller stub) and InsightsExecutor.
 * Handles ACL injection and delegates query execution to InsightsExecutor.
 *
 * ============================================================================
 */

namespace Services\AiInsights;

require_once __DIR__ . '/QueryBuilder.php';
require_once __DIR__ . '/InsightsExecutor.php';

class AiInsightsService
{
    private $pdo;
    private $arrAccessInfo;

    public function __construct($pdo, $arrAccessInfo = [])
    {
        $this->pdo = $pdo;
        $this->arrAccessInfo = $arrAccessInfo;
    }

    /**
     * Main entry point — delegates to InsightsExecutor after injecting ACL filters.
     *
     * @param string $query   Natural language query from the user
     * @param array  $filters Date range and scope filters
     * @return array          Result array with ai_text, charts, detectedFilters, etc.
     */
    public function getInsights(string $query, array $filters = []): array
    {
        // Inject ACL: restrict results to teams this user can see
        if (!empty($this->arrAccessInfo['user_teams'])) {
            $filters['user_teams'] = $this->arrAccessInfo['user_teams'];
        }

        $executor = new \AiInsights\InsightsExecutor($this->pdo, $this->arrAccessInfo);
        return $executor->getInsights($query, $filters);
    }
}
