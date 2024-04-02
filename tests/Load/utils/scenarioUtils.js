import {ScenarioBuilder} from './scenarioBuilder.js'

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
        const scenarios = {};

        const delay = this.config.delayBetweenScenarios;
        let averageTestStartTime = 0;
        let stressTestStartTime = 0;
        let spikeTestStartTime = 0;

        if (`${__ENV.run_smoke}` !== 'false') {
            scenarios.smoke = new ScenarioBuilder()
                .withExecutor('constant-arrival-rate')
                .withPreAllocatedVUs(this.smokeConfig.vus)
                .withDuration(this.smokeConfig.duration + 's')
                .withRate(this.smokeConfig.rps)
                .withName('smoke')
                .build();
            averageTestStartTime = this.smokeConfig.duration + delay;
        }
        if (`${__ENV.run_average}` !== 'false') {
            scenarios.average = new ScenarioBuilder()
                .withExecutor('ramping-arrival-rate')
                .withPreAllocatedVUs(this.averageConfig.vus)
                .withStages(
                    [
                        {
                            target: this.averageConfig.rps,
                            duration: this.averageConfig.duration.rise + 's'
                        },
                        {
                            target: this.averageConfig.rps,
                            duration: this.averageConfig.duration.plateau + 's'
                        },
                        {
                            target: 0,
                            duration: this.averageConfig.duration.fall + 's'
                        },
                    ]
                )
                .withName('average')
                .withStartTime(averageTestStartTime)
                .withStartRate(0)
                .build();
            stressTestStartTime = averageTestStartTime
                + this.averageConfig.duration.rise
                + this.averageConfig.duration.plateau
                + this.averageConfig.duration.fall + delay;
        }
        if (`${__ENV.run_stress}` !== 'false') {
            scenarios.stress = new ScenarioBuilder()
                .withExecutor('ramping-arrival-rate')
                .withPreAllocatedVUs(this.stressConfig.vus)
                .withStages(
                    [
                        {
                            target: this.stressConfig.rps,
                            duration: this.stressConfig.duration.rise + 's'
                        },
                        {
                            target: this.stressConfig.rps,
                            duration: this.stressConfig.duration.plateau + 's'
                        },
                        {
                            target: 0,
                            duration: this.stressConfig.duration.fall + 's'
                        },
                    ]
                )
                .withName('stress')
                .withStartTime(averageTestStartTime)
                .withStartRate(0)
                .build();
            spikeTestStartTime = stressTestStartTime
                + this.stressConfig.duration.rise
                + this.stressConfig.duration.plateau
                + this.stressConfig.duration.fall + delay
        }
        if (`${__ENV.run_spike}` !== 'false') {
            scenarios.spike = new ScenarioBuilder()
                .withExecutor('ramping-arrival-rate')
                .withPreAllocatedVUs(this.spikeConfig.vus)
                .withStages(
                    [
                        {
                            target: this.spikeConfig.rps,
                            duration: this.spikeConfig.duration.rise + 's'
                        },
                        {
                            target: 0,
                            duration: this.spikeConfig.duration.fall + 's'
                        },
                    ]
                )
                .withName('spike')
                .withStartTime(spikeTestStartTime)
                .withStartRate(0)
                .build();
        }

        return scenarios;
    }

    getThresholds() {
        const thresholds = {};

        if (`${__ENV.run_smoke}` !== 'false') {
            thresholds['http_req_duration{test_type:smoke}'] = ['p(99)<' + this.smokeConfig.threshold];
            thresholds['checks{scenario:smoke}'] = ['rate>0.99'];
        }
        if (`${__ENV.run_average}` !== 'false') {
            thresholds['http_req_duration{test_type:average}'] = ['p(99)<' + this.averageConfig.threshold];
            thresholds['checks{scenario:average}'] = ['rate>0.99'];
        }
        if (`${__ENV.run_stress}` !== 'false') {
            thresholds['http_req_duration{test_type:stress}'] = ['p(99)<' + this.stressConfig.threshold];
            thresholds['checks{scenario:stress}'] = ['rate>0.99'];
        }
        if (`${__ENV.run_spike}` !== 'false') {
            thresholds['http_req_duration{test_type:spike}'] = ['p(99)<' + this.spikeConfig.threshold];
            thresholds['checks{scenario:spike}'] = ['rate>0.70'];
        }
    }
}
