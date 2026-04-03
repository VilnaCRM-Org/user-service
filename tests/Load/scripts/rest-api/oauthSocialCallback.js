import http from 'k6/http';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'oauthSocialCallback';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export default function oauthSocialCallback() {
  const response = http.get(
    `${utils.getBaseUrl()}/auth/social/github/callback?code=test_code&state=test_state`,
    {
      headers: {
        Cookie: 'oauth_flow_binding=test_token',
      },
    }
  );

  utils.checkResponse(response, 'is status 400 or 422', res =>
    [400, 422].includes(res.status)
  );
}
