import http from 'k6/http';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'oauthAuthorize';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export default function oauthAuthorize() {
  const response = http.get(
    `${utils.getBaseUrl()}/oauth/authorize?response_type=code&client_id=memory-suite&redirect_uri=https%3A%2F%2Fexample.com%2Fcallback&scope=read&state=memory-state`,
    utils.getJsonHeader()
  );

  utils.checkResponse(response, 'is status 401', res => res.status === 401);
}
