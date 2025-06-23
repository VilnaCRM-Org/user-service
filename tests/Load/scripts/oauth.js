import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';

const scenarioName = 'oauth';

const utils = new Utils();
const config = utils.getConfig();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);

export const options = scenarioUtils.getOptions();

export default function getAccessToken() {
  const grantType = 'client_credentials';
  const clientId = config.endpoints[scenarioName].clientID;
  const { clientSecret } = config.endpoints[scenarioName];

  const payload = JSON.stringify({
    grant_type: grantType,
    client_id: clientId,
    client_secret: clientSecret,
  });

  const response = http.post(`${utils.getBaseUrl()}/oauth/token`, payload, utils.getJsonHeader());

  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}

export function teardown() {
  mailCatcherUtils.clearMessages();
}
