import http from 'k6/http';
import counter from 'k6/x/counter';

import InsertUsersUtils from '../../utils/insertUsersUtils.js';
import MailCatcherUtils from '../../utils/mailCatcherUtils.js';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils, { parseJson } from '../../utils/utils.js';

const scenarioName = 'passkeySigninOptions';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);

const users = insertUsersUtils.loadInsertedUsers();

export const options = scenarioUtils.getOptions();

export default function passkeySigninOptions() {
  const user = users[counter.up() % users.length];
  utils.checkUserIsDefined(user);

  const payload = JSON.stringify({
    email: user.email,
    rememberMe: false,
  });

  const response = http.post(
    `${utils.getBaseUrl()}/passkeys/signin/options`,
    payload,
    utils.getJsonHeader()
  );
  const body = parseJson(response);

  utils.checkResponse(response, 'passkey signin options is status 200', res => res.status === 200);
  utils.checkResponse(
    response,
    'passkey signin options returns challenge',
    () => typeof body?.challenge_id === 'string' && body.challenge_id.length > 0
  );
  utils.checkResponse(
    response,
    'passkey signin options avoids credential enumeration',
    () =>
      Array.isArray(body?.public_key?.allowCredentials) &&
      body.public_key.allowCredentials.length === 0 &&
      body.public_key.userVerification === 'required'
  );
}

export function teardown() {
  mailCatcherUtils.clearMessages();
}
