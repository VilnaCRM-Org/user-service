import { b64encode } from 'k6/encoding';
import http from 'k6/http';
import counter from 'k6/x/counter';

import MailCatcherUtils from '../../utils/mailCatcherUtils.js';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'oauth';

const utils = new Utils();
const config = utils.getConfig();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);
const oauthClientPoolSize = 20;

function resolvePooledCredential(baseValue, poolIndex) {
  return poolIndex === 0 ? baseValue : `${baseValue}-${poolIndex}`;
}

function getJsonHeaderWithBasicAuth(clientId, clientSecret) {
  return {
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Basic ${b64encode(`${clientId}:${clientSecret}`)}`,
    },
  };
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
  });

  const response = http.post(
    `${utils.getBaseUrl()}/oauth/token`,
    payload,
    getJsonHeaderWithBasicAuth(clientId, clientSecret)
  );

  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}

export function teardown() {
  mailCatcherUtils.clearMessages();
}
