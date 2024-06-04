const { loadEnvConfig } = require('@next/env');

const projectDir = process.cwd();
loadEnvConfig(projectDir);

class ScenarioBuilder {
  constructor() {
    this.url = () => process.env.NEXT_PUBLIC_WEBSITE_URL;
  }

  createScenario(scenarioOptions) {
    return { url: this.url, ...scenarioOptions };
  }
}

module.exports = ScenarioBuilder;
