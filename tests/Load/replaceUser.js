import http from 'k6/http';
import {utils} from "./utils.js";
import faker from "k6/x/faker";
import { check } from 'k6';

export function setup() {
    return {
        users: utils.insertUsers(
            Number(
                utils.getFromEnv('LOAD_TEST_USERS_TO_INSERT')
            )
        )
    }
}

export const options = utils.getOptions('REPLACE_USER');

export default function (data) {
    updateUser(data.users[utils.getRandomNumber(0, data.users.length - 1)]);
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

    const res = http.put(
        utils.getBaseHttpUrl() + `/${id}`,
        payload,
        utils.getJsonHeader()
    );

    check(res, {
        'is status 200': (r) => r.status === 200,
    });
}
