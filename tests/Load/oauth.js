import http from 'k6/http';
import {ScenarioUtils} from "./scenarioUtils.js";
import { check } from 'k6';
import {Utils} from "./utils.js";
import {Env} from "./env.js";

const scenarioName = 'OAUTH';
const scenarioUtils = new ScenarioUtils(scenarioName);
const utils = new Utils();
const env = new Env();

export const options = scenarioUtils.getOptions();

export default function () {
    getAccessToken();
}

function getAccessToken() {
    const grantType = 'client_credentials';
    const clientId = env.get('LOAD_TEST_OAUTH_CLIENT_ID');
    const clientSecret = env.get('LOAD_TEST_OAUTH_CLIENT_SECRET');

    const payload = JSON.stringify({
        grant_type: grantType,
        client_id: clientId,
        client_secret: clientSecret,
    });

    const res = http.post(
        utils.getBaseUrl() + '/oauth/token',
        payload,
        utils.getJsonHeader()
    );

    check(res, {
        'is status 200': (r) => r.status === 200,
    });
}
