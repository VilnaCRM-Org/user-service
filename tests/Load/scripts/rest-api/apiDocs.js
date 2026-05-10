import http from 'k6/http';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'apiDocs';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export default function apiDocs() {
  const response = http.get(`${utils.getBaseUrl()}/docs`, utils.getJsonHeader());

  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}
