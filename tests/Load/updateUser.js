import http from 'k6/http';
import * as utils from "./utils.js";

export const options = {
    insecureSkipTLSVerify: true,
    scenarios: utils.getScenarios(),
    thresholds: utils.getThresholds(),
};

export default function () {
    updateUser();
}

function updateUser() {
    const user = utils.getRandomUser();
    const id = user.id;
    const email = utils.generateRandomEmail();
    const initials = user.initials;
    const password = user.password;

    const payload = JSON.stringify({
        email: email,
        newPassword: password,
        initials: initials,
        oldPassword: password,
    });

    http.patch(
        utils.getBaseUrl() + `api/users/${id}`,
        payload,
        utils.getMergePatchHeader()
    );
}
