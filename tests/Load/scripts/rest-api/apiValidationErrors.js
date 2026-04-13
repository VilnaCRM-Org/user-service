import http from 'k6/http';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'apiValidationErrors';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export default function apiValidationErrors() {
  const response = http.get(
    `${utils.getBaseUrl()}/validation_errors/validation`,
    utils.getJsonHeader()
  );

  utils.checkResponse(response, 'is status 401', res => res.status === 401);
}
