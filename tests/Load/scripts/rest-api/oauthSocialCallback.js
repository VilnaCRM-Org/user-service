import http from 'k6/http';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'oauthSocialCallback';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

function extractState(locationHeader) {
  const location = new URL(locationHeader);

  return location.searchParams.get('state');
}

function extractCookie(setCookieHeader) {
  const headerValue = Array.isArray(setCookieHeader) ? setCookieHeader[0] : setCookieHeader;
  const cookieMatch = `${headerValue}`.match(/oauth_flow_binding=([^;]+)/);

  return cookieMatch ? cookieMatch[1] : null;
}

export default function oauthSocialCallback() {
  const initiateResponse = http.get(`${utils.getBaseUrl()}/auth/social/github`, {
    redirects: 0,
  });
  utils.checkResponse(initiateResponse, 'initiate is status 302', res => res.status === 302);

  const state = extractState(initiateResponse.headers.Location);
  const flowCookie = extractCookie(initiateResponse.headers['Set-Cookie']);

  const callbackResponse = http.get(
    `${utils.getBaseUrl()}/auth/social/github/callback?code=load-user&state=${state}`,
    {
      headers: {
        Cookie: `oauth_flow_binding=${flowCookie}`,
      },
    }
  );

  utils.checkResponse(callbackResponse, 'callback is status 200', res => res.status === 200);
}
