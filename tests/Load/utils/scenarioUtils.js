export class ScenarioUtils {
    constructor(utils, scenarioName) {
        this.config = utils.getConfig();
        this.smokeConfig = this.config.endpoints[scenarioName].smoke;
        this.averageConfig = this.config.endpoints[scenarioName].average;
        this.stressConfig = this.config.endpoints[scenarioName].stress;
        this.spikeConfig = this.config.endpoints[scenarioName].spike;
    }

    getOptions() {
        return {
            insecureSkipTLSVerify: true,
            scenarios: this.getScenarios(),
            thresholds: this.getThresholds()
        }
    }

    getScenarios() {
        const delay = this.config.delayBetweenScenarios;
        const averageTestStartTime = this.smokeConfig.duration + delay;
        const stressTestStartTime = averageTestStartTime
            + this.averageConfig.duration.rise
            + this.averageConfig.duration.plateau
            + this.averageConfig.duration.fall + delay;
        const spikeTestStartTime = stressTestStartTime
            + this.stressConfig.duration.rise
            + this.stressConfig.duration.plateau
            + this.stressConfig.duration.fall + delay;
        return {
            smoke: this.getSmokeScenario(
                this.smokeConfig.rps,
                this.smokeConfig.vus,
                this.smokeConfig.duration,
            ),
            average: this.getAverageScenario(
                this.averageConfig.rps,
                this.averageConfig.vus,
                this.averageConfig.duration.rise,
                this.averageConfig.duration.plateau,
                this.averageConfig.duration.fall,
                averageTestStartTime,
            ),
            stress: this.getStressScenario(
                this.stressConfig.rps,
                this.stressConfig.vus,
                this.stressConfig.duration.rise,
                this.stressConfig.duration.plateau,
                this.stressConfig.duration.fall,
                stressTestStartTime,
            ),
            spike: this.getSpikeScenario(
                this.spikeConfig.rps,
                this.spikeConfig.vus,
                this.spikeConfig.duration.rise,
                this.spikeConfig.duration.fall,
                spikeTestStartTime,
            )
        }
    }

    getThresholds() {
        return {
            'http_req_duration{test_type:smoke}': ['p(99)<' + this.smokeConfig.threshold],
            'http_req_duration{test_type:average}': ['p(99)<' + this.averageConfig.threshold],
            'http_req_duration{test_type:stress}': ['p(99)<' + this.stressConfig.threshold],
            'http_req_duration{test_type:spike}': ['p(99)<' + this.spikeConfig.threshold],
            'checks{scenario:smoke}': ['rate>0.99'],
            'checks{scenario:average}': ['rate>0.99'],
            'checks{scenario:stress}': ['rate>0.99'],
            'checks{scenario:spike}': ['rate>0.70'],
        }
    }

    getSmokeScenario(
        ratePerSecond,
        vus,
        duration
    ) {
        return {
            executor: 'constant-arrival-rate',
            rate: ratePerSecond,
            timeUnit: '1s',
            duration: duration + 's',
            preAllocatedVUs: vus,
            tags: {test_type: 'smoke'},
        }
    }

    getAverageScenario(
        targetRatePerSecond,
        vus,
        riseDuration,
        plateauDuration,
        fallDuration,
        startTime
    ) {
        return {
            executor: 'ramping-arrival-rate',
            startRate: 0,
            timeUnit: '1s',
            preAllocatedVUs: vus,
            stages: [
                {
                    target: targetRatePerSecond,
                    duration: riseDuration + 's'
                },
                {
                    target: targetRatePerSecond,
                    duration: plateauDuration + 's'
                },
                {target: 0, duration: fallDuration + 's'},
            ],
            startTime: startTime + 's',
            tags: {test_type: 'average'},
        }
    }

    getStressScenario(
        targetRatePerSecond,
        vus,
        riseDuration,
        plateauDuration,
        fallDuration,
        startTime
    ) {
        return {
            executor: 'ramping-arrival-rate',
            startRate: 0,
            timeUnit: '1s',
            preAllocatedVUs: vus,
            stages: [
                {
                    target: targetRatePerSecond,
                    duration: riseDuration + 's'
                },
                {
                    target: targetRatePerSecond,
                    duration: plateauDuration + 's'
                },
                {target: 0, duration: fallDuration + 's'},
            ],
            startTime: startTime + 's',
            tags: {test_type: 'stress'},
        }
    }

    getSpikeScenario(
        targetRatePerSecond,
        vus,
        riseDuration,
        fallDuration,
        startTime
    ) {
        return {
            executor: 'ramping-arrival-rate',
            startRate: 0,
            timeUnit: '1s',
            preAllocatedVUs: vus,
            stages: [
                {
                    target: targetRatePerSecond,
                    duration: riseDuration + 's'
                },
                {target: 0, duration: fallDuration + 's'},
            ],
            startTime: startTime + 's',
            tags: {test_type: 'spike'},
        }
    }
}
