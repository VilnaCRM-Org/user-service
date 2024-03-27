import http from 'k6/http';
import {utils} from "./utils.js";
import { check } from 'k6';

export const options = utils.getOptions('OAUTH');

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
