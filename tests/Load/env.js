import * as dotenv from "k6/x/dotenv";

export class Env {
    constructor(scenarioName) {
        this.env = dotenv.parse(open(".env.test"));

        this.loadTestPrefix = 'LOAD_TEST_';
    }

    getScenarioConfig(scenarioName) {
        const delay = Number(this.get('LOAD_TEST_DELAY_BETWEEN_SCENARIOS'));

        const smokeDuration = Number(this.get(this.loadTestPrefix + scenarioName + '_SMOKE_DURATION'));
        const averageRiseDuration = Number(this.get(this.loadTestPrefix + scenarioName + '_AVERAGE_DURATION_RISE'));
        const averagePlateauDuration = Number(this.get(this.loadTestPrefix + scenarioName + '_AVERAGE_DURATION_PLATEAU'));
        const averageFallDuration = Number(this.get(this.loadTestPrefix + scenarioName + '_AVERAGE_DURATION_FALL'));
        const stressRiseDuration = Number(this.get(this.loadTestPrefix + scenarioName + '_STRESS_DURATION_RISE'));
        const stressPlateauDuration = Number(this.get(this.loadTestPrefix + scenarioName + '_STRESS_DURATION_PLATEAU'));
        const stressFallDuration = Number(this.get(this.loadTestPrefix + scenarioName + '_STRESS_DURATION_FALL'));

        const averageTestStartTime = smokeDuration + delay;
        const stressTestStartTime = averageTestStartTime
            + averageRiseDuration
            + averagePlateauDuration
            + averageFallDuration + delay;
        const spikeTestStartTime = stressTestStartTime
            + stressRiseDuration
            + stressPlateauDuration
            + stressFallDuration + delay;

        return {
            smokeRps: Number(this.get(this.loadTestPrefix + scenarioName + '_SMOKE_RPS')),
            smokeVus: Number(this.get(this.loadTestPrefix + scenarioName + '_SMOKE_VUS')),
            smokeDuration: smokeDuration,
            averageRps: Number(this.get(this.loadTestPrefix + scenarioName + '_AVERAGE_RPS')),
            averageVus: Number(this.get(this.loadTestPrefix + scenarioName + '_AVERAGE_VUS')),
            averageRiseDuration: averageRiseDuration,
            averagePlateauDuration: averagePlateauDuration,
            averageFallDuration: averageFallDuration,
            stressRps: Number(this.get(this.loadTestPrefix + scenarioName + '_STRESS_RPS')),
            stressVus: Number(this.get(this.loadTestPrefix + scenarioName + '_STRESS_VUS')),
            stressRiseDuration: stressRiseDuration,
            stressPlateauDuration: stressPlateauDuration,
            stressFallDuration: stressFallDuration,
            spikeRps: Number(this.get(this.loadTestPrefix + scenarioName + '_SPIKE_RPS')),
            spikeVus: Number(this.get(this.loadTestPrefix + scenarioName + '_SPIKE_VUS')),
            spikeRiseDuration: Number(this.get(this.loadTestPrefix + scenarioName + '_SPIKE_DURATION_RISE')),
            spikeFallDuration: Number(this.get(this.loadTestPrefix + scenarioName + '_SPIKE_DURATION_FALL')),
            averageTestStartTime: averageTestStartTime,
            stressTestStartTime: stressTestStartTime,
            spikeTestStartTime: spikeTestStartTime,
            smokeThreshold: this.get(this.loadTestPrefix + scenarioName + '_SMOKE_THRESHOLD'),
            averageThreshold: this.get(this.loadTestPrefix + scenarioName + '_AVERAGE_THRESHOLD'),
            stressThreshold: this.get(this.loadTestPrefix + scenarioName + '_STRESS_THRESHOLD'),
            spikeThreshold: this.get(this.loadTestPrefix + scenarioName + '_SPIKE_THRESHOLD'),
        }
    }

    get(varName) {
        return this.env[varName];
    }
}