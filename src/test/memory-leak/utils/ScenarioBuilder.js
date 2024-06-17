const { loadEnvConfig } = require('@next/env');

const projectDir = process.cwd();
loadEnvConfig(projectDir);

class ScenarioBuilder {
  constructor() {
    this.url = () => process.env.NEXT_PUBLIC_WEBSITE_URL;
    this.beforeInitialPageLoad = async page => {
      await page.setExtraHTTPHeaders({
        [`aws-cf-cd-${process.env.NEXT_PUBLIC_CONTINUOUS_DEPLOYMENT_HEADER_NAME}`]:
          process.env.NEXT_PUBLIC_CONTINUOUS_DEPLOYMENT_HEADER_VALUE,
      });
    };
  }

  createScenario(scenarioOptions) {
    return { url: this.url, beforeInitialPageLoad: this.beforeInitialPageLoad, ...scenarioOptions };
  }
}

module.exports = ScenarioBuilder;
