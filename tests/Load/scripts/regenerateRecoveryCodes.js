import counter from 'k6/x/counter';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import InsertUsersUtils from '../utils/insertUsersUtils.js';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';
import AuthFlowUtils from '../utils/authFlowUtils.js';
import TotpUtils from '../utils/totpUtils.js';

const scenarioName = 'regenerateRecoveryCodes';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);
const authFlowUtils = new AuthFlowUtils(utils);
const totpUtils = new TotpUtils();

const users = insertUsersUtils.loadInsertedUsers();

export function setup() {
  return {
    users,
  };
}

export const options = scenarioUtils.getOptions();

function confirmWithCandidateCodes(accessToken, secret) {
  const candidateCodes = totpUtils.generateCandidateCodes(secret);
  let lastAttempt = authFlowUtils.confirmTwoFactor(accessToken, candidateCodes[0]);

  if (lastAttempt.response.status === 200) {
    return lastAttempt;
  }

  for (let index = 1; index < candidateCodes.length; index += 1) {
    lastAttempt = authFlowUtils.confirmTwoFactor(accessToken, candidateCodes[index]);
    if (lastAttempt.response.status === 200) {
      return lastAttempt;
    }
  }

  return lastAttempt;
}

export default function regenerateRecoveryCodes(data) {
  const user = data.users[counter.up() % data.users.length];
  utils.checkUserIsDefined(user);

  const signInResult = authFlowUtils.signIn(user.email, user.password);
  utils.checkResponse(
    signInResult.response,
    'sign-in for recovery code regeneration is status 200',
    res => res.status === 200
  );

  const accessToken = signInResult.body?.access_token;
  if (typeof accessToken !== 'string' || accessToken.length === 0) {
    utils.checkResponse(
      signInResult.response,
      'sign-in returns access token for recovery code regeneration',
      () => false
    );
    return;
  }

  const setupResult = authFlowUtils.setupTwoFactor(accessToken);
  utils.checkResponse(
    setupResult.response,
    'setup 2fa before recovery code regeneration is status 200',
    res => res.status === 200
  );

  const secret = setupResult.body?.secret;
  if (typeof secret !== 'string' || secret.length === 0) {
    utils.checkResponse(
      setupResult.response,
      'setup 2fa returns secret for recovery code regeneration',
      () => false
    );
    return;
  }

  const confirmResult = confirmWithCandidateCodes(accessToken, secret);
  utils.checkResponse(
    confirmResult.response,
    'confirm 2fa before recovery code regeneration is status 200',
    res => res.status === 200
  );

  const regenerateResult = authFlowUtils.regenerateRecoveryCodes(accessToken);
  utils.checkResponse(
    regenerateResult.response,
    'recovery code regeneration is status 200',
    res => res.status === 200
  );
  utils.checkResponse(
    regenerateResult.response,
    'recovery code regeneration returns recovery codes array',
    () => Array.isArray(regenerateResult.body?.recovery_codes)
  );
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
