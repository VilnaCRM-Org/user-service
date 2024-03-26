import http from 'k6/http';
import {utils} from "./utils.js";
import { check } from 'k6';

export const options = {
    insecureSkipTLSVerify: true,
    scenarios: utils.getScenarios(
        utils.getFromEnv('LOAD_TEST_OAUTH_SMOKE_RPS'),
        utils.getFromEnv('LOAD_TEST_OAUTH_SMOKE_VUS'),
        utils.getFromEnv('LOAD_TEST_OAUTH_SMOKE_DURATION'),
        utils.getFromEnv('LOAD_TEST_OAUTH_AVERAGE_RPS'),
        utils.getFromEnv('LOAD_TEST_OAUTH_AVERAGE_VUS'),
        utils.getFromEnv('LOAD_TEST_OAUTH_AVERAGE_DURATION_RISE'),
        utils.getFromEnv('LOAD_TEST_OAUTH_STRESS_DURATION_PLATEAU'),
        utils.getFromEnv('LOAD_TEST_OAUTH_STRESS_DURATION_FALL'),
        utils.getFromEnv('LOAD_TEST_OAUTH_STRESS_RPS'),
        utils.getFromEnv('LOAD_TEST_OAUTH_STRESS_VUS'),
        utils.getFromEnv('LOAD_TEST_OAUTH_STRESS_DURATION_RISE'),
        utils.getFromEnv('LOAD_TEST_OAUTH_STRESS_DURATION_PLATEAU'),
        utils.getFromEnv('LOAD_TEST_OAUTH_STRESS_DURATION_FALL'),
        utils.getFromEnv('LOAD_TEST_OAUTH_SPIKE_RPS'),
        utils.getFromEnv('LOAD_TEST_OAUTH_SPIKE_VUS'),
        utils.getFromEnv('LOAD_TEST_OAUTH_SPIKE_DURATION_RISE'),
        utils.getFromEnv('LOAD_TEST_OAUTH_SPIKE_DURATION_FALL'),
    ),
    thresholds: utils.getThresholds(
        utils.getFromEnv('LOAD_TEST_OAUTH_SMOKE_THRESHOLD'),
        utils.getFromEnv('LOAD_TEST_OAUTH_AVERAGE_THRESHOLD'),
        utils.getFromEnv('LOAD_TEST_OAUTH_STRESS_THRESHOLD'),
        utils.getFromEnv('LOAD_TEST_OAUTH_SPIKE_THRESHOLD'),
    )
};

export default function () {
    getAccessToken();
}

function getAccessToken() {
    const grantType = 'client_credentials';
    const clientId = utils.getFromEnv('LOAD_TEST_OAUTH_CLIENT_ID');
    const clientSecret = utils.getFromEnv('LOAD_TEST_OAUTH_CLIENT_SECRET');

    const payload = JSON.stringify({
        grant_type: grantType,
        client_id: clientId,
        client_secret: clientSecret,
    });

    const res = http.post(
        utils.getBaseUrl() + '/api/oauth/token',
        payload,
        utils.getJsonHeader()
    );

    check(res, {
        'is status 200': (r) => r.status === 200,
    });
}
