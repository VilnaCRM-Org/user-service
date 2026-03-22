# PHPInsights Monitoring and Tracking

Track code quality improvements over time, monitor trends, and ensure sustained excellence.

## Overview

Monitoring PHPInsights metrics helps you:

- **Track improvements** over time
- **Prevent degradation** through early detection
- **Identify problem areas** that need attention
- **Measure refactoring ROI** (return on investment)
- **Guide sprint planning** with data-driven priorities

---

## Quick Start: Baseline Establishment

Before tracking improvements, establish your current baseline.

### Create Initial Baseline

```bash
# Generate baseline report
make phpinsights --format=json > .metrics/baseline-$(date +%Y-%m-%d).json

# Create metrics directory if needed
mkdir -p .metrics

# Save current scores
vendor/bin/phpinsights --format=json | jq '{
    date: now | strftime("%Y-%m-%d"),
    code: .summary.code,
    complexity: .summary.complexity,
    architecture: .summary.architecture,
    style: .summary.style
}' > .metrics/baseline.json
```

### View Baseline

```bash
cat .metrics/baseline.json
```

Expected output:

```json
{
  "date": "2025-11-08",
  "code": 100.0,
  "complexity": 94.2,
  "architecture": 100.0,
  "style": 100.0
}
```

---

## Daily Monitoring

### Pre-Commit Check

Run PHPInsights before every commit:

```bash
# Manual check
make phpinsights

# Or use git hook (already configured via captainhook)
# .git/hooks/pre-commit runs automatically
```

**Captainhook Configuration** (captainhook.json):

```json
{
  "pre-commit": {
    "enabled": true,
    "actions": [
      {
        "action": "make phpinsights",
        "conditions": []
      }
    ]
  }
}
```

### Changed Files Only

For faster feedback during development:

```bash
# Get PHP files changed since last commit
git diff --name-only --diff-filter=ACMR HEAD | grep '\.php$' > /tmp/changed.txt

# Analyze only changed files
if [ -s /tmp/changed.txt ]; then
    cat /tmp/changed.txt | xargs vendor/bin/phpinsights analyse
fi
```

### Identify Hotspots Daily

Check the most complex classes before starting work:

```bash
# Quick check: top 10 most complex classes
make analyze-complexity N=10
```

**Track daily changes**:

```bash
# Save today's complexity snapshot
make analyze-complexity-json N=20 > .metrics/complexity-$(date +%Y-%m-%d).json

# Compare with yesterday (if exists)
YESTERDAY=$(date -d "1 day ago" +%Y-%m-%d 2>/dev/null || date -v-1d +%Y-%m-%d)
if [ -f ".metrics/complexity-$YESTERDAY.json" ]; then
    echo "📊 Complexity changes since yesterday:"
    diff .metrics/complexity-$YESTERDAY.json .metrics/complexity-$(date +%Y-%m-%d).json
fi
```

**Focus your refactoring**:

- Classes with CCN > 15: Priority refactoring targets
- Classes that appeared in top 10 multiple days: Chronic issues
- New entries in top 10: Recent complexity increases

---

## Sprint/Weekly Monitoring

### Weekly Report Generation

Create a weekly snapshot of code quality:

```bash
#!/bin/bash
# scripts/weekly-quality-report.sh

DATE=$(date +%Y-%m-%d)
REPORT_DIR=".metrics/weekly"
mkdir -p "$REPORT_DIR"

echo "📊 Generating Weekly Quality Report for $DATE"
echo "================================================"

# Generate full report
vendor/bin/phpinsights --format=json > "$REPORT_DIR/report-$DATE.json"

# Extract key metrics
jq '{
    date: "'$DATE'",
    scores: {
        code: .summary.code,
        complexity: .summary.complexity,
        architecture: .summary.architecture,
        style: .summary.style
    },
    total_issues: (.issues | length),
    critical_issues: ([.issues[] | select(.severity == "error")] | length),
    files_analyzed: (.files | length)
}' "$REPORT_DIR/report-$DATE.json" > "$REPORT_DIR/summary-$DATE.json"

# Display summary
cat "$REPORT_DIR/summary-$DATE.json"

echo ""
echo "🔥 Top 10 Most Complex Classes:"
echo "================================"
make analyze-complexity N=10

# Save complexity analysis for trending
make analyze-complexity-json N=20 > "$REPORT_DIR/complexity-$DATE.json"

# Compare with last week (if exists)
LAST_WEEK=$(date -d "7 days ago" +%Y-%m-%d 2>/dev/null || date -v-7d +%Y-%m-%d)
if [ -f "$REPORT_DIR/summary-$LAST_WEEK.json" ]; then
    echo ""
    echo "📈 Comparison with Last Week ($LAST_WEEK):"
    echo "=========================================="

    THIS_COMPLEXITY=$(jq -r '.scores.complexity' "$REPORT_DIR/summary-$DATE.json")
    LAST_COMPLEXITY=$(jq -r '.scores.complexity' "$REPORT_DIR/summary-$LAST_WEEK.json")

    echo "Complexity: $LAST_COMPLEXITY → $THIS_COMPLEXITY"

    # Calculate change
    CHANGE=$(echo "$THIS_COMPLEXITY - $LAST_COMPLEXITY" | bc)
    if (( $(echo "$CHANGE > 0" | bc -l) )); then
        echo "✅ Improved by $CHANGE points"
    elif (( $(echo "$CHANGE < 0" | bc -l) )); then
        echo "❌ Decreased by ${CHANGE#-} points"
    else
        echo "➡️ No change"
    fi
fi
```

**Make it executable**:

```bash
chmod +x scripts/weekly-quality-report.sh
```

**Run weekly**:

```bash
./scripts/weekly-quality-report.sh
```

---

## Trend Analysis

### Track Trends Over Time

```bash
# scripts/trend-analysis.sh

REPORT_DIR=".metrics/weekly"

echo "📈 Code Quality Trends"
echo "====================="

# Extract complexity scores from all reports
echo "Date,Complexity,Code,Architecture,Style" > /tmp/trends.csv

for file in "$REPORT_DIR"/summary-*.json; do
    DATE=$(jq -r '.date' "$file")
    COMPLEXITY=$(jq -r '.scores.complexity' "$file")
    CODE=$(jq -r '.scores.code' "$file")
    ARCH=$(jq -r '.scores.architecture' "$file")
    STYLE=$(jq -r '.scores.style' "$file")

    echo "$DATE,$COMPLEXITY,$CODE,$ARCH,$STYLE" >> /tmp/trends.csv
done

# Display trends (sorted by date)
sort /tmp/trends.csv

# Calculate average complexity
AVG_COMPLEXITY=$(awk -F, 'NR>1 {sum+=$2; count++} END {print sum/count}' /tmp/trends.csv)
echo ""
echo "Average Complexity: $AVG_COMPLEXITY%"

# Find best and worst weeks
echo ""
echo "Best Week:"
sort -t, -k2 -rn /tmp/trends.csv | head -2 | tail -1

echo ""
echo "Worst Week:"
sort -t, -k2 -n /tmp/trends.csv | head -2 | tail -1
```

### Visualize Trends

For visual representation, export to CSV and use spreadsheet software:

```bash
# Export all metrics to CSV
./scripts/trend-analysis.sh > metrics-export.csv

# Open in LibreOffice, Excel, or Google Sheets
# Create line chart with Date on X-axis, scores on Y-axis
```

---

## CI/CD Integration

### GitHub Actions Workflow

Create `.github/workflows/code-quality.yml`:

```yaml
name: Code Quality Monitoring

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  phpinsights:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, xml, dom, simplexml

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run PHPInsights
        run: make phpinsights

      - name: Generate JSON Report
        if: always()
        run: vendor/bin/phpinsights --format=json > phpinsights-report.json

      - name: Upload Report
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: phpinsights-report
          path: phpinsights-report.json

      - name: Comment PR with Results
        if: github.event_name == 'pull_request'
        uses: actions/github-script@v6
        with:
          script: |
            const fs = require('fs');
            const report = JSON.parse(fs.readFileSync('phpinsights-report.json', 'utf8'));

            const body = `## 📊 PHPInsights Report

            | Metric | Score | Target | Status |
            |--------|-------|--------|--------|
            | Code Quality | ${report.summary.code}% | 100% | ${report.summary.code >= 100 ? '✅' : '❌'} |
            | Complexity | ${report.summary.complexity}% | 94% | ${report.summary.complexity >= 94 ? '✅' : '❌'} |
            | Architecture | ${report.summary.architecture}% | 100% | ${report.summary.architecture >= 100 ? '✅' : '❌'} |
            | Style | ${report.summary.style}% | 100% | ${report.summary.style >= 100 ? '✅' : '❌'} |

            Total Issues: ${report.issues.length}
            `;

            github.rest.issues.createComment({
              issue_number: context.issue.number,
              owner: context.repo.owner,
              repo: context.repo.repo,
              body: body
            });
```

### Prevent Quality Degradation

Add quality gate to CI:

```yaml
- name: Quality Gate
  run: |
    COMPLEXITY=$(jq -r '.summary.complexity' phpinsights-report.json)

    if (( $(echo "$COMPLEXITY < 94" | bc -l) )); then
      echo "❌ Complexity score $COMPLEXITY% is below threshold (94%)"
      exit 1
    fi

    echo "✅ Quality gate passed"
```

---

## Metrics Dashboard

### Create Simple Dashboard Script

```bash
#!/bin/bash
# scripts/quality-dashboard.sh

clear
echo "╔════════════════════════════════════════════════════════════╗"
echo "║           Code Quality Dashboard - $(date +%Y-%m-%d)            ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Run PHPInsights and capture output
vendor/bin/phpinsights --format=json > /tmp/current-report.json

# Extract scores
CODE=$(jq -r '.summary.code' /tmp/current-report.json)
COMPLEXITY=$(jq -r '.summary.complexity' /tmp/current-report.json)
ARCH=$(jq -r '.summary.architecture' /tmp/current-report.json)
STYLE=$(jq -r '.summary.style' /tmp/current-report.json)

# Display scores with visual indicators
echo "📊 Current Scores"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
printf "Code Quality:    %6.2f%% " "$CODE"
[ $(echo "$CODE >= 100" | bc) -eq 1 ] && echo "✅" || echo "❌"

printf "Complexity:      %6.2f%% " "$COMPLEXITY"
[ $(echo "$COMPLEXITY >= 94" | bc) -eq 1 ] && echo "✅" || echo "❌"

printf "Architecture:    %6.2f%% " "$ARCH"
[ $(echo "$ARCH >= 100" | bc) -eq 1 ] && echo "✅" || echo "❌"

printf "Style:           %6.2f%% " "$STYLE"
[ $(echo "$STYLE >= 100" | bc) -eq 1 ] && echo "✅" || echo "❌"

echo ""

# Show top issues
TOTAL_ISSUES=$(jq '.issues | length' /tmp/current-report.json)
CRITICAL=$(jq '[.issues[] | select(.severity == "error")] | length' /tmp/current-report.json)
WARNINGS=$(jq '[.issues[] | select(.severity == "warning")] | length' /tmp/current-report.json)

echo "🔍 Issues Summary"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Total Issues:    $TOTAL_ISSUES"
echo "Critical:        $CRITICAL"
echo "Warnings:        $WARNINGS"
echo ""

# Show most complex files
echo "🔥 Top 5 Most Complex Files"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
jq -r '.files | to_entries | sort_by(.value.complexity) | reverse | .[0:5] | .[] | "\(.key): \(.value.complexity)"' /tmp/current-report.json | while read line; do
    echo "  $line"
done
echo ""

# Compare with baseline
if [ -f .metrics/baseline.json ]; then
    BASELINE_COMPLEXITY=$(jq -r '.complexity' .metrics/baseline.json)
    CHANGE=$(echo "$COMPLEXITY - $BASELINE_COMPLEXITY" | bc)

    echo "📈 Progress Since Baseline"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "Baseline Complexity: $BASELINE_COMPLEXITY%"
    echo "Current Complexity:  $COMPLEXITY%"
    printf "Change:              "

    if (( $(echo "$CHANGE > 0" | bc -l) )); then
        echo "+$CHANGE% ✅"
    elif (( $(echo "$CHANGE < 0" | bc -l) )); then
        echo "$CHANGE% ❌"
    else
        echo "No change ➡️"
    fi
fi

echo ""
echo "╚════════════════════════════════════════════════════════════╝"
```

**Run dashboard**:

```bash
chmod +x scripts/quality-dashboard.sh
./scripts/quality-dashboard.sh
```

---

## Hotspot Analysis

### Identify Problem Areas

**Use the built-in command** (easier and more accurate):

```bash
# Find top 20 complexity hotspots
make analyze-complexity N=20

# Or export for further analysis
make analyze-complexity-json N=50 > hotspots.json
```

**Analyze the results**:

- Focus on classes with CCN > 15 first
- Check Max Method Complexity > 10
- Review Maintainability Index < 65

**Export for reporting**:

```bash
# CSV format for spreadsheets
make analyze-complexity-csv N=20 > complexity-hotspots.csv

# Open in spreadsheet software to sort/filter/visualize
```

### Find Recently Degraded Files

```bash
# Compare current report with last week
# Find files that got worse

CURRENT=".metrics/weekly/report-$(date +%Y-%m-%d).json"
PREVIOUS=".metrics/weekly/report-$(date -d '7 days ago' +%Y-%m-%d).json"

if [ -f "$PREVIOUS" ] && [ -f "$CURRENT" ]; then
    echo "📉 Files with Increased Complexity (Last 7 Days)"
    echo "==============================================="

    comm -12 \
        <(jq -r '.files | keys[]' "$PREVIOUS" | sort) \
        <(jq -r '.files | keys[]' "$CURRENT" | sort) | \
    while read file; do
        PREV_COMPLEXITY=$(jq -r ".files[\"$file\"].complexity" "$PREVIOUS")
        CURR_COMPLEXITY=$(jq -r ".files[\"$file\"].complexity" "$CURRENT")

        if (( $(echo "$CURR_COMPLEXITY > $PREV_COMPLEXITY" | bc -l) )); then
            INCREASE=$(echo "$CURR_COMPLEXITY - $PREV_COMPLEXITY" | bc)
            echo "$file: $PREV_COMPLEXITY% → $CURR_COMPLEXITY% (+$INCREASE%)"
        fi
    done
fi
```

---

## Team Metrics

### Track Refactoring Velocity

```bash
# How many files improved this sprint?

echo "📊 Refactoring Velocity (This Sprint)"
echo "====================================="

# Files that improved
IMPROVED=$(comm -12 \
    <(jq -r '.files | keys[]' "$PREVIOUS" | sort) \
    <(jq -r '.files | keys[]' "$CURRENT" | sort) | \
while read file; do
    PREV=$(jq -r ".files[\"$file\"].complexity" "$PREVIOUS")
    CURR=$(jq -r ".files[\"$file\"].complexity" "$CURRENT")

    if (( $(echo "$CURR < $PREV" | bc -l) )); then
        echo "$file"
    fi
done | wc -l)

echo "Files improved: $IMPROVED"

# Files that degraded
DEGRADED=$(comm -12 \
    <(jq -r '.files | keys[]' "$PREVIOUS" | sort) \
    <(jq -r '.files | keys[]' "$CURRENT" | sort) | \
while read file; do
    PREV=$(jq -r ".files[\"$file\"].complexity" "$PREVIOUS")
    CURR=$(jq -r ".files[\"$file\"].complexity" "$CURRENT")

    if (( $(echo "$CURR > $PREV" | bc -l) )); then
        echo "$file"
    fi
done | wc -l)

echo "Files degraded: $DEGRADED"

# Net progress
NET=$(echo "$IMPROVED - $DEGRADED" | bc)
echo "Net progress: $NET files"
```

---

## Alerts and Notifications

### Slack Notification on Degradation

```bash
#!/bin/bash
# scripts/notify-quality-change.sh

WEBHOOK_URL="https://hooks.slack.com/services/YOUR/WEBHOOK/URL"

CURRENT_COMPLEXITY=$(vendor/bin/phpinsights --format=json | jq -r '.summary.complexity')
THRESHOLD=94

if (( $(echo "$CURRENT_COMPLEXITY < $THRESHOLD" | bc -l) )); then
    MESSAGE="⚠️ Code complexity dropped to $CURRENT_COMPLEXITY% (threshold: $THRESHOLD%)"

    curl -X POST "$WEBHOOK_URL" \
        -H 'Content-Type: application/json' \
        -d "{
            \"text\": \"$MESSAGE\",
            \"username\": \"PHPInsights Bot\",
            \"icon_emoji\": \":warning:\"
        }"
fi
```

### Email Notification

```bash
# If complexity falls below threshold, send email

CURRENT_COMPLEXITY=$(vendor/bin/phpinsights --format=json | jq -r '.summary.complexity')

if (( $(echo "$CURRENT_COMPLEXITY < 94" | bc -l) )); then
    mail -s "⚠️ Code Quality Alert" team@example.com <<EOF
Code complexity has fallen to $CURRENT_COMPLEXITY%.

Please review recent changes and refactor as needed.

View full report: http://ci-server/phpinsights-report.html
EOF
fi
```

---

## Best Practices

### Do's

✅ **Establish baseline** before starting improvements
✅ **Track weekly** to catch trends early
✅ **Compare with previous** sprint/week/month
✅ **Celebrate improvements** to motivate team
✅ **Automate monitoring** in CI/CD pipeline
✅ **Set up alerts** for degradation
✅ **Review hotspots** in sprint planning

### Don'ts

❌ **Don't track daily** (too granular, noisy)
❌ **Don't focus on absolute scores** (focus on trends)
❌ **Don't lower thresholds** to "fix" problems
❌ **Don't blame individuals** for complexity increases
❌ **Don't ignore small degradations** (they compound)
❌ **Don't skip baselines** (can't measure progress without them)

---

## Reporting Templates

### Sprint Report Template

```markdown
# Code Quality Sprint Report - Sprint 42

## Summary

- **Overall Complexity**: 94.8% (▲ 0.6% from last sprint)
- **Code Quality**: 100% (stable)
- **Architecture**: 100% (stable)
- **Style**: 100% (stable)

## Achievements

- ✅ Refactored `CustomerCommandHandler` (complexity: 15 → 6)
- ✅ Extracted `EmailValidator` value object
- ✅ Split `OrderService` into focused services

## Concerns

- ⚠️ `ReportGenerator` complexity increased (8 → 11)
- ⚠️ New feature added 3 methods with complexity > 10

## Next Sprint Goals

- [ ] Refactor `ReportGenerator` to < 10 complexity
- [ ] Extract complex validation logic to value objects
- [ ] Maintain or improve overall complexity score
```

---

## Long-Term Tracking

### Quarterly Review

Every quarter, review:

1. **Trend direction**: Is complexity trending down?
2. **Refactoring ROI**: Did refactoring reduce bugs/improve velocity?
3. **Hotspot patterns**: Are same areas repeatedly problematic?
4. **Threshold adjustment**: Should we raise the bar? (never lower)
5. **Process improvements**: What helped? What didn't?

### Annual Goals

Set ambitious annual targets:

```
Current:  Complexity 94%, Code 100%, Architecture 100%, Style 100%
Q1 Goal:  Complexity 95%, maintain others
Q2 Goal:  Complexity 96%, maintain others
Q3 Goal:  Complexity 97%, maintain others
Q4 Goal:  Complexity 98%, maintain others
```

---

## Tools Integration

### PHPStorm Integration

Configure real-time feedback:

1. **Settings → PHP → Quality Tools → PHP Insights**
2. Set path to `vendor/bin/phpinsights`
3. Enable inspection: **Editor → Inspections → PHP → Quality Tools → PHPInsights**
4. Complexity warnings appear inline while coding

### Git Hooks

Already configured via Captainhook (`captainhook.json`):

```json
{
  "pre-commit": {
    "enabled": true,
    "actions": [
      {
        "action": "make phpinsights"
      }
    ]
  }
}
```

Reinstall hooks:

```bash
vendor/bin/captainhook install
```

---

## Summary

**Daily**: Run before commits
**Weekly**: Generate reports, review trends
**Sprint**: Analyze hotspots, plan refactoring
**Quarterly**: Review strategy, adjust goals
**Annually**: Set ambitious targets

**Remember**: The goal is continuous improvement, not perfection.

---

**See Also**:

- [troubleshooting.md](troubleshooting.md) - Fix issues that arise
- [refactoring-strategies.md](../refactoring-strategies.md) - Reduce complexity
- [complexity-metrics.md](complexity-metrics.md) - Understand metrics
