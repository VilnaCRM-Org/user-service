import {Env} from "./env.js";

export class ScenarioUtils {
    constructor(scenarioName) {
        this.env = new Env();
        this.scenarioName = scenarioName;
        this.scenarioConfig = this.env.getScenarioConfig(scenarioName);
    }

    getOptions() {
        return {
            insecureSkipTLSVerify: true,
            scenarios: this.getScenarios(
                this.scenarioName
            ),
            thresholds: this.getThresholds(
                this.scenarioName
            )
        }
    }

    getScenarios() {
        return {
            smoke: this.getSmokeScenario(
                this.scenarioConfig.smokeRps,
                this.scenarioConfig.smokeVus,
                this.scenarioConfig.smokeDuration,
            ),
            average: this.getAverageScenario(
                this.scenarioConfig.averageRps,
                this.scenarioConfig.averageVus,
                this.scenarioConfig.averageRiseDuration,
                this.scenarioConfig.averagePlateauDuration,
                this.scenarioConfig.averageFallDuration,
                this.scenarioConfig.averageTestStartTime,
            ),
            stress: this.getStressScenario(
                this.scenarioConfig.stressRps,
                this.scenarioConfig.stressVus,
                this.scenarioConfig.stressRiseDuration,
                this.scenarioConfig.stressPlateauDuration,
                this.scenarioConfig.stressFallDuration,
                this.scenarioConfig.stressTestStartTime,
            ),
            spike: this.getSpikeScenario(
                this.scenarioConfig.spikeRps,
                this.scenarioConfig.spikeVus,
                this.scenarioConfig.spikeRiseDuration,
                this.scenarioConfig.spikeFallDuration,
                this.scenarioConfig.spikeTestStartTime,
            )
        }
    }

    getThresholds() {
        return {
            'http_req_duration{test_type:smoke}': ['p(99)<' + this.scenarioConfig.smokeThreshold],
            'http_req_duration{test_type:average}': ['p(99)<' + this.scenarioConfig.averageThreshold],
            'http_req_duration{test_type:stress}': ['p(99)<' + this.scenarioConfig.stressThreshold],
            'http_req_duration{test_type:spike}': ['p(99)<' + this.scenarioConfig.spikeThreshold],
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
