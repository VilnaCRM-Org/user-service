const { loadEnvConfig } = require('@next/env');

const projectDir = process.cwd();
loadEnvConfig(projectDir);

class ScenarioBuilder {
  constructor() {
    this.url = () => process.env.MEMLAB_WEBSITE_URL;
  }

  createScenario(scenarioOptions) {
    const scenario = { url: this.url, ...scenarioOptions };

    return scenario;
  }
}

module.exports = ScenarioBuilder;
