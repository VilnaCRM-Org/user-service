import http from 'k6/http';
import * as utils from "./utils.js";

export const options = {
    insecureSkipTLSVerify: true,
    scenarios: utils.getScenarios(),
    thresholds: utils.getThresholds(),
};

export default function () {
    createUser();
}

function createUser() {
    const email = utils.generateRandomEmail();
    const initials = "Name Surname";
    const password = "passWord1";

    const payload = JSON.stringify({
        email: email,
        password: password,
        initials: initials,
    });

    http.post(
        utils.getBaseUrl() + `api/users`,
        payload,
        utils.getJsonHeader()
    );
}
