# Complexity Analysis Tools Reference

Complete guide to the complexity analysis tools available in this project.

## Overview

This project uses **PHPMetrics** - a professional static analysis tool - to calculate accurate complexity metrics. Custom scripts parse PHPMetrics output and provide ranked complexity reports.

## Available Commands

### make analyze-complexity

Analyze and rank classes by cyclomatic complexity.

```bash
# Analyze top 20 classes (default)
make analyze-complexity

# Analyze custom number of classes
make analyze-complexity N=10

# Analyze top 50 classes
make analyze-complexity N=50
```

**Output format**: Human-readable table with color-coded metrics

**Example output**:

```
╔════════════════════════════════════════════════════════════════════════════╗
║                TOP 10 MOST COMPLEX CLASSES (PHPMetrics)                     ║
╚════════════════════════════════════════════════════════════════════════════╝

#1 - App\Core\Customer\Application\Factory\CustomerUpdateFactory
──────────────────────────────────────────────────────────────────────────────
  🔢 Cyclomatic Complexity (CCN):    10
  🎯 Weighted Method Count (WMC):    20
  📊 Methods:                        11
  📏 Logical Lines of Code (LLOC):   45
  ⚡ Avg Complexity per Method:       3.33
  🔴 Max Method Complexity:          8
  💚 Maintainability Index:          77.13
```

### make analyze-complexity-json

Export complexity analysis as JSON for programmatic processing.

```bash
# Export top 20 classes
make analyze-complexity-json N=20 > complexity.json

# Save with date for tracking
make analyze-complexity-json N=20 > .metrics/complexity-$(date +%Y-%m-%d).json
```

**Output format**: JSON array

**Example**:

```json
[
  {
    "rank": 1,
    "class": "App\\Core\\Customer\\Application\\Factory\\CustomerUpdateFactory",
    "ccn": 10,
    "wmc": 20,
    "methods": 11,
    "lloc": 45,
    "avgComplexity": 3.33,
    "maxMethodComplexity": 8,
    "maintainabilityIndex": 77.13
  }
]
```

**Use cases**:

- CI/CD integration
- Trend analysis
- Automated reporting
- Programmatic filtering

### make analyze-complexity-csv

Export complexity analysis as CSV for spreadsheet analysis.

```bash
# Export to CSV
make analyze-complexity-csv N=50 > complexity-report.csv

# Open in spreadsheet software
libreoffice complexity-report.csv
```

**Output format**: CSV with headers

**Example**:

```csv
Rank,Class,CCN,WMC,Methods,LLOC,AvgComplexity,MaxComplexity,Maintainability
1,App\Core\Customer\Application\Factory\CustomerUpdateFactory,10,20,11,45,3.33,8,77.13
2,App\Shared\Application\OpenApi\Processor\ContentPropertyProcessor,6,8,4,25,2.00,4,89.10
```

**Use cases**:

- Visual analysis in Excel/LibreOffice
- Sorting and filtering
- Charts and graphs
- Management reports

## Metrics Explained

### Cyclomatic Complexity (CCN)

**What**: Total number of decision points in all class methods

**Includes**: if, else, for, foreach, while, case, catch, try, ternary operators, && and ||

**Thresholds**:

- **1-7**: ✅ Good
- **8-15**: ⚠️ Consider refactoring
- **16+**: 🔴 Critical - immediate action required

**Example**:

```php
// CCN = 3
public function example($value)
{
    if ($value > 0) {      // +1
        return true;
    } elseif ($value < 0) { // +1
        return false;
    }
    return null;            // base = 1
}
```

### Weighted Method Count (WMC)

**What**: Sum of complexity across all methods in the class

**Formula**: WMC = Σ(complexity of each method)

**Thresholds**:

- **< 20**: ✅ Good
- **20-50**: ⚠️ Moderate
- **> 50**: 🔴 High - split class

**Interpretation**:

- High WMC indicates overall class complexity
- Combined with method count, shows if complexity is concentrated or distributed

### Methods

**What**: Total number of methods (public + protected + private) in the class

**Thresholds**:

- **< 10**: ✅ Good
- **10-20**: ⚠️ Acceptable
- **> 20**: 🔴 Too many - consider splitting

**Interpretation**:

- High method count may indicate too many responsibilities
- Consider Single Responsibility Principle

### Logical Lines of Code (LLOC)

**What**: Count of executable code lines (excludes blank lines, comments, declarations)

**Thresholds**:

- **< 100**: ✅ Good
- **100-200**: ⚠️ Moderate
- **> 200**: 🔴 Large - consider splitting

**Interpretation**:

- Pure measure of actual code volume
- Different from physical lines (which include whitespace/comments)

### Average Complexity per Method

**Formula**: CCN ÷ Number of Methods

**Thresholds**:

- **< 5**: ✅ Target met
- **5-10**: ⚠️ Approaching limit
- **> 10**: 🔴 Exceeds standards

**Interpretation**:

- **Key metric** for PHPInsights compliance
- Target: < 5 for this project
- Shows if complexity is evenly distributed or concentrated

### Max Method Complexity

**What**: Highest cyclomatic complexity of any single method in the class

**Thresholds**:

- **1-5**: ✅ Excellent
- **6-10**: ⚠️ Watch this method
- **> 10**: 🔴 Refactor this method immediately

**Interpretation**:

- Identifies the "worst offender" method
- This method should be refactored first
- Even if average is low, a high max indicates a problem

### Maintainability Index

**What**: Holistic metric combining complexity, volume, and code structure

**Scale**: 0-100 (higher is better)

**Thresholds**:

- **> 85**: ✅ Highly maintainable
- **65-85**: ⚠️ Moderately maintainable
- **< 65**: 🔴 Difficult to maintain

**Formula**: Based on Halstead metrics, cyclomatic complexity, and lines of code

**Interpretation**:

- Overall health indicator
- < 20 indicates "legacy code" difficulty
- Combines multiple factors for holistic view

## How the Analysis Works

### Step 1: PHPMetrics Execution

```bash
vendor/bin/phpmetrics --report-json=/tmp/phpmetrics.json src/
```

PHPMetrics analyzes all PHP files and generates comprehensive metrics.

### Step 2: JSON Parsing

Custom PHP script (`scripts/analyze-complexity.php`) parses the JSON output:

```php
$data = json_decode(file_get_contents('/tmp/phpmetrics.json'), true);
$classes = [];

foreach ($data as $file => $metrics) {
    if (isset($metrics['complexity']['cyclomatic'])) {
        $classes[] = [
            'class' => $metrics['name'],
            'ccn' => $metrics['complexity']['cyclomatic'],
            'wmc' => $metrics['complexity']['wmc'],
            // ... other metrics
        ];
    }
}
```

### Step 3: Ranking and Sorting

Classes are sorted by cyclomatic complexity (descending):

```php
usort($classes, fn($a, $b) => $b['ccn'] <=> $a['ccn']);
```

### Step 4: Output Formatting

Results are formatted based on requested format (table, JSON, CSV).

## Integration with PHPInsights

### How They Work Together

**PHPMetrics** (via `make analyze-complexity`):

- Identifies WHICH classes are complex
- Provides ranking and prioritization
- Gives detailed metrics per class

**PHPInsights** (via `make phpinsights`):

- Enforces complexity thresholds
- Checks code quality, architecture, style
- Must pass for CI/CD

**Workflow**:

```bash
# 1. Find complex classes
make analyze-complexity N=10

# 2. Refactor identified classes
# ... apply refactoring patterns ...

# 3. Verify improvements
make phpinsights  # Must show 94%+ complexity
```

## Advanced Usage

### Track Complexity Over Time

```bash
# Daily tracking
DATE=$(date +%Y-%m-%d)
make analyze-complexity-json N=20 > .metrics/complexity-$DATE.json

# Compare with yesterday
YESTERDAY=$(date -d "1 day ago" +%Y-%m-%d)
diff .metrics/complexity-$YESTERDAY.json .metrics/complexity-$DATE.json
```

### Filter by Specific Metric

Using `jq` with JSON output:

```bash
# Classes with CCN > 15 (critical)
make analyze-complexity-json N=100 | jq '.[] | select(.ccn > 15)'

# Classes with max method complexity > 10
make analyze-complexity-json N=100 | jq '.[] | select(.maxMethodComplexity > 10)'

# Classes with low maintainability (< 65)
make analyze-complexity-json N=100 | jq '.[] | select(.maintainabilityIndex < 65)'
```

### Analyze Specific Directory

```bash
# Analyze only Customer bounded context
vendor/bin/phpmetrics --report-json=/tmp/customer.json src/Core/Customer/

# Parse with custom script
php scripts/analyze-complexity.php /tmp/customer.json 10
```

### CI/CD Integration

```bash
# In CI pipeline
make analyze-complexity-json N=20 > complexity-report.json

# Check if any class exceeds threshold
CRITICAL=$(jq '[.[] | select(.ccn > 15)] | length' complexity-report.json)

if [ "$CRITICAL" -gt 0 ]; then
    echo "❌ $CRITICAL classes exceed complexity threshold"
    exit 1
fi
```

### Export for Reporting

```bash
# Generate dated report
DATE=$(date +%Y-%m-%d)
make analyze-complexity-csv N=50 > reports/complexity-$DATE.csv

# Create trending report
{
    echo "Date,AvgComplexity,MaxComplexity,TotalClasses"
    for file in reports/complexity-*.csv; do
        DATE=$(basename "$file" | sed 's/complexity-//' | sed 's/.csv//')
        AVG=$(awk -F, 'NR>1 {sum+=$7; count++} END {print sum/count}' "$file")
        MAX=$(awk -F, 'NR>1 {if($3>max)max=$3} END {print max}' "$file")
        COUNT=$(wc -l < "$file")
        echo "$DATE,$AVG,$MAX,$COUNT"
    done
} > complexity-trends.csv
```

## Troubleshooting

### "Command not found: phpmetrics"

**Solution**:

```bash
composer install
# PHPMetrics installed via composer.json
```

### "Out of memory"

**Solution**:

```bash
# Increase PHP memory limit
php -d memory_limit=512M vendor/bin/phpmetrics ...
```

### "No classes found"

**Cause**: Invalid source directory or no PHP files

**Solution**:

```bash
# Verify source path
ls src/  # Should show PHP files

# Check PHPMetrics can find files
vendor/bin/phpmetrics --report-text src/
```

### "JSON parse error"

**Cause**: PHPMetrics JSON output corrupted

**Solution**:

```bash
# Regenerate clean JSON
rm /tmp/phpmetrics.json
make analyze-complexity
```

## Files and Scripts

### Script Locations

```
scripts/
├── analyze-complexity.php    # PHP parser for PHPMetrics JSON
└── analyze-complexity.sh     # Bash wrapper script
```

### Makefile Targets

Defined in `Makefile`:

```makefile
analyze-complexity:
	@N=$${N:-20}; \
	vendor/bin/phpmetrics --report-json=/tmp/phpmetrics.json src/ && \
	php scripts/analyze-complexity.php /tmp/phpmetrics.json $$N

analyze-complexity-json:
	@N=$${N:-20}; \
	vendor/bin/phpmetrics --report-json=/tmp/phpmetrics.json src/ && \
	php scripts/analyze-complexity.php /tmp/phpmetrics.json $$N json

analyze-complexity-csv:
	@N=$${N:-20}; \
	vendor/bin/phpmetrics --report-json=/tmp/phpmetrics.json src/ && \
	php scripts/analyze-complexity.php /tmp/phpmetrics.json $$N csv
```

## Best Practices

### Daily Workflow

```bash
# Morning: Check complexity status
make analyze-complexity N=10

# During dev: Check changed files only
git diff --name-only | grep '\.php$' | xargs vendor/bin/phpmetrics ...

# Before commit: Verify overall status
make phpinsights
```

### Sprint Planning

```bash
# Start of sprint: Baseline
make analyze-complexity-json N=20 > .metrics/sprint-start.json

# End of sprint: Compare
make analyze-complexity-json N=20 > .metrics/sprint-end.json
diff .metrics/sprint-start.json .metrics/sprint-end.json
```

### Continuous Monitoring

```bash
# Weekly cron job
0 9 * * 1 cd /project && make analyze-complexity-json N=50 > .metrics/weekly-$(date +%Y-%m-%d).json
```

---

**See Also**:

- [complexity-metrics.md](complexity-metrics.md) - Detailed metric explanations
- [quick-start.md](quick-start.md) - Fast-track refactoring workflow
- [monitoring.md](monitoring.md) - Long-term tracking strategies
