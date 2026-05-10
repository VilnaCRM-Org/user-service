import http from 'k6/http';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'apiErrors400';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export default function apiErrors400() {
  const response = http.get(`${utils.getBaseUrl()}/errors/400`, utils.getJsonHeader());

  utils.checkResponse(response, 'is status 401', res => res.status === 401);
}
