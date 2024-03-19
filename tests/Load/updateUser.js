import http from 'k6/http';
import * as utils from "./utils.js";
import faker from "k6/x/faker";

let users;

export function setup() {
    users = utils.insertUsers(5000);
}

export const options = {
    insecureSkipTLSVerify: true,
    scenarios: utils.getScenarios(),
    thresholds: utils.getThresholds(),
};

export default function () {
    updateUser();
}

function updateUser() {
    const user = users[utils.getRandomNumber(0, users.length-1)];
    const id = user.id;
    const email = faker.person.email();
    const initials = faker.person.name();
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
