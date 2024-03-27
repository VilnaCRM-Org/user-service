import http from 'k6/http';
import {utils} from "./utils.js";
import faker from "k6/x/faker";
import { check } from 'k6';

export const options= utils.getOptions('CREATE_USER');

export default function () {
    createUser();
}

function createUser() {
    const email = faker.person.email();
    const initials = faker.person.name();
    const password = faker.internet.password(true, true, true, false, false, 60);

    const payload = JSON.stringify({
        email: email,
        password: password,
        initials: initials,
    });

    const res = http.post(
        utils.getBaseHttpUrl(),
        payload,
        utils.getJsonHeader()
    );

    check(res, {
        'is status 201': (r) => r.status === 201,
    });
}
