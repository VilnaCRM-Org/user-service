import http from 'k6/http';
import {ScenarioUtils} from "./utils/scenarioUtils.js";
import {check} from 'k6';
import {Utils} from "./utils/utils.js";

const scenarioName = 'oauthToken';
const utils = new Utils();
const config = utils.getConfig();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);


export const options = scenarioUtils.getOptions();

export default function () {
    getAccessToken();
}

function getAccessToken() {
    const grantType = 'client_credentials';
    const clientId = config.endpoints[scenarioName].clientID;
    const clientSecret = config.endpoints[scenarioName].clientSecret;

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
