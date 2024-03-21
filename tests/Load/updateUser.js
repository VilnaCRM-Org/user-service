import http from 'k6/http';
import {utils} from "./utils.js";
import faker from "k6/x/faker";

export function setup() {
    return {users: utils.insertUsers(10)}
}

export const options = {
    insecureSkipTLSVerify: true,
    scenarios: utils.getScenarios(),
    thresholds: utils.getThresholds(
        utils.getFromEnv('UPDATE_USER_SMOKE_THRESHOLD'),
        utils.getFromEnv('UPDATE_USER_AVERAGE_THRESHOLD'),
        utils.getFromEnv('UPDATE_USER_STRESS_THRESHOLD'),
        utils.getFromEnv('UPDATE_USER_SPIKE_THRESHOLD'),
    ),
};

export default function (data) {
    updateUser(data.users[utils.getRandomNumber(0, data.users.length-1)]);
}

function updateUser(user) {
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
        utils.getBaseHttpUrl() + `/${id}`,
        payload,
        utils.getMergePatchHeader()
    );
}
