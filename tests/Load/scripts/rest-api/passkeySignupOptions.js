import http from 'k6/http';

import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils, { parseJson } from '../../utils/utils.js';

const scenarioName = 'passkeySignupOptions';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export default function passkeySignupOptions() {
  const generatedUser = utils.generateUser();
  const generatedProfile = utils.generateUserProfile();
  const payload = JSON.stringify({
    email: generatedUser.email,
    initials: generatedProfile.initials,
    displayName: generatedProfile.displayName,
  });

  const response = http.post(
    `${utils.getBaseUrl()}/passkeys/signup/options`,
    payload,
    utils.getJsonHeader()
  );
  const body = parseJson(response);

  utils.checkResponse(response, 'passkey signup options is status 200', res => res.status === 200);
  utils.checkResponse(
    response,
    'passkey signup options returns challenge',
    () => typeof body?.challenge_id === 'string' && body.challenge_id.length > 0
  );
  utils.checkResponse(
    response,
    'passkey signup options returns browser public key JSON',
    () =>
      typeof body?.public_key?.challenge === 'string' &&
      body.public_key.authenticatorSelection?.userVerification === 'required' &&
      body.public_key.authenticatorSelection?.residentKey === 'required'
  );
}
