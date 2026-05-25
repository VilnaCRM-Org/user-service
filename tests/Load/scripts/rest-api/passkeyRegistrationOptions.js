import http from 'k6/http';
import counter from 'k6/x/counter';

import InsertUsersUtils from '../../utils/insertUsersUtils.js';
import MailCatcherUtils from '../../utils/mailCatcherUtils.js';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils, { parseJson } from '../../utils/utils.js';

const scenarioName = 'passkeyRegistrationOptions';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);

const users = insertUsersUtils.loadInsertedUsers();

export const options = scenarioUtils.getOptions();

export default function passkeyRegistrationOptions() {
  const user = users[counter.up() % users.length];
  utils.checkUserIsDefined(user);

  const response = http.post(
    `${utils.getBaseUrl()}/passkeys/register/options`,
    JSON.stringify({}),
    utils.getJsonHeaderWithAuth(user.accessToken)
  );
  const body = parseJson(response);

  utils.checkResponse(
    response,
    'passkey registration options is status 200',
    res => res.status === 200
  );
  utils.checkResponse(
    response,
    'passkey registration options returns challenge',
    () => typeof body?.challenge_id === 'string' && body.challenge_id.length > 0
  );
  utils.checkResponse(
    response,
    'passkey registration options returns discoverable credential policy',
    () =>
      Array.isArray(body?.public_key?.excludeCredentials) &&
      body.public_key.authenticatorSelection?.userVerification === 'required' &&
      body.public_key.authenticatorSelection?.residentKey === 'required'
  );
}

export function teardown() {
  mailCatcherUtils.clearMessages();
}
