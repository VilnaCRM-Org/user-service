import http from 'k6/http';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'oauthSocialInitiate';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export default function oauthSocialInitiate() {
  const response = http.get(`${utils.getBaseUrl()}/auth/social/github`, {
    redirects: 0,
  });

  utils.checkResponse(response, 'is status 302', res => res.status === 302);
}
