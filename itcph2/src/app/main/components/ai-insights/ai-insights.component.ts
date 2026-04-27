import { Component, OnDestroy, OnInit, ViewEncapsulation } from '@angular/core';
import { LegendPosition } from '@swimlane/ngx-charts';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { DomSanitizer, SafeHtml } from '@angular/platform-browser';
import { Subscription } from 'rxjs';
import { debounceTime, distinctUntilChanged } from 'rxjs/operators';
import { finalize } from 'rxjs/operators';
import * as L from 'leaflet';

import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { AiInsightsResponse, AiInsightsBreezeRecord } from 'src/app/core/interfaces/http-response.interface';
import { HttpRequestResponse } from 'src/app/core/interfaces/common.interface';
import { FormService } from 'src/app/core/services/form.service';
import { LoaderService } from 'src/app/core/services/loader.service';
import { environment } from 'src/environments/environment';

@Component({
  templateUrl: './ai-insights.component.html',
  styleUrls: ['./ai-insights.component.scss'],
  encapsulation: ViewEncapsulation.None,
  standalone: false,
})
export class AiInsightsComponent implements OnInit, OnDestroy {
  private subscription: Subscription[] = [];
  form!: UntypedFormGroup;
  url = environment.aiInsightsUrl;
  result: AiInsightsResponse | null = null;
  errorMessage = '';
  clarifyingMessage = '';
  clarifyingSuggestions: string[] = [];
  topProductsChart: Array<{ name: string; value: number }> = [];
  topWdCodesChart: Array<{ name: string; value: number }> = [];
  dsPerformanceChart: Array<{ name: string; value: number }> = [];
  comparativeChart: Array<{ name: string; value: number }> = [];
  branchQualifiedChart: Array<{ name: string; value: number }> = [];
  trendChart: Array<{ name: string; series: Array<{ name: string; value: number }> }> = [];
  colorScheme: any = { domain: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#14b8a6'] };
  gradientScheme: any = { domain: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'] };
  trendScheme: any = { domain: ['#3b82f6'] };
  showTrendChart = false;
  showDsPerformance = false;
  showComparative = false;
  showBranchQualified = false;
  showDsCount = false;
  showWdCodePerformance = false;
  showPeriodComparison = false;
  showDayOfWeek = false;
  showGrowthDecline = false;
  showExecutiveSummary = false;
  showAnomalyDetection = false;
  showOutletCoverage = false;
  showProductivity = false;
  showInventory = false;
  showRouteAdherence = false;
  showCategorySales = false;
  showProductComparison = false;
  showFocusBrand = false;
  showDsScorecard = false;
  showHierarchyScorecard = false;
  showComparisonHeatmap = false;
  showOutletSales = false;
  showGeographicHeatmap = false;
  showEntityComparison = false;
  showBreezeInsights = false;
  dsTrendChart: Array<{ name: string; series: Array<{ name: string; value: number }> }> = [];
  dsProductChart: Array<{ name: string; value: number }> = [];
  hierarchyTrendChart: Array<{ name: string; series: Array<{ name: string; value: number }> }> = [];
  hierarchySubChart: Array<{ name: string; value: number }> = [];
  outletSalesChart: Array<{ name: string; value: number }> = [];
  categoryChart: Array<{ name: string; value: number }> = [];
  dsCountChart: Array<{ name: string; value: number }> = [];
  breezeSalesChart: Array<{ name: string; series: Array<{ name: string; value: number }> }> = [];
  breezeStackedScheme: any = { domain: ['#3b82f6', '#f59e0b'] };
  breezeLegendPos = LegendPosition.Below;
  breezeTypeChart: Array<{ name: string; value: number }> = [];
  breezeTrendChart: Array<{ name: string; series: Array<{ name: string; value: number }> }> = [];
  sortedBreezeData: AiInsightsBreezeRecord[] = [];
  breezeSortCol = '';
  breezeSortDir = 'desc';
  Math = Math;
  focusBrandAnomalyChart: Array<{ name: string; series: Array<{ name: string; value: number }> }> = [];
  focusBrandAnomalyDeviationChart: Array<{ name: string; value: number }> = [];

  // ── DS Leaderboard charts ──
  showDsLeaderboard = false;
  dsLeaderboardTop10Chart: Array<{ name: string; value: number }> = [];
  dsLeaderboardBottom10Chart: Array<{ name: string; value: number }> = [];

  // ── Outlet Visit Frequency charts ──
  showOutletVisitFrequency = false;
  outletVisitFrequencyChart: Array<{ name: string; value: number }> = [];
  private outletVisitMap: L.Map | null = null;

  private dsMap: L.Map | null = null;
  private heatMap: L.Map | null = null;
  private comparisonMap: L.Map | null = null;
  private todaySummaryMap: L.Map | null = null;
  private hierarchyHeatMap: L.Map | null = null;
  private anomalyMap: L.Map | null = null;
  selectedAnomalyKey = '';

  // ── Loading state ──
  isQuerying = false;

  // ── Anomaly table sort ──
  anomalySortCol = '';
  anomalySortDir: 'asc' | 'desc' = 'desc';
  sortedAnomalies: any[] = [];

  // ── Collapsible sections (persisted in localStorage) ──
  collapsedSections: Record<string, boolean> = {};
  private readonly COLLAPSED_KEY = 'ai_insights_collapsed';

  // ── AI typewriter effect ──
  typewriterRaw = '';
  typewriterDone = false;
  private typewriterTimer: any = null;

  // ── Anomaly Analytics Charts ──
  anomalyDensityChart: Array<{ name: string; value: number }> = [];
  rootCauseChart: Array<{ name: string; value: number }> = [];
  actualVsExpectedChart: Array<{ name: string; series: Array<{ name: string; value: number }> }> = [];
  anomalyCalendarBranches: Array<{ branch: string; cells: Array<{ date: string; deviation: number; type: string; urgency: string; hasData: boolean }> }> = [];
  anomalyCalendarDates: string[] = [];
  causeColorScheme: any = { domain: ['#f59e0b', '#ef4444', '#3b82f6', '#10b981', '#8b5cf6'] };
  twoSeriesScheme: any = { domain: ['#94a3b8', '#ef4444'] };
  productComparisonChart: Array<{ name: string; series: Array<{ name: string; value: number }> }> = [];
  focusBrandChart: Array<{ name: string; value: number }> = [];
  dayOfWeekChart: Array<{ name: string; value: number }> = [];
  wdCodePerformanceChart: Array<{ name: string; value: number }> = [];
  periodComparisonChart: Array<{ name: string; series: Array<{ name: string; value: number }> }> = [];
  briefingRegionsChart: Array<{ name: string; value: number }> = [];
  briefingTrendChart: Array<{ name: string; series: Array<{ name: string; value: number }> }> = [];
  briefingProductsChart: Array<{ name: string; value: number }> = [];

  // ── Extra charts (donuts + grouped bars per query type) ──
  growthDistributionChart: Array<{ name: string; value: number }> = [];
  dsQualTierChart: Array<{ name: string; value: number }> = [];
  categoryDonutChart: Array<{ name: string; value: number }> = [];
  focusBrandSplitChart: Array<{ name: string; value: number }> = [];
  periodGroupedChart: Array<{ name: string; series: Array<{ name: string; value: number }> }> = [];
  entityComparisonBarChart: Array<{ name: string; series: Array<{ name: string; value: number }> }> = [];
  outletCoverageChart: Array<{ name: string; value: number }> = [];
  wdCodeDonutChart: Array<{ name: string; value: number }> = [];
  branchQualTierChart: Array<{ name: string; value: number }> = [];
  dsScorecardRadarChart: Array<{ name: string; value: number }> = [];
  growthGreenRedScheme: any = { domain: ['#10b981', '#94a3b8', '#ef4444'] };
  qualTierScheme: any = { domain: ['#10b981', '#f59e0b', '#ef4444'] };
  focusSplitScheme: any = { domain: ['#f59e0b', '#94a3b8'] };
  periodGroupedScheme: any = { domain: ['#3b82f6', '#94a3b8'] };
  entityCompareScheme: any = { domain: ['#3b82f6', '#8b5cf6'] };
  productLabel = 'Top Products';
  wdLabel = 'Top WD Codes';
  dsLabel = 'Top DS Performance';
  dataLabel = 'Comparative Data';
  branchLabel = 'Region Qualified Attendance';
  parsedAiText: SafeHtml = '';
  aiSummaryExpanded = false;

  // ── ARIA thinking state ──
  currentThinkingTip = 'Scanning your field force data...';
  private readonly ariaThinkingTips = [
    'Scanning your field force data...',
    'Cross-referencing branch performance...',
    'Analyzing 30 days of sales records...',
    'Identifying top performing DS...',
    'Detecting patterns across regions...',
    'Correlating outlet coverage data...',
    'Running comparative analysis...',
    'Processing distributor metrics...',
    'Calculating qualification rates...',
    'Generating intelligent insights...',
  ];
  private thinkingTipTimer: any = null;

  // Quick chips, recent queries, follow-ups
  activeCategory = 'all';
  chipsExpanded = false;
  recentQueries: string[] = [];
  followUpSuggestions: string[] = [];
  private readonly RECENT_KEY = 'ai_insights_recent';
  private readonly MAX_RECENT = 8;

  // ── Context filters (scope all chips to a branch/circle/DS/etc.) ──
  contextFilters: Array<{ type: string; value: string }> = [];
  pendingContextType = '';
  pendingContextValue = '';
  scopeOptions: string[] = [];
  scopeOptionsLoading = false;
  readonly contextFilterTypes = [
    { type: 'District', icon: 'fa-map', phrase: 'district' },
    { type: 'Branch', icon: 'fa-building', phrase: 'branch' },
    { type: 'Region', icon: 'fa-globe', phrase: 'region' },
    { type: 'Circle', icon: 'fa-circle', phrase: 'circle' },
    { type: 'Section', icon: 'fa-layer-group', phrase: 'section' },
    { type: 'WD Code', icon: 'fa-barcode', phrase: 'wd code' },
    { type: 'DS', icon: 'fa-user', phrase: 'for ds' },
  ];

  // Typeahead suggestions (dropdown while typing)
  showTypeahead = false;
  typeaheadItems: Array<{ text: string; hint?: string }> = [];
  activeTypeaheadIndex = 0;
  private readonly MAX_TYPEAHEAD = 10;
  private typeaheadSuppress = false; // true while submitting or when result is showing

  queryCategories = [
    { id: 'all', label: 'All' },
    { id: 'sales', label: 'Sales' },
    { id: 'team', label: 'Team' },
    { id: 'coverage', label: 'Coverage' },
    { id: 'compare', label: 'Compare' },
    { id: 'hierarchy', label: 'Hierarchy' },
    { id: 'executive', label: 'Executive' },
  ];

  quickChips: Array<{ query: string; icon: string; category: string }> = [
    // ── Sales ──
    { query: 'Top selling products this month', icon: 'fa-trophy', category: 'sales' },
    { query: 'Worst performing products', icon: 'fa-arrow-down', category: 'sales' },
    { query: 'Daily sales trend', icon: 'fa-chart-line', category: 'sales' },
    { query: 'Compare this month vs last month', icon: 'fa-exchange-alt', category: 'sales' },
    { query: 'Compare this week vs last week', icon: 'fa-exchange-alt', category: 'sales' },
    { query: 'Top WD codes by sales', icon: 'fa-barcode', category: 'sales' },
    { query: 'Category wise sales breakdown', icon: 'fa-layer-group', category: 'sales' },
    { query: 'Focus brand performance', icon: 'fa-star', category: 'sales' },
    { query: 'Top outlets by sales', icon: 'fa-store-alt', category: 'sales' },
    { query: 'Which categories are growing?', icon: 'fa-chart-pie', category: 'sales' },
    // ── Team ──
    { query: 'Best performing DS', icon: 'fa-user-check', category: 'team' },
    { query: 'Worst DS by sales', icon: 'fa-user-times', category: 'team' },
    { query: 'How many active DS are there?', icon: 'fa-users', category: 'team' },
    { query: 'Who is improving?', icon: 'fa-arrow-up', category: 'team' },
    { query: 'Who improved most this month?', icon: 'fa-rocket', category: 'team' },
    { query: 'Rising stars in my team', icon: 'fa-star', category: 'team' },
    { query: 'Which DS are declining?', icon: 'fa-arrow-down', category: 'team' },
    // ── Coverage ──
    { query: 'Outlet coverage', icon: 'fa-store', category: 'coverage' },
    { query: 'Route adherence', icon: 'fa-route', category: 'coverage' },
    { query: 'Who spends most time in market?', icon: 'fa-clock', category: 'coverage' },
    { query: 'Circle-wise qualified attendance', icon: 'fa-check-circle', category: 'coverage' },
    { query: 'Region-wise qualified attendance', icon: 'fa-check-circle', category: 'coverage' },
    { query: 'Section-wise qualified attendance', icon: 'fa-check-circle', category: 'coverage' },
    { query: 'District-wise attendance ranking', icon: 'fa-map', category: 'coverage' },
    { query: 'WD code attendance ranking', icon: 'fa-barcode', category: 'coverage' },
    { query: 'Best branch by qualified attendance', icon: 'fa-building', category: 'coverage' },
    { query: 'Worst circle by qualified attendance', icon: 'fa-times-circle', category: 'coverage' },
    // ── Compare ──
    { query: 'Compare performance across circles', icon: 'fa-globe', category: 'compare' },
    { query: 'Compare districts by sales', icon: 'fa-map', category: 'compare' },
    { query: 'Compare all regions by sales', icon: 'fa-globe', category: 'compare' },
    { query: 'Compare all sections by sales', icon: 'fa-layer-group', category: 'compare' },
    { query: 'Compare all branches by sales', icon: 'fa-building', category: 'compare' },
    { query: 'WD code performance ranking', icon: 'fa-barcode', category: 'compare' },
    { query: 'Bihar vs UP East', icon: 'fa-balance-scale', category: 'compare' },
    // ── Hierarchy ──
    { query: 'Best performing region', icon: 'fa-globe', category: 'hierarchy' },
    { query: 'Worst performing region', icon: 'fa-globe', category: 'hierarchy' },
    { query: 'Best performing circle', icon: 'fa-circle', category: 'hierarchy' },
    { query: 'Worst performing circle', icon: 'fa-circle', category: 'hierarchy' },
    { query: 'Best performing section', icon: 'fa-layer-group', category: 'hierarchy' },
    { query: 'Worst performing section', icon: 'fa-layer-group', category: 'hierarchy' },
    { query: 'Best performing branch', icon: 'fa-building', category: 'hierarchy' },
    { query: 'Worst performing branch', icon: 'fa-building', category: 'hierarchy' },
    { query: 'Top districts by sales', icon: 'fa-map', category: 'hierarchy' },
    { query: 'Which circles are growing?', icon: 'fa-arrow-circle-up', category: 'hierarchy' },
    { query: 'Which circles are declining?', icon: 'fa-arrow-circle-down', category: 'hierarchy' },
    { query: 'Which sections are growing?', icon: 'fa-arrow-circle-up', category: 'hierarchy' },
    { query: 'Which sections are declining?', icon: 'fa-arrow-circle-down', category: 'hierarchy' },
    { query: 'Which districts are improving?', icon: 'fa-arrow-circle-up', category: 'hierarchy' },
    { query: 'Which districts are declining?', icon: 'fa-arrow-circle-down', category: 'hierarchy' },
    { query: 'Which branches are growing?', icon: 'fa-arrow-circle-up', category: 'hierarchy' },
    { query: 'Which regions are declining?', icon: 'fa-arrow-circle-down', category: 'hierarchy' },
    { query: 'Which WD codes are growing?', icon: 'fa-barcode', category: 'hierarchy' },
    { query: 'Scorecard for my region', icon: 'fa-id-card', category: 'hierarchy' },
    { query: 'Scorecard for my circle', icon: 'fa-id-card', category: 'hierarchy' },
    { query: 'Scorecard for my section', icon: 'fa-id-card', category: 'hierarchy' },
    // ── Executive ──
    { query: 'Give me a full executive summary', icon: 'fa-briefcase', category: 'executive' },
    { query: 'What happened today?', icon: 'fa-calendar-day', category: 'executive' },
    { query: "This week's summary", icon: 'fa-calendar-week', category: 'executive' },
    { query: 'Any anomalies or unusual patterns?', icon: 'fa-exclamation-triangle', category: 'executive' },
    { query: 'Which day has highest sales?', icon: 'fa-calendar-day', category: 'executive' },
    { query: 'What should I focus on today?', icon: 'fa-crosshairs', category: 'executive' },
    { query: 'Geographic sales heatmap', icon: 'fa-map-marked-alt', category: 'executive' },
    // ── Leaderboard ──
    { query: 'Show me the DS leaderboard', icon: 'fa-trophy', category: 'team' },
    { query: 'Top 10 DS this month', icon: 'fa-medal', category: 'team' },
    { query: 'Bottom 10 DS ranking', icon: 'fa-arrow-down', category: 'team' },
    { query: 'Who moved up in rankings?', icon: 'fa-arrow-circle-up', category: 'team' },
    // ── Outlet Visit Frequency ──
    { query: 'Which outlets are rarely visited?', icon: 'fa-store-slash', category: 'coverage' },
    { query: 'Show under-visited outlets', icon: 'fa-exclamation-circle', category: 'coverage' },
    { query: 'Outlets never visited this month', icon: 'fa-times-circle', category: 'coverage' },
  ];

  private followUpMap: Record<string, string[]> = {
    'product': ['Worst performing products', 'Category wise sales breakdown', 'Focus brand performance', 'Top WD codes by sales'],
    'category-sales': ['Focus brand performance', 'Top selling products this month', 'Compare this month vs last month'],
    'product-comparison': ['Category wise sales breakdown', 'Top selling products this month', 'Who is improving?'],
    'focus-brand': ['Category wise sales breakdown', 'Top selling products this month', 'Compare this month vs last month'],
    'ds-scorecard': ['Best performing DS', 'Who is improving?', 'Outlet coverage'],
    'hierarchy-scorecard': ['Best performing DS', 'Compare performance across circles', 'Who is improving?'],
    'outlet-sales': ['Top selling products this month', 'Geographic sales heatmap', 'Best performing DS'],
    'geographic-heatmap': ['Top selling products this month', 'Compare performance across circles', 'Which regions are declining?'],
    'ds-performance': ['Worst DS by sales', 'Who is improving?', 'Outlet coverage', 'Route adherence'],
    'growth-decline': ['Best performing DS', 'Which regions are declining?', 'Give me a full executive summary'],
    'executive-summary': ['Top selling products this month', 'Best performing DS', 'Any anomalies or unusual patterns?'],
    'anomaly': ['Which branches are declining?', 'Give me a full executive summary', 'Compare this month vs last month'],
    'wd-code': ['Top selling products this month', 'Best performing DS', 'Outlet coverage'],
    'branch-qualified': ['Compare performance across circles', 'Who is improving?', 'Best performing DS'],
    'comparative': ['Compare districts by sales', 'Best branch by qualified attendance', 'Which branches are declining?'],
    'period-comparison': ['Daily sales trend', 'Who is improving?', 'Any anomalies or unusual patterns?'],
    'day-of-week': ['Daily sales trend', 'Compare this month vs last month', 'Top selling products this month'],
    'ds-leaderboard': ['Best performing DS', 'Who is improving?', 'Show me the DS leaderboard', 'DS performance by region'],
    'outlet-visit-frequency': ['Outlet coverage', 'Best performing DS', 'Geographic sales heatmap', 'Which outlets are rarely visited?'],
  };

  constructor(private fb: UntypedFormBuilder, private formService: FormService, private loaderService: LoaderService, private sanitizer: DomSanitizer) { }

  ngOnInit() {
    const today = new Date();
    const start = new Date(today.getFullYear(), today.getMonth(), 1);

    this.form = this.fb.group({
      query: [''],
      startDate: [this.formatDate(start)],
      endDate: [this.formatDate(today)],
    });

    this.loadRecentQueries();
    this.loadCollapsedSections();

    // Live suggestions while typing (prevents "wrong query type" by nudging valid templates)
    const qCtrl = this.form.get('query');
    if (qCtrl) {
      this.subscription.push(
        qCtrl.valueChanges
          .pipe(debounceTime(120), distinctUntilChanged())
          .subscribe((val: any) => {
            const text = (val || '').toString();
            this.updateTypeahead(text);
          })
      );
    }
  }

  ngOnDestroy() {
    this.destroyMaps();
    this.subscription.forEach(sub => sub.unsubscribe());
    if (this.typewriterTimer) { clearInterval(this.typewriterTimer); }
    this.stopAriaThinking();
  }

  private destroyMaps() {
    if (this.dsMap) { this.dsMap.remove(); this.dsMap = null; }
    if (this.heatMap) { this.heatMap.remove(); this.heatMap = null; }
    if (this.comparisonMap) { this.comparisonMap.remove(); this.comparisonMap = null; }
    if (this.hierarchyHeatMap) { this.hierarchyHeatMap.remove(); this.hierarchyHeatMap = null; }
    if (this.todaySummaryMap) { this.todaySummaryMap.remove(); this.todaySummaryMap = null; }
    if (this.anomalyMap) { this.anomalyMap.remove(); this.anomalyMap = null; }
    if (this.outletVisitMap) { this.outletVisitMap.remove(); this.outletVisitMap = null; }
  }

  formatDate(date: Date): string {
    return date.toISOString().slice(0, 10);
  }

  formatPercent = (value: number): string => {
    if (value == null || isNaN(value)) { return ''; }
    return value.toFixed(1) + '%';
  };

  private startAriaThinking() {
    let i = 0;
    this.currentThinkingTip = this.ariaThinkingTips[0];
    this.thinkingTipTimer = setInterval(() => {
      i = (i + 1) % this.ariaThinkingTips.length;
      this.currentThinkingTip = this.ariaThinkingTips[i];
    }, 1800);
  }

  private stopAriaThinking() {
    if (this.thinkingTipTimer) { clearInterval(this.thinkingTipTimer); this.thinkingTipTimer = null; }
  }

  submit() {
    this.closeTypeahead();
    this.typeaheadSuppress = true;
    this.errorMessage = '';
    this.clarifyingMessage = '';
    this.clarifyingSuggestions = [];
    this.result = null;
    this.topProductsChart = [];
    this.topWdCodesChart = [];
    this.dsPerformanceChart = [];
    this.comparativeChart = [];
    this.branchQualifiedChart = [];
    this.trendChart = [];
    this.showTrendChart = false;
    this.showDsPerformance = false;
    this.showComparative = false;
    this.showBranchQualified = false;
    this.showDsCount = false;
    this.dsCountChart = [];
    this.showWdCodePerformance = false;
    this.showPeriodComparison = false;
    this.showDayOfWeek = false;
    this.showGrowthDecline = false;
    this.showExecutiveSummary = false;
    this.briefingRegionsChart = [];
    this.briefingTrendChart = [];
    this.briefingProductsChart = [];
    this.showAnomalyDetection = false;
    this.selectedAnomalyKey = '';
    this.anomalyDensityChart = [];
    this.rootCauseChart = [];
    this.actualVsExpectedChart = [];
    this.anomalyCalendarBranches = [];
    this.anomalyCalendarDates = [];
    this.anomalySortCol = '';
    this.sortedAnomalies = [];
    this.typewriterRaw = '';
    this.typewriterDone = false;
    if (this.typewriterTimer) { clearInterval(this.typewriterTimer); }
    this.showOutletCoverage = false;
    this.showProductivity = false;
    this.showInventory = false;
    this.showRouteAdherence = false;
    this.showCategorySales = false;
    this.showProductComparison = false;
    this.showFocusBrand = false;
    this.showDsScorecard = false;
    this.showHierarchyScorecard = false;
    this.showComparisonHeatmap = false;
    this.showOutletSales = false;
    this.showGeographicHeatmap = false;
    this.showEntityComparison = false;
    this.showBreezeInsights = false;
    this.breezeSalesChart = [];
    this.breezeTypeChart = [];
    this.breezeTrendChart = [];
    this.sortedBreezeData = [];
    this.focusBrandAnomalyChart = [];
    this.focusBrandAnomalyDeviationChart = [];
    this.showDsLeaderboard = false;
    this.dsLeaderboardTop10Chart = [];
    this.dsLeaderboardBottom10Chart = [];
    this.showOutletVisitFrequency = false;
    this.outletVisitFrequencyChart = [];
    this.aiSummaryExpanded = false;
    this.outletSalesChart = [];
    this.destroyMaps();
    this.dayOfWeekChart = [];
    this.wdCodePerformanceChart = [];
    this.periodComparisonChart = [];
    this.categoryChart = [];
    this.productComparisonChart = [];
    this.focusBrandChart = [];
    this.dsTrendChart = [];
    this.dsProductChart = [];
    this.hierarchyTrendChart = [];
    this.hierarchySubChart = [];
    this.growthDistributionChart = [];
    this.dsQualTierChart = [];
    this.categoryDonutChart = [];
    this.focusBrandSplitChart = [];
    this.periodGroupedChart = [];
    this.entityComparisonBarChart = [];
    this.outletCoverageChart = [];
    this.wdCodeDonutChart = [];
    this.branchQualTierChart = [];
    this.dsScorecardRadarChart = [];

    const queryText = (this.form?.get('query')?.value || '').trim();
    if (!queryText) {
      this.typeaheadSuppress = false;
      this.errorMessage = 'Please enter a question.';
      return;
    }

    this.saveRecentQuery(queryText);
    this.followUpSuggestions = [];
    this.isQuerying = true;
    this.startAriaThinking();
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService
        .customActionCall<AiInsightsResponse>(
          STATIC_MODULES.custom.getAiInsights,
          {
            ...this.form.getRawValue(),
            context_filters: this.contextFilters.length
              ? JSON.stringify(this.contextFilters)
              : ''
          },
          null,
          this.url
        )
        .pipe(finalize(() => {
          this.loaderService.stopLoader();
          this.isQuerying = false;
          this.stopAriaThinking();
          this.typeaheadSuppress = false;
          this.closeTypeahead();
        }))
        .subscribe((resp: HttpRequestResponse<AiInsightsResponse>) => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            const data = resp.data as any;
            // Clarifying questions: ambiguous query, show suggestions instead of guessing
            if (data?.success === false && data?.clarifying_questions && data?.suggestions?.length) {
              this.result = null;
              this.errorMessage = '';
              this.clarifyingMessage = data.message || 'Your query could mean a few different things. Which are you looking for?';
              this.clarifyingSuggestions = data.suggestions || [];
              return;
            }
            // Normal error (success false, not clarifying)
            if (data?.success === false) {
              this.result = null;
              this.clarifyingMessage = '';
              this.clarifyingSuggestions = [];
              this.errorMessage = data.message || 'Unable to fetch AI insights.';
              return;
            }
            this.clarifyingMessage = '';
            this.clarifyingSuggestions = [];
            // PHP nests all handler-specific fields under data.data; flatten + normalise here
            const innerData = (data as any)?.data || {};
            const dr = (data as any)?.date_range || {};
            const queryType: string = (data as any)?.query_type || '';

            // Convert metrics keys from snake_case → camelCase
            const rawMetrics: any = innerData.metrics || {};
            const metrics: any = {};
            Object.keys(rawMetrics).forEach(k => {
              metrics[k.replace(/_([a-z])/g, (_: string, c: string) => c.toUpperCase())] = rawMetrics[k];
            });

            // Map 'records' → the right camelCase field + derive boolean flags from query_type
            const records: any[] = innerData.records || [];
            const recordKeyMap: Record<string, string> = {
              product_sales: 'topProducts',
              ds_performance: 'topDs',
              wd_code_performance: 'topWdCodes',
              branch_qualified_attendance: 'topBranches',
              period_comparison: 'comparisonData',
              day_of_week: 'dayOfWeekData',
              growth_decline: 'growthData',
              executive_summary: 'topBranches',
              anomaly_detection: 'anomalyData',
              circle_performance: 'comparativeData',
              district_performance: 'comparativeData',
              section_performance: 'comparativeData',
              branch_performance: 'comparativeData',
              region_performance: 'comparativeData',
              category_sales: 'categoryData',
              product_comparison: 'productComparisonData',
              focus_brand_analysis: 'focusProducts',
              outlet_sales: 'outletSalesData',
              geographic_heatmap: 'heatmapPoints',
              active_ds_count: 'dsCountBreakdown',
              daily_sales_trend: 'dailyTrend',
              outlet_coverage: 'outletData',
              time_productivity: 'productivityData',
              route_analysis: 'routeData',
              inventory_analysis: 'inventoryData',
              ds_leaderboard: 'dsTop10',
              outlet_visit_frequency: 'underVisitedOutlets',
            };
            const flagMap: Record<string, string> = {
              ds_performance: 'isDsQuery',
              wd_code_performance: 'isWdCodeQuery',
              branch_qualified_attendance: 'isBranchQualifiedQuery',
              period_comparison: 'isPeriodComparison',
              day_of_week: 'isDayOfWeekQuery',
              growth_decline: 'isGrowthQuery',
              executive_summary: 'isExecutiveSummary',
              anomaly_detection: 'isAnomalyQuery',
              circle_performance: 'isComparativeQuery',
              district_performance: 'isComparativeQuery',
              section_performance: 'isComparativeQuery',
              branch_performance: 'isComparativeQuery',
              region_performance: 'isComparativeQuery',
              category_sales: 'isCategoryQuery',
              product_comparison: 'isProductComparison',
              focus_brand_analysis: 'isFocusBrandQuery',
              ds_scorecard: 'isDsScorecard',
              hierarchy_scorecard: 'isHierarchyScorecard',
              outlet_sales: 'isOutletSales',
              geographic_heatmap: 'isGeographicHeatmap',
              entity_comparison: 'isEntityComparison',
              active_ds_count: 'isDsCountQuery',
              route_analysis: 'isRouteQuery',
              breeze_insights: 'isBreezeQuery',
              ds_leaderboard: 'isDsLeaderboard',
              outlet_visit_frequency: 'isOutletVisitFrequency',
            };

            const mapped: any = {};
            const recKey = recordKeyMap[queryType];
            if (recKey && records.length > 0 && !innerData[recKey]) {
              // ds_scorecard/hierarchy_scorecard handlers return single object in records
              if (queryType === 'ds_scorecard') { mapped.dsScorecard = records[0]; }
              else if (queryType === 'hierarchy_scorecard') { mapped.hierarchyScorecard = records[0]; }
              else { mapped[recKey] = records; }
            }
            const flag = flagMap[queryType];
            if (flag) { mapped[flag] = true; }

            this.result = {
              ...innerData,
              ...mapped,
              metrics,
              aiText: innerData.ai_text || (data as any)?.ai_text || '',
              showWorst: innerData.show_worst ?? false,
              dateRange: {
                startDate: dr.from || '',
                endDate: dr.to || '',
                days: dr.days || 0,
              },
              detectedFilters: (data as any)?.detected_filters || [],
              // DS Scorecard: remap snake_case backend fields → camelCase interface names
              ...(queryType === 'ds_scorecard' ? {
                dsDailyTrend: innerData.daily_trend || [],
                dsProductBreakdown: innerData.product_breakdown || [],
                dsMapLocations: innerData.map_locations || [],
                dsOutletLocations: innerData.outlet_locations || [],
              } : {}),
              // Hierarchy Scorecard: remap snake_case backend fields → camelCase interface names
              ...(queryType === 'hierarchy_scorecard' ? {
                hierarchyTrend: innerData.daily_trend || [],
                hierarchyTopDs: innerData.top_ds || [],
                hierarchyBottomDs: innerData.bottom_ds || [],
                hierarchySubBreakdown: innerData.sub_breakdown || null,
                hierarchyHeatmapPoints: innerData.heatmap_points || [],
              } : {}),
              // Comparative queries (dimension performance): remap heatmap_points + compare_type
              ...(['circle_performance', 'district_performance', 'section_performance', 'branch_performance', 'region_performance'].includes(queryType) ? {
                heatmapPoints: innerData.heatmap_points || [],
                compareType: innerData.compare_type || '',
                comparativeData: innerData.records || [],
              } : {}),
              // Entity Comparison: remap snake_case → camelCase
              ...(queryType === 'entity_comparison' ? {
                comparisonLeft: innerData.comparison_left || null,
                comparisonRight: innerData.comparison_right || null,
                comparisonLevel: innerData.comparison_level || '',
              } : {}),
              // Anomaly Detection: remap snake_case → camelCase
              ...(queryType === 'anomaly_detection' ? {
                focusBrandAnomalies: innerData.focus_brand_anomalies || [],
                regionalEvents: innerData.regional_events || [],
                anomalyMapPoints: innerData.anomaly_map_points || [],
              } : {}),
              // Executive Summary: remap snake_case → camelCase (overrides the wrong recordKeyMap mapping)
              ...(queryType === 'executive_summary' ? {
                topBranches: innerData.top_branches || [],
                bottomBranches: innerData.bottom_branches || [],
                executiveSummaryDailyTrend: innerData.daily_trend || [],
                executiveSummaryTopProducts: innerData.top_products || [],
                executiveSummaryTopDs: innerData.top_ds || [],
                todayHeatmapPoints: innerData.today_heatmap_points || [],
                executiveSummary: innerData.records?.[0] ? {
                  totalSales: innerData.records[0].totalSales ?? 0,
                  avgDailySales: innerData.records[0].avgDailySales ?? 0,
                  totalDs: innerData.records[0].activeDsCount ?? 0,
                  avgQualificationRate: innerData.records[0].qualificationRate ?? 0,
                  totalBranches: innerData.records[0].activeBranches ?? 0,
                } : null,
              } : {}),
              // DS Leaderboard: remap snake_case → camelCase
              ...(queryType === 'ds_leaderboard' ? {
                dsTop10: innerData.top_10 || [],
                dsBottom10: innerData.bottom_10 || [],
              } : {}),
              // Outlet Visit Frequency: remap snake_case → camelCase
              ...(queryType === 'outlet_visit_frequency' ? {
                underVisitedOutlets: innerData.records || [],
                outletVisitMapPoints: innerData.outlet_visit_map_points || [],
                visitBuckets: innerData.visit_buckets || {},
              } : {}),
            } as any;
            this.parsedAiText = this.parseAiText(this.result?.aiText || '');
            this.aiSummaryExpanded = false;
            this.startTypewriter(this.result?.aiText || '');

            // === New query types (check first) ===
            if (this.result?.isDsScorecard) {
              this.showDsScorecard = true;
              const sc: any = this.result.dsScorecard;
              if (sc) {
                this.dsScorecardRadarChart = [
                  { name: 'Sales vs Peer', value: Math.min(sc.salesVsPeer || 0, 150) },
                  { name: 'Qualification', value: sc.qualificationRate || 0 },
                  { name: 'Route Adherence', value: sc.adherenceRate || 0 },
                  { name: 'Coverage', value: sc.coverageRate || 0 },
                ];
              }
              if (this.result.dsDailyTrend?.length) {
                this.dsTrendChart = [{
                  name: 'Daily Sales',
                  series: this.result.dsDailyTrend.map(d => ({ name: d.date, value: d.sales }))
                }];
              }
              if (this.result.dsProductBreakdown?.length) {
                this.dsProductChart = this.result.dsProductBreakdown.map(p => ({
                  name: p.productName, value: p.totalSales
                }));
              }
              setTimeout(() => this.initDsMap(), 500);
            } else if (this.result?.isHierarchyScorecard) {
              this.showHierarchyScorecard = true;
              if (this.result.hierarchyTrend?.length) {
                this.hierarchyTrendChart = [{
                  name: 'Daily Sales',
                  series: this.result.hierarchyTrend.map(d => ({ name: d.date, value: d.sales }))
                }];
              }
              if (this.result.hierarchySubBreakdown?.data?.length) {
                this.hierarchySubChart = this.result.hierarchySubBreakdown.data.map(s => ({
                  name: s.name, value: s.sales
                }));
              }
              if (this.result.hierarchyHeatmapPoints?.length) {
                setTimeout(() => this.initHierarchyHeatmap(), 500);
              }
            } else if (this.result?.isOutletSales) {
              this.showOutletSales = true;
              this.outletSalesChart = (this.result.outletSalesData || []).slice(0, 15).map(o => ({
                name: o.outletName, value: o.totalRevenue
              }));
            } else if (this.result?.isGeographicHeatmap) {
              this.showGeographicHeatmap = true;
              setTimeout(() => this.initHeatmap(), 500);
            } else if (this.result?.isEntityComparison) {
              this.showEntityComparison = true;
              if (this.result.comparisonLeft && this.result.comparisonRight) {
                const L: any = this.result.comparisonLeft;
                const R: any = this.result.comparisonRight;
                const ln = L.entityName || 'Left';
                const rn = R.entityName || 'Right';
                this.entityComparisonBarChart = [
                  { name: 'Sales', series: [{ name: ln, value: L.totalSales || 0 }, { name: rn, value: R.totalSales || 0 }] },
                  { name: 'DS Count', series: [{ name: ln, value: L.totalDsCount || 0 }, { name: rn, value: R.totalDsCount || 0 }] },
                  { name: 'Qual%', series: [{ name: ln, value: L.qualificationRate || 0 }, { name: rn, value: R.qualificationRate || 0 }] },
                  { name: 'Adherence%', series: [{ name: ln, value: L.adherenceRate || 0 }, { name: rn, value: R.adherenceRate || 0 }] },
                  { name: 'Coverage%', series: [{ name: ln, value: L.coverageRate || 0 }, { name: rn, value: R.coverageRate || 0 }] },
                ];
              }
            } else if (this.result?.isCategoryQuery) {
              this.showCategorySales = true;
              const allCatItems = (this.result.categoryData || []).map(c => ({
                name: c.categoryName,
                value: c.totalSales,
              }));
              this.categoryChart = allCatItems.slice(0, 10);       // top 10 in bar chart
              this.categoryDonutChart = allCatItems.slice(0, 8);   // top 8 in donut
            } else if (this.result?.isProductComparison) {
              this.showProductComparison = true;
              // Single comparison graph: focus on % change only
              this.productComparisonChart = [{
                name: 'Change %',
                series: (this.result.productComparisonData || []).map(d => ({
                  name: d.name,
                  value: d.changePercent
                }))
              }];
            } else if (this.result?.isFocusBrandQuery) {
              this.showFocusBrand = true;
              this.focusBrandChart = (this.result.focusProducts || []).map(p => ({
                name: p.productName,
                value: p.totalSales,
              }));
              this.focusBrandSplitChart = [
                { name: 'Focus Brands', value: this.result.metrics?.focusTotal || 0 },
                { name: 'Non-Focus', value: this.result.metrics?.nonFocusTotal || 0 },
              ].filter(d => d.value > 0);
            } else if (this.result?.isExecutiveSummary) {
              this.showExecutiveSummary = true;
              this.briefingRegionsChart = (this.result.topBranches || []).map((b: any) => ({
                name: b.branch_name || b.regionName || b.branchName || 'Unknown',
                value: b.totalSales ?? 0,
              }));
              this.briefingTrendChart = (this.result.executiveSummaryDailyTrend?.length)
                ? [{ name: 'Sales', series: (this.result.executiveSummaryDailyTrend || []).map((d: any) => ({ name: d.date, value: d.totalSales ?? 0 })) }]
                : [];
              this.briefingProductsChart = (this.result.executiveSummaryTopProducts || []).map((p: any) => ({
                name: p.productName || 'Unknown',
                value: p.totalSales ?? 0,
              }));
              if (this.result?.todayHeatmapPoints?.length) {
                setTimeout(() => this.initTodaySummaryMap(), 500);
              }
            } else if (this.result?.isAnomalyQuery) {
              this.showAnomalyDetection = true;
              this.buildAnomalyCharts();
              if (this.result?.anomalyMapPoints?.length) {
                setTimeout(() => this.initAnomalyMap(), 500);
              }
            } else if (this.result?.isDsLeaderboard) {
              this.showDsLeaderboard = true;
              const top10: any[] = (this.result as any)?.dsTop10 || [];
              const bot10: any[] = (this.result as any)?.dsBottom10 || [];
              this.dsLeaderboardTop10Chart = top10.map(d => ({ name: d.dsName, value: d.totalSales }));
              this.dsLeaderboardBottom10Chart = bot10.map(d => ({ name: d.dsName, value: d.totalSales }));
            } else if (this.result?.isOutletVisitFrequency) {
              this.showOutletVisitFrequency = true;
              const buckets: any = (this.result as any)?.visitBuckets || {};
              this.outletVisitFrequencyChart = Object.entries(buckets).map(([name, value]) => ({ name, value: value as number }));
              const mapPoints: any[] = (this.result as any)?.outletVisitMapPoints || [];
              if (mapPoints.length) {
                setTimeout(() => this.initOutletVisitMap(), 500);
              }
            } else if (this.result?.isBreezeQuery) {
              this.showBreezeInsights = true;
              this.buildBreezeCharts();
            } else if (this.result?.isPeriodComparison) {
              this.showPeriodComparison = true;
              this.periodComparisonChart = [{
                name: 'Change %',
                series: (this.result.comparisonData || []).map(d => ({
                  name: d.name,
                  value: d.changePercent
                }))
              }];
              this.periodGroupedChart = (this.result.comparisonData || []).slice(0, 10).map(d => ({
                name: d.name,
                series: [
                  { name: 'Current', value: d.currentSales || 0 },
                  { name: 'Previous', value: d.previousSales || 0 }
                ]
              }));
            } else if (this.result?.isDayOfWeekQuery) {
              this.showDayOfWeek = true;
              this.dayOfWeekChart = (this.result.dayOfWeekData || []).map(d => ({
                name: d.dayName,
                value: d.avgSales,
              }));
            } else if (this.result?.isGrowthQuery) {
              this.showGrowthDecline = true;
              this.growthDistributionChart = [
                { name: 'Growing', value: this.result.metrics?.growingCount || 0 },
                { name: 'Stable', value: this.result.metrics?.stableCount || 0 },
                { name: 'Declining', value: this.result.metrics?.decliningCount || 0 },
              ].filter(d => d.value > 0);
            } else if (this.result?.isWdCodeQuery) {
              this.showWdCodePerformance = true;
              this.wdCodePerformanceChart = (this.result.topWdCodes || []).map(item => ({
                name: item.wdCode,
                value: item.totalSales,
              }));
              this.wdCodeDonutChart = (this.result.topWdCodes || []).slice(0, 8).map(item => ({
                name: item.wdCode, value: item.totalSales
              }));
            }
            // === Existing query types ===
            // Check if it's a DS count query
            else if (this.result?.isDsCountQuery) {
              this.showDsCount = true;
              if (this.result?.dsCountBreakdown?.length) {
                this.dsCountChart = this.result.dsCountBreakdown.map((b: any) => ({
                  name: b.name || 'Unknown',
                  value: b.dsCount || 0
                }));
              }
            }
            // Check if it's a branch qualified attendance query
            else if (this.result?.isBranchQualifiedQuery) {
              this.showBranchQualified = true;
              this.branchLabel = this.result?.branchLabel || 'Region Qualified Attendance';

              this.branchQualifiedChart = (this.result?.topBranches || []).map(item => ({
                name: item.regionName || item.branchName || 'Unknown',
                value: item.qualificationRate,
              }));

              const qtiers = { good: 0, avg: 0, poor: 0 };
              (this.result.topBranches || []).forEach((b: any) => {
                if (b.qualificationRate >= 80) { qtiers.good++; }
                else if (b.qualificationRate >= 50) { qtiers.avg++; }
                else { qtiers.poor++; }
              });
              this.branchQualTierChart = [
                { name: '≥80% Excellent', value: qtiers.good },
                { name: '50-79% Average', value: qtiers.avg },
                { name: '<50% Poor', value: qtiers.poor },
              ].filter(d => d.value > 0);

              console.log('branchQualifiedChart:', this.branchQualifiedChart);
            }
            // Check if it's a comparative query (circle/section/wd comparison)
            else if (this.result?.isComparativeQuery) {
              this.showComparative = true;
              this.dataLabel = this.result?.dataLabel || 'Comparative Data';

              this.comparativeChart = (this.result?.comparativeData || []).map(item => ({
                name: item.name,
                value: item.totalSales,
              }));

              if (this.result?.heatmapPoints?.length) {
                this.showComparisonHeatmap = true;
                setTimeout(() => this.initComparisonHeatmap(), 500);
              }
            }
            // Check if it's a combined query (DS + Products)
            else if (this.result?.isCombinedQuery) {
              this.showDsPerformance = true;
              this.dsLabel = this.result?.dsLabel || 'Top DS Performance';
              this.productLabel = this.result?.productLabel || 'Top Products';

              this.dsPerformanceChart = (this.result?.topDs || []).map(item => ({
                name: item.dsName,
                value: item.totalSales,
              }));

              this.topProductsChart = (this.result?.topProducts || []).map(item => ({
                name: item.productName,
                value: item.totalSales,
              }));

              const ct = { good: 0, avg: 0, poor: 0 };
              (this.result.topDs || []).forEach((ds: any) => {
                if (ds.qualificationRate >= 80) { ct.good++; }
                else if (ds.qualificationRate >= 50) { ct.avg++; }
                else { ct.poor++; }
              });
              this.dsQualTierChart = [
                { name: 'Excellent ≥80%', value: ct.good },
                { name: 'Average 50-79%', value: ct.avg },
                { name: 'Poor <50%', value: ct.poor },
              ].filter(d => d.value > 0);

              console.log('Combined query - dsPerformanceChart:', this.dsPerformanceChart);
              console.log('Combined query - topProductsChart:', this.topProductsChart);
            }
            // Check if it's a DS-only query
            else if (this.result?.isDsQuery) {
              this.showDsPerformance = true;
              this.dsLabel = this.result?.dsLabel || 'Top DS Performance';

              this.dsPerformanceChart = (this.result?.topDs || []).map(item => ({
                name: item.dsName,
                value: item.totalSales,
              }));

              const tiers = { good: 0, avg: 0, poor: 0 };
              (this.result.topDs || []).forEach((ds: any) => {
                if (ds.qualificationRate >= 80) { tiers.good++; }
                else if (ds.qualificationRate >= 50) { tiers.avg++; }
                else { tiers.poor++; }
              });
              this.dsQualTierChart = [
                { name: 'Excellent ≥80%', value: tiers.good },
                { name: 'Average 50-79%', value: tiers.avg },
                { name: 'Poor <50%', value: tiers.poor },
              ].filter(d => d.value > 0);

              console.log('dsPerformanceChart:', this.dsPerformanceChart);
            } else {
              this.productLabel = this.result?.showWorst ? 'Worst Products' : 'Top Products';
              this.wdLabel = this.result?.showWorst ? 'Worst WD Codes' : 'Top WD Codes';

              this.topProductsChart = (this.result?.topProducts || []).map(item => ({
                name: item.productName,
                value: item.totalSales,
              }));
              this.topWdCodesChart = (this.result?.topWdCodes || []).map(item => ({
                name: item.wdCode,
                value: item.totalSales,
              }));

              console.log('topProductsChart:', this.topProductsChart);
              console.log('topWdCodesChart:', this.topWdCodesChart);

              if (this.result?.dailyTrend && this.result.dailyTrend.length > 0) {
                this.showTrendChart = true;
                this.trendChart = [{
                  name: 'Daily Sales',
                  series: this.result.dailyTrend.map(d => ({
                    name: d.date,
                    value: d.totalSales,
                  }))
                }];
              }

              // Check for outlet, productivity, inventory, route data
              if (this.result?.outletData?.length) {
                this.showOutletCoverage = true;
                this.outletCoverageChart = (this.result.outletData || []).slice(0, 15).map((o: any) => ({
                  name: o.dsName, value: o.coveragePercent || 0
                }));
              }
              if (this.result?.productivityData?.length) { this.showProductivity = true; }
              if (this.result?.inventoryData?.length) { this.showInventory = true; }
              if (this.result?.routeData?.length) { this.showRouteAdherence = true; }
            }

            this.generateFollowUps();
          } else {
            this.errorMessage = (resp?.message && resp.message.length > 0)
              ? resp.message.join(', ')
              : 'Unable to fetch AI insights.';
          }
        })
    );
  }

  // ── Quick Chips ──
  get filteredChips() {
    if (this.activeCategory === 'all') { return this.quickChips; }
    return this.quickChips.filter(c => c.category === this.activeCategory);
  }

  // ── Context filter helpers ──
  // Query text is sent clean — context is sent as a separate parameter for server-side resolution
  private buildContextualQuery(base: string): string {
    return base;
  }

  openContextAdder(type: string) {
    if (this.pendingContextType === type) {
      this.pendingContextType = '';
      this.pendingContextValue = '';
      this.scopeOptions = [];
    } else {
      this.pendingContextType = type;
      const existing = this.contextFilters.find(f => f.type === type);
      this.pendingContextValue = existing?.value || '';
      this.scopeOptions = [];
      this.loadScopeOptions(type);
    }
  }

  loadScopeOptions(type: string) {
    const typeKey = type.toLowerCase().replace(/\s+/g, '_');
    // Determine parent filter from already-selected contextFilters
    // Cascade: WD Code narrows by Branch; DS narrows by WD Code or Branch;
    // Circle/Section/District narrow by Branch
    const parentMap: { [key: string]: string[] } = {
      branch: ['District'],
      region: ['Branch', 'District'],
      circle: ['Region', 'Branch'],
      section: ['Circle', 'Region', 'Branch'],
      wd_code: ['Section', 'Circle', 'Region', 'Branch'],
      ds: ['WD Code', 'Section', 'Circle', 'Region', 'Branch'],
    };
    let parentType = '';
    let parentValue = '';
    const candidates = parentMap[typeKey] || [];
    for (let i = 0; i < candidates.length; i++) {
      const candidate = candidates[i];
      const found = this.contextFilters.find(f => f.type === candidate);
      if (found) {
        parentType = candidate.toLowerCase().replace(/\s+/g, '_');
        parentValue = found.value;
        break;
      }
    }

    this.scopeOptionsLoading = true;
    this.subscription.push(
      this.formService
        .customActionCall<{ options: string[] }>(
          STATIC_MODULES.custom.getAiScopeOptions,
          { type: typeKey, parent_type: parentType, parent_value: parentValue },
          null,
          this.url
        )
        .pipe(finalize(() => { this.scopeOptionsLoading = false; }))
        .subscribe((resp: HttpRequestResponse<{ options: string[] }>) => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data && resp.data.options) {
            this.scopeOptions = resp.data.options;
          } else {
            this.scopeOptions = [];
          }
        })
    );
  }

  confirmContextFilter() {
    const val = this.pendingContextValue.trim();
    this.contextFilters = this.contextFilters.filter(f => f.type !== this.pendingContextType);
    if (val) {
      this.contextFilters.push({ type: this.pendingContextType, value: val });
      // Remove child-level filters that may not exist under the newly selected parent
      const childrenMap: { [key: string]: string[] } = {
        'District': ['Branch', 'Region', 'Circle', 'Section', 'WD Code', 'DS'],
        'Branch': ['Region', 'Circle', 'Section', 'WD Code', 'DS'],
        'Region': ['Circle', 'Section', 'WD Code', 'DS'],
        'Circle': ['Section', 'WD Code', 'DS'],
        'Section': ['WD Code', 'DS'],
        'WD Code': ['DS'],
      };
      const children = childrenMap[this.pendingContextType] || [];
      if (children.length) {
        this.contextFilters = this.contextFilters.filter(f => children.indexOf(f.type) === -1);
      }
    }
    this.pendingContextType = '';
    this.pendingContextValue = '';
    this.scopeOptions = [];
  }

  removeContextFilter(type: string) {
    this.contextFilters = this.contextFilters.filter(f => f.type !== type);
  }

  clearAllContextFilters() {
    this.contextFilters = [];
    this.pendingContextType = '';
    this.pendingContextValue = '';
    this.scopeOptions = [];
  }

  onScopeSelectChange(event: Event) {
    this.pendingContextValue = (event.target as HTMLSelectElement).value;
  }

  isContextFilterActive(type: string): boolean {
    return this.contextFilters.some(f => f.type === type);
  }

  cancelContextAdder() {
    this.pendingContextType = '';
    this.pendingContextValue = '';
    this.scopeOptions = [];
  }

  selectChip(query: string) {
    this.form.get('query')?.setValue(this.buildContextualQuery(query));
    this.submit();
  }

  selectCategory(id: string) {
    this.activeCategory = id;
    this.chipsExpanded = false;
  }

  toggleChips() {
    this.chipsExpanded = !this.chipsExpanded;
  }

  // ── Recent Queries ──
  private loadRecentQueries() {
    try {
      const stored = localStorage.getItem(this.RECENT_KEY);
      this.recentQueries = stored ? JSON.parse(stored) : [];
    } catch {
      this.recentQueries = [];
    }
  }

  private saveRecentQuery(query: string) {
    this.recentQueries = [query, ...this.recentQueries.filter(q => q.toLowerCase() !== query.toLowerCase())].slice(0, this.MAX_RECENT);
    try { localStorage.setItem(this.RECENT_KEY, JSON.stringify(this.recentQueries)); } catch { /* noop */ }
  }

  runRecentQuery(query: string) {
    this.form.get('query')?.setValue(this.buildContextualQuery(query));
    this.submit();
  }

  removeRecentQuery(query: string, event: Event) {
    event.stopPropagation();
    this.recentQueries = this.recentQueries.filter(q => q !== query);
    try { localStorage.setItem(this.RECENT_KEY, JSON.stringify(this.recentQueries)); } catch { /* noop */ }
  }

  // ── Follow-up Suggestions ──
  private generateFollowUps() {
    const vizType = this.result?.visualizationType || '';
    const currentQuery = (this.form.get('query')?.value || '').toLowerCase();

    let suggestions: string[] = [];
    if (this.followUpMap[vizType]) {
      suggestions = this.followUpMap[vizType];
    } else if (this.showOutletCoverage) {
      suggestions = ['Route adherence', 'Who spends most time in market?', 'Best performing DS'];
    } else if (this.showProductivity) {
      suggestions = ['Route adherence', 'Outlet coverage', 'Best performing DS'];
    } else if (this.showRouteAdherence) {
      suggestions = ['Outlet coverage', 'Who spends most time in market?', 'Best performing DS'];
    } else if (this.showInventory) {
      suggestions = ['Top selling products this month', 'Daily sales trend', 'Best performing DS'];
    } else if (this.showCategorySales) {
      suggestions = ['Focus brand performance', 'Top selling products this month', 'Compare this month vs last month'];
    } else if (this.showProductComparison) {
      suggestions = ['Category wise sales breakdown', 'Top selling products this month', 'Who is improving?'];
    } else if (this.showFocusBrand) {
      suggestions = ['Category wise sales breakdown', 'Top selling products this month', 'Compare this month vs last month'];
    } else if (this.showDsScorecard) {
      suggestions = ['Best performing DS', 'Who is improving?', 'Outlet coverage'];
    } else if (this.showHierarchyScorecard) {
      suggestions = ['Best performing DS', 'Compare performance across circles', 'Give me a full executive summary'];
    } else if (this.showOutletSales) {
      suggestions = ['Top selling products this month', 'Geographic sales heatmap', 'Best performing DS'];
    } else if (this.showGeographicHeatmap) {
      suggestions = ['Top selling products this month', 'Compare performance across circles', 'Which regions are declining?'];
    } else if (this.showDsLeaderboard) {
      suggestions = this.followUpMap['ds-leaderboard'] || ['Best performing DS', 'Who is improving?', 'Show me the DS leaderboard'];
    } else if (this.showOutletVisitFrequency) {
      suggestions = this.followUpMap['outlet-visit-frequency'] || ['Outlet coverage', 'Best performing DS', 'Geographic sales heatmap'];
    } else {
      suggestions = ['Top selling products this month', 'Best performing DS', 'Who is improving?'];
    }

    suggestions = suggestions.filter(s => s.toLowerCase() !== currentQuery);
    this.followUpSuggestions = suggestions.slice(0, 3);
  }

  runFollowUp(query: string) {
    this.form.get('query')?.setValue(this.buildContextualQuery(query));
    this.submit();
  }

  // ── Export PDF (includes metrics, charts, tables) ──
  exportPDF(): void {
    if (!this.result) return;
    const el = document.getElementById('aiInsightsPdfContent');
    if (!el) {
      console.warn('AI Insights PDF content element not found');
      return;
    }
    this.loaderService.startLoader();
    Promise.all([
      import('html2canvas').then(m => m.default),
      import('jspdf').then(m => m.jsPDF),
    ]).then(([html2canvas, jsPDF]) => {
      return html2canvas(el, {
        scale: 2,
        useCORS: true,
        logging: false,
        backgroundColor: '#ffffff',
        allowTaint: true,
      }).then((canvas) => {
        const imgW = canvas.width;
        const imgH = canvas.height;
        const doc = new jsPDF('p', 'mm', 'a4');
        const pdfW = doc.internal.pageSize.getWidth();
        const pdfH = doc.internal.pageSize.getHeight();
        const totalPdfH = (imgH / imgW) * pdfW;
        const numPages = Math.ceil(totalPdfH / pdfH) || 1;
        let yOffset = 0;
        const imgData = canvas.toDataURL('image/png');
        for (let p = 0; p < numPages; p++) {
          if (p > 0) doc.addPage();
          doc.addImage(imgData, 'PNG', 0, -yOffset, pdfW, totalPdfH);
          yOffset += pdfH;
        }
        doc.save(`ai-insights-${new Date().toISOString().slice(0, 10)}.pdf`);
      });
    }).then(() => {
      this.loaderService.stopLoader();
    }).catch((err) => {
      console.error('PDF export failed', err);
      this.loaderService.stopLoader();
    });
  }

  // ── Typeahead (autocomplete) ──
  onQueryFocus() {
    const text = (this.form.get('query')?.value || '').toString();
    this.updateTypeahead(text);
  }

  onQueryBlur() {
    // Delay close so click on dropdown works
    setTimeout(() => this.closeTypeahead(), 150);
  }

  onQueryKeyDown(e: KeyboardEvent) {
    if (!this.showTypeahead || !this.typeaheadItems?.length) return;
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      this.activeTypeaheadIndex = Math.min(this.activeTypeaheadIndex + 1, this.typeaheadItems.length - 1);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      this.activeTypeaheadIndex = Math.max(this.activeTypeaheadIndex - 1, 0);
    } else if (e.key === 'Enter') {
      // Only hijack Enter if dropdown is open and user is navigating suggestions
      if (this.showTypeahead && this.typeaheadItems[this.activeTypeaheadIndex]) {
        e.preventDefault();
        this.pickTypeahead(this.typeaheadItems[this.activeTypeaheadIndex].text, true);
      }
    } else if (e.key === 'Escape') {
      this.closeTypeahead();
    }
  }

  pickTypeahead(text: string, run = false) {
    this.form.get('query')?.setValue(text);
    this.closeTypeahead();
    if (run) {
      this.submit();
    }
  }

  private closeTypeahead() {
    this.showTypeahead = false;
    this.typeaheadItems = [];
    this.activeTypeaheadIndex = 0;
  }

  private updateTypeahead(raw: string) {
    if (this.typeaheadSuppress) {
      this.closeTypeahead();
      return;
    }
    const text = (raw || '').toString().trim();
    const normalized = text.toLowerCase();
    if (!normalized || normalized.length < 2) {
      this.closeTypeahead();
      return;
    }

    const items: Array<{ text: string; hint?: string }> = [];

    // Helper: add only if not already present (case-insensitive)
    const add = (t: string, hint?: string) => {
      if (!t) return;
      const key = t.toLowerCase();
      if (!items.some(x => x.text.toLowerCase() === key)) {
        items.push({ text: t, hint });
      }
    };

    // Helper: extract entity after " in " or " for " from raw query (preserves user casing, e.g. NSAH, NLUC)
    const entityAfterIn = (): string | null => {
      const m = text.match(/\s+in\s+(\S+(?:\s+\S+)*)\s*$/i) || text.match(/\s+for\s+(\S+(?:\s+\S+)*)\s*$/i);
      return m ? m[1].trim() : null;
    };
    const entity = entityAfterIn();

    if (normalized.startsWith('best') || normalized.includes(' best ')) {
      if (entity) add(`Best DS in ${entity}`, 'Team + Region');
      add('Best selling products this month', 'Products');
      add('Best performing DS', 'Team');
      add('Best branch by qualified attendance', 'Compare');
      add('Best DS in Bihar', 'Team + Region');
      add('Best DS in my region', 'Team');
    }
    if (normalized.startsWith('top') || normalized.includes(' top ')) {
      add('Top selling products this month', 'Products');
      add('Top WD codes by sales', 'WD');
      add('Top outlets by sales', 'Outlets');
      add('Top 5 DS this month', 'Team');
    }
    if (normalized.startsWith('worst') || normalized.includes(' worst ') || normalized.includes('bottom')) {
      add('Worst performing products', 'Products');
      add('Worst DS by sales', 'Team');
      add('Which regions are declining?', 'Compare');
    }
    if (normalized.includes('anomal') || normalized.includes('unusual') || normalized.includes('issue') || normalized.includes('wrong')) {
      // If user typed "any anomalies in X", suggest that exact query first so they can run it
      if (entity && entity.length >= 2) {
        add(`Any anomalies in ${entity}`, 'Anomalies + Region');
      }
      add('Any anomalies or unusual patterns?', 'Anomalies');
      add('Any anomalies in Bihar', 'Anomalies + Region');
    }
    if (normalized.includes('scorecard') || normalized.includes('profile') || normalized.includes('report card')) {
      if (entity && entity.length >= 2) add(`Scorecard for ${entity}`, 'Scorecard');
      add('Scorecard for Bihar', 'Scorecard');
      add('Scorecard for EGAU', 'Scorecard');
      add('Tell me about Raviranjan Kumar', 'DS Scorecard');
    }
    if (normalized.includes('trend') || normalized.includes('over time')) {
      add('Daily sales trend', 'Trend');
    }
    if (normalized.includes('compare') || normalized.includes('vs') || normalized.includes('versus')) {
      add('Compare this month vs last month', 'Comparison');
      add('Compare performance across circles', 'Comparison');
      add('Compare districts by sales', 'Comparison');
    }
    if (normalized.includes('coverage') || normalized.includes('outlet') || normalized.includes('visit')) {
      add('Outlet coverage', 'Coverage');
      add('Top outlets by sales', 'Outlets');
    }
    if (normalized.includes('route') || normalized.includes('adherence') || normalized.includes('pjp')) {
      add('Route adherence', 'Coverage');
    }

    // 2) Existing quick chips (match by substring/tokens)
    const chips = (this.quickChips || []).map(c => c.query);
    const recent = (this.recentQueries || []);
    const base = Array.from(new Set([...items.map(i => i.text), ...chips, ...recent]));

    const tokens = normalized.split(/\s+/).filter(Boolean);
    const score = (candidate: string) => {
      const c = candidate.toLowerCase();
      let s = 0;
      for (const t of tokens) {
        const idx = c.indexOf(t);
        if (idx === -1) return -1;
        s += 10;
        if (idx === 0) s += 4;
      }
      // Prefer shorter suggestions when equal
      s -= Math.min(6, Math.floor(candidate.length / 20));
      return s;
    };

    const ranked = base
      .map(t => ({ t, s: score(t) }))
      .filter(x => x.s >= 0)
      .sort((a, b) => b.s - a.s)
      .map(x => x.t);

    ranked.slice(0, this.MAX_TYPEAHEAD).forEach(t => add(t));

    // If user typed a full query that looks complete (e.g. "Any anomalies in NSAH"), put it first so Enter runs it
    const looksComplete = text.length >= 10 && (/\s+in\s+\S+/.test(normalized) || /\s+for\s+\S+/.test(normalized));
    if (looksComplete && (normalized.includes('anomal') || normalized.includes('scorecard') || normalized.includes('best') || normalized.includes('top'))) {
      if (!items.some(x => x.text.toLowerCase() === text.toLowerCase())) {
        items.unshift({ text, hint: 'Use this exact query' });
      }
    }

    this.typeaheadItems = items.slice(0, this.MAX_TYPEAHEAD);
    this.activeTypeaheadIndex = 0;
    this.showTypeahead = this.typeaheadItems.length > 0;
  }

  // ── Leaflet Map Initialization ──
  toggleAnomalyDrillDown(branch: string, date: string) {
    const key = `${branch}|${date}`;
    this.selectedAnomalyKey = this.selectedAnomalyKey === key ? '' : key;
  }

  isAnomalyExpanded(branch: string, date: string): boolean {
    return this.selectedAnomalyKey === `${branch}|${date}`;
  }

  getDsBarWidth(dsBreakdown: Array<{ dsName: string; sales: number }>, sales: number): number {
    if (!dsBreakdown?.length) return 0;
    const max = Math.max(...dsBreakdown.map(d => d.sales));
    return max > 0 ? Math.round((sales / max) * 100) : 0;
  }

  // ── Anomaly Analytics: build chart data from anomaly response ──
  private buildAnomalyCharts() {
    const anomalies = this.result?.anomalyData || [];
    if (!anomalies.length) return;

    // 1. Density timeline — how many anomalies per date
    const dateCounts: Record<string, number> = {};
    anomalies.forEach(a => { dateCounts[a.date] = (dateCounts[a.date] || 0) + 1; });
    this.anomalyDensityChart = Object.entries(dateCounts)
      .sort(([a], [b]) => a.localeCompare(b))
      .map(([date, count]) => ({ name: this.shortDate(date), value: count }));

    // 2. Root cause donut — count by cause label
    const causeMap: Record<string, number> = {};
    anomalies.forEach(a => {
      const label = a.cause === 'no_ds_in_field' ? 'No DS'
        : a.cause === 'attendance_issue' ? 'Attendance'
          : a.cause === 'market_issue' ? 'Market'
            : a.cause === 'demand_spike' ? 'Demand Spike'
              : a.cause === 'extra_ds_deployed' ? 'Extra DS' : 'Other';
      causeMap[label] = (causeMap[label] || 0) + 1;
    });
    this.rootCauseChart = Object.entries(causeMap)
      .sort(([, a], [, b]) => b - a)
      .map(([name, value]) => ({ name, value }));

    // 3. Actual vs Expected overlay — aggregate anomalous branch totals per date
    const dateActual: Record<string, number> = {};
    const dateExpected: Record<string, number> = {};
    anomalies.forEach(a => {
      dateActual[a.date] = (dateActual[a.date] || 0) + a.actualSales;
      dateExpected[a.date] = (dateExpected[a.date] || 0) + a.expectedSales;
    });
    const sortedDates = Object.keys(dateActual).sort();
    this.actualVsExpectedChart = [
      { name: 'Expected', series: sortedDates.map(d => ({ name: this.shortDate(d), value: Math.round(dateExpected[d]) })) },
      { name: 'Actual', series: sortedDates.map(d => ({ name: this.shortDate(d), value: Math.round(dateActual[d]) })) },
    ];

    // 4. Heatmap calendar — branch × date grid
    const branches = [...new Set(anomalies.map(a => a.branch))];
    const dates = [...new Set(anomalies.map(a => a.date))].sort();
    this.anomalyCalendarDates = dates;
    const lookup: Record<string, any> = {};
    anomalies.forEach(a => { lookup[`${a.branch}|${a.date}`] = a; });
    this.anomalyCalendarBranches = branches.map(branch => ({
      branch,
      cells: dates.map(date => {
        const a = lookup[`${branch}|${date}`];
        return a
          ? { date, deviation: a.deviationPercent, type: a.type, urgency: a.urgency, hasData: true }
          : { date, deviation: 0, type: 'none', urgency: 'none', hasData: false };
      })
    }));
    this.applyAnomalySort();

    // 5. Focus Brand Anomaly charts
    const focusAnomalies: any[] = this.result?.focusBrandAnomalies || [];
    if (focusAnomalies.length) {
      // Aggregate Actual vs Expected per product (sum across dates)
      const prodMap: Record<string, { actual: number; expected: number }> = {};
      focusAnomalies.forEach(a => {
        if (!prodMap[a.productName]) { prodMap[a.productName] = { actual: 0, expected: 0 }; }
        prodMap[a.productName].actual += a.actualSales || 0;
        prodMap[a.productName].expected += a.expectedSales || 0;
      });
      // Sort by expected desc, take top 10
      const sortedProds = Object.entries(prodMap)
        .sort(([, a], [, b]) => b.expected - a.expected)
        .slice(0, 10);

      this.focusBrandAnomalyChart = [
        { name: 'Expected', series: sortedProds.map(([name, v]) => ({ name, value: Math.round(v.expected) })) },
        { name: 'Actual', series: sortedProds.map(([name, v]) => ({ name, value: Math.round(v.actual) })) },
      ];

      // Deviation % bar (worst drops first)
      const devMap: Record<string, number> = {};
      focusAnomalies
        .filter(a => a.type === 'drop')
        .forEach(a => {
          const key = a.productName;
          if (!devMap[key] || a.deviationPercent < devMap[key]) {
            devMap[key] = a.deviationPercent;
          }
        });
      this.focusBrandAnomalyDeviationChart = Object.entries(devMap)
        .sort(([, a], [, b]) => a - b)
        .slice(0, 10)
        .map(([name, value]) => ({ name, value: Math.abs(value) }));
    }
  }

  private buildBreezeCharts() {
    const data = this.result?.breezeData || [];

    // Stacked sales bar — one bar per branch, RMD + Stockist DS segments
    const branchMap = new Map<string, { rmd: number; stockist: number }>();
    data.forEach(r => {
      if (!branchMap.has(r.branch)) branchMap.set(r.branch, { rmd: 0, stockist: 0 });
      const entry = branchMap.get(r.branch)!;
      if (r.type === 'RMD') entry.rmd += r.totalSalesM;
      else entry.stockist += r.totalSalesM;
    });
    // Sort branches by total sales descending
    const sortedBranches = Array.from(branchMap.entries())
      .sort((a, b) => (b[1].rmd + b[1].stockist) - (a[1].rmd + a[1].stockist));
    this.breezeSalesChart = sortedBranches.map(([branch, vals]) => ({
      name: branch,
      series: [
        { name: 'RMD', value: Math.round(vals.rmd * 100) / 100 },
        { name: 'Stockist DS', value: Math.round(vals.stockist * 100) / 100 },
      ]
    }));

    // RMD vs Stockist DS donut
    const rmdTotal = data.filter(r => r.type === 'RMD').reduce((s, r) => s + r.totalSalesM, 0);
    const stTotal = data.filter(r => r.type === 'Stockist DS').reduce((s, r) => s + r.totalSalesM, 0);
    this.breezeTypeChart = [
      { name: 'RMD', value: Math.round(rmdTotal * 100) / 100 },
      { name: 'Stockist DS', value: Math.round(stTotal * 100) / 100 },
    ].filter(d => d.value > 0);

    // Daily sales trend line
    const trend = this.result?.breezeDailyTrend || [];
    this.breezeTrendChart = trend.length
      ? [{ name: 'Sales (M)', series: trend.map(d => ({ name: this.shortDate(d.date), value: d.totalSalesM })) }]
      : [];

    this.sortedBreezeData = [...data];
  }

  sortBreezeData(col: string) {
    if (this.breezeSortCol === col) {
      this.breezeSortDir = this.breezeSortDir === 'asc' ? 'desc' : 'asc';
    } else {
      this.breezeSortCol = col;
      this.breezeSortDir = 'desc';
    }
    const dir = this.breezeSortDir === 'asc' ? 1 : -1;
    this.sortedBreezeData = [...(this.result?.breezeData || [])].sort((a: any, b: any) => {
      const va = a[col], vb = b[col];
      if (typeof va === 'number') return (va - vb) * dir;
      return String(va ?? '').localeCompare(String(vb ?? '')) * dir;
    });
  }

  getBreezeSortIcon(col: string): string {
    if (this.breezeSortCol !== col) return 'fa-sort';
    return this.breezeSortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
  }

  // ── Anomaly Table Sorting ──
  sortAnomalies(col: string) {
    if (this.anomalySortCol === col) {
      this.anomalySortDir = this.anomalySortDir === 'asc' ? 'desc' : 'asc';
    } else {
      this.anomalySortCol = col;
      this.anomalySortDir = 'desc';
    }
    this.applyAnomalySort();
  }

  private applyAnomalySort() {
    const rows = [...(this.result?.anomalyData || [])];
    if (!this.anomalySortCol) {
      this.sortedAnomalies = rows;
      return;
    }
    const dir = this.anomalySortDir === 'asc' ? 1 : -1;
    const col = this.anomalySortCol;
    this.sortedAnomalies = rows.sort((a: any, b: any) => {
      const va = a[col], vb = b[col];
      if (typeof va === 'number') return (va - vb) * dir;
      return String(va ?? '').localeCompare(String(vb ?? '')) * dir;
    });
  }

  getSortIcon(col: string): string {
    if (this.anomalySortCol !== col) return 'fa-sort';
    return this.anomalySortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
  }

  getDevBarWidth(deviation: number): number {
    return Math.min(Math.abs(deviation ?? 0), 100);
  }

  // ── Collapsible Sections ──
  isSectionCollapsed(id: string): boolean {
    return !!this.collapsedSections[id];
  }

  toggleSection(id: string, event?: Event) {
    if (event) { event.stopPropagation(); }
    this.collapsedSections[id] = !this.collapsedSections[id];
    try {
      localStorage.setItem(this.COLLAPSED_KEY, JSON.stringify(this.collapsedSections));
    } catch {
      //
    }
  }

  private loadCollapsedSections() {
    try {
      const s = localStorage.getItem(this.COLLAPSED_KEY);
      this.collapsedSections = s ? JSON.parse(s) : {};
    } catch { this.collapsedSections = {}; }
  }

  // ── AI Typewriter Effect ──
  private startTypewriter(text: string) {
    if (this.typewriterTimer) { clearInterval(this.typewriterTimer); }
    if (!text) { this.typewriterRaw = ''; this.typewriterDone = true; return; }
    this.typewriterRaw = '';
    this.typewriterDone = false;
    let i = 0;
    const speed = 8;
    this.typewriterTimer = setInterval(() => {
      i = Math.min(i + speed, text.length);
      this.typewriterRaw = text.slice(0, i);
      if (i >= text.length) {
        clearInterval(this.typewriterTimer);
        this.typewriterDone = true;
      }
    }, 10);
  }

  shortDate(dateStr: string): string {
    return dateStr ? dateStr.slice(5).replace('-', '/') : '';
  }

  getHeatmapColor(deviation: number, type: string): string {
    if (type === 'none' || !deviation) return '#f1f5f9';
    if (type === 'drop') {
      const i = Math.min(Math.abs(deviation) / 100, 1);
      if (i > 0.8) return '#7f1d1d';
      if (i > 0.6) return '#b91c1c';
      if (i > 0.4) return '#dc2626';
      if (i > 0.2) return '#f87171';
      return '#fecaca';
    }
    const i = Math.min(Math.abs(deviation) / 100, 1);
    if (i > 0.6) return '#065f46';
    if (i > 0.4) return '#10b981';
    if (i > 0.2) return '#6ee7b7';
    return '#a7f3d0';
  }

  getHeatmapTextColor(deviation: number, type: string): string {
    if (type === 'none' || !deviation) return '#9ca3af';
    return Math.min(Math.abs(deviation) / 100, 1) > 0.4 ? '#fff' : '#1f2937';
  }

  private initAnomalyMap() {
    const points = (this.result?.anomalyMapPoints || []).filter(p => p.lat && p.lng);
    if (!points.length) return;

    this.anomalyMap = this.createMap('anomaly-branch-map', { scrollWheelZoom: true });
    if (!this.anomalyMap) return;

    const urgencyColor = (u: string) => u === 'critical' ? '#ef4444' : u === 'warning' ? '#f59e0b' : '#eab308';
    points.forEach(p => {
      const color = urgencyColor(p.urgency);
      const label = p.urgency === 'critical' ? '🔴' : p.urgency === 'warning' ? '🟠' : '🟡';
      const radius = 7 + Math.min(p.anomalyCount * 2, 12);
      L.circleMarker([p.lat, p.lng] as L.LatLngExpression, {
        radius,
        fillColor: color,
        color: '#fff',
        weight: 2,
        fillOpacity: 0.88
      }).bindPopup(
        `<b>${p.branch}</b><br>` +
        `${label} <span style="color:${color};font-weight:600">${p.urgency.charAt(0).toUpperCase() + p.urgency.slice(1)}</span><br>` +
        `${p.anomalyCount} anomal${p.anomalyCount === 1 ? 'y' : 'ies'} detected`
      ).addTo(this.anomalyMap as L.Map);
    });

    setTimeout(() => {
      this.anomalyMap?.invalidateSize();
      this.anomalyMap?.fitBounds(
        L.latLngBounds(points.map(p => [p.lat, p.lng] as L.LatLngTuple)),
        { padding: [20, 20] }
      );
    }, 600);
  }

  private initOutletVisitMap() {
    const points: any[] = ((this.result as any)?.outletVisitMapPoints || []).filter((p: any) => p.lat && p.lng);
    if (!points.length) return;

    this.outletVisitMap = this.createMap('outlet-visit-map', { scrollWheelZoom: true });
    if (!this.outletVisitMap) return;

    points.forEach((p: any) => {
      const visits = p.visitCount || 0;
      // Red = visited only 1 day (very neglected), Amber = 2-3 days, Green = 4+ days
      const color = visits <= 1 ? '#ef4444' : visits <= 3 ? '#f59e0b' : '#10b981';
      const radius = Math.max(5, 10 - Math.min(visits, 5));
      L.circleMarker([p.lat, p.lng] as L.LatLngExpression, {
        radius,
        fillColor: color,
        color: '#fff',
        weight: 1.5,
        fillOpacity: 0.85
      }).bindPopup(
        `<b>${p.name}</b><br>` +
        `DS: ${p.dsName || 'N/A'}<br>` +
        `Visits: <b style="color:${color}">${visits}</b>`
      ).addTo(this.outletVisitMap as L.Map);
    });

    setTimeout(() => {
      this.outletVisitMap?.invalidateSize();
      this.outletVisitMap?.fitBounds(
        L.latLngBounds(points.map((p: any) => [p.lat, p.lng] as L.LatLngTuple)),
        { padding: [20, 20] }
      );
    }, 600);
  }

  private createMap(elId: string, opts: L.MapOptions = {}): L.Map | null {
    const el = document.getElementById(elId);
    if (!el) return null;

    // Force explicit dimensions before Leaflet init
    el.style.height = el.style.height || '220px';
    el.style.width = '100%';
    el.style.position = 'relative';

    const map = L.map(el, { zoomControl: true, attributionControl: false, ...opts });
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
      maxZoom: 19,
      subdomains: 'abcd'
    }).addTo(map);

    // Multiple invalidateSize calls to handle any render timing
    setTimeout(() => { map.invalidateSize(); }, 100);
    setTimeout(() => { map.invalidateSize(); }, 400);
    setTimeout(() => { map.invalidateSize(); }, 1000);

    return map;
  }

  private initDsMap() {
    if (!this.result?.dsMapLocations?.length && !this.result?.dsOutletLocations?.length) return;

    const attendancePts = (this.result.dsMapLocations || []).filter(p => p.lat && p.lng);
    const outletPts = (this.result.dsOutletLocations || []).filter(p => p.lat && p.lng);
    if (!attendancePts.length && !outletPts.length) return;

    const allLats = [...attendancePts.map(p => p.lat), ...outletPts.map(p => p.lat)];
    const allLngs = [...attendancePts.map(p => p.lng), ...outletPts.map(p => p.lng)];
    const center: L.LatLngExpression = [
      allLats.reduce((a, b) => a + b, 0) / allLats.length,
      allLngs.reduce((a, b) => a + b, 0) / allLngs.length
    ];

    this.dsMap = this.createMap('ds-scorecard-map', { scrollWheelZoom: false });
    if (!this.dsMap) return;
    this.dsMap.setView(center, 13);

    outletPts.forEach(p => {
      L.circleMarker([p.lat, p.lng], { radius: 5, fillColor: '#10b981', color: '#fff', weight: 1.5, fillOpacity: 0.85 })
        .bindPopup(`<b>${p.name}</b><br><small>${p.route}<br>${p.shopType} | ${p.type}</small>`)
        .addTo(this.dsMap as L.Map);
    });

    const routeCoords: L.LatLngExpression[] = [];
    attendancePts.forEach(p => {
      routeCoords.push([p.lat, p.lng]);
      L.circleMarker([p.lat, p.lng], { radius: 6, fillColor: '#3b82f6', color: '#fff', weight: 2, fillOpacity: 0.9 })
        .bindPopup(`<b>Check-in</b><br>${p.date} ${p.time?.split(' ')[1] || ''}<br><small>${p.route}</small>`)
        .addTo(this.dsMap as L.Map);
    });

    if (routeCoords.length > 1) {
      L.polyline(routeCoords, { color: '#3b82f6', weight: 2.5, opacity: 0.5, dashArray: '6, 8' }).addTo(this.dsMap as L.Map);
    }

    const allPts = [...attendancePts.map(p => [p.lat, p.lng] as L.LatLngTuple), ...outletPts.map(p => [p.lat, p.lng] as L.LatLngTuple)];
    if (allPts.length) {
      setTimeout(() => {
        this.dsMap?.invalidateSize();
        this.dsMap?.fitBounds(L.latLngBounds(allPts), { padding: [20, 20] });
      }, 600);
    }
  }

  private initHeatmap() {
    if (!this.result?.heatmapPoints?.length) return;
    const points = this.result.heatmapPoints.filter(p => p.lat && p.lng);
    if (!points.length) return;

    this.heatMap = this.createMap('geographic-heatmap', { scrollWheelZoom: true });
    if (!this.heatMap) return;
    this.heatMap.setView([22.5, 80], 5);

    const maxSales = Math.max(...points.map(p => p.sales));
    points.forEach(p => {
      const intensity = maxSales > 0 ? p.sales / maxSales : 0.5;
      const radius = 5 + intensity * 18;
      const color = intensity > 0.7 ? '#ef4444' : intensity > 0.4 ? '#f59e0b' : intensity > 0.15 ? '#3b82f6' : '#94a3b8';
      L.circleMarker([p.lat, p.lng], { radius, fillColor: color, color: '#fff', weight: 1.5, opacity: 0.9, fillOpacity: 0.7 })
        .bindPopup(`<b>${p.region}</b><br>Sales: ${p.sales.toLocaleString()}<br>DS: ${p.dsCount}<br><small>${p.district} | ${p.circle}</small>`)
        .addTo(this.heatMap as L.Map);
    });

    setTimeout(() => {
      this.heatMap?.invalidateSize();
      this.heatMap?.fitBounds(L.latLngBounds(points.map(p => [p.lat, p.lng] as L.LatLngTuple)), { padding: [15, 15] });
    }, 600);
  }

  private initComparisonHeatmap() {
    if (!this.result?.heatmapPoints?.length) return;
    const points = this.result.heatmapPoints.filter((p: any) => p.lat && p.lng);
    if (!points.length) return;

    this.comparisonMap = this.createMap('comparison-heatmap', { scrollWheelZoom: true });
    if (!this.comparisonMap) return;
    this.comparisonMap.setView([22.5, 80], 5);

    const maxSales = Math.max(...points.map((p: any) => p.sales));
    points.forEach((p: any) => {
      const intensity = maxSales > 0 ? p.sales / maxSales : 0.5;
      const radius = 5 + intensity * 18;
      const color = intensity > 0.7 ? '#ef4444' : intensity > 0.4 ? '#f59e0b' : intensity > 0.15 ? '#3b82f6' : '#94a3b8';
      L.circleMarker([p.lat, p.lng], { radius, fillColor: color, color: '#fff', weight: 1.5, opacity: 0.9, fillOpacity: 0.7 })
        .bindPopup(`<b>${p.region}</b><br>Sales: ${p.sales.toLocaleString()}<br>DS: ${p.dsCount}<br><small>${p.district} | ${p.circle}</small>`)
        .addTo(this.comparisonMap as L.Map);
    });

    setTimeout(() => {
      this.comparisonMap?.invalidateSize();
      this.comparisonMap?.fitBounds(L.latLngBounds(points.map((p: any) => [p.lat, p.lng] as L.LatLngTuple)), { padding: [15, 15] });
    }, 600);
  }

  private initHierarchyHeatmap() {
    if (!this.result?.hierarchyHeatmapPoints?.length) return;
    const points = this.result.hierarchyHeatmapPoints.filter((p: any) => p.lat && p.lng);
    if (!points.length) return;

    this.hierarchyHeatMap = this.createMap('hierarchy-scorecard-map', { scrollWheelZoom: true });
    if (!this.hierarchyHeatMap) return;
    this.hierarchyHeatMap.setView([22.5, 80], 5);

    const maxSales = Math.max(...points.map((p: any) => p.sales));
    points.forEach((p: any) => {
      const intensity = maxSales > 0 ? p.sales / maxSales : 0.5;
      const radius = 5 + intensity * 18;
      const color = intensity > 0.7 ? '#ef4444' : intensity > 0.4 ? '#f59e0b' : intensity > 0.15 ? '#3b82f6' : '#94a3b8';
      L.circleMarker([p.lat, p.lng], { radius, fillColor: color, color: '#fff', weight: 1.5, opacity: 0.9, fillOpacity: 0.7 })
        .bindPopup(`<b>${p.region}</b><br>Sales: ${p.sales.toLocaleString()}<br>DS: ${p.dsCount}<br><small>${p.district} | ${p.circle}</small>`)
        .addTo(this.hierarchyHeatMap as L.Map);
    });

    setTimeout(() => {
      this.hierarchyHeatMap?.invalidateSize();
      this.hierarchyHeatMap?.fitBounds(L.latLngBounds(points.map((p: any) => [p.lat, p.lng] as L.LatLngTuple)), { padding: [15, 15] });
    }, 600);
  }

  private initTodaySummaryMap() {
    const points = (this.result?.todayHeatmapPoints || []).filter((p: any) => p.lat && p.lng);
    if (!points.length) return;

    this.todaySummaryMap = this.createMap('today-summary-map', { scrollWheelZoom: true });
    if (!this.todaySummaryMap) return;
    this.todaySummaryMap.setView([22.5, 80], 5);

    const maxSales = Math.max(...points.map((p: any) => p.sales));
    points.forEach((p: any) => {
      const intensity = maxSales > 0 ? p.sales / maxSales : 0.5;
      const radius = 5 + intensity * 18;
      const color = intensity > 0.7 ? '#ef4444' : intensity > 0.4 ? '#f59e0b' : intensity > 0.15 ? '#3b82f6' : '#94a3b8';
      L.circleMarker([p.lat, p.lng], { radius, fillColor: color, color: '#fff', weight: 1.5, opacity: 0.9, fillOpacity: 0.7 })
        .bindPopup(`<b>${p.region}</b><br>Sales: ${p.sales?.toLocaleString?.() ?? p.sales}<br>DS: ${p.dsCount ?? ''}<br><small>${p.district ?? ''} | ${p.circle ?? ''}</small>`)
        .addTo(this.todaySummaryMap as L.Map);
    });

    setTimeout(() => {
      this.todaySummaryMap?.invalidateSize();
      this.todaySummaryMap?.fitBounds(L.latLngBounds(points.map((p: any) => [p.lat, p.lng] as L.LatLngTuple)), { padding: [15, 15] });
    }, 600);
  }

  // ── Chart Drill-down ──
  onChartDrillDown(event: any, context: string) {
    const name = typeof event === 'string' ? event : (event?.name || event?.label || '');
    if (!name) return;
    let query = '';
    switch (context) {
      case 'region': query = `Scorecard for ${name}`; break;
      case 'branch': query = `Scorecard for ${name}`; break;
      case 'branch-drill': query = `Scorecard for ${name}`; break;
      case 'district': query = `Scorecard for ${name}`; break;
      case 'circle': query = `Scorecard for ${name}`; break;
      case 'section': query = `Scorecard for ${name}`; break;
      case 'wd': query = `Scorecard for ${name}`; break;
      case 'category': query = `${name} product breakdown`; break;
      case 'product': query = `Compare ${name} this month vs last month`; break;
      case 'ds': query = `Tell me about ${name}`; break;
      case 'sub-entity': query = `Scorecard for ${name}`; break;
      default: query = `Tell me about ${name}`;
    }
    this.form.get('query')?.setValue(query);
    this.submit();
  }

  formatUnits(value: number): string {
    if (value === null || value === undefined) {
      return '';
    }
    return Number(value).toLocaleString(undefined, { maximumFractionDigits: 2 });
  }

  toggleAiSummary() {
    this.aiSummaryExpanded = !this.aiSummaryExpanded;
  }

  parseAiText(text: string): SafeHtml {
    if (!text) {
      return this.sanitizer.sanitize(1, '') || '';
    }

    const lines = text.split('\n');
    let html = '';
    let inList = false;

    for (const line of lines) {
      const processed = line
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/__(.*?)__/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>');

      // Section headings (#### 1. Title / ### Title / ## Title)
      const h4Match = processed.match(/^#{3,4}\s+(.*)$/);
      const h3Match = !h4Match && processed.match(/^##\s+(.*)$/);
      const h2Match = !h4Match && !h3Match && processed.match(/^#\s+(.*)$/);

      if (h4Match || h3Match || h2Match) {
        if (inList) {
          html += '</ul>'; inList = false;
        }
        let title = '';
        if (h4Match) title = h4Match[1].trim();
        else if (h3Match) title = h3Match[1].trim();
        else if (h2Match) title = h2Match[1].trim();
        html += `<div class="ai-section-heading">${title}</div>`;
        continue;
      }

      // Bullet items
      const bulletMatch = processed.match(/^\s*[-*]\s+(.*)$/);
      if (bulletMatch) {
        if (!inList) { html += '<ul class="ai-list">'; inList = true; }
        html += `<li>${bulletMatch[1]}</li>`;
        continue;
      }

      // Numbered items (1. Text)
      const numMatch = processed.match(/^\s*\d+\.\s+(.*)$/);
      if (numMatch) {
        if (!inList) { html += '<ul class="ai-list">'; inList = true; }
        html += `<li>${numMatch[1]}</li>`;
        continue;
      }

      // Empty lines
      if (!processed.trim()) {
        if (inList) { html += '</ul>'; inList = false; }
        continue;
      }

      // Regular paragraph text
      if (inList) { html += '</ul>'; inList = false; }
      html += `<p class="ai-para">${processed}</p>`;
    }
    if (inList) { html += '</ul>'; }

    return this.sanitizer.bypassSecurityTrustHtml(html);
  }
}
