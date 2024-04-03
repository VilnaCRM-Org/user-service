import {ScenariosBuilder} from './scenariosBuilder.js'
import {ThresholdsBuilder} from "./thesholdsBuilder.js";

export class ScenarioUtils {
    constructor(utils, scenarioName) {
        this.utils = utils;
        this.config = utils.getConfig();
        this.smokeConfig = this.config.endpoints[scenarioName].smoke;
        this.averageConfig = this.config.endpoints[scenarioName].average;
        this.stressConfig = this.config.endpoints[scenarioName].stress;
        this.spikeConfig = this.config.endpoints[scenarioName].spike;
        this.delay = this.config.delayBetweenScenarios;
        this.averageTestStartTime = 0;
        this.stressTestStartTime = 0;
        this.spikeTestStartTime = 0;
    }

    getOptions() {
        return {
            insecureSkipTLSVerify: true,
            scenarios: this.getScenarios(),
            thresholds: this.getThresholds()
        }
    }

    getScenarios() {
        const scenariosBuilder = new ScenariosBuilder();

        if (this.utils.getCLIVariable('run_smoke') !== 'false') {
            this.addSmokeScenario(scenariosBuilder);
        }
        if (this.utils.getCLIVariable('run_average') !== 'false') {
            this.addAverageScenario(scenariosBuilder);
        }
        if (this.utils.getCLIVariable('run_stress') !== 'false') {
            this.addStressScenario(scenariosBuilder);
        }
        if (this.utils.getCLIVariable('run_spike') !== 'false') {
            this.addSpikeScenario(scenariosBuilder);
        }

        return scenariosBuilder.build();
    }

    addSmokeScenario(scenariosBuilder){
        scenariosBuilder.addSmokeScenario(this.smokeConfig);
        this.averageTestStartTime = this.smokeConfig.duration + this.delay;
    }

    addAverageScenario(scenariosBuilder){
        scenariosBuilder.addAverageScenario(
            this.averageConfig,
            this.averageTestStartTime
        );
        this.stressTestStartTime = this.averageTestStartTime
            + this.averageConfig.duration.rise
            + this.averageConfig.duration.plateau
            + this.averageConfig.duration.fall + this.delay;
    }

    addStressScenario(scenariosBuilder){
        scenariosBuilder.addStressScenario(
            this.stressConfig,
            this.stressTestStartTime
        );
        this.spikeTestStartTime = this.stressTestStartTime
            + this.stressConfig.duration.rise
            + this.stressConfig.duration.plateau
            + this.stressConfig.duration.fall + this.delay;
    }

    addSpikeScenario(scenariosBuilder){
        scenariosBuilder.addSpikeScenario(
            this.spikeConfig,
            this.spikeTestStartTime
        );
    }

    getThresholds() {
        const thresholdsBuilder = new ThresholdsBuilder();

        if (this.utils.getCLIVariable('run_smoke') !== 'false') {
            thresholdsBuilder.addSmokeThreshold(this.smokeConfig);
        }
        if (this.utils.getCLIVariable('run_average') !== 'false') {
            thresholdsBuilder.addAverageThreshold(this.averageConfig);
        }
        if (this.utils.getCLIVariable('run_stress') !== 'false') {
            thresholdsBuilder.addStressThreshold(this.stressConfig);
        }
        if (this.utils.getCLIVariable('run_spike') !== 'false') {
            thresholdsBuilder.addSpikeThreshold(this.spikeConfig);
        }

        return thresholdsBuilder.build();
    }
}
