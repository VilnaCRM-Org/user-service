import http from 'k6/http';
import * as utils from "./utils.js";

export const options = {
    insecureSkipTLSVerify: true,
    scenarios: utils.getScenarios(),
    thresholds: utils.getThresholds(),
};

export default function () {
    getUser();
}

function getUser() {
    const user = utils.getRandomUser();
    const id = user.id;

    http.get(
        utils.getBaseUrl() + `api/users/${id}`,
        utils.getJsonHeader()
    );
}
