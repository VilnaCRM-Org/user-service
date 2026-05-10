import http from 'k6/http';
import counter from 'k6/x/counter';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';
import MailCatcherUtils from '../../utils/mailCatcherUtils.js';

const scenarioName = 'oauth';

const utils = new Utils();
const config = utils.getConfig();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);
const oauthClientPoolSize = 20;

function resolvePooledCredential(baseValue, poolIndex) {
  return poolIndex === 0 ? baseValue : `${baseValue}-${poolIndex}`;
}

export const options = scenarioUtils.getOptions();

export default function getAccessToken() {
  const grantType = 'client_credentials';
  const poolIndex = counter.up() % oauthClientPoolSize;
  const clientId = resolvePooledCredential(config.endpoints[scenarioName].clientID, poolIndex);
  const clientSecret = resolvePooledCredential(
    config.endpoints[scenarioName].clientSecret,
    poolIndex
  );

  const payload = JSON.stringify({
    grant_type: grantType,
    client_id: clientId,
    client_secret: clientSecret,
  });

  const response = http.post(`${utils.getBaseUrl()}/oauth/token`, payload, utils.getJsonHeader());

  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
