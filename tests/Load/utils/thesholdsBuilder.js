export class ThresholdsBuilder {
    constructor() {
        this.thresholds = {};
    }

    addSmokeThreshold(smokeConfig) {
        this.thresholds['http_req_duration{test_type:smoke}'] = ['p(99)<' + smokeConfig.threshold];
        this.thresholds['checks{scenario:smoke}'] = ['rate>0.99'];
        return this;
    }

    addAverageThreshold(averageConfig) {
        this.thresholds['http_req_duration{test_type:average}'] = ['p(99)<' + averageConfig.threshold];
        this.thresholds['checks{scenario:average}'] = ['rate>0.99'];
        return this;
    }

    addStressThreshold(stressConfig) {
        this.thresholds['http_req_duration{test_type:stress}'] = ['p(99)<' + stressConfig.threshold];
        this.thresholds['checks{scenario:stress}'] = ['rate>0.99'];
        return this;
    }

    addSpikeThreshold(spikeConfig) {
        this.thresholds['http_req_duration{test_type:spike}'] = ['p(99)<' + spikeConfig.threshold];
        this.thresholds['checks{scenario:spike}'] = ['rate>0.70'];
        return this;
    }

    build() {
        return this.thresholds;
    }
}
