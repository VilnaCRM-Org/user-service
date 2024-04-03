export class ScenariosBuilder {
    constructor() {
        this.scenarios = {};
    }

    addSmokeScenario(smokeConfig) {
        this.scenarios.smoke = {
            executor: 'constant-arrival-rate',
            rate: smokeConfig.rps,
            timeUnit: '1s',
            duration: smokeConfig.duration + 's',
            preAllocatedVUs: smokeConfig.vus,
            tags: {test_type: 'smoke'},
        }

        return this;
    }

    addAverageScenario(averageConfig, startTime) {
        this.scenarios.average = {
            executor: 'ramping-arrival-rate',
            startRate: 0,
            timeUnit: '1s',
            preAllocatedVUs: averageConfig.vus,
            stages: [
                {
                    target: averageConfig.rps,
                    duration: averageConfig.duration.rise + 's'
                },
                {
                    target: averageConfig.rps,
                    duration: averageConfig.duration.plateau + 's'
                },
                {target: 0, duration: averageConfig.duration.fall + 's'},
            ],
            startTime: startTime + 's',
            tags: {test_type: 'average'},
        }

        return this;
    }

    addStressScenario(stressConfig, startTime) {
        this.scenarios.stress = {
            executor: 'ramping-arrival-rate',
            startRate: 0,
            timeUnit: '1s',
            preAllocatedVUs: stressConfig.vus,
            stages: [
                {
                    target: stressConfig.rps,
                    duration: stressConfig.duration.rise + 's'
                },
                {
                    target: stressConfig.rps,
                    duration: stressConfig.duration.plateau + 's'
                },
                {target: 0, duration: stressConfig.duration.fall + 's'},
            ],
            startTime: startTime + 's',
            tags: {test_type: 'stress'},
        }

        return this;
    }

    addSpikeScenario(spikeConfig, startTime) {
        this.scenarios.spike = {
            executor: 'ramping-arrival-rate',
            startRate: 0,
            timeUnit: '1s',
            preAllocatedVUs: spikeConfig.vus,
            stages: [
                {
                    target: spikeConfig.rps,
                    duration: spikeConfig.duration.rise + 's'
                },
                {target: 0, duration: spikeConfig.duration.fall + 's'},
            ],
            startTime: startTime + 's',
            tags: {test_type: 'spike'},
        }

        return this;
    }

    build() {
        return this.scenarios;
    }
}
