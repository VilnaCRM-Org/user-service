export class ScenarioBuilder {
    constructor() {
        this.scenario = {
            timeUnit: '1s',
            tags: {test_type: ''},
        };
    }

    withExecutor(executor) {
        this.scenario.executor = executor;
        return this;
    }

    withStartRate(startRate) {
        this.scenario.startRate = startRate;
        return this;
    }

    withDuration(duration) {
        this.scenario.duration = duration;
        return this;
    }

    withRate(rate) {
        this.scenario.rate = rate;
        return this;
    }

    withPreAllocatedVUs(preAllocatedVUs) {
        this.scenario.preAllocatedVUs = preAllocatedVUs;
        return this;
    }

    withStages(stages) {
        this.scenario.stages = stages;
        return this;
    }

    withStartTime(startTime) {
        this.scenario.startTime = startTime;
        return this;
    }

    withName(name) {
        this.scenario.tags.test_type = name;
        return this;
    }

    build() {
        return this.scenario;
    }
}
