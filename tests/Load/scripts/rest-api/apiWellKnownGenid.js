import http from 'k6/http';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'apiWellKnownGenid';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export default function apiWellKnownGenid() {
  const response = http.get(
    `${utils.getBaseUrl()}/.well-known/genid/memory-surface`,
    utils.getJsonHeader()
  );

  utils.checkResponse(response, 'is status 404', res => res.status === 404);
}
