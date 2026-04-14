<?php
/**
 * ARIA AI Insights - Service Layer
 *
 * Sits between the controller (AiInsights) and the executor (InsightsExecutor).
 * Injects ACL filters based on the user's access info before running queries.
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
