import http from 'k6/http';
import {Utils} from "./utils.js";
import { check } from 'k6';

const utils = new Utils('GET_USER');

export function setup() {
    return {
        users: utils.prepareUsers()
    }
}

export const options = utils.getOptions();

export default function (data) {
    getUser(data.users[utils.getRandomNumber(0, data.users.length - 1)]);
}

function getUser(user) {
    const id = user.id;

    const res = http.get(
        utils.getBaseHttpUrl() + `/${id}`,
        utils.getJsonHeader()
    );

    check(res, {
        'is status 200': (r) => r.status === 200,
    });
}
