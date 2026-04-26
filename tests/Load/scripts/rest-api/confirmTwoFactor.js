import exec from 'k6/execution';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';
import InsertUsersUtils from '../../utils/insertUsersUtils.js';
import MailCatcherUtils from '../../utils/mailCatcherUtils.js';
import AuthFlowUtils from '../../utils/authFlowUtils.js';
import TotpUtils from '../../utils/totpUtils.js';

const scenarioName = 'confirmTwoFactor';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);
const authFlowUtils = new AuthFlowUtils(utils);
const totpUtils = new TotpUtils();

const users = insertUsersUtils.loadInsertedUsers();


export const options = scenarioUtils.getOptions();

function getUser(data) {
  const messageNumber = insertUsersUtils.getMessageNumberForProfile(
    exec.scenario.name,
    exec.scenario.iterationInTest
  );

  return users[(messageNumber - 1) % users.length];
}

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

export default function confirmTwoFactor(data) {
  const user = getUser(data);
  utils.checkUserIsDefined(user);

  let accessToken = user.accessToken;

  if (typeof accessToken !== 'string' || accessToken.length === 0) {
    const signInResult = authFlowUtils.signIn(user.email, user.password);
    utils.checkResponse(
      signInResult.response,
      'sign-in for confirm 2fa is status 200',
      res => res.status === 200
    );

    accessToken = signInResult.body?.access_token;
    if (typeof accessToken !== 'string' || accessToken.length === 0) {
      utils.checkResponse(
        signInResult.response,
        'sign-in returns access token for confirm 2fa',
        () => false
      );
      return;
    }
  }

  const setupResult = authFlowUtils.setupTwoFactor(accessToken);
  utils.checkResponse(
    setupResult.response,
    'setup 2fa before confirm is status 200',
    res => res.status === 200
  );

  const secret = setupResult.body?.secret;
  if (typeof secret !== 'string' || secret.length === 0) {
    utils.checkResponse(setupResult.response, 'setup 2fa returns secret for confirm', () => false);
    return;
  }

  const confirmResult = confirmWithCandidateCodes(accessToken, secret);
  utils.checkResponse(
    confirmResult.response,
    'confirm 2fa is status 200',
    res => res.status === 200
  );
  const recoveryCodes = confirmResult.body?.recovery_codes;

  utils.checkResponse(confirmResult.response, 'confirm 2fa returns recovery codes array', () =>
    Array.isArray(recoveryCodes)
  );

  if (!Array.isArray(recoveryCodes) || recoveryCodes.length === 0) {
    return;
  }

  const disableResult = authFlowUtils.disableTwoFactor(accessToken, recoveryCodes[0]);
  utils.checkResponse(
    disableResult.response,
    'disable 2fa after confirm is status 204',
    res => res.status === 204
  );
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
